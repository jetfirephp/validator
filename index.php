<?php

require_once 'src/Validator.php';


\JetFire\Validator\Validator::addRule('ma',function($request,$param,$parameters = null){
    if (!empty($parameters['ma'])) {
        if ((int)$request[$param] <= (int)$parameters['ma'])return true;
    }
    return '"' . $param . '" must be lower than "'.$parameters['ma'].'"';
});

$response = JetFire\Validator\Validator::validate([
    'name::Peter' => 'alpha|length:<10',
    'number::2'   => 'numeric|ma:1'
]);

var_dump($response);