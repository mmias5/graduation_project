<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$adminId   = (int)$_SESSION['admin_id'];
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($requestId <= 0) {
    header('Location: clubeditreq.php');
    exit;
}

/* helpers */
function pickValue($newVal, $oldVal){
    if ($newVal === null) return $oldVal;
    if (is_string($newVal) && trim($newVal) === '') return $oldVal;
    return $newVal;
}
function isChanged($newVal, $oldVal){
    $n = trim((string)$newVal);
    $o = trim((string)$oldVal);
    if ($n === '') return false;
    return strcasecmp($n, $o) !== 0;
}

/* =========================
   1) Fetch edit request
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
   2) Fetch current club
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

/* current values */
$origName   = $currentClub['club_name'] ?? '';
$origCat    = $currentClub['category'] ?? '';
$origEmail  = $currentClub['contact_email'] ?? '';
$origDesc   = $currentClub['description'] ?? '';
$origMain   = $currentClub['social_media_link'] ?? '';
$origInsta  = $currentClub['instagram_url'] ?? '';
$origFb     = $currentClub['facebook_url'] ?? '';
$origLi     = $currentClub['linkedin_url'] ?? '';
$origLogo   = $currentClub['logo'] ?? '';

/* requested values (raw) */
$reqName   = $row['new_club_name'] ?? '';
$reqCat    = $row['new_category'] ?? '';
$reqEmail  = $row['new_contact_email'] ?? '';
$reqDesc   = $row['new_description'] ?? '';
$reqMain   = $row['new_social_media_link'] ?? '';
$reqInsta  = $row['instagram'] ?? '';
$reqFb     = $row['facebook'] ?? '';
$reqLi     = $row['linkedin'] ?? '';
$reqLogo   = $row['new_logo'] ?? '';

/* final preview (what will apply) */
$finalName  = pickValue($reqName,  $origName);
$finalCat   = pickValue($reqCat,   $origCat);
$finalEmail = pickValue($reqEmail, $origEmail);
$finalDesc  = pickValue($reqDesc,  $origDesc);
$finalMain  = pickValue($reqMain,  $origMain);
$finalInsta = pickValue($reqInsta, $origInsta);
$finalFb    = pickValue($reqFb,    $origFb);
$finalLi    = pickValue($reqLi,    $origLi);
$finalLogo  = pickValue($reqLogo,  $origLogo);

/* change flags */
$nameChanged  = isChanged($reqName,  $origName);
$catChanged   = isChanged($reqCat,   $origCat);
$emailChanged = isChanged($reqEmail, $origEmail);
$descChanged  = isChanged($reqDesc,  $origDesc);
$mainChanged  = isChanged($reqMain,  $origMain);
$instaChanged = isChanged($reqInsta, $origInsta);
$fbChanged    = isChanged($reqFb,    $origFb);
$liChanged    = isChanged($reqLi,    $origLi);
$logoChanged  = isChanged($reqLogo,  $origLogo);

$anythingChanged = $nameChanged || $catChanged || $emailChanged || $descChanged || $mainChanged || $instaChanged || $fbChanged || $liChanged || $logoChanged;

$placeholderLogo = 'assets/club-placeholder.png';
$origLogoShow = trim((string)$origLogo) !== '' ? $origLogo : $placeholderLogo;
$reqLogoShow  = trim((string)$reqLogo) !== '' ? $reqLogo : $origLogoShow;

