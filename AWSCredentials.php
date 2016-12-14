<?php

/**
 * Created by PhpStorm.
 * User: randy
 * Date: 11/17/16
 * Time: 7:47 AM
 * Description: This class is designed to handle reconfiguration of AWS profiles using the CLI for multiple amazon accounts.
 */

define("AWS_DEFAULT_PROFILE_FILECONFIG","current_profile");

class AWSCredentials
{
    private $keepallbackups=false;
    private $changedConfig=false;
    private $changedCredentials=false;
    private $currentDefaultProfile;

    private $profiles;
    private $aws_vars;

    private $returncode;

    function __construct() {

        $this->returncode=0;

        $this->set_local_env("AWS_ACCESS_KEY_ID");
        $this->set_local_env("AWS_SECRET_ACCESS_KEY");
        $this->set_local_env("AWS_SESSION_TOKEN");
        $this->set_local_env("AWS_DEFAULT_REGION");
        $this->set_local_env("AWS_DEFAULT_PROFILE");
        $this->set_local_env("AWS_CONFIG_FILE");

        $this->read_aws_configuration_file('credentials');
        $this->read_aws_configuration_file('config');

        if (isset($this->profiles['default'])) {
            $defaultKey=$this->profiles['default']['credentials']['aws_access_key_id'];
            foreach($this->profiles as $key => $value) {
                if (($key != 'default') && (isset($value['credentials']))) {
                    if ($defaultKey == $value['credentials']['aws_access_key_id']) {
                        $this->currentDefaultProfile = $key;
                    }
                }
            }
        }
    }

    function __destruct()
    {
        $this->save_aws_configuration_file('credentials');
        $this->save_aws_configuration_file('config');
    }

    private function set_local_env($varname) {
        $this->aws_vars[$varname] = getenv($varname);
    }

    private function backup_configuration($fullfilename) {
        if (!file_exists($fullfilename)) return; // file doesn't exist, so nothing to backup
        $backupfilename=$fullfilename.".backup";

        $backupnumber="";
        if ($this->keepallbackups) {
            while (file_exists($backupfilename . $backupnumber)) {
                $backupnumber++;
            }
        }
        copy($fullfilename,$backupfilename.$backupnumber);
        echo "Backup file created: $fullfilename -> $backupfilename$backupnumber\n";
    }

    private function get_full_file_path($filename) {
        $path=getenv('HOME').'/.aws/';
        if ($this->aws_vars['AWS_CONFIG_FILE']) {
            $path=$this->aws_vars['AWS_CONFIG_FILE'];
        }
        $fullfilename=$path.$filename;
        return $fullfilename;
    }

    private function read_aws_configuration_file($filename) {
        $fullfilename = $this->get_full_file_path($filename);
        $configurationcontents_raw=file($fullfilename,FILE_SKIP_EMPTY_LINES|FILE_IGNORE_NEW_LINES);
        $current_profile="";
        foreach ($configurationcontents_raw as $line) {
            if ($line[0]=='[') {
                $start_index=1;
                if (stripos($line,'profile ') !== false) {
                    $start_index=9;
                }
                $current_profile=substr($line,$start_index,-1);
            } else {
                list($key,$value) = explode(" = ",$line);
                $key=trim($key);
                $value=trim($value);
                $this->profiles[$current_profile][$filename][$key] = $value;
            }
        }
    }

    function save_aws_configuration_file($filename) {
//        echo "Saving $filename\n";
        if (($filename == 'credentials') && (!$this->changedCredentials)) return;
        if (($filename == 'config') && (!$this->changedConfig)) return;
        $outputArray=[];
        $fullfilename=$this->get_full_file_path($filename);
        $this->backup_configuration($fullfilename);
        foreach ($this->profiles as $profilename => $profile) {
            if (isset($profile[$filename])) {
                switch ($filename) {
                    case 'config':
                        if ($profilename == 'default') {
                            $outputArray[] = "[$profilename]";
                        } else {
                            $outputArray[] = "[profile $profilename]";
                        }
                        foreach ($profile['config'] as $key => $value) {
                            $outputArray[] = "$key = $value";
                        }
                        break;
                    case 'credentials':
                        $outputArray[] = "[$profilename]";
                        foreach ($profile['credentials'] as $key => $value) {
                            $outputArray[] = "$key = $value";
                        }
                        break;
                }
                $outputArray[]="";
            }
        }

        file_put_contents($fullfilename,implode("\n",$outputArray));
        //echo implode("\n",$outputArray) . "\n";
    }

    public function setDefaultProfileName($newDefaultProfileName) {
        $currentprofilepath=$this->get_full_file_path(AWS_DEFAULT_PROFILE_FILECONFIG);
        if ($this->validProfileName($newDefaultProfileName)) {
            echo "export AWS_DEFAULT_PROFILE=$newDefaultProfileName\n";
            file_put_contents($currentprofilepath,$newDefaultProfileName);
            $this->returncode=0;
            $this->changed=true;
        } else {
            $allprofiles=array_keys($this->profiles);
            fprintf(STDERR,"Profile not found... Select from the following:\n");
            foreach ($allprofiles as $profilename) {
                fprintf(STDERR,"   $profilename\n");
            }
            $this->returncode=1;
        }
    }

    public function getDefaultProfileName() {
        return $this->currentDefaultProfile;
    }

    public function rotateAccessKey($profilename) {
        if ($this->validProfileName($profilename)) {
            $this->changed=true;
            // do the magic
        }
    }

    public function rotateAllAccessKeys() {

        foreach ($this->profiles as $key => $value) {
            if ($key == 'default') break;
            $this->rotateAccessKey($key);
            if ($this->currentDefaultProfile == $key) {
                $this->profiles['default']['credentials']['aws_access_key_id']=$this->profiles[$key]['credentials']['aws_access_key_id'];
                $this->profiles['default']['credentials']['aws_secret_access_key']=$this->profiles[$key]['credentials']['aws_secret_access_key'];
            }
        }

    }

    private function validProfileName($profile) {
        if (key_exists($profile,$this->profiles)) {
            return true;
        } else {
            return false;
        }
    }

    function listProfiles() {
        $profileArray = array_keys($this->profiles);
        foreach ($profileArray as $profile) {
            echo "  $profile\n";
        }
    }
}

$aws = new AWSCredentials();

function usage()
{
    echo "Usage: php AWSCredentials.php COMMAND options ... options\n";
    echo "Valid COMMANDs:\n";
    echo "  rotate-all-keys\n";
    echo "  rotate-key [profilename]\n";
    echo "  list-profiles\n";
    echo "  set-profile [profilename]\n";
    exit(1);
}

if ($argc >= 2) {
    switch ($argv[1]) {
        case 'list-profiles':
            $aws->listProfiles();
            break;
        case 'set-profile':
            if ($argc == 3) {
                @$aws->setDefaultProfileName($argv[2]);
            } else {
                usage();
            }
            break;
        case 'rotate-key':
            if ($argc == 3) {
                @$aws->rotateAccessKey($argv[2]);
            } else {
                usage();
            }
            break;
        case 'rotate-all-keys':
            @$aws->rotateAllAccessKeys();
            break;
        default:
            usage();
            break;
    }
} else {
    usage();
}


