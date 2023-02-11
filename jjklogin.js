/*==============================================================================
 * (C) Copyright 2020 John J Kauflin, All rights reserved.
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
 * $cookiePath = '/grha/hoadb';
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
 * 2020-12-22 JJK   Added login event userJJKLoginAuth
 *============================================================================*/
var jjklogin = (function () {
    'use strict'

    //=================================================================================================================
    // Private variables for the Module
    var jjkloginRoot = "vendor/jkauflin/jjklogin/";
    var userRec = null
    var url

    //=================================================================================================================
    // Variables cached from the DOM
    var LoggedIn = document.getElementById('LoggedIn')

    // Create an event to tell calling applications that a user has authenticated
    var userJJKLoginAuthEvent = new CustomEvent("userJJKLoginAuth", {
        detail: {
            userName: '',
            userLevel: 0,
            userMessage: ''
        },
        bubbles: true,
        cancelable: true
    });
    var jjkloginEventElement = document.getElementById("jjkloginEventElement");

    //=================================================================================================================
    // Bind events
    document.getElementById('login').addEventListener('click', loginRedirect)

    //=================================================================================================================
    // Checks on initial load
    var urlParam = 'resetPass';
    var results = new RegExp('[\?&]' + urlParam + '=([^&#]*)').exec(window.location.href);
    if (results != null) {
        var regCodeResult = results[1] || 0;
        //console.log("regCode = " + regCode);
        // When the password reset request comes to the domain url, pass it to the jjklogin page
        window.location.href = jjkloginRoot + '?resetPass='+regCodeResult;
    } else {
        // Check for the authentication token when the page loads
        url = jjkloginRoot + 'authentication.php'
        fetch(url)
        .then(response => response.json())
        .then(userRec => {
            if (userRec == null ||
                userRec.userName == null ||
                userRec.userName == '' ||
                userRec.userLevel < 1) {
                // Nothing for now (don't automatically redirect to Login - make the user choose to login)
                LoggedIn.innerHTML = ''
                if (userRec.userMessage == 'Redirect to login') {
                    window.location.href = jjkloginRoot;
                }
            } else {
                LoggedIn.innerHTML = 'Logged in as ' + userRec.userName
                dispatchJJKLoginEvent(userRec);
            }
        });
    }
    
    // Re-Direct to main page of the jjklogin package (in the package root)
    function loginRedirect () {
        window.location.href = jjkloginRoot;
    }

    // Dispatch an event to tell calling applications that a user has authenticated
    function dispatchJJKLoginEvent(userRec) {
        if (jjkloginEventElement != null) {
            userJJKLoginAuthEvent.detail.userName = userRec.userName;
            userJJKLoginAuthEvent.detail.userLevel = userRec.userLevel;
            userJJKLoginAuthEvent.detail.userMessage = userRec.userMessage;

            jjkloginEventElement.dispatchEvent(userJJKLoginAuthEvent);
        }

        /* Implement the following to use event
        HTML - Declare an element for the event
        <div id="jjkloginEventElement"></div>

        JAVASCRIPT - Respond to the login authentication event
        var $jjkloginEventElement = $(document).find('#jjkloginEventElement')
        $jjkloginEventElement.on('userJJKLoginAuth', function (event) {
            console.log('After login, username = '+event.originalEvent.detail.userName);
        });
        */
    }
   
    //=================================================================================================================
    // Module methods

    function getUserName () {
        if (userRec != null) {
            return userRec.userName
        } else {
            return null
        }
    }
    function getUserLevel () {
        if (userRec != null) {
            return userRec.userLevel
        } else {
            return null
        }
    }

    function isUserLoggedIn() {
        if (userRec == null ||
            userRec.userName == null ||
            userRec.userName == '' ||
            userRec.userLevel < 1
        ) {
            return false;
        } else {
            return true;
        }
    }

    //=================================================================================================================
    // This is what is exposed from this Module
    return {
        getUserName,
        getUserLevel,
        isUserLoggedIn
    }
})() // var jjklogin = (function(){
