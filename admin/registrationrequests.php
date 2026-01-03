<?php
require_once '../config.php';
require_once 'admin_auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);

$message = '';
$messageType = ''; // success / error

// ===== mark as processed =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
    $adminId   = (int)$_SESSION['admin_id'];

    $stmt = $conn->prepare("
        UPDATE sponsor_request
        SET status = 'processed',
            reviewed_at = NOW(),
            review_admin_id = ?
        WHERE request_id = ? AND LOWER(status) = 'pending'
    ");
    if ($stmt) {
        $stmt->bind_param("ii", $adminId, $requestId);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = 'Request marked as processed. It will no longer appear in this list.';
            $messageType = 'success';
        } else {
            $message = 'Request not found or already processed.';
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = 'Database error while updating request.';
        $messageType = 'error';
    }

    header("Location: registrationrequests.php?msg=" . urlencode($message) . "&type=" . urlencode($messageType));
    exit;
}

// ===== Read message after redirect =====
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'success';
}

// ===== Fetch pending requests  =====
$sql = "
  SELECT request_id, company_name, email, phone, description, website, submitted_at
  FROM sponsor_request
  WHERE LOWER(status) = 'pending'
  ORDER BY submitted_at DESC
";
$result = $conn->query($sql);

$pendingSponsors = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pendingSponsors[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>UniHive Admin — Sponsor Requests</title>
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --sidebarWidth:240px;
      --navy:#242751;
      --royal:#4871db;
      --coral:#ff5e5e;
      --gold:#e5b758;
      --paper:#eef2f7;
      --card:#ffffff;
      --ink:#0e1228;
      --muted:#6b7280;
      --shadow:0 18px 38px rgba(12,22,60,.18);
      --radius:22px;
    }

    *{box-sizing:border-box;margin:0;padding:0}
    body{
      font-family:"Raleway",system-ui,-apple-system,Segoe UI,sans-serif;
      background:radial-gradient(circle at top,left,#242751 0,#242751 8%,#2b305d 18%,#eef2f7 55%);
      color:var(--ink);
      min-height:100vh;
    }

    .page-wrapper{
      margin-left:var(--sidebarWidth);
      padding:28px 32px 40px;
      min-height:100vh;
      background:linear-gradient(180deg,rgba(255,255,255,.12),rgba(255,255,255,.5)),var(--paper);
      display:flex;
      flex-direction:column;
      gap:24px;
    }

    .page-header{
      display:flex;
      align-items:flex-end;
      justify-content:space-between;
      gap:16px;
    }

    .page-title{
      font-size:1.7rem;
      font-weight:800;
      letter-spacing:.02em;
      color:var(--navy);
    }

    .page-subtitle{
      font-size:.95rem;
      color:var(--muted);
      max-width:520px;
    }

    .pill-counter{
      padding:6px 14px;
      border-radius:999px;
      background:rgba(255,92,92,.1);
      color:var(--coral);
      font-size:.85rem;
      font-weight:600;
    }

    .alert{
      padding:10px 14px;
      border-radius:12px;
      font-size:.9rem;
    }
    .alert-success{
      background:#ecfdf3;
      color:#166534;
      border:1px solid #bbf7d0;
    }
    .alert-error{
      background:#fef2f2;
      color:#b91c1c;
      border:1px solid #fecaca;
    }

    .requests-list{
      display:flex;
      flex-direction:column;
      gap:18px;
    }

    .request-card{
      background:var(--card);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      padding:18px 20px 16px;
      display:flex;
      flex-direction:column;
      gap:10px;
      position:relative;
      transition:transform .22s ease, box-shadow .22s ease;
    }

    .request-card:hover{
      transform:translateY(-2px);
      box-shadow:0 24px 56px rgba(15,23,42,.22);
    }

    .card-top{
      display:flex;
      justify-content:space-between;
      gap:16px;
      flex-wrap:wrap;
      align-items:flex-start;
    }

    .sponsor-main{
      display:flex;
      flex-direction:column;
      gap:4px;
    }

    .sponsor-name{
      font-weight:700;
      font-size:1.05rem;
      color:var(--navy);
    }

    .sponsor-meta{
      font-size:.9rem;
      color:var(--muted);
    }

    .sponsor-meta span{
      display:inline-flex;
      align-items:center;
      gap:6px;
      margin-right:14px;
    }

    .meta-label{
      font-size:.8rem;
      font-weight:600;
      text-transform:uppercase;
      letter-spacing:.05em;
      color:var(--muted);
    }

    .submitted-tag{
      font-size:.82rem;
      color:var(--muted);
    }

    .card-bottom{
      display:flex;
      justify-content:space-between;
      align-items:flex-end;
      gap:14px;
      margin-top:6px;
      flex-wrap:wrap;
    }

    .brand-intro{
      font-size:.9rem;
      color:var(--ink);
      line-height:1.4;
      max-width:600px;
    }

    .mark-done-btn{
      border:none;
      outline:none;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:8px 16px;
      border-radius:999px;
      font-size:.85rem;
      font-weight:600;
      letter-spacing:.03em;
      text-transform:uppercase;
      background:linear-gradient(135deg,#22c55e,#4ade80);
      color:#f9fafb;
      box-shadow:0 10px 26px rgba(22,163,74,.35);
      transition:transform .18s ease, box-shadow .18s ease;
    }

    .mark-done-btn span.check-icon{
      width:20px;
      height:20px;
      border-radius:999px;
      border:2px solid rgba(248,250,252,.8);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:.9rem;
      line-height:1;
      background:rgba(15,118,110,.15);
    }

    .mark-done-btn:hover{
      transform:translateY(-1px);
      box-shadow:0 16px 40px rgba(22,163,74,.45);
    }

    .empty-state{
      margin-top:12px;
      background:rgba(255,255,255,.86);
      border-radius:var(--radius);
      padding:26px 22px;
      text-align:center;
      box-shadow:var(--shadow);
      color:var(--muted);
      font-size:.95rem;
    }

    @media(max-width:900px){
      .page-wrapper{
        margin-left:0;
        padding:24px 18px 40px;
      }
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="page-wrapper">
  <header class="page-header">
    <div>
      <h1 class="page-title">Sponsor Requests</h1>
      <p class="page-subtitle">
        Review new sponsor registration forms submitted from the UniHive sponsor page.
        Mark each request as processed after you create their account.
      </p>
    </div>
    <div class="pill-counter">
      Pending: <?= count($pendingSponsors); ?>
    </div>
  </header>

  <?php if (!empty($message)): ?>
    <div class="alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success'; ?>">
      <?= htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <?php if (count($pendingSponsors) === 0): ?>
    <div class="empty-state">
      ✅ All sponsor requests have been reviewed. New requests will appear here automatically.
    </div>
  <?php else: ?>
    <section class="requests-list">
      <?php foreach ($pendingSponsors as $req): ?>
        <article class="request-card">
          <div class="card-top">
            <div class="sponsor-main">
              <div class="sponsor-name"><?= htmlspecialchars($req["company_name"]); ?></div>
              <div class="sponsor-meta">
                <span><strong>Phone:</strong> <?= htmlspecialchars($req["phone"]); ?></span>
                <span><strong>Email:</strong> <?= htmlspecialchars($req["email"]); ?></span>
                <span><strong>Website:</strong> <?= htmlspecialchars($req["website"]); ?></span>
              </div>
            </div>
            <div class="submitted-tag">
              <span class="meta-label">Submitted</span><br>
              <?= htmlspecialchars($req["submitted_at"]); ?>
            </div>
          </div>

          <div class="card-bottom">
            <p class="brand-intro">
              <?= nl2br(htmlspecialchars($req["description"])); ?>
            </p>

            <form method="post" action="registrationrequests.php">
              <input type="hidden" name="request_id" value="<?= (int)$req['request_id']; ?>">
              <button class="mark-done-btn" type="submit">
                <span class="check-icon">✓</span>
                Mark as processed
              </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</div>

</body>
</html>
