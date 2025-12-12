<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    echo json_encode(["ok"=>false, "error"=>"Unauthorized"]);
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(["ok"=>false, "error"=>"Invalid CSRF token"]);
    exit;
}

require_once '../config.php';

$president_id = (int)$_SESSION['student_id'];
$action = $_POST['action'] ?? '';

/* get president club_id */
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? AND role='club_president' LIMIT 1");
$stmt->bind_param("i", $president_id);
$stmt->execute();
$res = $stmt->get_result();
$pres = $res->fetch_assoc();
$stmt->close();

$club_id = isset($pres['club_id']) ? (int)$pres['club_id'] : 1;
if ($club_id <= 1) {
    echo json_encode(["ok"=>false, "error"=>"President has no active club"]);
    exit;
}

try {
    if ($action === 'kick') {
        $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        if ($student_id <= 0) throw new Exception("Invalid student");

        if ($student_id === $president_id) throw new Exception("You cannot kick yourself");

        // verify member belongs to president club
        $stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? LIMIT 1");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $m = $r->fetch_assoc();
        $stmt->close();

        if (!$m || (int)$m['club_id'] !== $club_id) throw new Exception("Member not in your club");

        $conn->begin_transaction();

        // set to default no-club (club_id=1)
        $stmt = $conn->prepare("UPDATE student SET club_id=1 WHERE student_id=?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();

        // decrement member_count (safe)
        $stmt = $conn->prepare("UPDATE club SET member_count = GREATEST(member_count - 1, 0) WHERE club_id=?");
        $stmt->bind_param("i", $club_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(["ok"=>true]);
        exit;
    }

    if ($action === 'accept' || $action === 'reject') {
        $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
        if ($request_id <= 0) throw new Exception("Invalid request");

        // load request and verify it belongs to this club and is pending
        $stmt = $conn->prepare("
            SELECT request_id, club_id, student_id, status
            FROM club_membership_request
            WHERE request_id=?
            LIMIT 1
        ");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $req = $res->fetch_assoc();
        $stmt->close();

        if (!$req) throw new Exception("Request not found");
        if ((int)$req['club_id'] !== $club_id) throw new Exception("Not your club request");
        if (($req['status'] ?? '') !== 'Pending') throw new Exception("Request already decided");

        $newStatus = ($action === 'accept') ? 'Approved' : 'Rejected';
        $student_id = (int)$req['student_id'];

        $conn->begin_transaction();

        // update request
        $stmt = $conn->prepare("
            UPDATE club_membership_request
            SET status=?, decided_at=NOW(), decided_by_student_id=?
            WHERE request_id=?
        ");
        $stmt->bind_param("sii", $newStatus, $president_id, $request_id);
        $stmt->execute();
        $stmt->close();

        if ($action === 'accept') {
            // move student into club
            $stmt = $conn->prepare("UPDATE student SET club_id=? WHERE student_id=?");
            $stmt->bind_param("ii", $club_id, $student_id);
            $stmt->execute();
            $stmt->close();

            // increment member_count
            $stmt = $conn->prepare("UPDATE club SET member_count = member_count + 1 WHERE club_id=?");
            $stmt->bind_param("i", $club_id);
            $stmt->execute();
            $stmt->close();

            // optional: if you want to auto-reject other pending requests for same student to other clubs, add it here.
        }

        $conn->commit();
        echo json_encode(["ok"=>true]);
        exit;
    }

    echo json_encode(["ok"=>false, "error"=>"Unknown action"]);
} catch (Exception $e) {
    if ($conn && $conn->errno === 0) { /* ignore */ }
    if ($conn && $conn->ping()) {
        // rollback if in transaction
        try { $conn->rollback(); } catch (Throwable $t) {}
    }
    echo json_encode(["ok"=>false, "error"=>$e->getMessage()]);
}
