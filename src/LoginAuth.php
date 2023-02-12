<?php
/*==============================================================================
 * (C) Copyright 2020,2023 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: PHP functions to interact with a database, and security
 *              components for authentication and login
 * 
 *----------------------------------------------------------------------------
 * Modification History
 * 2020-07-25 JJK 	Initial version
 * 2020-07-28 JJK   Added registerUser and expired token check
 * 2020-07-31 JJK   Re-factor as a class
 * 2020-08-04 JJK   Added setPassword, resetPassword, and setUserToken
 * 2020-08-11 JJK   Corrected the cookie/jwt expiration to be 30 days
 * 2020-12-17 JJK   Updated for composer package and general use
 * 2023-02-11 JJK   Updated to use non-static functions, a constructor for
 *                  database credentials, and to use the database for
 *                  settings.  Added the mail functions here as well
 * 2023-02-12 JJK   Updated for new Firebase\JWT v6.4.0 Key concept
 *============================================================================*/
namespace jkauflin\jjklogin;

use Exception;
use mysqli;
// Library class for JWT authentication work (includes are in the calling PHP using autoload)
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Library classes to send email
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;


class UserRec
{
	public $userName;
    public $userLevel;
    public $userMessage;
    public $autoRedirect;
}

class LoginAuth
{
    private $dbHost;
    private $dbAdmin;
    private $dbPassword;
    private $dbName;

    private $CookiePath;
    private $CookieName;
    private $ServerKey;
    private $DomainUrl;
    
    private $MailServer;
    private $MailPort;
    private $MailUser;
    private $MailPass;

    private $AutoRedirect;

    function __construct($dbHost,$dbAdmin,$dbPassword,$dbName) {
        $this->dbHost = $dbHost;
        $this->dbAdmin = $dbAdmin;
        $this->dbPassword = $dbPassword;
        $this->dbName = $dbName;

        $conn = new mysqli($this->dbHost,$this->dbAdmin,$this->dbPassword,$this->dbName);
        // Check connection
        if ($conn->connect_error) {
            error_log(date('[Y-m-d H:i:s] '). "in " . basename(__FILE__,".php") . ", Connection failed: " . $conn->connect_error . PHP_EOL, 3, LOG_FILE);
        	die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT * FROM jjkloginSettings WHERE SettingsId = 1 ";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->CookiePath = $row["CookiePath"];
            $this->CookieName = $row["CookieName"];
            $this->ServerKey = $row["ServerKey"];
            $this->DomainUrl = $row["DomainUrl"];
            $this->MailServer = $row["MailServer"];
            $this->MailPort = $row["MailPort"];
            $this->MailUser = $row["MailUser"];
            $this->MailPass = $row["MailPass"];
            $this->AutoRedirect = $row["AutoRedirect"];
        }
            
        $stmt->close();
        $conn->close();
    }

    public function initUserRec() {
        return new UserRec();
    }

