<?php
// "Become a Sponsor" and save it in table sponsor_request

require_once 'config.php'; 
// NO session_start() here – الصفحة عامة

// تأكد أن الصفحة تم استدعاؤها عبر POST من الفورم
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// recieve values from form 
$company     = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$website     = trim($_POST['website'] ?? '');     // لازم تغيري name حقل الموقع في الفورم لـ website
$description = trim($_POST['description'] ?? '');

// validate values
if ($company === '' || $email === '' || $phone === '' || $description === '') {
    // error code بسيط
    header('Location: register.php?error=missing');
    exit;
}

//  validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=email');
    exit;
}

// insert into database table sponsor_request 

$sql = "
    INSERT INTO sponsor_request
        (company_name, email, phone, description, website, status, submitted_at)
    VALUES
        (?, ?, ?, ?, ?, 'Pending', NOW())
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // if preparation failed
    // die('Prepare failed: ' . $conn->error);
    header('Location: register.php?error=server');
    exit;
}

$stmt->bind_param(
    "sssss",
    $company,
    $email,
    $phone,
    $description,
    $website
);

$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    // if execution failed
    // die('Execute failed: ' . $conn->error);
    header('Location: register.php?error=server');
    exit;
}

// if everything worked go to thankyou page
header('Location: thankyou.php');
exit;
