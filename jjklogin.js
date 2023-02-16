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
 * 2020-12-22 JJK   Added login event userJJKLoginAuth
 * 2023-02-11 JJK   Re-factored for Bootstrap 5 and get rid of JQuery in 
 *                  favor of vanilla JS
 * 2023-02-15 JJK   Removed set of LogginIn element and functions to return
 *                  user values for better abstraction.  Expectation is that
 *                  calling page and JS will define the jjkloginEventElement
 *                  and handle the userJJKLoginAuth event to get user values
 *============================================================================*/
var jjklogin = (function () {
    'use strict'

    //=================================================================================================================
    // Private variables for the Module
    var jjkloginRoot = "vendor/jkauflin/jjklogin/";
    var tempPath = window.location.pathname;
    var strPos = tempPath.indexOf('/vendor');
    if (strPos >= 0) {
        jjkloginRoot = tempPath.substring(0,strPos+1) + jjkloginRoot;
    }
    var url

    //=================================================================================================================
    // Variables cached from the DOM
    var jjkloginEventElement = document.getElementById("jjkloginEventElement");

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
                userRec.userLevel < 1) 
            {
                // If configured, redirect to the login if user is not authenticated
                if (userRec.autoRedirect) {
                    window.location.href = jjkloginRoot;
                }
            } else {
                //LoggedIn.innerHTML = 'Logged in as ' + userRec.userName
                //dispatchJJKLoginEvent(userRec);
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
                var jjkloginEventElement = document.getElementById("jjkloginEventElement");
        
                jjkloginEventElement.addEventListener('userJJKLoginAuth', function (event) {
                    console.log('After login, username = '+event.originalEvent.detail.userName);
                });
                */
            }
        });
    }
    
    // Re-Direct to main page of the jjklogin package (in the package root)
    function loginRedirect () {
        window.location.href = jjkloginRoot;
    }

    //=================================================================================================================
    // This is what is exposed from this Module
    return {
    }
})() // var jjklogin = (function(){
