'use strict';

let geoController = (function () {
    // *****************************
    // Private Variables
    // *****************************
    let position = null;
    let lastMessage = null;
    let lastError = null;
    let successCallback = null;
    let errorCallback = null;

    // Enable high accuracy, if available
    // Timeout after 10 seconds
    // Only cached positions where age < 5 minutes
    let options = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000
    };

    // *****************************
    // Private Functions
    // *****************************
    function getCurrentPosition(success, error, posOptions) {
        // Set callbacks
        successCallback = success;
        errorCallback = error;
        if (posOptions) { options = posOptions; }

        // Reset private variables
        position = null;
        lastError = null;
        lastMessage = null;
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(setPosition, handleError, options);
        }
        else {
            callError("Update your browser to use Geolocation.");
        }
    }

    function setPosition(pos) {
        position = pos;
        if (successCallback) {
            successCallback(position);
        }
    }

    function handleError(error) {
        lastError = error;
        switch (error.code) {
            case error.PERMISSION_DENIED:
                lastMessage = "User does not want to display their location.";
                break;
            case error.POSITION_UNAVAILABLE:
                lastMessage = "Can't determine user's location.";
                break;
            case error.TIMEOUT:
                lastMessage = "The request for geolocation information timed out.";
                break;
            case error.UNKNOWN_ERROR:
                lastMessage = "An unknown error occurred."
                break;
        }
        callError(lastMessage);
    }

    function callError(msg) {
        lastMessage = msg;
        console.log(msg);
        if (errorCallback) {
            errorCallback(lastMessage);
        }
    }

    // *****************************
    // Public Functions
    // *****************************
    return {
        "getCurrentPosition": function (success, error, posOptions) {
            getCurrentPosition(success, error, posOptions);
        },
        "getPosition": function () {
            return position;
        },
        "getLastError": function () {
            return lastError;
        },
        "getLastMessage": function () {
            return lastMessage;
        },
        "getOptions": function () {
            return options;
        }
    }
})();
