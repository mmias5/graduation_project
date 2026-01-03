<?php
require_once '../config.php';
require_once 'admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['sponsor_id'])) {
    header('Location: sponsors.php');
    exit;
}

$sponsorId = (int)$_POST['sponsor_id'];

$message = '';
$type = 'success';

// delete related entries first 
$stmt1 = $conn->prepare("DELETE FROM sponsor_club_support WHERE sponsor_id = ?");
if ($stmt1) {
    $stmt1->bind_param("i", $sponsorId);
    $stmt1->execute();
    $stmt1->close();
}

// then delete sposnor 
$stmt2 = $conn->prepare("DELETE FROM sponsor WHERE sponsor_id = ?");
if ($stmt2) {
    $stmt2->bind_param("i", $sponsorId);
    if ($stmt2->execute() && $stmt2->affected_rows > 0) {
        $message = 'Sponsor deleted successfully.';
        $type = 'success';
    } else {
        $message = 'Sponsor not found or already deleted.';
        $type = 'error';
    }
    $stmt2->close();
} else {
    $message = 'Database error while deleting sponsor.';
    $type = 'error';
}

header('Location: sponsors.php?msg=' . urlencode($message) . '&type=' . urlencode($type));
exit;
