<?php

// From https://stackoverflow.com/a/14270161
if (isset($_SERVER['HTTPS']) &&
        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    $protocol = 'https://';
}
else {
    $protocol = 'http://';
}


# This variable MUST have a trailing slash character
define('baseUrl',$protocol.$_SERVER['HTTP_HOST'].'/')

?>
