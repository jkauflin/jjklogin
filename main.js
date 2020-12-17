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
 *============================================================================*/
var jjkloginMain = (function () {
    'use strict'

    //=================================================================================================================
    // Private variables for the Module
    // Assume that when we include this file in the package root page (and execute PHP) it is all at the root level
    var jjkloginRoot = "";
    var userRec = null
    var url

    //=================================================================================================================
    // Variables cached from the DOM
    var $document = $(document)
    //var $ajaxError = $document.find(".ajaxError");
    var $LoginModal = $document.find('#LoginModal')
    var $logout = $document.find('#logout')
    var $LoggedIn = $document.find('#username')

    var $LoginInput = $LoginModal.find('#LoginInput')
    var $username = $LoginModal.find('#username')
    var $password = $LoginModal.find('#password')
    var $LoginButton = $LoginModal.find('#LoginButton')
    var $LoginDisplay = $LoginModal.find('#LoginDisplay')

    var $ForgotPassword = $document.find('#ForgotPassword')
    var $ResetPasswordModal = $document.find('#ResetPasswordModal')
    var $ResetPasswordInput = $ResetPasswordModal.find('#ResetPasswordInput')
    var $ResetPasswordButton = $ResetPasswordModal.find('#ResetPasswordButton')
    var $ResetPasswordDisplay = $ResetPasswordModal.find('#ResetPasswordDisplay')

    var $PasswordModal = $document.find('#PasswordModal')
    var $PasswordInput = $PasswordModal.find('#PasswordInput')
    var $regCode = $PasswordInput.find('#regCode')
    var $PasswordButton = $PasswordModal.find('#PasswordButton')
    var $PasswordDisplay = $PasswordModal.find('#PasswordDisplay')

    var $NewUserButton = $document.find('#NewUserButton')
    var $RegisterModal = $document.find('#RegisterModal')
    var $RegisterInput = $RegisterModal.find('#RegisterInput')
    var $usernameReg = $RegisterModal.find('#usernameReg')
    var $emailAddrReg = $RegisterModal.find('#emailAddrReg')
    var $RegisterButton = $RegisterModal.find('#RegisterButton')
    var $RegisterDisplay = $RegisterModal.find('#RegisterDisplay')

    var isTouchDevice = 'ontouchstart' in document.documentElement;

    //=================================================================================================================
    // Bind events
    $LoginButton.on('click', loginUser)
    // Accept input change on Enter (but not on touch devices because it won't turn off the text input)
    if (!isTouchDevice) {
        $password.change(loginUser);
    }

    $logout.on('click', logoutUser)
    $ForgotPassword.on('click', forgotPassword)
    $ResetPasswordButton.on('click', resetPassword)
    $PasswordButton.on('click', setPassword)
    $NewUserButton.on('click', displayRegistration)
    $RegisterButton.on('click', registerUser)

    //=================================================================================================================
    // Checks on initial load

    // Check for password reset in the request url
    //console.log("window.location.href = "+window.location.href);
    const firstPath = '/'+window.location.pathname.split('/')[1];

    var urlParam = 'resetPass';
    var results = new RegExp('[\?&]' + urlParam + '=([^&#]*)').exec(window.location.href);
    if (results != null) {
        var regCode = results[1] || 0;
        //console.log("regCode = " + regCode);
        $regCode.val(regCode);
        $PasswordModal.modal()
    } else {
        // Check for the authentication token when the page loads
        url = jjkloginRoot + 'authentication.php'
        $.getJSON(url, function (response) {
            userRec = response
            if (userRec == null ||
                userRec.userName == null ||
                userRec.userName == '' ||
                userRec.userLevel < 1) 
            {
                $username.val('');
                $password.val('');
                $LoginModal.modal()
            } else {
                $LoggedIn.html('Logged in as ' + userRec.userName)
            }
        });
    }

    //=================================================================================================================
    // Module methods
    function loginUser() {
        $LoginDisplay.html("")
        //$ajaxError.html("");
        url = jjkloginRoot + 'login.php'
        $.ajax(url, {
            type: 'POST',
            data: getJSONfromInputs($LoginInput, null),
            contentType: 'application/json',
            dataType: 'json' 
            //dataType: "html"
        }).done(function (result) {
            //console.log("loginUser result = " + result);
            if (result.error) {
                console.log("error = " + result.error);
                //$ajaxError.html("<b>" + result.error + "</b>");
            } else { 
                userRec = result
                if (
                    userRec == null ||
                    userRec.userName == null ||
                    userRec.userName == '' ||
                    userRec.userLevel < 1
                ) {
                    // redirect to Login
                    //console.log("userRec.userMessage = "+userRec.userMessage)
                    $LoginDisplay.html(userRec.userMessage)
                    $LoginModal.modal()
                } else {
                    $LoginModal.modal('hide')
                    //console.log("After authentication, userName = " + userRec.userName + ", level = " + userRec.userLevel)
                    // re-direct back to main page
                    window.location.href = firstPath;
                }
            }
        }).fail(function (xhr, status, error) {
            console.log('Error in AJAX request to ' + url + ', status = ' + status + ', error = ' + error)
            userRec = null
            //$ajaxError.html("<b>" + "Error in request" + "</b>");
        })
    }

    function logoutUser() {
        //$ajaxError.html("");
        url = jjkloginRoot + 'logout.php'
        $.ajax(url, {
            type: 'GET'
        }).done(function (result) {
            if (result.error) {
                console.log("error = " + result.error);
                //$ajaxError.html("<b>" + result.error + "</b>");
            } else {
                userRec = null
                $LoggedIn.html('');
                // re-direct back to main page
                window.location.href = firstPath;
            }
        }).fail(function (xhr, status, error) {
            console.log('Error in AJAX request to ' + url + ', status = ' + status + ', error = ' + error)
            userRec = null
            //$ajaxError.html("<b>" + "Error in request" + "</b>");
        })
    }

    function forgotPassword() {
        $LoginModal.modal('hide')
        $ResetPasswordDisplay.html("")
        $ResetPasswordModal.modal()
    }

    function resetPassword() {
        $LoginModal.modal('hide')
        $ResetPasswordDisplay.html("")
        url = jjkloginRoot + 'passwordReset.php'
        $.ajax(url, {
            type: 'POST',
            data: getJSONfromInputs($ResetPasswordInput, null),
            contentType: 'application/json',
            dataType: 'json'
            //dataType: "html"
        }).done(function (result) {
            //console.log("result = " + result);
            if (result.error) {
                console.log("error = " + result.error);
                //$ajaxError.html("<b>" + result.error + "</b>");
            } else {
                userRec = result
                $ResetPasswordDisplay.html(userRec.userMessage)
            }
        }).fail(function (xhr, status, error) {
            console.log('Error in AJAX request to ' + url + ', status = ' + status + ', error = ' + error)
            userRec = null
            //$ajaxError.html("<b>" + "Error in request" + "</b>");
        })
    }

    function setPassword() {
        $LoginModal.modal('hide')
        $PasswordDisplay.html("")
        url = jjkloginRoot + 'password.php'
        $.ajax(url, {
            type: 'POST',
            data: getJSONfromInputs($PasswordInput, null),
            contentType: 'application/json',
            dataType: 'json' 
            //dataType: "html"
        }).done(function (result) {
            //console.log("result = " + result);
            if (result.error) {
                console.log("error = " + result.error);
                //$ajaxError.html("<b>" + result.error + "</b>");
            } else { 
                userRec = result
                if (
                    userRec == null ||
                    userRec.userName == null ||
                    userRec.userName == '' ||
                    userRec.userLevel < 1
                ) {
                    // redirect to Login
                    $PasswordDisplay.html(userRec.userMessage)
                    $PasswordModal.modal()
                } else {
                    $PasswordModal.modal('hide')
                    $LoggedIn.html('Logged in as ' + userRec.userName)
                    // re-direct back to main page
                    window.location.href = firstPath;
                }
            }
        }).fail(function (xhr, status, error) {
            console.log('Error in AJAX request to ' + url + ', status = ' + status + ', error = ' + error)
            userRec = null
            //$ajaxError.html("<b>" + "Error in request" + "</b>");
        })
    }

    function displayRegistration() {
        $RegisterDisplay.html("")
        $usernameReg.html("")
        $emailAddrReg.html("")
        $RegisterModal.modal()
    }

    function registerUser() {
        $LoginModal.modal('hide')
        $RegisterDisplay.html("")
        //$ajaxError.html("");
        url = jjkloginRoot + 'register.php'
        $.ajax(url, {
            type: 'POST',
            data: getJSONfromInputs($RegisterInput, null),
            contentType: 'application/json',
            dataType: 'json'
            //dataType: "html"
        }).done(function (result) {
            //console.log("result = " + result);
            if (result.error) {
                console.log("error = " + result.error);
                //$ajaxError.html("<b>" + result.error + "</b>");
            } else {
                var tempUserRec = result
                $RegisterDisplay.html(tempUserRec.userMessage)
                $RegisterModal.modal()
            }
        }).fail(function (xhr, status, error) {
            console.log('Error in AJAX request to ' + url + ', status = ' + status + ', error = ' + error)
            userRec = null
            //$ajaxError.html("<b>" + "Error in request" + "</b>");
        })
    }
    
    // Function to get all input objects within a DIV, and extra entries from a map
    // and construct a JSON object with names and values (to pass in POST updates)
    function getJSONfromInputs (InputsDiv, paramMap) {
        var first = true
        var jsonStr = '{'

        if (InputsDiv !== null) {
            // Get all the input objects within the DIV
            var FormInputs = InputsDiv.find('input,textarea,select')

            // Loop through the objects and construct the JSON string
            $.each(FormInputs, function (index) {
                //id = useEmailCheckbox, type = checkbox
                //id = propertyComments, type = text
                // Only include elements that have an "id" in the JSON string
                if (typeof $(this).attr('id') !== 'undefined') {
                    if (first) {
                        first = false
                    } else {
                        jsonStr += ','
                    }
                    //console.log("id = " + $(this).attr('id') + ", type = " + $(this).attr("type"));
                    if ($(this).attr('type') == 'checkbox') {
                        //console.log("id = " + $(this).attr('id') + ", $(this).prop('checked') = " + $(this).prop('checked'));
                        if ($(this).prop('checked')) {
                            jsonStr += '"' + $(this).attr('id') + '" : 1'
                        } else {
                            jsonStr += '"' + $(this).attr('id') + '" : 0'
                        }
                    } else {
                        //jsonStr += '"' + $(this).attr('id') + '" : "' + cleanStr($(this).val()) + '"';
                        jsonStr +=
                            '"' +
                            $(this).attr('id') +
                            '" : "' +
                            $(this).val() +
                            '"'
                    }
                }
            })
        }

        if (paramMap !== null) {
            paramMap.forEach(function (value, key) {
                if (first) {
                    first = false
                } else {
                    jsonStr += ','
                }
                jsonStr += '"' + key + '" : "' + value + '"'
            })
        }

        jsonStr += '}'
        //console.log("jsonStr = "+jsonStr);
        return jsonStr
    }

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
})() // var jjkloginMain = (function(){
