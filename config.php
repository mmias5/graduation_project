<?php

$host    = 'localhost';
$user    = 'root';
$pass    = '';
$db_name = 'unihive';

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
define('BASE_URL', '/GRADUATION_PROJECT/'); 
