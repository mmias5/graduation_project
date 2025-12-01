<?php
session_start();
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header('Location: login.php?error=1');
    exit;
}

$stmt = $conn->prepare("
    SELECT admin_id, admin_name, email, password
    FROM uni_administrator
    WHERE email = ?
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: login.php?error=1');
    exit;
}

// حالياً مقارنة نصية بسيطة، لاحقاً نستخدم password_hash
if ($password !== $user['password']) {
    header('Location: login.php?error=1');
    exit;
}

$_SESSION['admin_id']   = $user['admin_id'];
$_SESSION['admin_name'] = $user['admin_name'];
$_SESSION['role']       = 'admin';

header('Location: index.php');
exit;
?>
