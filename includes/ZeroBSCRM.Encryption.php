<?php
/*!
 * Zero BS CRM
 * http://zerobscrm.com
 * V2.2+
 *
 * Copyright 2019 Zero BS Software Ltd.
 *
 * Date: 12/09/2017
 */

 // NOTE - NOT GOOD for hard encryption, for now used basically
 // https://gist.github.com/joashp/a1ae9cb30fa533f4ad94
function zeroBSCRM_encrpytion_unsafe_process($action, $string, $key, $iv) {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = $key;
    $secret_iv = $iv;
    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}