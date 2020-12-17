<?php
/*==============================================================================
 * (C) Copyright 2020 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: Logout to delete the cookie and JWT token
 *----------------------------------------------------------------------------
 * Modification History
 * 2020-07-25 JJK 	Initial version
 * 2020-12-15 JJK   Update for package
 * 2020-12-17 JJK   Corrected separator bug
 *============================================================================*/
// Define a super global constant for the log file (this will be in scope for all functions)
define("LOG_FILE", "./php.log");
// Figure out how many levels up to get to the "public_html" root folder
$webRootDirOffset = substr_count(strstr(dirname(__FILE__),"public_html"),DIRECTORY_SEPARATOR) + 1;
// Assume /vendor is 3 levels up from a file in the package root
require_once dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . 'autoload.php';
// Get settings and credentials from a file in a directory outside of public_html
// (assume a settings file in the "external_includes" folder one level up from "public_html"
$settingsFile = dirname(__FILE__, $webRootDirOffset+1).DIRECTORY_SEPARATOR.'external_includes'.DIRECTORY_SEPARATOR.'jjkloginSettings.php';
require_once $settingsFile;
require_once 'commonUtil.php';

use \jkauflin\jjklogin\LoginAuth;

try {
    LoginAuth::deleteUserCookie($cookieName,$cookiePath);

} catch(Exception $e) {
    //error_log(date('[Y-m-d H:i] '). "in " . basename(__FILE__,".php") . ", Exception = " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
    echo json_encode(
        array(
            'error' => $e->getMessage(),
            'error_code' => $e->getCode()
        )
    );
    exit;
}
?>
