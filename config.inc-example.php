<?php if (!class_exists('CFRuntime')) die('No direct access allowed.');
/**
 * This example configuration uses the Class AWSCredentials which is designed to use the AWS CLI credentials files
 * copy this file to 'config.inc.php' in your PHP SDK subdirectory.
 *
 * Documentation on installing the AWS SDK for PHP:
 * https://aws.amazon.com/articles/CloudFront/4261#getsetup
 *
 * @version 2011.12.14
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 * @link http://aws.amazon.com/security-credentials AWS Security Credentials
 */

require getenv("HOME")."/bin/aws_profile_scripts/AWSCredentials.php";
$aws = new AWSCredentials();
CFCredentials::set($aws->getSDKCredentialsArray());

