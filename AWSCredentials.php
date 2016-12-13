<?php

/**
 * Created by PhpStorm.
 * User: randy
 * Date: 11/17/16
 * Time: 7:47 AM
 * Description: This class is designed to handle
 */

define("AWS_DEFAULT_PROFILE_FILECONFIG","current_profile");

class AWSCredentials
{
    private $keepallbackups=false;
    private $changed=false;
    private $credentialsArray;
    private $currentDefaultProfile;

    private $profiles;
    private $aws_vars;

    private $returncode;

    private function set_local_env($varname) {
        $this->aws_vars[$varname] = getenv($varname);
    }
    private function backup_configuration($fullfilename) {
        if (!file_exists($fullfilename)) return; // file doesn't exist, so nothing to backup
//        echo "fullfilename: $fullfilename\n";
        $backupfilename=$fullfilename.".backup";


//        echo "backupfilename: $backupfilename\n";
        $backupnumber="";
        if ($this->keepallbackups) {
//        echo "backupnumber: $backupnumber\n";
            while (file_exists($backupfilename . $backupnumber)) {
                $backupnumber++;
//            echo "backupnumber: $backupnumber\n";
            }
        }
        copy($fullfilename,$backupfilename.$backupnumber);
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
//                echo "Line: $line\n";
                if (stripos($line,'profile ') !== false) {
                    $start_index=9;
                }
                $current_profile=substr($line,$start_index,-1);
//                echo "current: $current_profile\n";
//                echo $current_profile."\n";
            } else {
                list($key,$value) = explode(" = ",$line);
                $key=trim($key);
                $value=trim($value);
                $this->profiles[$current_profile][$filename][$key] = $value;
            }
        }
    }
    function save_aws_configuration_file($filename) {
//        if (!$this->changed) return; // do nothing, since no changes were detected
        $fullfilename=$this->get_full_file_path($filename);
        $this->backup_configuration($fullfilename);
        foreach ($this->profiles as $profilename => $profile) {
            echo "profile:$profilename\n";
        }
    }
    function __construct() {
        global $argv;
        $this->returncode=0;
        // this function will read the contents of ~/.aws/config and ~/.aws/credentials
        $this->set_local_env("AWS_ACCESS_KEY_ID");
        $this->set_local_env("AWS_SECRET_ACCESS_KEY");
        $this->set_local_env("AWS_SESSION_TOKEN");
        $this->set_local_env("AWS_DEFAULT_REGION");
        $this->set_local_env("AWS_DEFAULT_PROFILE");
        $this->set_local_env("AWS_CONFIG_FILE");

//        print_r($this->aws_vars);
        $this->read_aws_configuration_file('credentials');
        $this->read_aws_configuration_file('config');

//        print_r($argv);
//        print_r($this->profiles);
    }
    function __destruct()
    {
        // this function will save the contents of ~/.aws/config and ~/.aws/credentials
        // TODO: Implement __destruct() method.
//        error_log("\nReturn Value:" . $this->returncode);
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

    }
    public function rotateAccessKeys($profilename) {

    }
    public function rotateAllAccessKeys() {

    }
    private function validProfileName($profile) {
        if (key_exists($profile,$this->profiles)) {
            return true;
        } else {
            return false;
//            echo "key not found, valid key values:\n";
//            $allprofiles=array_keys($this->profiles);
//            print_r($allprofiles);
        }
    }
    function listProfiles() {
        $profileArray = array_keys($this->profiles);
        foreach ($profileArray as $profile) {
            echo "  $profile\n";
        }
    }
}

$aws= new AWSCredentials();

//$aws->setDefaultProfileName('rkhtech');

//$aws->save_aws_configuration_file("testfile");
function usage()
{
//    echo "This class is designed to be run from a bash script, but if you insist on running manually, here is the syntax:\n\n";
    echo "Usage: php AWSCredentials.php COMMAND options ... options\n";
    echo "Valid COMMANDs:\n";
    echo "  rotate-all-keys\n";
    echo "  rotate-key\n";
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
            @$aws->setDefaultProfileName($argv[2]);
            break;
        default:
            usage();
            break;
    }
} else {
    usage();
}


