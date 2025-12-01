<?php
// admin/deletenews.php
require_once '../config.php';
require_once 'admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: news.php');
    exit;
}

$newsId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($newsId <= 0) {
    $_SESSION['flash_error'] = 'Invalid news id.';
    header('Location: news.php');
    exit;
}

// أولاً نجيب الصورة عشان نحذفها من الملفات
$sql = "SELECT image FROM news WHERE news_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $newsId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    $imagePath = $row['image'];
    if (!empty($imagePath) && file_exists('../' . $imagePath)) {
        @unlink('../' . $imagePath);
    }
}

// حذف السطر
$deleteSql = "DELETE FROM news WHERE news_id = ?";
$deleteStmt = $conn->prepare($deleteSql);
$deleteStmt->bind_param("i", $newsId);

if ($deleteStmt->execute()) {
    $_SESSION['flash_success'] = 'News deleted successfully.';
} else {
    $_SESSION['flash_error'] = 'Error deleting news: ' . $deleteStmt->error;
}

header('Location: news.php');
exit;
