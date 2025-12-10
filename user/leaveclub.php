<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$studentId = $_SESSION['student_id'];

// 1) Get current club
$stmt = $conn->prepare("
    SELECT club_id
    FROM student
    WHERE student_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$res = $stmt->get_result();
$current = $res->fetch_assoc();
$stmt->close();

if (!$current) {
    $_SESSION['club_flash'] = [
        'type' => 'error',
        'msg'  => 'Student not found.'
    ];
    header('Location: clubpage.php');
    exit;
}

$currentClubId = (int)($current['club_id'] ?? 0);

// club_id = 1 => No club / Not assigned
if ($currentClubId === 0 || $currentClubId === 1) {
    $_SESSION['club_flash'] = [
        'type' => 'error',
        'msg'  => 'You are not currently in a club.'
    ];
    header('Location: clubpage.php');
    exit;
}

try {
    $conn->begin_transaction();

    // 2) Move student to "No Club / Not Assigned"
    $newClubId = 1;
    $stmt = $conn->prepare("
        UPDATE student
        SET club_id = ?
        WHERE student_id = ?
    ");
    $stmt->bind_param('ii', $newClubId, $studentId);
    $stmt->execute();
    $stmt->close();

    // 3) Decrease member_count (but not below 0)
    $stmt = $conn->prepare("
        UPDATE club
        SET member_count = GREATEST(member_count - 1, 0)
        WHERE club_id = ?
    ");
    $stmt->bind_param('i', $currentClubId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $_SESSION['club_flash'] = [
        'type' => 'success',
        'msg'  => 'You have successfully left your club.'
    ];
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['club_flash'] = [
        'type' => 'error',
        'msg'  => 'Something went wrong while leaving the club. Please try again.'
    ];
}

header('Location: clubpage.php');
exit;
