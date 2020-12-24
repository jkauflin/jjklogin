# jjklogin
``jjklogin`` is a PHP JWT based project to add user authentication for SPA/JQuery type web apps.  
It provides library classes and UI for:
- user registration
- login
- password reset
- logout

As well as [JQuery](https://jquery.com/) functions to access:
- authentication confirmation
- user name
- user level

## Dependencies
To use this project there must be a hosted web application on a server that supports [PHP](https://www.php.net/), [MySQL/MariaDB](https://mariadb.org/), and [Composer/Packagist](https://getcomposer.org/).  
Internally it uses CDN includes for [JQuery](https://jquery.com/) and [Bootstrap](https://getbootstrap.com/docs/4.5/getting-started/introduction/)

## Installation
1. Add the following dependencies to ``composer.json`` to pull in the package from 	[packagist.org](https://packagist.org/packages/jkauflin/jjklogin)

```
    {
        "require": {
            "php": ">=7.0.0",
            "firebase/php-jwt": "^5.2.0",
            "jkauflin/jjklogin": "^1.1.4"
        }
    }
```

2. Include the following javascript file in your web page
```
    <script src="vendor/jkauflin/jjklogin/jjklogin.js?ver=1.002"></script>
```
3. Use the ``vendor/jkauflin/jjklogin/createUsersTable.sql`` to create a ``users`` table in a MySQL database

4. Copy the ``vendor/jkauflin/jjklogin/jjkloginSettings.php`` settings file into an ``external_includes`` folder that is on the same level as the ``public_html`` of the web app (i.e. parent folder of the web outside of public access), and adjust the settings for the web app, email, keys, and database access


## Usage
### HTML page usage
After including the ``jjklogin.js`` in a web page, include a link with an id of ``login`` to re-direct to the project page for authentication functions:

    <a class="nav-link" id="login" href="#" role="button">login</a>

It could be included in a Bootstrap navigation list:

    <li class="nav-item"><a class="nav-link" id="login" href="#" role="button">login</a></li>

If desired, include a DIV with a ``username`` id to display the username after login is authenticated:

        <div id="username" class="float-right"></div>


### Javascript use
After user authentication, a ``userRec`` variable is kept in the javascript, and the following functions are available:
- isUserLoggedIn
- getUserName
- getUserLevel

To access the function from other javascript modules simple use the ``jjklogin`` module name:

    if (jjklogin.isUserLoggedIn()) {
        // jquery code for a logged-in in user
        console.log("user is logged in, username is "+jjklogin.getUserName);
        if (jjklogin.getUserLevel() > 1) {
            // jquery code for a certain user level
            console.log("user level is greater than 1");
        }
    }

### PHP usage
The javascript ``userRec`` variable is helpful for adjusting the display but additional security checks should be done in any PHP files doing service work.  The PHP should get the ``UserRec`` directly and check authentication and user level before allowing functions.  Here is an example of code that can be used in the PHP to throw an exception if the user is not authorized:

    $userRec = LoginAuth::getUserRec($cookieName,$cookiePath,$serverKey);
    if ($userRec->userName == null || $userRec->userName == '') {
        throw new Exception('User is NOT logged in', 500);
    }
    if ($userRec->userLevel < 1) {
        throw new Exception('User is NOT authorized (contact Administrator)', 500);
    }

### Login Authentication Event
An Event for the user login authentication is available.  Simply include the following element in the HTML:

    <div id="jjkloginEventElement"></div>

Then you can add the following javascript to respond to the authentication event:

    var $jjkloginEventElement = $(document).find('#jjkloginEventElement')
    $jjkloginEventElement.on('userJJKLoginAuth', function (event) {
        console.log('After login, username = '+
            event.originalEvent.detail.userName);
    });


## Security
This project uses [firebase/php-jwt](https://github.com/firebase/php-jwt) to encode and decode JSON Web Tokens (JWT) in PHP, conforming to RFC 7519. Look in the ``src/LoginAuth.php`` class to see how this project securely uses cookies to store the JWT tokens, including:
- ``'samesite' => 'strict'`` to prevent cross-site scripting
- ``'secure' => TRUE`` to insure use of HTTPS
- ``'httponly' => TRUE`` to insure non-javascript, HTTP only handling of cookies

Registration and Password Set is done via confirmed Email links with registration tokens, and Passwords are encrypted with the newest PHP ``password_hash`` function

User authorization and level should be checked before allowing any service functions (**see PHP usage above**).  DO NOT count on the javascript userRec variable, use the direct PHP lookup to get the ``UserRec`` from the cookie to double-check authorization before allowing any function

If you feel these measures still have vulnerabilities, please do not use this project

