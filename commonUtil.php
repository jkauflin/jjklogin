<?php
/*==============================================================================
 * (C) Copyright 2015,2020 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: 
 *----------------------------------------------------------------------------
 * Modification History
 * 2015-03-06 JJK 	Initial version with some common utilities 
 * 2015-09-08 JJK	Added getAdminLevel to return an admin level based on
 *                  username to control updates
 * 2015-10-01 JJK	Added $fromEmailAddress to sendHtmlEMail                
 * 2015-10-20 JJK   Added function wildCardStrFromTokens to build a wild
 * 					card parameter string from the tokens in a string
 * 2016-04-10 JJK	Added calcCompoundInterest to calculate compound 
 * 					interests for the total dues calculation
 * 2016-09-11 JJK   Corrected handling of bad dates for interest calculation
 * 2016-09-11 JJK   Modified the truncDate routine to take the 1st token
 * 					before truncating to 10 characters (to handle bad dates
 * 					like "4/7/2007 0"
 * 2020-08-05 JJK   Removed getAdminLevel and getUsername (in favor of new
 *                  Login/Authentication logic)
 * 2020-09-19 JJK   If using SwiftMailer don't forget to include autoload.php
 *============================================================================*/

function strToUSD($inStr) {
	// Replace every ascii character except decimal and digits with a null
	$numericStr = preg_replace('/[\x01-\x2D\x2F\x3A-\x7F]+/', '', $inStr);
	// Convert to a float value and round down to 2 digits
	//return round(floatval($numericStr),2,PHP_ROUND_HALF_DOWN);
	return round(floatval($numericStr),2);
}

// Replace comma with null so you can use it as a CSV value
function csvFilter($inVal) {
	return preg_replace('/[\x2C]+/', '', String($inVal));
}

// Set 0 or 1 according to the boolean value of a string
function paramBoolVal($paramName) {
	$retBoolean = 0;
	if (strtolower(getParamVal($paramName)) == 'true') {
		$retBoolean = 1;
	}
	return $retBoolean;
}

function getParamVal($paramName) {
	$paramVal = "";
	if (isset($_REQUEST[$paramName])) {
		$paramVal = trim(urldecode($_REQUEST[$paramName]));
		// more input string cleanup ???  invalid characters?
	}
	return $paramVal;
}

function truncDate($inStr) {
	$outStr = "";
	if ($inStr != null) {
		$outStr = strtok($inStr," ");
		if (strlen($outStr) > 10) {
			$outStr = substr($outStr,0,10);
		}
	}
	return $outStr;
}

// Create a wild card parameter string from the tokens in a string
function wildCardStrFromTokens($inStr) {
	$string = $inStr;
	$token = strtok($string, " ");
	$paramStr = '';
	while ($token !== false)
	{
		$paramStr = $paramStr . '%' . $token;
		$token = strtok(" ");
	}
	$paramStr = $paramStr . '%';
	//error_log('$paramStr = ' . $paramStr);
	return $paramStr;
}

// Replace every ascii character except decimal and digits with a null, and round to 2 decimal places
function stringToMoney($inAmountStr) {
	return round(floatval( preg_replace('/[\x01-\x2D\x2F\x3A-\x7F]+/', '', $inAmountStr) ),2);
}

function sendHtmlEMail($toStr,$subject,$messageStr,$fromEmailAddress) {
    //mb_internal_encoding("UTF-8");
	$message = '<html><head><title>' . $subject .'</title></head><body>' . $messageStr . '</body></html>';
	
	// Always set content-type when sending HTML email
	//$headers = "MIME-Version: 1.0" . "\r\n";
	//$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	
	// More headers
	//$headers .= 'From: ' . $fromEmailAddress . "\r\n";
	/*
	 $headers = 'From: webmaster@example.com' . "\r\n" .
	 'Reply-To: webmaster@example.com' . "\r\n" .
	 'X-Mailer: PHP/' . phpversion();
	 */
	
    //mail($toStr,$subject,$message,$headers);

    //$mimeType = 'text/plain';
    $mimeType = 'text/html';

    try {
    	// Create the Transport (using default linux sendmail)
    	$transport = new Swift_SendmailTransport();

    	// Create the Mailer using your created Transport
    	$mailer = new Swift_Mailer($transport);

    	// Create a message
    	$message = (new Swift_Message($subject))
    		->setFrom([$fromEmailAddress])
    		->setTo([$toStr])
    		->setBody($messageStr,$mimeType);

        // Create the attachment with your data
    	//$attachment = new Swift_Attachment($filedata, $filename, 'application/pdf');
    	// Attach it to the message
    	//$message->attach($attachment);
         
    	// Send the message and check for success
    	if ($mailer->send($message)) {
            //error_log(date('[Y-m-d H:i:s] '). "in " . basename(__FILE__,".php") . ", sendHtmlEMail SUCCESS " . PHP_EOL, 3, LOG_FILE);
            return true;
    	} else {
            error_log(date('[Y-m-d H:i:s] '). "in " . basename(__FILE__,".php") . ", sendHtmlEMail ERROR " . PHP_EOL, 3, LOG_FILE);
            return false;
    	}

    } catch(Exception $e) {
        error_log(date('[Y-m-d H:i:s] '). "in " . basename(__FILE__,".php") . ", sendHtmlEMail Exception = " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
        return false;
    }
}

?>
