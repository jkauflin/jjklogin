<?php
/*==============================================================================
 * (C) Copyright 2020 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: Check for JWT token to authenticate user
 *----------------------------------------------------------------------------
 * Modification History
 * 2020-07-25 JJK 	Initial version
 * 2020-07-31 JJK   Re-factor to use new class
 * 2020-12-15 JJK   Update for package
 *============================================================================*/
// Figure out how many levels up to get to the "public_html" root folder
$webRootDirOffset = substr_count(strstr(dirname(__FILE__),"public_html"),"\\") + 1;
// Assume /vendor is 3 levels up from a file in the package root
require_once dirname(__FILE__, 3).'\autoload.php';
// Get settings and credentials from a file in a directory outside of public_html
// (assume a settings file in the "external_includes" folder one level up from "public_html"
require_once dirname(__FILE__, $webRootDirOffset+1).'/external_includes/jjkloginSettings.php';
require_once 'commonUtil.php';

use \jkauflin\jjklogin\LoginAuth;
// Define a super global constant for the log file (this will be in scope for all functions)
define("LOG_FILE", "./php.log");

try {
    $userRec = LoginAuth::getUserRec($cookieName,$cookiePath,$serverKey);
    echo json_encode($userRec);

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
