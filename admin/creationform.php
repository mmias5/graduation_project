<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

/* ✅ خلي mysqli يرمي Exceptions عشان try/catch يشتغل صح */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$adminId   = (int)$_SESSION['admin_id'];
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($requestId <= 0) {
    header('Location: clubcreation.php');
    exit;
}

/* =========================
   Fetch request from DB
========================= */
$stmt = $conn->prepare("SELECT * FROM club_creation_request WHERE request_id = ? LIMIT 1");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$result  = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    header('Location: clubcreation.php');
    exit;
}

$alreadyReviewed = !is_null($request['reviewed_at']);
$errorMsg = "";

/* =========================
   Approve / Reject
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyReviewed) {

    /* ========= APPROVE ========= */
    if (isset($_POST['approve'])) {

        $conn->begin_transaction();

        try {
            // Request data
            $clubName        = $request['club_name'];
            $description     = $request['description'];
            $category        = $request['category'];

            $socialMain      = $request['social_links'];
            $facebook        = $request['facebook_url'];
            $instagram       = $request['instagram_url'];
            $linkedin        = $request['linkedin_url'];

            $logo            = $request['logo'];
            $email           = $request['applicant_email'];

            $applicantId     = (int)$request['applicant_student_id']; // ✅ مهم

            $creationDate    = date('Y-m-d H:i:s');
            $status          = 'active';
            $memberCount     = 1;  // ✅ بما إنه صار عنده نادي، خليه أول عضو
            $points          = 0;

            /* 1) Insert club */
            $stmt1 = $conn->prepare("
                INSERT INTO club
                    (club_name, description, category,
                     social_media_link, facebook_url, instagram_url, linkedin_url,
                     logo, creation_date, status, contact_email, member_count, points)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt1->bind_param(
                "sssssssssssii",
                $clubName,
                $description,
                $category,
                $socialMain,
                $facebook,
                $instagram,
                $linkedin,
                $logo,
                $creationDate,
                $status,
                $email,
                $memberCount,
                $points
            );
            $stmt1->execute();

            // ✅ club_id الجديد
            $newClubId = (int)$conn->insert_id;
            $stmt1->close();

            if ($newClubId <= 0) {
                throw new Exception("Failed to create club.");
            }

            /* 2) Update applicant role -> club_president + assign club_id */
            if ($applicantId > 0) {
                $stmtU = $conn->prepare("
                    UPDATE student
                    SET role = 'club_president',
                        club_id = ?
                    WHERE student_id = ?
                    LIMIT 1
                ");
                $stmtU->bind_param("ii", $newClubId, $applicantId);
                $stmtU->execute();
                $stmtU->close();
            } else {
                // إذا ما في applicant_student_id بالطلب (غير متوقع حسب الـ DB تبعك)
                throw new Exception("Applicant student id not found in request.");
            }

            /* 3) Mark request reviewed */
            $reviewedAt = date('Y-m-d H:i:s');
            $stmt2 = $conn->prepare("
                UPDATE club_creation_request
                SET reviewed_at = ?, review_admin_id = ?
                WHERE request_id = ?
                LIMIT 1
            ");
            $stmt2->bind_param("sii", $reviewedAt, $adminId, $requestId);
            $stmt2->execute();
            $stmt2->close();

            $conn->commit();

            header("Location: clubcreation.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errorMsg = "Error while approving the request: " . $e->getMessage();
        }

    /* ========= REJECT ========= */
    } elseif (isset($_POST['reject'])) {

        try {
            $reviewedAt = date('Y-m-d H:i:s');
            $stmt3 = $conn->prepare("
                UPDATE club_creation_request
                SET reviewed_at = ?, review_admin_id = ?
                WHERE request_id = ?
                LIMIT 1
            ");
            $stmt3->bind_param("sii", $reviewedAt, $adminId, $requestId);
            $stmt3->execute();
            $stmt3->close();

            header("Location: clubcreation.php");
            exit;

        } catch (Exception $e) {
            $errorMsg = "Error while rejecting the request: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Review Club Request</title>
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
body{
  margin:0;
  background:var(--paper);
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
}
.content{
  margin-left:var(--sidebarWidth);
  padding:40px 50px 60px;
}
.page-title{
  font-size:2rem;
  font-weight:800;
  margin-bottom:25px;
  color:var(--ink);
}
.form-box{
  background:var(--card);
  padding:32px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
}
.logo-img{
  width:120px;
  height:120px;
  border-radius:18px;
  object-fit:cover;
  background:#f3f4f6;
  margin-bottom:24px;
}
.field-label{
  font-weight:700;
  margin-top:10px;
  margin-bottom:6px;
  color:var(--ink);
}
.field-value{
  background:#f8f9fc;
  padding:14px 18px;
  border-radius:14px;
  margin-bottom:8px;
  color:var(--ink);
}
.social-pill{
  display:flex;
  align-items:center;
  gap:12px;
  padding:14px 16px;
  background:#f8f9fc;
  border-radius:14px;
  color:var(--ink);
  border:1px solid #e5e7eb;
  margin-top:10px;
}
.social-pill img{ width:26px; height:26px; }
.btn-row{
  margin-top:26px;
  display:flex;
  gap:16px;
}
.action-btn{
  padding:14px 34px;
  border-radius:999px;
  font-weight:700;
  color:#fff;
  text-decoration:none;
  border:none;
  cursor:pointer;
}
.action-btn.approve{ background:var(--navy); }
.action-btn.reject{ background:var(--coral); }
.action-btn[disabled]{ opacity:0.6; cursor:not-allowed; }
.error{ margin-top:18px; color:var(--coral); font-size:.95rem; }
.info{ margin-top:12px; color:var(--muted); font-size:.95rem; }
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-title">Review Club Request</div>

  <div class="form-box">
    <img src="<?= htmlspecialchars($request['logo']) ?>" alt="Club logo" class="logo-img">

    <div class="field-label">Club Name</div>
    <div class="field-value"><?= htmlspecialchars($request['club_name']) ?></div>

    <div class="field-label">Applicant Name</div>
    <div class="field-value"><?= htmlspecialchars($request['applicant_name']) ?></div>

    <div class="field-label">Category</div>
    <div class="field-value"><?= htmlspecialchars($request['category']) ?></div>

    <div class="field-label">Description</div>
    <div class="field-value"><?= nl2br(htmlspecialchars($request['description'])) ?></div>

    <div class="field-label">Contact Email</div>
    <div class="field-value"><?= htmlspecialchars($request['applicant_email']) ?></div>

    <div class="field-label" style="margin-top:20px;">Social Links</div>

    <?php
      $hasSocial =
        !empty($request['facebook_url']) ||
        !empty($request['instagram_url']) ||
        !empty($request['linkedin_url']) ||
        !empty($request['social_links']);
    ?>

    <?php if (!$hasSocial): ?>
      <div class="field-value">No social links provided.</div>
    <?php else: ?>

      <?php if (!empty($request['facebook_url'])): ?>
        <div class="social-pill">
          <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" alt="Facebook">
          <a href="<?= htmlspecialchars($request['facebook_url']) ?>" target="_blank" style="color:var(--ink); text-decoration:none;">
            <?= htmlspecialchars($request['facebook_url']) ?>
          </a>
        </div>
      <?php endif; ?>

      <?php if (!empty($request['instagram_url'])): ?>
        <div class="social-pill">
          <img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" alt="Instagram">
          <a href="<?= htmlspecialchars($request['instagram_url']) ?>" target="_blank" style="color:var(--ink); text-decoration:none;">
            <?= htmlspecialchars($request['instagram_url']) ?>
          </a>
        </div>
      <?php endif; ?>

      <?php if (!empty($request['linkedin_url'])): ?>
        <div class="social-pill">
          <img src="https://cdn-icons-png.flaticon.com/512/174/174857.png" alt="LinkedIn">
          <a href="<?= htmlspecialchars($request['linkedin_url']) ?>" target="_blank" style="color:var(--ink); text-decoration:none;">
            <?= htmlspecialchars($request['linkedin_url']) ?>
          </a>
        </div>
      <?php endif; ?>

      <?php if (!empty($request['social_links'])): ?>
        <div class="social-pill">
          <img src="https://cdn-icons-png.flaticon.com/512/25/25694.png" alt="Website">
          <a href="<?= htmlspecialchars($request['social_links']) ?>" target="_blank" style="color:var(--ink); text-decoration:none;">
            <?= htmlspecialchars($request['social_links']) ?>
          </a>
        </div>
      <?php endif; ?>

    <?php endif; ?>

    <?php if ($alreadyReviewed): ?>
      <div class="info">
        This request was already reviewed on <?= htmlspecialchars($request['reviewed_at']) ?>.
      </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
      <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>
  </div>

  <form method="post" class="btn-row">
    <button type="submit" name="approve" class="action-btn approve" <?= $alreadyReviewed ? 'disabled' : '' ?>>
      Approve
    </button>

    <button type="submit" name="reject" class="action-btn reject" <?= $alreadyReviewed ? 'disabled' : '' ?>>
      Reject
    </button>
  </form>
</div>

</body>
</html>
