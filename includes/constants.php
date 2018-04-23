<?php

    /*  API CONSTANTS   */
        // System Specific variables
        $hosturl = "sandbox.iii.com"; // Sierra application server
        $apiurl = "https://".$hosturl."/iii/sierra-api/v5/"; // Sierra API address

        // Authorization section
        $auth = "Key:Secret"; // Authentication client key and secret (key:secret) for the III SANDBOX SERVER
        $encauth = base64_encode($auth);
        
?>
