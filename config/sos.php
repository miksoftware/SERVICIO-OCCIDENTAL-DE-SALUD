<?php

return [
    'base_url'     => env('SOS_BASE_URL', 'https://centralaplicaciones.sos.com.co/ValidadorWeb2'),
    'login_url'    => env('SOS_LOGIN_URL', 'https://centralaplicaciones.sos.com.co/ValidadorWeb2/Logueo/login.jsf'),
    'inicio_url'   => env('SOS_INICIO_URL', 'https://centralaplicaciones.sos.com.co/ValidadorWeb2/Logueo/inicio.jsf'),
    'consulta_url' => env('SOS_CONSULTA_URL', 'https://centralaplicaciones.sos.com.co/ValidadorWeb2/view/consultaAfilido.jsf'),
    'username'     => env('SOS_USERNAME', ''),
    'password'     => env('SOS_PASSWORD', ''),
    'delay'        => (int) env('SOS_DELAY', 1500),
    'timeout'      => (int) env('SOS_TIMEOUT', 30),
];