/* =========================
   3) Approve / Reject (POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyReviewed) {

    if (isset($_POST['approve'])) {

        $conn->begin_transaction();
        try {
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
                $finalName,
                $finalDesc,
                $finalCat,
                $finalEmail,
                $finalMain,
                $finalInsta,
                $finalFb,
                $finalLi,
                $finalLogo,
                $clubId
            );
            $stmtUp->execute();
            $stmtUp->close();

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
            header('Location: clubeditreq.php?msg=approved');
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

            header('Location: clubeditreq.php?msg=rejected');
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
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Review Club Edit Request</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --sidebarWidth:240px;
      --navy:#242751;
      --coral:#ff5e5e;
      --paper:#eef2f7;
      --card:#ffffff;
      --ink:#0e1228;
      --muted:#6b7280;
      --shadow:0 18px 38px rgba(12,22,60,.16);
      --radius-lg:20px;
      --radius-pill:999px;
    }

    *{box-sizing:border-box;margin:0;padding:0}

    body{
      margin:0;
      font-family:"Raleway",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      background:var(--paper);
      color:var(--ink);
    }

    .page-shell{
      margin-left:var(--sidebarWidth);
      min-height:100vh;
      padding:32px 40px 40px;
    }

    .page-header{
      display:flex;
      align-items:flex-end;
      justify-content:space-between;
      gap:16px;
      margin-bottom:18px;
    }

    .page-title{
      font-size:1.6rem;
      font-weight:800;
      letter-spacing:.02em;
      color:var(--navy);
    }

    .page-subtitle{
      font-size:.97rem;
      color:var(--muted);
      margin-top:4px;
    }

    .badge-pill{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:6px 12px;
      border-radius:999px;
      font-size:.8rem;
      font-weight:700;
      background:rgba(255,94,94,.06);
      color:var(--coral);
      white-space:nowrap;
    }
    .chip-icon{ width:6px; height:6px; border-radius:50%; background:var(--coral); }

    .request-card{
      background:var(--card);
      border-radius:var(--radius-lg);
      box-shadow:var(--shadow);
      padding:18px 20px 16px;
      display:flex;
      flex-direction:column;
      gap:14px;
    }

    .meta-line{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      font-size:.86rem;
      color:var(--muted);
    }
    .meta-line strong{ color:var(--ink); }

    .compare-grid{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:14px;
      font-size:.83rem;
    }

    .compare-column{
      background:#f9fafb;
      border-radius:14px;
      padding:10px 12px;
    }

    .column-title{
      font-size:.8rem;
      font-weight:800;
      text-transform:uppercase;
      letter-spacing:.04em;
      margin-bottom:6px;
      color:var(--muted);
    }

    .field-row{
      padding:5px 6px;
      border-radius:10px;
      margin-bottom:2px;
    }

    .field-label{
      font-size:.78rem;
      font-weight:700;
      color:var(--muted);
      display:block;
      margin-bottom:2px;
    }

    .field-value{
      font-size:.84rem;
      color:var(--ink);
      word-break:break-word;
    }

    .changed-field{
      background:#fff1f2;
      border-left:3px solid var(--coral);
    }

    .img-row{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:12px;
      margin-top:6px;
    }

    .img-box{
      background:#fff;
      border-radius:14px;
      padding:10px;
      border:1px solid rgba(15,23,42,.08);
    }

    .img-box.changed{
      border-color:rgba(255,94,94,.35);
      background:#fff1f2;
    }

    .img-box .mini-title{
      font-size:.78rem;
      font-weight:900;
      color:var(--muted);
      letter-spacing:.08em;
      margin-bottom:8px;
      text-transform:uppercase;
    }

    .img{
      width:100%;
      max-height:180px;
      border-radius:12px;
      object-fit:cover;
      background:#e5e7eb;
    }

    .actions-row{
      display:flex;
      justify-content:flex-end;
      gap:10px;
      flex-wrap:wrap;
      margin-top:6px;
    }

    .btn{
      padding:8px 18px;
      border-radius:var(--radius-pill);
      border:1px solid transparent;
      font-size:.86rem;
      font-weight:700;
      cursor:pointer;
      transition:.18s ease all;
      font-family:inherit;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
    }

    .btn-approve{
      background:var(--coral);
      color:#ffffff;
      box-shadow:0 10px 20px rgba(255,94,94,.35);
    }
    .btn-approve:hover{
      transform:translateY(-1px);
      box-shadow:0 12px 26px rgba(255,94,94,.45);
    }

    .btn-reject{
      background:#ffffff;
      color:#b91c1c;
      border-color:rgba(185,28,28,.2);
    }
    .btn-reject:hover{ background:#fef2f2; }

    .btn[disabled]{ opacity:.6; cursor:not-allowed; }

    .error{
      padding:10px 12px;
      border-radius:14px;
      background:#fff;
      border:1px solid rgba(255,94,94,.25);
      color:#b91c1c;
      font-weight:800;
    }
    .info{
      padding:10px 12px;
      border-radius:14px;
      background:#fff;
      border:1px solid rgba(15,23,42,.08);
      color:var(--muted);
      font-weight:800;
    }

    @media (max-width:900px){
      .page-shell{ margin-left:0; padding:20px 16px 28px; }
      .compare-grid{ grid-template-columns:1fr; }
      .actions-row{ justify-content:flex-start; }
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="page-shell">
  <header class="page-header">
    <div>
      <h1 class="page-title">Review Club Edit Request</h1>
      <p class="page-subtitle">Compare current club details vs requested changes, then approve or reject.</p>
    </div>
    <span class="badge-pill">
      <span class="chip-icon"></span>
      <?php echo $anythingChanged ? 'Changes detected' : 'No changes detected'; ?>
    </span>
  </header>

  <div class="request-card">
    <div class="meta-line">
      <span>Club: <strong><?php echo htmlspecialchars($origName ?: '—'); ?></strong></span>
      <span>Request ID: <strong><?php echo (int)$requestId; ?></strong></span>
      <span>Submitted at: <strong><?php echo htmlspecialchars($row['submitted_at'] ?? '—'); ?></strong></span>
    </div>

    <div class="compare-grid">
      <!-- Current -->
      <div class="compare-column">
        <div class="column-title">Current Club</div>

        <div class="field-row <?php echo $nameChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Club name</span>
          <span class="field-value"><?php echo htmlspecialchars($origName ?: '—'); ?></span>
        </div>

        <div class="field-row <?php echo $catChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Category</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($origCat) === '' ? '—' : $origCat); ?></span>
        </div>

        <div class="field-row <?php echo $emailChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Contact email</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($origEmail) === '' ? '—' : $origEmail); ?></span>
        </div>

        <div class="field-row <?php echo $mainChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Main social link</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($origMain) === '' ? '—' : $origMain); ?></span>
        </div>

        <div class="field-row <?php echo $descChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Description</span>
          <span class="field-value"><?php echo nl2br(htmlspecialchars($origDesc ?: '—')); ?></span>
        </div>

        <div class="field-row <?php echo $logoChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Logo (preview)</span>
          <div class="img-row">
            <div class="img-box">
              <div class="mini-title">Current</div>
              <img src="<?php echo htmlspecialchars($origLogoShow); ?>" class="img" alt="Current logo">
            </div>
            <div class="img-box <?php echo $logoChanged ? 'changed' : ''; ?>">
              <div class="mini-title">Requested</div>
              <img src="<?php echo htmlspecialchars($reqLogoShow); ?>" class="img" alt="Requested logo">
            </div>
          </div>
        </div>

        <div class="field-row <?php echo $instaChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Instagram</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($origInsta) === '' ? '—' : $origInsta); ?></span>
        </div>

        <div class="field-row <?php echo $fbChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Facebook</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($origFb) === '' ? '—' : $origFb); ?></span>
        </div>

        <div class="field-row <?php echo $liChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">LinkedIn</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($origLi) === '' ? '—' : $origLi); ?></span>
        </div>
      </div>

      <!-- Requested -->
      <div class="compare-column">
        <div class="column-title">Requested Changes</div>

        <div class="field-row <?php echo $nameChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Club name</span>
          <span class="field-value"><?php echo htmlspecialchars($finalName ?: '—'); ?></span>
        </div>

        <div class="field-row <?php echo $catChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Category</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($finalCat) === '' ? '—' : $finalCat); ?></span>
        </div>

        <div class="field-row <?php echo $emailChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Contact email</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($finalEmail) === '' ? '—' : $finalEmail); ?></span>
        </div>

        <div class="field-row <?php echo $mainChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Main social link</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($finalMain) === '' ? '—' : $finalMain); ?></span>
        </div>

        <div class="field-row <?php echo $descChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Description</span>
          <span class="field-value"><?php echo nl2br(htmlspecialchars($finalDesc ?: '—')); ?></span>
        </div>

        <div class="field-row <?php echo $logoChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Logo (path)</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($finalLogo) === '' ? '—' : $finalLogo); ?></span>
        </div>

        <div class="field-row <?php echo $instaChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Instagram</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($finalInsta) === '' ? '—' : $finalInsta); ?></span>
        </div>

        <div class="field-row <?php echo $fbChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">Facebook</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($finalFb) === '' ? '—' : $finalFb); ?></span>
        </div>

        <div class="field-row <?php echo $liChanged ? 'changed-field' : ''; ?>">
          <span class="field-label">LinkedIn</span>
          <span class="field-value"><?php echo htmlspecialchars(trim($finalLi) === '' ? '—' : $finalLi); ?></span>
        </div>
      </div>
    </div>

    <?php if ($alreadyReviewed): ?>
      <div class="info">This request was already reviewed on <?php echo htmlspecialchars($row['reviewed_at']); ?>.</div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
      <div class="error"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <form method="post" class="actions-row">
      <button type="submit" name="approve" class="btn btn-approve" <?php echo $alreadyReviewed ? 'disabled' : ''; ?>>
        Approve edit
      </button>

      <button type="submit" name="reject" class="btn btn-reject"
              <?php echo $alreadyReviewed ? 'disabled' : ''; ?>
              onclick="return confirm('Are you sure you want to reject this edit request?');">
        Reject edit
      </button>
    </form>

  </div>
</div>

</body>
</html>
