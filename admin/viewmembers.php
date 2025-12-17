<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
$BASE = '/graduation_project/';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ===============================
// Read club_id from URL
// ===============================
$clubId = (int)($_GET['club_id'] ?? 0);
if ($clubId <= 0) {
    die("Missing club_id. Open this page like: clubmembers.php?club_id=2");
}

// ===============================
// Handle Make President (POST)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'make_president') {
    $newPresidentId = (int)($_POST['student_id'] ?? 0);

    if ($newPresidentId > 0) {
        $check = $conn->prepare("SELECT student_id FROM student WHERE student_id = ? AND club_id = ? LIMIT 1");
        $check->bind_param("ii", $newPresidentId, $clubId);
        $check->execute();
        $ok = $check->get_result()->num_rows > 0;
        $check->close();

        if ($ok) {
            $conn->begin_transaction();
            try {
                $demote = $conn->prepare("
                    UPDATE student
                    SET role = 'student'
                    WHERE club_id = ? AND role = 'club_president'
                ");
                $demote->bind_param("i", $clubId);
                $demote->execute();
                $demote->close();

                $promote = $conn->prepare("
                    UPDATE student
                    SET role = 'club_president'
                    WHERE student_id = ? AND club_id = ?
                    LIMIT 1
                ");
                $promote->bind_param("ii", $newPresidentId, $clubId);
                $promote->execute();
                $promote->close();

                $conn->commit();
                header("Location: " . basename(__FILE__) . "?club_id={$clubId}&ok=1");
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                header("Location: " . basename(__FILE__) . "?club_id={$clubId}&err=" . urlencode("Failed to update president."));
                exit;
            }
        } else {
            header("Location: " . basename(__FILE__) . "?club_id={$clubId}&err=" . urlencode("Student is not in this club."));
            exit;
        }
    } else {
        header("Location: " . basename(__FILE__) . "?club_id={$clubId}&err=" . urlencode("Invalid student."));
        exit;
    }
}

// ===============================
// Fetch club info (name)
// ===============================
$clubName = "Club Members";
$clubStmt = $conn->prepare("SELECT club_name FROM club WHERE club_id = ? LIMIT 1");
$clubStmt->bind_param("i", $clubId);
$clubStmt->execute();
$clubRes = $clubStmt->get_result();
if ($c = $clubRes->fetch_assoc()) {
    $clubName = $c['club_name'] . " — Members";
}
$clubStmt->close();

// ===============================
// Fetch members from DB
// ===============================
$members = [];
$stmt = $conn->prepare("
    SELECT student_id, student_name, email, major, role, profile_photo
    FROM student
    WHERE club_id = ?
    ORDER BY (role = 'club_president') DESC, student_name ASC
");
$stmt->bind_param("i", $clubId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $sid = (int)$row['student_id'];

    $photo = trim((string)($row['profile_photo'] ?? ''));
    if ($photo === '') {
        $photo = "uploads/students/student_{$sid}.png"; // fallback
    }

    $members[] = [
        "id" => $sid,
        "name" => $row['student_name'],
        "email" => $row['email'],
        "major" => $row['major'] ?? '—',
        "student_id" => (string)$sid,
        "joined" => "—",
        "avatar" => $BASE . ltrim($photo, '/'),
        "role" => ($row['role'] === 'club_president') ? 'President' : 'Member',
    ];
}

$stmt->close();

$totalMembers = count($members);

$okMsg  = isset($_GET['ok']) ? "President updated successfully." : null;
$errMsg = isset($_GET['err']) ? $_GET['err'] : null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Club Members</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751;
  --coral:#ff5e5e;
  --paper:#eef2f7;
  --card:#ffffff;
  --ink:#0e1228;
  --muted:#6b7280;
  --radius:22px;
  --shadow:0 14px 34px rgba(10,23,60,.12);
  --sidebarWidth:260px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{margin:0;background:var(--paper);font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
.content{margin-left:var(--sidebarWidth);padding:40px 50px 60px;}
.header-row{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:20px;}
.page-title{font-size:2rem;font-weight:800;color:var(--ink);}
.total-count{font-size:.95rem;color:var(--muted);}

.notice{
  margin:0 0 18px;
  padding:12px 14px;
  border-radius:14px;
  font-weight:700;
  border:2px solid #e7e9f2;
  background:#fff;
}
.notice.ok{ border-color:#bbf7d0; background:#f0fdf4; color:#14532d; }
.notice.err{ border-color:#fecaca; background:#fff1f2; color:#7f1d1d; }

.search-wrapper{background:#ffffff;padding:14px 16px;border-radius:999px;box-shadow:0 10px 26px rgba(15,23,42,.18);margin-bottom:26px;}
.search-input{width:100%;border:none;outline:none;font-size:.96rem;color:var(--ink);}
.search-input::placeholder{color:#9ca3af;}

.members-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:18px 20px;}
.member-card{
  background:var(--card);
  border-radius:20px;
  box-shadow:0 16px 34px rgba(15,23,42,.16);
  padding:16px 18px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:16px;
  overflow:hidden;
}
.member-left{display:flex;gap:14px;align-items:flex-start;min-width:0;}
.avatar{width:52px;height:52px;border-radius:50%;object-fit:cover;background:#e5e7eb;flex-shrink:0;}
.member-info{display:flex;flex-direction:column;gap:3px;min-width:0;}
.member-name{font-weight:800;font-size:1rem;color:var(--ink);}
.member-email{font-size:.9rem;color:var(--muted);}
.member-meta{font-size:.86rem;color:var(--muted);}

.role-badge{display:inline-flex;align-items:center;justify-content:center;padding:5px 12px;border-radius:999px;font-size:.8rem;font-weight:600;margin-top:6px;}
.role-president{background:var(--coral);color:#ffffff;}
.role-member{background:#e5e7eb;color:#374151;}

.member-right{display:flex;align-items:center;}
.member-right form{margin:0;}

.make-president-btn{
  padding:9px 16px;border-radius:999px;border:none;cursor:pointer;
  font-size:.88rem;font-weight:600;background:#242751;color:#ffffff;
  transition:background .15s ease, transform .1s ease, opacity .1s ease;
  white-space:nowrap;
}
.make-president-btn:hover{background:#181b3b;transform:translateY(-1px);}
.make-president-btn.is-president{background:#ffffff;color:var(--navy);border:2px solid var(--navy);}

@media(max-width:900px){.members-grid{grid-template-columns:1fr;}}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">

  <div class="header-row">
    <div class="page-title"><?= htmlspecialchars($clubName) ?></div>
    <div class="total-count"><?= (int)$totalMembers ?> total</div>
  </div>

  <?php if ($okMsg): ?>
    <div class="notice ok"><?= htmlspecialchars($okMsg) ?></div>
  <?php endif; ?>
  <?php if ($errMsg): ?>
    <div class="notice err"><?= htmlspecialchars($errMsg) ?></div>
  <?php endif; ?>

  <div class="search-wrapper">
    <input type="text" id="searchMembers" class="search-input" placeholder="Search by name...">
  </div>

  <div class="members-grid" id="membersGrid">
    <?php foreach($members as $m): ?>
      <div
        class="member-card"
        data-name="<?= htmlspecialchars(strtolower($m['name'])); ?>"
      >
        <div class="member-left">
          <img
            src="<?= htmlspecialchars($m['avatar']); ?>"
            alt="Avatar"
            class="avatar"
            onerror="this.src='<?= htmlspecialchars($BASE); ?>uploads/students/default.png';"
          >

          <div class="member-info">
            <div class="member-name"><?= htmlspecialchars($m['name']); ?></div>
            <div class="member-email"><?= htmlspecialchars($m['email']); ?></div>
            <div class="member-meta">
              <?= htmlspecialchars($m['major']); ?> · <?= htmlspecialchars($m['student_id']); ?> · Joined <?= htmlspecialchars($m['joined']); ?>
            </div>

            <?php if($m['role'] === 'President'): ?>
              <span class="role-badge role-president">President</span>
            <?php else: ?>
              <span class="role-badge role-member">Member</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="member-right">
          <form method="POST">
            <input type="hidden" name="action" value="make_president">
            <input type="hidden" name="student_id" value="<?= (int)$m['id']; ?>">

            <button
              class="make-president-btn <?= $m['role'] === 'President' ? 'is-president' : '' ?>"
              type="submit"
              <?= $m['role'] === 'President' ? 'disabled style="opacity:.55;cursor:not-allowed"' : '' ?>
              title="<?= $m['role'] === 'President' ? 'Already President' : 'Make President' ?>"
            >
              Make President
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<script>
const searchInput = document.getElementById('searchMembers');
const memberCards = document.querySelectorAll('.member-card');

searchInput.addEventListener('input', () => {
  const q = searchInput.value.toLowerCase().trim();
  memberCards.forEach(card => {
    const name = card.dataset.name || '';
    card.style.display = (!q || name.includes(q)) ? 'flex' : 'none';
  });
});
</script>

</body>
</html>
