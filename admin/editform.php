<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

/* ✅ Exceptions + strict */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$adminId   = (int)$_SESSION['admin_id'];
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($requestId <= 0) {
    header('Location: clubeditreq.php');
    exit;
}

/* =========================
   1) Fetch edit request (Prepared)
========================= */
$stmt = $conn->prepare("SELECT * FROM club_edit_request WHERE request_id = ? LIMIT 1");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header('Location: clubeditreq.php');
    exit;
}

$alreadyReviewed = !is_null($row['reviewed_at']);
$errorMsg = "";

/* =========================
   2) Fetch current club (to avoid overwriting with NULL)
========================= */
$clubId = (int)$row['club_id'];

$stmtC = $conn->prepare("SELECT * FROM club WHERE club_id = ? LIMIT 1");
$stmtC->bind_param("i", $clubId);
$stmtC->execute();
$currentClub = $stmtC->get_result()->fetch_assoc();
$stmtC->close();

if (!$currentClub) {
    header('Location: clubeditreq.php');
    exit;
}

/* =========================
   3) Helper: choose new value if not empty, else keep old
========================= */
function pickValue($newVal, $oldVal) {
    // إذا كانت NULL أو فاضية أو spaces => رجّع القديم
    if ($newVal === null) return $oldVal;
    if (is_string($newVal) && trim($newVal) === '') return $oldVal;
    return $newVal;
}

/* =========================
   4) Prepare values for UI (preview)
========================= */
$finalClubName   = pickValue($row['new_club_name'],        $currentClub['club_name']        ?? '');
$finalCategory   = pickValue($row['new_category'],         $currentClub['category']         ?? '');
$finalEmail      = pickValue($row['new_contact_email'],    $currentClub['contact_email']    ?? '');
$finalDesc       = pickValue($row['new_description'],      $currentClub['description']      ?? '');
$finalMainLink   = pickValue($row['new_social_media_link'], $currentClub['social_media_link'] ?? '');
$finalInsta      = pickValue($row['instagram'],            $currentClub['instagram_url']    ?? '');
$finalFacebook   = pickValue($row['facebook'],             $currentClub['facebook_url']     ?? '');
$finalLinkedin   = pickValue($row['linkedin'],             $currentClub['linkedin_url']     ?? '');
$finalLogo       = pickValue($row['new_logo'],             $currentClub['logo']             ?? '');

/* نفس فكرة الواجهة القديمة */
$editRequest = [
    "club_name"   => $finalClubName,
    "category"    => $finalCategory,
    "email"       => $finalEmail,
    "sponsor"     => "", // ما عندك sponsor column هون
    "description" => $finalDesc,
    "logo"        => $finalLogo,
    "cover"       => $finalLogo, // ما في cover column، فخليه logo
    "instagram"   => $finalInsta,
    "facebook"    => $finalFacebook,
    "linkedin"    => $finalLinkedin
];

