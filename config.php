<?php
// config.php  (ROOT)

$host    = 'localhost';   // أو 'localhost'
$user    = 'root';
$pass    = '';
$db_name = 'unihive';

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// مهم عشان العربي والـ utf8mb4
$conn->set_charset('utf8mb4');
