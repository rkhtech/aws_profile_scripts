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
    private $currentDefaultProfile=false;

    private $profiles;
    private $numberOfKeys=0;
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
        $this->numberOfKeys=count($this->profiles);
        if (isset($this->profiles['default'])) $this->numberOfKeys--;
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
        if (isset($this->aws_vars['AWS_DEFAULT_PROFILE']) && isset($this->profiles[$this->aws_vars['AWS_DEFAULT_PROFILE']])) {  // env var OVERRIDES default in config files
            $this->currentDefaultProfile = $this->aws_vars['AWS_DEFAULT_PROFILE'];
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

    function printEnv() {
        print_r($this->aws_vars);
    }

    function getProfileKey()
    {
        if (($this->currentDefaultProfile) && (isset($this->profiles[$this->currentDefaultProfile]['credentials'])))
            return $this->profiles[$this->currentDefaultProfile]['credentials']['aws_access_key_id'];
        return false;
    }

    function getProfileSecretKey()
    {
        if (($this->currentDefaultProfile) && (isset($this->profiles[$this->currentDefaultProfile]['credentials'])))
            return $this->profiles[$this->currentDefaultProfile]['credentials']['aws_secret_access_key'];
        return false;
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
            echo "Profile [$profilename] changes:\n";
            $output=[];
            $return_var=0;
            exec("aws iam list-access-keys --profile $profilename",$output,$return_var);
            if ($return_var) {
                fprintf(STDERR,"Error: AWS Keys for profile [$profilename] are invalid\n");
                return ($return_var);
            }
            $aws_list_access_keys=json_decode(implode("",$output),true);
            if (count($aws_list_access_keys['AccessKeyMetadata']) > 1) {
                fprintf(STDERR, "Profile Name: [$profilename]\n");
                fprintf(STDERR, "Access Key Id: " . $this->profiles[$profilename]['credentials']['aws_access_key_id'] . "\n");
                fprintf(STDERR, "Error Detected: You currently have more than one access key associated with this key id\n");
                fprintf(STDERR, "and profile.  In order to rotate your keys, you must have only one key on your account.\n");
                fprintf(STDERR, "Please login to your account on the Console, and delete all but a single key.\n");
                fprintf(STDERR, "\n");
                fprintf(STDERR, "%s\n",print_r($aws_list_access_keys,true));
                fprintf(STDERR, "\n");
                fprintf(STDERR, "YOU CAN DELETE ONE BY USING THE FOLLOWING COMMAND:\n\n");
                fprintf(STDERR, "    aws iam delete-access-key --access-key-id ".$this->profiles[$profilename]['credentials']['aws_access_key_id']."\n");
                fprintf(STDERR, "\n\n");
                return(1);
            } else {
                $output=[];
                exec("aws iam create-access-key --profile $profilename",$output,$return_var);
                if ($return_var) {
                    fprintf(STDERR,"Error: Could not create access key. \n%s\n",implode("\n",$output));
                    return($return_var);
                }
                $new_access_key=json_decode(implode("",$output),true);

                $old_credentials=$this->profiles[$profilename]['credentials'];
                $this->profiles[$profilename]['credentials']['aws_access_key_id'] = $new_access_key['AccessKey']["AccessKeyId"];
                $this->profiles[$profilename]['credentials']['aws_secret_access_key'] = $new_access_key['AccessKey']["SecretAccessKey"];

                echo "OLD_CREDENTIALS: ";
                print_r($old_credentials);
                echo "NEW_CREDENTIALS: ";
                print_r($this->profiles[$profilename]['credentials']);

                shell_exec("aws iam delete-access-key --profile $profilename --access-key-id ${old_credentials['aws_access_key_id']}");

                echo "\n";

                $this->changedCredentials=true;
            }
        }
        return 0;
    }

    public function rotateAllAccessKeys() {
        $i = 1;
        foreach ($this->profiles as $key => $value) {
            if ($key == 'default') continue;
            if (!isset($value['credentials'])) continue;
            echo "($i of ".$this->numberOfKeys.") ";
            $i++;
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

    # this function returns 1 if tempoary credentials from an assumed role have not expired, 2 if the credentials are static, or 0 if temp creds have expired.
    function validAssumedRoleCredentials($profilename=NULL) {
        if ($profilename === NULL) {
            $profilename = $this->currentDefaultProfile;
        }
        if (isset($this->profiles[$profilename])) {
            if (isset($this->profiles[$profilename]['credentials'])) {
//                fprintf(STDERR,"Credentials profile [$profilename], success.\n");
                return 2;
            } // verified, because we are not assuming a role
            $tempcreds = [
                'expires' => -1,
                ];
//            print_r($this->profiles[$profilename]);
            $filename = $profilename . "--" . str_replace([":", "/"], ["_", "-"], $this->profiles[$profilename]['config']['role_arn']) . ".json";
//            fprintf(STDERR,"filename: $filename ");
            if (file_exists(getenv("HOME")."/.aws/cli/cache/".$filename)) {
//                fprintf(STDERR,"found!\n");
                $creds = json_decode(file_get_contents(getenv("HOME")."/.aws/cli/cache/".$filename),true);
                //print_r($creds);
                $expires = new DateTime($creds['Credentials']['Expiration']);
                //var_dump($expires);
                $now = new DateTime();
                //echo date_format($expires,"U")."\n";
                //echo date_format($now,"U")."\n";
                $tempcreds = $creds['Credentials'];
                $tempcreds['expires'] = date_format($expires,"U") - date_format($now,"U");
            }
            $this->profiles[$profilename]['tempcreds'] = $tempcreds;
//            fprintf(STDERR,print_r($this->profiles[$profilename],true));
            if ($this->profiles[$profilename]['tempcreds']['expires'] > 0) return 1; // success because time to expire is greater than 0
        }
        return 0;
    }

    function getCredentialsArray() {
        $credentials = array();
        foreach ($this->profiles as $profilename => $data) {
            if (isset($data['credentials']) && ($profilename != 'default')) {
                $credentials[$profilename] = array(
                    'key' => $data['credentials']['aws_access_key_id'],
                    'secret' => $data['credentials']['aws_secret_access_key'],
                );
            }
        }
        return $credentials;
    /*
        array(

            // Credentials for the development environment.
            'development' => array(

                // Amazon Web Services Key. Found in the AWS Security Credentials. You can also pass
                // this value as the first parameter to a service constructor.
                'key' => 'development-key',

                // Amazon Web Services Secret Key. Found in the AWS Security Credentials. You can also
                // pass this value as the second parameter to a service constructor.
                'secret' => 'development-secret',

                // This option allows you to configure a preferred storage type to use for caching by
                // default. This can be changed later using the set_cache_config() method.
                //
                // Valid values are: `apc`, `xcache`, or a file system path such as `./cache` or
                // `/tmp/cache/`.
                'default_cache_config' => '',

                // Determines which Cerificate Authority file to use.
                //
                // A value of boolean `false` will use the Certificate Authority file available on the
                // system. A value of boolean `true` will use the Certificate Authority provided by the
                // SDK. Passing a file system path to a Certificate Authority file (chmodded to `0755`)
                // will use that.
                //
                // Leave this set to `false` if you're not sure.
                'certificate_authority' => false
            ),

            // Specify a default credential set to use if there are more than one.
            '@default' => 'development'
        )
            */
    }
}

$aws = new AWSCredentials();


