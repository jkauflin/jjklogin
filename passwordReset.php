<?php
/*==============================================================================
 * (C) Copyright 2020 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: Create database user record
 *----------------------------------------------------------------------------
 * Modification History
 * 2020-08-04 JJK 	Initial version
 * 2020-12-15 JJK   Update for package
 *============================================================================*/
// Figure out how many levels up to get to the "public_html" root folder
$webRootDirOffset = substr_count(strstr(dirname(__FILE__),"public_html"),"\\") + 1;
// Assume /vendor is 3 levels up from a file in the package root
require_once dirname(__FILE__, 3).'\autoload.php';
// Get settings and credentials from a file in a directory outside of public_html
// (assume a settings file in the "external_includes" folder one level up from "public_html"
require_once dirname(__FILE__, $webRootDirOffset+1).'/external_includes/jjkloginSettings.php';

use \jkauflin\jjklogin\LoginAuth;
// Define a super global constant for the log file (this will be in scope for all functions)
define("LOG_FILE", "./php.log");

try {
    header("Content-Type: application/json; charset=UTF-8");
    $json_str = file_get_contents('php://input');
    $param = json_decode($json_str);

    $userRec = LoginAuth::initUserRec();
    if (empty($param->usernameReset) && empty($param->emailAddrReset)) {
        $userRec->userMessage = 'Username or Email address is required';
    } else {
        $conn = getConn($host, $dbadmin, $password, $dbname);
        $userRec = LoginAuth::resetPassword($conn,$cookieName,$cookiePath,$serverKey,$param,$fromEmailAddress,$passwordResetUrl);
        $conn->close();
    }

    echo json_encode($userRec);

} catch(Exception $e) {
    //error_log(date('[Y-m-d H:i] '). "in " . basename(__FILE__,".php") . ", Exception = " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
    echo json_encode(
        array(
            'error' => $e->getMessage(),
            'error_code' => $e->getCode()
        )
    );
}
?>
