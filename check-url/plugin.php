<?php
/*
Plugin Name: Check URL
Plugin URI: http://code.google.com/p/yourls-check-url/
Description: This plugin checks the reachability of an entered URL before creating the short link for it. An error is then returned if the entered URL is unreachable.
Version: 1.5
Author: Aylwin
Author URI: http://adigitalife.net/
*/

// Hook our custom function into the 'shunt_add_new_link' filter
yourls_add_filter( 'shunt_add_new_link', 'churl_reachability', 1 );

// Add a new link in the DB, either with custom keyword, or find one
function churl_reachability( $churl_reachable, $url, $keyword = '' ) {
        global $ydb;

        preg_match( '!^[a-zA-Z0-9\+\.-]+:(//)?!', $url, $matches );
        $protocol = ( isset( $matches[0] ) ? $matches[0] : '' );
        $different_protocols = array (
            'http://',
            'https://'
        );

        if ($protocol == '') {
        	$protocol = 'http://';
        	$url = 'http://' . $url;
        }

        $check_url = in_array( $protocol, $different_protocols );

        // Return to normal routine if non-http(s) protocol is valid
        if ($check_url == false){
                return false;
        }

        // Check if the long URL is reachable
         $resURL = curl_init();
        curl_setopt($resURL, CURLOPT_URL, $url);
        curl_setopt($resURL, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($resURL, CURLOPT_HEADERFUNCTION, 'curlHeaderCallback');
        curl_setopt($resURL, CURLOPT_FAILONERROR, 1);
        curl_exec ($resURL);
        $intReturnCode = curl_getinfo($resURL, CURLINFO_HTTP_CODE);
        curl_close ($resURL);

        // Return error if the entered URL is unreachable
        if ($intReturnCode == '' || $intReturnCode == 404) {
                $return['status']   = 'fail';
                $return['code']     = 'error:url';
                $return['message']  = 'The entered URL is unreachable.  Check the URL or try again later.';
                $return['statusCode'] = 200; // regardless of result, this is still a valid request
                return yourls_apply_filter( 'add_new_link_fail_unreachable', $return, $url, $keyword, $title );
        }

        return false;
}