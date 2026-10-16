<?php
require_once(__DIR__ . "/core/Env.php");
Env::load(__DIR__ . "/core/.env");

#   SECURITY & SESSIONS
ini_set('session.cookie_secure', Env::get('COOKIE_SECURE', '1'));
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

session_name(Env::get('COOKIE_NAME', 'TUTORIAS_SID'));
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

#   ENVIRONMENT DEFINITIONS
define('COOKIE_NAME', Env::get('COOKIE_NAME', 'TUTORIAS_SID'));
define('COOKIE_KEY', Env::get('COOKIE_KEY'));
define('COOKIE_SECURE', Env::get('COOKIE_SECURE', '1') === '1');
define('DIAS_RECORDAR', 30);

define("HOST_DB", Env::get('HOST_DB'));
define("PORT_DB", Env::get('PORT_DB', '3306'));
define("USER_DB", Env::get('USER_DB'));
define("PASSWORD_DB", Env::get('PASSWORD_DB'));
define("DATABASE", Env::get('DATABASE'));
define("CHARSET", Env::get('CHARSET', 'utf8mb4'));
