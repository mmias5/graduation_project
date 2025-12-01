<?php
// معالجة نموذج "Become a Sponsor" وحفظه في جدول sponsor_request

require_once 'config.php';   // اتصال قاعدة البيانات فقط
// NO session_start() here – الصفحة عامة

// تأكد أن الصفحة تم استدعاؤها عبر POST من الفورم
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// استلام القيم من الفورم
$company     = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$website     = trim($_POST['website'] ?? '');     // لازم تغيري name حقل الموقع في الفورم لـ website
$description = trim($_POST['description'] ?? '');

// ✅ تحقق بسيط من القيم المطلوبة
if ($company === '' || $email === '' || $phone === '' || $description === '') {
    // ممكن نرجع مع error code بسيط
    header('Location: register.php?error=missing');
    exit;
}

// ✅ تحقق من صيغة الإيميل
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=email');
    exit;
}

// تحضير جملة الـ INSERT لجدول sponsor_request
// جدولك حسب الـ SQL:
// sponsor_request (request_id, company_name, email, phone, description, website, status, submitted_at, reviewed_at, review_admin_id)

$sql = "
    INSERT INTO sponsor_request
        (company_name, email, phone, description, website, status, submitted_at)
    VALUES
        (?, ?, ?, ?, ?, 'Pending', NOW())
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // في حالة فشل التحضير – للتطوير ممكن تطبعي الرسالة
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
    // فشل التنفيذ
    // die('Execute failed: ' . $conn->error);
    header('Location: register.php?error=server');
    exit;
}

// ✅ لو كل شيء تمام → روح على صفحة الشكر
header('Location: thankyou.php');
exit;
