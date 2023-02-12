<?php
/*==============================================================================
 * (C) Copyright 2020,2023 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: Create database user record
 *----------------------------------------------------------------------------
 * Modification History
 * 2020-08-03 JJK 	Initial version
 * 2020-12-15 JJK   Update for package
 * 2020-12-17 JJK   Corrected separator bug
 * 2020-12-21 JJK   Made the settings variables unique
 * 2023-02-11 JJK   Re-factor for non-static class and settings from DB
 *============================================================================*/
// Define a super global constant for the log file (this will be in scope for all functions)
define("LOG_FILE", "./php.log");
// Assume /vendor is 3 levels up from a file in the package root
require_once dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . 'autoload.php';

// Figure out how many levels up to get to the "public_html" root folder
$webRootDirOffset = substr_count(strstr(dirname(__FILE__),"public_html"),DIRECTORY_SEPARATOR) + 1;
// Get settings and credentials from a file in a directory outside of public_html
// (assume a settings file in the "external_includes" folder one level up from "public_html"
$extIncludePath = dirname(__FILE__, $webRootDirOffset+1).DIRECTORY_SEPARATOR.'external_includes'.DIRECTORY_SEPARATOR;
require_once $extIncludePath.'jjkloginSettings.php';

use \jkauflin\jjklogin\LoginAuth;

try {
    header("Content-Type: application/json; charset=UTF-8");
    $json_str = file_get_contents('php://input');
    $param = json_decode($json_str);

    $passwordStrengthMsg = "Password must be at least 8 characters, and include at least one number, letter, symbol, and CAPS";

    $loginAuth = new LoginAuth($hostJJKLogin, $dbadminJJKLogin, $passwordJJKLogin, $dbnameJJKLogin);
    $userRec = $loginAuth->initUserRec();

    if (empty($param->password_1)) {
        $userRec->userMessage = 'Password is required';
    } else if (empty($param->regCode)) {
        $userRec->userMessage = 'Registration Code is missing';
    } else if (empty($param->password_2)) {
        $userRec->userMessage = 'Confirmation Password is required';
    } else if ($param->password_2 != $param->password_1) {
        $userRec->userMessage = 'Confirmation Password does not match Password';
    } else if( strlen($param->password_1) < 8 ) {
        $userRec->userMessage = $passwordStrengthMsg;
    } else if( !preg_match("#[0-9]+#", $param->password_1) ) {
        $userRec->userMessage = $passwordStrengthMsg;
    } else if( !preg_match("#[a-z]+#", $param->password_1) ) {
        $userRec->userMessage = $passwordStrengthMsg;
    } else if( !preg_match("#[A-Z]+#", $param->password_1) ) {
        $userRec->userMessage = $passwordStrengthMsg;
    } else if( !preg_match("#\W+#", $param->password_1) ) {
        $userRec->userMessage = $passwordStrengthMsg;
    } else {
        $userRec = $loginAuth->setPassword($param);
        //LoginAuth::setPassword($conn,$cookieNameJJKLogin,$cookiePathJJKLogin,$serverKeyJJKLogin,$param);
    }

    echo json_encode($userRec);

} catch(Exception $e) {
    error_log(date('[Y-m-d H:i] '). "in " . basename(__FILE__,".php") . ", Exception = " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
    echo json_encode(
        array(
            'error' => $e->getMessage(),
            'error_code' => $e->getCode()
        )
    );
}
?>
