/*==============================================================================
 * (C) Copyright 2020,2023 John J Kauflin, All rights reserved.
 *----------------------------------------------------------------------------
 * DESCRIPTION:  Login authentication and authorization handling based on
 *               credentials stored in JWT Tokens, saved in HttpOnly, Secure,
 *               Samesite cookies, and user/auth properties in a database
 *
 * list what the library does and what it expects from caller
 * caller implements all ui and DIV's
 * library provides all authentication and login functions (and UserRec properties
 * to indicate an authenticated user.
 * and function to get AdminLevel)
 *
 * top level app must include JWT-PHP library in it's composer
 * and include and call jjklogin PHP functions?
 *
 *----------------------------------------------------------------------------
 * Modification History
 * 2020-07-24 JJK 	Initial version
 * 2020-07-28 JJK   Added Registration handling
 * 2020-08-01 JJK   Re-factored to be in the same path as project
 * 2020-08-03 JJK   Re-factored for new error handling
 * 2020-08-04 JJK   Added password set logic, and NewUser function
 * 2020-12-13 JJK   Updated to be a Composer package.  Move the set of the
 *                  jjkloginRoot to the parent page
 * 2020-12-19 JJK   Corrected some navigation issues and added HomeNav
 * 2020-12-21 JJK   Corrected set of webRootPath
 * 2023-02-11 JJK   Re-factored for Bootstrap 5 and get rid of JQuery in 
 *                  favor of vanilla JS
 *============================================================================*/
