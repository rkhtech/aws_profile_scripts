<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 12/16/16
 * Time: 8:13 PM
 */

require "AWSCredentials.php";

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

foreach ($argv as $argument) {
    switch ($argument) {

    }
}

if ($argc >= 2) {
    switch ($argv[1]) {
        case 'get-time-to-expire':
            $AWSCredentials->validAssumedRoleCredentials();
            $seconds = $AWSCredentials->getExpireSeconds();
            $min = floor($seconds/60);
            $sec = $seconds%60;
            if ($min > 5) {
                echo "about $min minutes.";
            } else
            if ($min > 1) {
                echo "less than 5 minutes.";
            } else {
                echo "$seconds seconds.";
            }
//            echo sprintf("%02d:%02d (%s)",$min,$sec,$seconds);
            break;
        case 'get-sdk-credentials-array':
            print_r($AWSCredentials->getSDKCredentialsArray());
            break;
        case 'verify-assumed-role':
            die($AWSCredentials->validAssumedRoleCredentials($AWSCredentials->getDefaultProfileName()));
            break;
        case 'get_current_keys':
            echo "[" . $AWSCredentials->getDefaultProfileName() . "]\n";
            echo "aws_access_key_id = ".$AWSCredentials->getProfileKey()."\n";
            echo "aws_secret_access_key = ".$AWSCredentials->getProfileSecretKey()."\n";
            break;
        case 'get_env':
            $AWSCredentials->printEnv();
            break;
        case 'list-profiles':
            $AWSCredentials->listProfiles();
            break;
        case 'set-profile':
            if ($argc == 3) {
                @$AWSCredentials->setDefaultProfileName($argv[2]);
                die($AWSCredentials->validAssumedRoleCredentials($argv[2]));
            } else {
                usage();
            }
            break;
        case 'rotate-key':
            if ($argc == 3) {
                @$AWSCredentials->rotateAccessKey($argv[2]);
            } else {
                usage();
            }
            break;
        case 'rotate-all-keys':
            $AWSCredentials->rotateAllAccessKeys();
            break;
        default:
            usage();
            break;
    }
} else {
    usage();
}
