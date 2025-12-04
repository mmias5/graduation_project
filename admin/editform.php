<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

$adminId   = (int)$_SESSION['admin_id'];
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($requestId <= 0) {
    header('Location: clubeditreq.php');
    exit;
}

// جلب طلب التعديل من DB
$sql  = "SELECT * FROM club_edit_request WHERE request_id = $requestId";
$res  = mysqli_query($conn, $sql);
$row  = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res) : null;

if (!$row) {
    header('Location: clubeditreq.php');
    exit;
}

$alreadyReviewed = !is_null($row['reviewed_at']);

// نكوّن array بنفس keys الواجهة القديمة
$editRequest = [
    "club_name"   => $row['new_club_name'],
    "category"    => $row['new_category'],
    "email"       => $row['new_contact_email'],
    "sponsor"     => "",                    // ما في بالسكنشوت عمود للسـبونسر، نخليه فاضي
    "description" => $row['new_description'],
    "logo"        => $row['new_logo'],
    "cover"       => $row['new_logo'],      // لسا ما عنا new_cover، فبنستعمل نفس اللوغو مؤقتاً
    "instagram"   => $row['instagram'],
    "facebook"    => $row['facebook'],
    "linkedin"    => $row['linkedin']
];

$errorMsg = "";

// معالجة Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyReviewed) {

    $clubId = (int)$row['club_id'];

    if (isset($_POST['approve'])) {

        // قيم جاهزة للتحديث
        $clubName   = mysqli_real_escape_string($conn, $editRequest['club_name']);
        $category   = mysqli_real_escape_string($conn, $editRequest['category']);
        $email      = mysqli_real_escape_string($conn, $editRequest['email']);
        $desc       = mysqli_real_escape_string($conn, $editRequest['description']);
        $mainLink   = mysqli_real_escape_string($conn, $row['new_social_media_link']);
        $insta      = mysqli_real_escape_string($conn, $editRequest['instagram']);
        $fb         = mysqli_real_escape_string($conn, $editRequest['facebook']);
        $ln         = mysqli_real_escape_string($conn, $editRequest['linkedin']);
        $logo       = mysqli_real_escape_string($conn, $editRequest['logo']);

        // تحديث جدول club
        $updateClubSql = "
            UPDATE club
            SET club_name        = '$clubName',
                description      = '$desc',
                category         = '$category',
                contact_email    = '$email',
                social_media_link= '$mainLink',
                instagram_url    = '$insta',
                facebook_url     = '$fb',
                linkedin_url     = '$ln',
                logo             = '$logo'
            WHERE club_id = $clubId
        ";

        mysqli_begin_transaction($conn);

        if (mysqli_query($conn, $updateClubSql)) {

            $reviewedAt = date('Y-m-d H:i:s');
            $updateReqSql = "
                UPDATE club_edit_request
                SET reviewed_at = '$reviewedAt',
                    review_admin_id = $adminId
                WHERE request_id = $requestId
            ";

            if (mysqli_query($conn, $updateReqSql)) {
                mysqli_commit($conn);
                header('Location: clubeditreq.php');
                exit;
            } else {
                mysqli_rollback($conn);
                $errorMsg = "Error updating edit request.";
            }

        } else {
            mysqli_rollback($conn);
            $errorMsg = "Error updating club.";
        }

    } elseif (isset($_POST['reject'])) {

        $reviewedAt = date('Y-m-d H:i:s');
        $updateReqSql = "
            UPDATE club_edit_request
            SET reviewed_at = '$reviewedAt',
                review_admin_id = $adminId
            WHERE request_id = $requestId
        ";

        if (mysqli_query($conn, $updateReqSql)) {
            header('Location: clubeditreq.php');
            exit;
        } else {
            $errorMsg = "Error rejecting edit request.";
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

/* Main box */
.form-shell{
  background:var(--card);
  padding:32px 32px 36px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
}

/* Grid for top fields */
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

/* Description */
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

/* Section titles like IMAGES / SOCIAL LINKS */
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

/* Images grid */
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

/* Social links */
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

/* Buttons */
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

.action-btn.approve{
  background:var(--navy);
}

.action-btn.reject{
  background:var(--coral);
}

.action-btn[disabled]{
  opacity:0.6;
  cursor:not-allowed;
}

.error{
  margin-top:18px;
  color:var(--coral);
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
        <div class="field-box"><?= $editRequest['club_name']; ?></div>
      </div>

      <div>
        <div class="field-label">Category</div>
        <div class="field-box"><?= $editRequest['category']; ?></div>
      </div>

      <div>
        <div class="field-label">Contact email</div>
        <div class="field-box"><?= $editRequest['email']; ?></div>
      </div>

      <div>
        <div class="field-label">Sponsor name</div>
        <div class="field-box"><?= $editRequest['sponsor']; ?></div>
      </div>
    </div>

    <!-- Description -->
    <div class="description-block">
      <div class="field-label">About the club</div>
      <div class="description-area"><?= $editRequest['description']; ?></div>
      <div class="helper-text">Short and clear. Appears on the public club page.</div>
    </div>

    <!-- Images section -->
    <div class="section-title">IMAGES</div>
    <div class="section-underline"></div>

    <div class="images-grid">
      <div class="image-card">
        <div class="field-label">Logo</div>
        <img src="<?= $editRequest['logo']; ?>" alt="Club logo" class="image-preview">
        <div class="helper-text">PNG/JPG. Square ~512×512 recommended.</div>
      </div>

      <div class="image-card">
        <div class="field-label">Cover</div>
        <img src="<?= $editRequest['cover']; ?>" alt="Club cover" class="image-preview">
        <div class="helper-text">Wide ~1200×600 works well.</div>
      </div>
    </div>

    <!-- Social links section -->
    <div class="section-title">SOCIAL LINKS</div>
    <div class="section-underline"></div>

    <div class="social-grid">
      <div>
        <div class="field-label">Instagram</div>
        <div class="social-input"><?= $editRequest['instagram']; ?></div>
      </div>

      <div>
        <div class="field-label">Facebook</div>
        <div class="social-input"><?= $editRequest['facebook']; ?></div>
      </div>

      <div class="full-width">
        <div class="field-label">LinkedIn</div>
        <div class="social-input"><?= $editRequest['linkedin']; ?></div>
      </div>
    </div>

    <?php if (!empty($errorMsg)): ?>
      <div class="error"><?= $errorMsg; ?></div>
    <?php endif; ?>

  </div>

  <!-- Approve / Reject buttons -->
  <form method="post" class="btn-row">
    <button
      type="submit"
      name="approve"
      class="action-btn approve"
      <?= $alreadyReviewed ? 'disabled' : '' ?>
    >
      Approve
    </button>

    <button
      type="submit"
      name="reject"
      class="action-btn reject"
      <?= $alreadyReviewed ? 'disabled' : '' ?>
    >
      Reject
    </button>
  </form>

</div>

</body>
</html>
