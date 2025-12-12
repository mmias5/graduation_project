<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

/* CSRF check */
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';
$presidentId = (int)$_SESSION['student_id'];

/* get president club_id */
$stmt = $conn->prepare("
    SELECT club_id
    FROM student
    WHERE student_id = ? AND role = 'club_president'
    LIMIT 1
");
$stmt->bind_param("i", $presidentId);
$stmt->execute();
$res = $stmt->get_result();
$pres = $res->fetch_assoc();
$stmt->close();

$clubId = (int)($pres['club_id'] ?? 0);

if ($clubId <= 1) {
    echo json_encode(['ok' => false, 'error' => 'No club assigned']);
    exit;
}

/* =========================
   ACCEPT / REJECT request
   ========================= */
if (in_array($action, ['accept', 'reject'], true)) {

    $requestId = (int)($_POST['request_id'] ?? 0);
    if ($requestId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Invalid request']);
        exit;
    }

    /* get request */
    $stmt = $conn->prepare("
        SELECT student_id
        FROM club_membership_request
        WHERE request_id = ? AND club_id = ? AND status = 'Pending'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $requestId, $clubId);
    $stmt->execute();
    $res = $stmt->get_result();
    $req = $res->fetch_assoc();
    $stmt->close();

    if (!$req) {
        echo json_encode(['ok' => false, 'error' => 'Request not found']);
        exit;
    }

    $studentId = (int)$req['student_id'];

    $conn->begin_transaction();
    try {
        if ($action === 'accept') {

            /* approve request */
            $stmt = $conn->prepare("
                UPDATE club_membership_request
                SET status = 'Approved',
                    decided_at = NOW(),
                    decided_by_student_id = ?
                WHERE request_id = ?
            ");
            $stmt->bind_param("ii", $presidentId, $requestId);
            $stmt->execute();
            $stmt->close();

            /* assign student to club */
            $stmt = $conn->prepare("
                UPDATE student
                SET club_id = ?
                WHERE student_id = ?
            ");
            $stmt->bind_param("ii", $clubId, $studentId);
            $stmt->execute();
            $stmt->close();

            /* increment member_count */
            $stmt = $conn->prepare("
                UPDATE club
                SET member_count = member_count + 1
                WHERE club_id = ?
            ");
            $stmt->bind_param("i", $clubId);
            $stmt->execute();
            $stmt->close();

        } else {

            /* reject request */
            $stmt = $conn->prepare("
                UPDATE club_membership_request
                SET status = 'Rejected',
                    decided_at = NOW(),
                    decided_by_student_id = ?
                WHERE request_id = ?
            ");
            $stmt->bind_param("ii", $presidentId, $requestId);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        echo json_encode(['ok' => true]);
        exit;

    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'error' => 'Database error']);
        exit;
    }
}

/* =========================
   KICK member
   ========================= */
if ($action === 'kick') {

    $studentId = (int)($_POST['student_id'] ?? 0);
    if ($studentId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Invalid student']);
        exit;
    }

    // prevent kicking yourself
    if ($studentId === $presidentId) {
        echo json_encode(['ok' => false, 'error' => "You can't kick yourself"]);
        exit;
    }

    // make sure this student is actually in your club
    $stmt = $conn->prepare("
        SELECT club_id, role
        FROM student
        WHERE student_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $res = $stmt->get_result();
    $st = $res->fetch_assoc();
    $stmt->close();

    if (!$st) {
        echo json_encode(['ok' => false, 'error' => 'Student not found']);
        exit;
    }

    if ((int)$st['club_id'] !== $clubId) {
        echo json_encode(['ok' => false, 'error' => 'Student is not in your club']);
        exit;
    }

    if (($st['role'] ?? '') === 'club_president') {
        echo json_encode(['ok' => false, 'error' => "You can't kick the president"]);
        exit;
    }

    $conn->begin_transaction();
    try {

        // remove student from club -> back to default (1)
        $stmt = $conn->prepare("
            UPDATE student
            SET club_id = 1
            WHERE student_id = ?
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $stmt->close();

        // decrement member_count (protect from negative)
        $stmt = $conn->prepare("
            UPDATE club
            SET member_count = CASE WHEN member_count > 0 THEN member_count - 1 ELSE 0 END
            WHERE club_id = ?
        ");
        $stmt->bind_param("i", $clubId);
        $stmt->execute();
        $stmt->close();

        // mark latest approved request as Left (optional but useful for history)
        $stmt = $conn->prepare("
            SELECT request_id
            FROM club_membership_request
            WHERE student_id = ? AND club_id = ? AND status = 'Approved'
            ORDER BY COALESCE(decided_at, submitted_at) DESC, request_id DESC
            LIMIT 1
        ");
        $stmt->bind_param("ii", $studentId, $clubId);
        $stmt->execute();
        $res = $stmt->get_result();
        $r = $res->fetch_assoc();
        $stmt->close();

        if ($r) {
            $reqId = (int)$r['request_id'];
            $stmt = $conn->prepare("
                UPDATE club_membership_request
                SET status = 'Left',
                    decided_at = NOW(),
                    decided_by_student_id = ?
                WHERE request_id = ?
            ");
            $stmt->bind_param("ii", $presidentId, $reqId);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        echo json_encode(['ok' => true]);
        exit;

    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'error' => 'Database error']);
        exit;
    }
}

echo json_encode(['ok' => false, 'error' => 'Unknown action']);
exit;