/* =========================
   5) Approve / Reject
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyReviewed) {

    if (isset($_POST['approve'])) {

        $conn->begin_transaction();

        try {
            /* Update club with final values (Prepared) */
            $stmtUp = $conn->prepare("
                UPDATE club
                SET club_name         = ?,
                    description       = ?,
                    category          = ?,
                    contact_email     = ?,
                    social_media_link = ?,
                    instagram_url     = ?,
                    facebook_url      = ?,
                    linkedin_url      = ?,
                    logo              = ?
                WHERE club_id = ?
                LIMIT 1
            ");
            $stmtUp->bind_param(
                "sssssssssi",
                $finalClubName,
                $finalDesc,
                $finalCategory,
                $finalEmail,
                $finalMainLink,
                $finalInsta,
                $finalFacebook,
                $finalLinkedin,
                $finalLogo,
                $clubId
            );
            $stmtUp->execute();
            $stmtUp->close();

            /* Mark request reviewed + status Approved */
            $reviewedAt = date('Y-m-d H:i:s');
            $status = 'Approved';

            $stmtReq = $conn->prepare("
                UPDATE club_edit_request
                SET reviewed_at = ?,
                    review_admin_id = ?,
                    status = ?
                WHERE request_id = ?
                LIMIT 1
            ");
            $stmtReq->bind_param("sisi", $reviewedAt, $adminId, $status, $requestId);
            $stmtReq->execute();
            $stmtReq->close();

            $conn->commit();

            header('Location: clubeditreq.php');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errorMsg = "Error updating club: " . $e->getMessage();
        }

    } elseif (isset($_POST['reject'])) {

        try {
            $reviewedAt = date('Y-m-d H:i:s');
            $status = 'Rejected';

            $stmtReq = $conn->prepare("
                UPDATE club_edit_request
                SET reviewed_at = ?,
                    review_admin_id = ?,
                    status = ?
                WHERE request_id = ?
                LIMIT 1
            ");
            $stmtReq->bind_param("sisi", $reviewedAt, $adminId, $status, $requestId);
            $stmtReq->execute();
            $stmtReq->close();

            header('Location: clubeditreq.php');
            exit;

        } catch (Exception $e) {
            $errorMsg = "Error rejecting edit request: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Review Club Edit Request</title>
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
.form-shell{
  background:var(--card);
  padding:32px 32px 36px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
}
.top-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  column-gap:32px;
  row-gap:18px;
  margin-bottom:26px;
}
.field-label{
  font-weight:700;
  margin-bottom:6px;
  color:var(--ink);
}
.helper-text{
  margin-top:4px;
  font-size:.83rem;
  color:var(--muted);
}
.field-box{
  background:#f8f9fc;
  border-radius:14px;
  padding:12px 16px;
  color:var(--ink);
  font-size:.95rem;
}
.description-block{
  margin-top:10px;
  margin-bottom:32px;
}
.description-area{
  background:#f8f9fc;
  border-radius:18px;
  padding:14px 16px;
  min-height:110px;
  white-space:pre-wrap;
  line-height:1.5;
}
.section-title{
  font-size:1rem;
  letter-spacing:.12em;
  font-weight:800;
  margin-top:8px;
  margin-bottom:10px;
  color:var(--ink);
}
.section-underline{
  width:120px;
  height:3px;
  border-radius:999px;
  background:#c6cadb;
  margin-bottom:22px;
}
.images-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  column-gap:32px;
  row-gap:22px;
  margin-bottom:28px;
}
.image-card{
  border-radius:18px;
  background:#f8f9fc;
  padding:16px;
  border:1px dashed #d4d7e5;
}
.image-preview{
  width:100%;
  max-height:160px;
  border-radius:14px;
  object-fit:cover;
  background:#e5e7eb;
  margin-bottom:12px;
}
.social-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  column-gap:32px;
  row-gap:18px;
}
.social-grid .full-width{
  grid-column:1 / 3;
}
.social-input{
  background:#f8f9fc;
  border-radius:999px;
  padding:11px 16px;
  font-size:.95rem;
  color:var(--ink);
  border:none;
}
.btn-row{
  margin-top:30px;
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
.error{
  margin-top:18px;
  color:var(--coral);
  font-size:.95rem;
}
.info{
  margin-top:12px;
  color:var(--muted);
  font-size:.95rem;
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-title">Review Club Edit Request</div>

  <div class="form-shell">

    <!-- Top fields -->
    <div class="top-grid">
      <div>
        <div class="field-label">Club name</div>
        <div class="field-box"><?= htmlspecialchars($editRequest['club_name']) ?></div>
      </div>

      <div>
        <div class="field-label">Category</div>
        <div class="field-box"><?= htmlspecialchars($editRequest['category']) ?></div>
      </div>

      <div>
        <div class="field-label">Contact email</div>
        <div class="field-box"><?= htmlspecialchars($editRequest['email']) ?></div>
      </div>

      <div>
        <div class="field-label">Sponsor name</div>
        <div class="field-box"><?= htmlspecialchars($editRequest['sponsor']) ?></div>
      </div>
    </div>

    <!-- Description -->
    <div class="description-block">
      <div class="field-label">About the club</div>
      <div class="description-area"><?= nl2br(htmlspecialchars($editRequest['description'])) ?></div>
      <div class="helper-text">Short and clear. Appears on the public club page.</div>
    </div>

    <!-- Images section -->
    <div class="section-title">IMAGES</div>
    <div class="section-underline"></div>

    <div class="images-grid">
      <div class="image-card">
        <div class="field-label">Logo</div>
        <img src="<?= htmlspecialchars($editRequest['logo']) ?>" alt="Club logo" class="image-preview">
        <div class="helper-text">PNG/JPG. Square ~512×512 recommended.</div>
      </div>

      <div class="image-card">
        <div class="field-label">Cover</div>
        <img src="<?= htmlspecialchars($editRequest['cover']) ?>" alt="Club cover" class="image-preview">
        <div class="helper-text">Wide ~1200×600 works well.</div>
      </div>
    </div>

    <!-- Social links section -->
    <div class="section-title">SOCIAL LINKS</div>
    <div class="section-underline"></div>

    <div class="social-grid">
      <div>
        <div class="field-label">Instagram</div>
        <div class="social-input"><?= htmlspecialchars($editRequest['instagram']) ?></div>
      </div>

      <div>
        <div class="field-label">Facebook</div>
        <div class="social-input"><?= htmlspecialchars($editRequest['facebook']) ?></div>
      </div>

      <div class="full-width">
        <div class="field-label">LinkedIn</div>
        <div class="social-input"><?= htmlspecialchars($editRequest['linkedin']) ?></div>
      </div>
    </div>

    <?php if ($alreadyReviewed): ?>
      <div class="info">This request was already reviewed on <?= htmlspecialchars($row['reviewed_at']) ?>.</div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
      <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

  </div>

  <!-- Approve / Reject buttons -->
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