    private function setUserToken($UserId,$UserName,$UserLevel) {
        try {
            if(isset($_COOKIE[$this->CookieName])) {
                unset($_COOKIE[$this->CookieName]);
            }

            // create a token
            $payloadArray = array();
            $payloadArray['userId'] = $UserId;
            $payloadArray['userName'] = $UserName;
            $payloadArray['userLevel'] = $UserLevel;
            $payloadArray['exp'] = time()+60*60*24*30;  // 30 days

            $token = JWT::encode($payloadArray, $this->ServerKey, 'HS256');

            setcookie($this->CookieName, $token, [
                'expires' =>  time()+60*60*24*30,  // 30 days
                'path' => $this->CookiePath,
                'samesite' => 'strict',
                //'secure' => TRUE,     // This will be the default on sites using HTTPS
                'httponly' => TRUE
            ]);
        }
        catch(Exception $e) {
            error_log(date('[Y-m-d H:i] '). "in " . basename(__FILE__,".php") . ", Exception = " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
        }
    }

    public function setUserCookie($param) {
        $userRec = new UserRec();
        $userRec->userMessage = 'Username not found';

        $conn = new mysqli($this->dbHost,$this->dbAdmin,$this->dbPassword,$this->dbName);
        // Check connection
        if ($conn->connect_error) {
            error_log(date('[Y-m-d H:i:s] '). "in " . basename(__FILE__,".php") . ", Connection failed: " . $conn->connect_error . PHP_EOL, 3, LOG_FILE);
        	die("Connection failed: " . $conn->connect_error);
        }

        $username = mysqli_real_escape_string($conn, $param->username);

        $sql = "SELECT * FROM users WHERE UserName = ? ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = mysqli_fetch_assoc($result);
        $stmt->close();
        $conn->close();

        if ($user) {
            if ($user['UserLevel'] < 1) {
                $userRec->userMessage = 'User is not authorized (contact Administrator)';
            } else {
                if (password_verify($param->password, $user['UserPassword'])) {
                    $this->setUserToken($user['UserId'],$user['UserName'],$user['UserLevel']);
                    $userRec->userName = $user['UserName'];
                    $userRec->userLevel = $user['UserLevel'];
                } else {
                    $userRec->userMessage = 'Password for this username does not match';
                }
            }
        }

        return $userRec;
    }

    public function deleteUserCookie() 
    {
        $userRec = new UserRec();
        $userRec->userMessage = 'User NOT authenticated';

        if(isset($_COOKIE[$this->CookieName])) {
            // If set, expire and unset
            setcookie($this->CookieName, "", [
                'expires' => time()-3600,
                'path' => $this->CookiePath,
                'samesite' => 'strict',
                //'secure' => TRUE,     // This will be the default on sites using HTTPS
                'httponly' => TRUE
            ]);

            unset($_COOKIE[$this->CookieName]);
        }
        
        return $userRec;
    }

    public function getUserRec() {
        $userRec = new UserRec();
        $userRec->userMessage = 'User NOT authenticated';
        $userRec->autoRedirect = $this->AutoRedirect;

        $token = null;

        if (isset($_COOKIE[$this->CookieName])) {
            $token = $_COOKIE[$this->CookieName];

            if (!is_null($token)) {
                try {
                    $payload = JWT::decode($token, new Key($this->ServerKey, 'HS256'));
                    
                    //$currTime = mktime();
                    //error_log(date('[Y-m-d H:i] '). "in getUserRec, exp = $payload->exp, currTime = $currTime" . PHP_EOL, 3, LOG_FILE);
                    // [2020-07-29 02:28] in getUserRec, exp = 1609455601, currTime = 1595982502

                    $userRec->userName = $payload->userName;
                    $userRec->userLevel = $payload->userLevel;
                }
                catch(Exception $e) {
                    // If the token is expired, the JWT::decode will throw an exception
                    if (strpos($e,"Expired") || strpos($e,"expired")) {
                        // if expired, delete the cookie
                        $this->deleteUserCookie();
                    } else {
                        error_log(date('[Y-m-d H:i] '). "in getUserRec, exception in decode = $e" . PHP_EOL, 3, LOG_FILE);
                    }
                }
            }
        }

        return $userRec;
    }

    public function resetPassword($param) {
        $userRec = new UserRec();
        $userRec->userMessage = 'Error in request';
        
        try {
            $conn = new mysqli($this->dbHost,$this->dbAdmin,$this->dbPassword,$this->dbName);
            // Check connection
            if ($conn->connect_error) {
                error_log(date('[Y-m-d H:i:s] '). "in " . basename(__FILE__,".php") . ", Connection failed: " . $conn->connect_error . PHP_EOL, 3, LOG_FILE);
                die("Connection failed: " . $conn->connect_error);
            }

            $username = mysqli_real_escape_string($conn, $param->usernameReset);
            $emailAddr = mysqli_real_escape_string($conn, $param->emailAddrReset);

            $sql = null;
            $stmt = null;
            if (!empty($username)) {
                $userRec->userMessage = 'Username not found';
                $sql = "SELECT * FROM users WHERE UserName = ? ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $username);
            }
            if (!empty($emailAddr)) {
                $userRec->userMessage = 'Email address not found';
                $sql = "SELECT * FROM users WHERE UserEmailAddr = ? ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $emailAddr);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $user = mysqli_fetch_assoc($result);
            $stmt->close();
            $conn->close();

        /*
            1	UserId Primary	int(7)			No	None	AUTO_INCREMENT	Change Change	Drop Drop	
            2	UserEmailAddr	varchar(100)	No	None			Change Change	Drop Drop	
            3	UserPassword	varchar(100)	No	None			Change Change	Drop Drop	
            4	UserName	    varchar(80)	    No	guest			Change Change	Drop Drop	
            5	UserLevel	    int(2)			No	0			Change Change	Drop Drop	
            6	UserLastLogin	datetime		No	current_timestamp()			Change Change	Drop Drop	
            7	RegistrationCode varchar(100)	No	None			Change Change	Drop Drop	
            8	EmailVerified	int(1)			No	0			Change Change	Drop Drop	
            9	LastChangedBy	varchar(80)	    No	system			Change Change	Drop Drop	
            10	LastChangedTs	datetime	        current_timestamp()
        */

            if ($user) {
                if ($user['UserLevel'] < 1) {
                    $userRec->userMessage = 'User is not authorized (contact Administrator)';
                } else {
                    $subject = "Password Reset";
                    $messageStr = 'Click the following to enter a new password for username [' . $user['UserName'] . ']:  ' 
                        . $this->DomainUrl . '?resetPass=' . $user['RegistrationCode'];

                    $sendMailSuccess = $this->sendMail($user['UserEmailAddr'],$subject,$messageStr);
                    if (!$sendMailSuccess) {
                        $userRec->userMessage = 'Error sending email for reset password verification';
                    } else {
                        $userRec->userMessage = 'Reset password verification sent to your email address';
                    }
                }
            }
        }
        catch(Exception $e) {
            error_log(date('[Y-m-d H:i] '). "in " . basename(__FILE__,".php") . ", Exception = " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
        }

        return $userRec;
    }

    public function setPassword($param) {
        $userRec = new UserRec();
        $userRec->userMessage = 'Error in request';

        $conn = new mysqli($this->dbHost,$this->dbAdmin,$this->dbPassword,$this->dbName);
        // Check connection
        if ($conn->connect_error) {
            error_log(date('[Y-m-d H:i:s] '). "in " . basename(__FILE__,".php") . ", Connection failed: " . $conn->connect_error . PHP_EOL, 3, LOG_FILE);
        	die("Connection failed: " . $conn->connect_error);
        }

        $regCode = mysqli_real_escape_string($conn, $param->regCode);
        $password = mysqli_real_escape_string($conn, $param->password_1);

        if (empty($regCode)) {
            $userRec->userMessage = 'Registraction Code is missing';
        } else {
            // Make sure the user record exists for this registraction code
            $sql = "SELECT * FROM users WHERE RegistrationCode = ? ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $regCode);

            $stmt->execute();
            $result = $stmt->get_result();
            $user = mysqli_fetch_assoc($result);
            $stmt->close();

            if ($user) {
                //$password = md5($password_1);//encrypt the password before saving in the database
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $registrationCode = uniqid();

                $sql = "UPDATE users SET UserPassword = ?, RegistrationCode = ? WHERE RegistrationCode = ? ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $passwordHash,$registrationCode,$regCode);
                $stmt->execute();
                $stmt->close();

                $this->setUserToken($user['UserId'],$user['UserName'],$user['UserLevel']);

                $userRec->userName = $user['UserName'];
                $userRec->userLevel = $user['UserLevel'];

                // level is 0  and userMessage is still Error in Request

            } else {
                $userRec->userMessage = 'User not found for this Registration Code';
            }
        }

        $conn->close();

        return $userRec;
    }

    public function registerUser($param) {
        $userRec = new UserRec();
        $userRec->userMessage = 'Error in request';

        try {
            $conn = new mysqli($this->dbHost,$this->dbAdmin,$this->dbPassword,$this->dbName);
            // Check connection
            if ($conn->connect_error) {
                error_log(date('[Y-m-d H:i:s] '). "in " . basename(__FILE__,".php") . ", Connection failed: " . $conn->connect_error . PHP_EOL, 3, LOG_FILE);
                die("Connection failed: " . $conn->connect_error);
            }
    
            $username = mysqli_real_escape_string($conn, $param->usernameReg);

            $sql = "SELECT * FROM users WHERE UserName = ? ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = mysqli_fetch_assoc($result);
            $stmt->close();

            if ($user) {
                $userRec->userMessage = 'Username already exists';
                // check if email exists as well - you can only create 1 user for username and email
            } else {
                $registrationCode = uniqid();
                $tempPassword = "Temp" . uniqid();
                $password = password_hash($tempPassword, PASSWORD_DEFAULT);
                // sanitizing email(Remove unexpected symbol like <,>,?,#,!, etc.)
                $email = filter_var($param->emailAddrReg, FILTER_SANITIZE_EMAIL); 
                // Default the user level to 1 (Leave it up to Admin to manually change in database)
                $userLevel = 1;

                $sql = 'INSERT INTO users (UserEmailAddr,UserPassword,UserName,UserLevel,RegistrationCode) VALUES(?,?,?,?,?); ';
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssis", 
                    $email,
                    $password,
                    $username,
                    $userLevel,
                    $registrationCode);
                $stmt->execute();
                $stmt->close();

                // Send email
                $subject = "User Registration";
                $messageStr = 'A new user account has been created for you.  Click the following to enter a new password for username [' . 
                    $username . ']:  ' . $this->DomainUrl . '?resetPass=' . $registrationCode;

                // Create a Mailer object for the SMTP transport
                $sendMailSuccess = $this->sendMail($email,$subject,$messageStr);
                if (!$sendMailSuccess) {
                    $userRec->userMessage = 'User registered successfully (but email FAILED)';
                } else {
                    $userRec->userMessage = 'User registered successfully (and email sent)';
                }
            }

            $conn->close();
        }
        catch(Exception $e) {
            //error_log(date('[Y-m-d H:i] '). "in " . basename(__FILE__,".php") . ", Exception = " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
        }

        return $userRec;
    }

    private function sendMail($toStr,$subject,$messageStr) {
        try {
            $message = '<html><head><title>' . $subject .'</title></head><body>' . $messageStr . '</body></html>';
    
            $email = (new Email());
            $email->from($this->MailUser);
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
    
            // Create a Transport object
            $transport = Transport::fromDsn('smtp://' . $this->MailUser . ':' . $this->MailPass . '@' . $this->MailServer . ':' . $this->MailPort);
            // Create a Mailer object
            $mailer = new Mailer($transport);
            $mailer->send($email);
            return true;
    
        } catch(Exception $e) {
            error_log(date('[Y-m-d H:i:s] '). "in " . basename(__FILE__,".php") . ", sendEMail Exception = " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
            return false;
        }
    }

} // class LoginAuth
