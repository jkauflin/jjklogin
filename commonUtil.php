<?php
/*==============================================================================
 * (C) Copyright 2023 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION:  Common utility functions for project
 *----------------------------------------------------------------------------
 * Modification History
 * 2022-08-29 JJK   Added Symfony Mailer for outgoing email sends using SMTP
 *                  Added getMailer to create the mailer part
 *                  and sendMail to create the email and send using the mailer
 *============================================================================*/

 use Symfony\Component\Mailer\Transport;
 use Symfony\Component\Mailer\Mailer;
 use Symfony\Component\Mime\Email;
 
 function getMailer($mailUsername, $mailPassword, $mailServer, $mailPort) {
    //error_log(date('[Y-m-d H:i] '). "in " . basename(__FILE__,".php") . ", BEFORE " . PHP_EOL, 3, LOG_FILE);

    // Create a Transport object
    $transport = Transport::fromDsn('smtp://' . $mailUsername . ':' . $mailPassword . '@' . $mailServer . ':' . $mailPort);
    // Create a Mailer object
    $mailer = new Mailer($transport);

	return $mailer;
}

function sendMail($mailer,$toStr,$subject,$messageStr,$fromEmailAddress) {
    try {
    	$message = '<html><head><title>' . $subject .'</title></head><body>' . $messageStr . '</body></html>';

        $email = (new Email());
        $email->from($fromEmailAddress);
        $email->to($toStr);
        $email->subject($subject);
        // Set the plain-text "Body"
        //$email->text('This is the plain text body of the message.\nThanks,\nAdmin');
        // Set HTML "Body"
        $email->html($message);
        // Add an "Attachment"
        //$email->attachFromPath('/path/to/example.txt');
        // Add an "Image"
        //$email->embed(fopen('/path/to/mailor.jpg', 'r'), 'nature');

    	$mailer->send($email);
        return true;

    } catch(Exception $e) {
        error_log(date('[Y-m-d H:i:s] '). "in " . basename(__FILE__,".php") . ", sendEMail Exception = " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
        return false;
    }
}

?>