var jjkloginMain = (function () {
    'use strict'

    //=================================================================================================================
    // Private variables for the Module
    // Assume that when we include this file in the package root page (and execute PHP) it is all at the root level
    var jjkloginRoot = ""
    var url = ""

    //=================================================================================================================
    // Variables cached from the DOM
    var LoginModal = new bootstrap.Modal(document.getElementById('LoginModal'))
    var LoggedIn = document.getElementById('LoggedIn')
    var username = document.getElementById('username')
    var password = document.getElementById('password')
    var LoginDisplay = document.getElementById('LoginDisplay')
    var ResetPasswordModal = new bootstrap.Modal(document.getElementById('ResetPasswordModal'))
    var ResetPasswordDisplay = document.getElementById('ResetPasswordDisplay')
    var PasswordModal = new bootstrap.Modal(document.getElementById('PasswordModal'))
    var regCode = document.getElementById('regCode')
    var PasswordDisplay = document.getElementById('PasswordDisplay')
    var RegisterModal = new bootstrap.Modal(document.getElementById('RegisterModal'))
    var usernameReg = document.getElementById('usernameReg')
    var emailAddrReg = document.getElementById('emailAddrReg')
    var RegisterDisplay = document.getElementById('RegisterDisplay')

    var isTouchDevice = 'ontouchstart' in document.documentElement;

    //=================================================================================================================
    // Bind events

    document.getElementById('LoginButton').addEventListener('click', loginUser)
    document.getElementById('HomeNav').addEventListener('click', redirectHome)
    document.getElementById('logout').addEventListener('click', logoutUser)
    document.getElementById('ForgotPassword').addEventListener('click', forgotPassword)
    document.getElementById('ResetPasswordButton').addEventListener('click', resetPassword)
    document.getElementById('PasswordButton').addEventListener('click', setPassword)
    document.getElementById('RegisterButton').addEventListener('click', registerUser)

    // Accept input on Enter (but not on touch devices because it won't turn off the text input)
    if (!isTouchDevice) {
        document.getElementById("password").addEventListener("keyup", function(event) {
          if (event.key === "Enter") {
            event.preventDefault();
            loginUser();
          }
        });
        document.getElementById("emailAddrReset").addEventListener("keyup", function(event) {
          if (event.key === "Enter") {
            event.preventDefault();
            resetPassword();
          }
        });
        document.getElementById("password_2").addEventListener("keyup", function(event) {
          if (event.key === "Enter") {
            event.preventDefault();
            setPassword();
          }
        });
        document.getElementById("emailAddrReg").addEventListener("keyup", function(event) {
          if (event.key === "Enter") {
            event.preventDefault();
            registerUser();
          }
        });
    }

    function redirectHome() {
        window.location.href = webRootPath;
    }

    //=================================================================================================================
    // Checks on initial load

    // Get the path of the root web page (for re-directing home)
    //console.log("window.location.href = "+window.location.href);
    //console.log("window.location.pathname = "+window.location.pathname);
    var tempPath = window.location.pathname;
    var strPos = tempPath.indexOf('/vendor/jkauflin/jjklogin');
    const webRootPath = tempPath.substring(0,strPos);

    // Check for password reset in the request url
    var urlParam = 'resetPass';
    var results = new RegExp('[\?&]' + urlParam + '=([^&#]*)').exec(window.location.href);
    if (results != null) {
        var regCodeResult = results[1] || 0;
        regCode.value = regCodeResult
        PasswordModal.show()
    } else {
        // Check for the authentication token when the page loads
        url = jjkloginRoot + 'authentication.php'
        fetch(url)
        .then(response => response.json())
        .then(userRec => {
            if (userRec == null ||
                userRec.userName == null ||
                userRec.userName == '' ||
                userRec.userLevel < 1) 
            {
                username.value = ''
                password.value = ''
                LoginModal.show()
            } else {
                LoggedIn.innerHTML = 'Logged in as ' + userRec.userName
            }
        });
    }

    //=================================================================================================================
    // Module methods
    function loginUser() {
        LoginDisplay.innerHTML = ""
        url = jjkloginRoot + 'login.php'

        let InputData = {username:username.value, password:password.value};

        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(InputData)
        })
        .then(response => response.json())
        .then(userRec => {
            if (
                userRec == null ||
                userRec.userName == null ||
                userRec.userName == '' ||
                userRec.userLevel < 1
                ) 
            {
                // redirect to Login
                //console.log("userRec.userMessage = "+userRec.userMessage)
                LoginDisplay.innerHTML = userRec.userMessage
                LoginModal.show()
            } else {
                LoginModal.hide()
                //console.log("After authentication, userName = " + userRec.userName + ", level = " + userRec.userLevel)
                // re-direct back to main page
                redirectHome();
            }
        });
    }

    function logoutUser() {
        url = jjkloginRoot + 'logout.php'
        fetch(url)
        .then(response => response.json())
        .then(userRec => {
            LoggedIn.innerHTML = ''
            // re-direct back to main page
            redirectHome();
        });
    }

    function forgotPassword() {
        LoginModal.hide()
        ResetPasswordDisplay.innerHTML = ""
        ResetPasswordModal.show()
    }

    function resetPassword() {
        LoginModal.hide()
        ResetPasswordDisplay.innerHTML = ""
        url = jjkloginRoot + 'passwordReset.php'

        let InputData = {usernameReset:usernameReset.value, emailAddrReset:emailAddrReset.value};

        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(InputData)
        })
        .then(response => response.json())
        .then(userRec => {
            ResetPasswordDisplay.innerHTML = userRec.userMessage
        });
    }

    function setPassword() {
        LoginModal.hide()
        PasswordDisplay.innerHTML = ""
        url = jjkloginRoot + 'password.php'

        let InputData = {regCode:regCode.value, password_1:password_1.value, password_2:password_2.value};

        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(InputData)
        })
        .then(response => response.json())
        .then(userRec => {
            if (
                userRec == null ||
                userRec.userName == null ||
                userRec.userName == '' ||
                userRec.userLevel < 1
            ) {
                // redirect to Login
                PasswordDisplay.innerHTML = userRec.userMessage
                PasswordModal.show()
            } else {
                PasswordModal.hide()
                LoggedIn.innerHTML = 'Logged in as ' + userRec.userName
                // re-direct back to main page
                redirectHome();
            }
        });
    }

    function displayRegistration() {
        RegisterDisplay.innerHTML = ""
        usernameReg.innerHTML = ""
        emailAddrReg.innerHTML = ""
        RegisterModal.show()
    }

    function registerUser() {
        LoginModal.hide()
        RegisterDisplay.innerHTML = ""
        url = jjkloginRoot + 'register.php'

        let InputData = {usernameReg:usernameReg.value, emailAddrReg:emailAddrReg.value};

        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(InputData)
        })
        .then(response => response.json())
        .then(userRec => {
            RegisterDisplay.innerHTML = userRec.userMessage
            RegisterModal.show()
        });
    }
    
    //=================================================================================================================
    // This is what is exposed from this Module
    return {
    }
})() // var jjkloginMain = (function(){
