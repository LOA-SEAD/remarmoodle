<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$token = '972a7db37773bf9edd86b71aae49d346';
$domainname = 'localhost/moodle';

/// FUNCTION NAME
$functionname = 'mod_remarmoodle_token_verifier';
$restformat = 'json';

/// PARAMETERS

/// REST CALL
$serverurl = $domainname . '/webservice/rest/server.php'. '?wstoken=' . $token . '&wsfunction='.$functionname;
require_once('../curl.php');
$curl = new curl;
//if rest format == 'xml', then we do not add the param for backward compatibility with Moodle < 2.2
$restformat = ($restformat == 'json')?'&moodlewsrestformat=' . $restformat:'';
$resp = $curl->post($serverurl . $restformat, array('hash' => 'dsadsadsadsadsadsa'));
print_r($resp);
