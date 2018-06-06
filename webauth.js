// Add javascript to set value of webauth_user field if present and empty

var webauth = webauth || {};


webauth.log = function() {
    if (!webauth.isDev) return false;

    // Make console logging more resilient to Redmond
    try {
        console.log.apply(this,arguments);
    } catch(err) {
        // Error trying to apply logs to console (problem with IE11)
        try {
            console.log(arguments);
        } catch (err2) {
            // Can't even do that!  Argh - no logging
        }
    }
};

$(document).ready(function() {
    // Does the webauth_user field exist - if so, we push the current user into that field for tracking...
    var wa = $('input[name^=\"webauth_user\"]');
    if (wa.length) {
        // It exists and is empty - then update it
        if( wa.val().length == 0 ) {
            $(wa).val(webauth.sunet).blur(); //prop('disabled',true);
            calculate();doBranching();
        }
    }
    webauth.webAuthSession = true;   // Default the session to true.

    // Add a test to verify that we have a valid session - this handles scenarios where someone starts an authed survey
    // and then goes to bed and awakes in the morning thinking they can still submit the survey but the submit is
    // rejected since their session has expired...
    window.setInterval( function() {
        $.ajax({
            type: "GET",
            url: webauth.refreshUrl
        })
        .always(function(data, textStatus, xhr) {
            if (data == 1) {
                webauth.log("Good");
                if (! webauth.webAuthSession) {
                    // hide the alert as a session was re-established
                    $('div.webauth-alert-wrapper').slideUp();
                }
                webauth.webAuthSession = true;
                //console.log('connection good');
            } else {
                webauth.log("Bad");
                // console.log("webauthSessoin", webauth.webAuthSession);
                if (webauth.webAuthSession) {
                    //good session went bad
                    webauth.webAuthSession = false;
                    $('div.webauth-alert-wrapper').slideDown();
                }
                //console.log('webauth connection unknown: ' + xhr.status);
            }
            webauth.log("Session state", webauth.webAuthSession);

        });
    }, 30 * 1000);
});
