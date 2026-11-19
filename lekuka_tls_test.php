<?php

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);

$errno = 0;
$errstr = '';

$connection = @stream_socket_client(
    'ssl://lekukaapi.rsl.org.ls:8443',
    $errno,
    $errstr,
    15,
    STREAM_CLIENT_CONNECT,
    $context
);

var_dump([
    'connected' => $connection !== false,
    'error_number' => $errno,
    'error_message' => $errstr,
]);

if ($connection !== false) {
    fclose($connection);
}
