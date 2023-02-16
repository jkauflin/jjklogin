# jjklogin
``jjklogin`` is a PHP JWT based project to add user authentication for SPA type web apps.  
It provides library classes and UI for:
- user registration
- login
- password reset
- logout

As well as a Custom Event to access:
- authentication confirmation
- user name
- user level

## Dependencies
To use this project there must be a hosted web application on a server that supports [PHP](https://www.php.net/), [MySQL/MariaDB](https://mariadb.org/), and [Composer/Packagist](https://getcomposer.org/).  
Internally it uses CDN includes for [Bootstrap](https://getbootstrap.com/docs/5.2/getting-started/introduction/)

## Installation
1. Add the following dependencies to ``composer.json`` to pull in the package from 	[packagist.org](https://packagist.org/packages/jkauflin/jjklogin)

```
    {
        "require": {
            "php": ">=8.0.0",
            "symfony/mailer": "^6.1",
            "firebase/php-jwt": "^6.4.0",
            "jkauflin/jjklogin": "^1.2.3"
        }
    }
```

2. Include the following javascript file in your web page
```
    <script src="vendor/jkauflin/jjklogin/jjklogin.js?ver=1.020"></script>
```
3. Use the ``vendor/jkauflin/jjklogin/createUsersTable.sql`` to create a ``users`` and a ``jjkloginSettings`` table in a MySQL database

4. Copy the ``vendor/jkauflin/jjklogin/jjkloginSettings.php`` settings file into an ``external_includes`` folder that is on the same level as the ``public_html`` of the web app (i.e. parent folder of the web outside of public access), and adjust the settings for the web app, email, keys, and database access


## Usage
### HTML page usage
After including the ``jjklogin.js`` in a web page, include a link with an id of ``login`` to re-direct to the project page for authentication functions:

    <a class="nav-link" id="login" href="#" role="button">login</a>

It could be included in a Bootstrap navigation list:

    <li class="nav-item"><a class="nav-link" id="login" href="#" role="button">login</a></li>


### Javascript (Login Authentication Event)
An Event for the user login authentication is available.  Simply include the following element in the HTML:

    <div id="jjkloginEventElement" class="float-end"></div>

Then you can add the following javascript to respond to the authentication event:

    var userName = ""
    var userLevel = 0
    var jjkloginEventElement = document.getElementById("jjkloginEventElement")
    jjkloginEventElement.innerHTML = 'User not logged in'

    jjkloginEventElement.addEventListener('userJJKLoginAuth', function (event) {
        userName = event.detail.userName
        userLevel = event.detail.userLevel
        jjkloginEventElement.innerHTML = 'Logged in as ' + userName
    });

### PHP usage
The javascript variable is helpful for adjusting the display but additional security checks should be done in any PHP files doing service work.  The PHP should get the ``UserRec`` directly and check authentication and user level before allowing functions.  Here is an example of code that can be used in the PHP to throw an exception if the user is not authorized:

    $userRec = LoginAuth::getUserRec($cookieName,$cookiePath,$serverKey);
    if ($userRec->userName == null || $userRec->userName == '') {
        throw new Exception('User is NOT logged in', 500);
    }
    if ($userRec->userLevel < 1) {
        throw new Exception('User is NOT authorized (contact Administrator)', 500);
    }



## Security
This project uses [firebase/php-jwt](https://github.com/firebase/php-jwt) to encode and decode JSON Web Tokens (JWT) in PHP, conforming to RFC 7519. Look in the ``src/LoginAuth.php`` class to see how this project securely uses cookies to store the JWT tokens, including:
- ``'samesite' => 'strict'`` to prevent cross-site scripting
- ``'secure' => TRUE`` to insure use of HTTPS
- ``'httponly' => TRUE`` to insure non-javascript, HTTP only handling of cookies

Registration and Password Set is done via confirmed Email links with registration tokens, and Passwords are encrypted with the newest PHP ``password_hash`` function

User authorization and level should be checked before allowing any service functions (**see PHP usage above**).  DO NOT count on the javascript userRec variable, use the direct PHP lookup to get the ``UserRec`` from the cookie to double-check authorization before allowing any function

If you feel these measures still have vulnerabilities, please do not use this project

