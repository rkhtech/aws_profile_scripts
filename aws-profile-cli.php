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
            $aws->rotateAllAccessKeys();
            break;
        default:
            usage();
            break;
    }
} else {
    usage();
}