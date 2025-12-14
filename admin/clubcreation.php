<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php'; // اتصال DB

// اجلب كل الطلبات اللي لسا ما انعمل إلها review
$sql = "
    SELECT request_id, club_name, applicant_name, logo, description
    FROM club_creation_request
    WHERE reviewed_at IS NULL
    ORDER BY submitted_at DESC
";
$result = mysqli_query($conn, $sql);

$requests = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Club Creation Requests</title>
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

  /* used by sidebar.php */
  --sidebarWidth:260px;
}

*{box-sizing:border-box;margin:0;padding:0}

body{
  margin:0;
  background:var(--paper);
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
}

/* ===== Main content (sidebar is in sidebar.php) ===== */
.content{
  margin-left:var(--sidebarWidth);
  padding:40px 50px;
}

.page-title{
  font-size:2rem;
  font-weight:800;
  margin-bottom:20px;
  color:var(--ink);
}

/* ===== Search row ===== */
.search-row{
  margin-bottom:24px;
}

.search-input{
  width:320px;
  max-width:100%;
  padding:11px 14px;
  border-radius:999px;
  border:none;
  outline:none;
  font-size:.95rem;
  background:#ffffff;
  box-shadow:0 8px 22px rgba(15,23,42,.12);
  color:var(--ink);
}

.search-input::placeholder{
  color:#9ca3af;
}

/* ===== Request cards ===== */
.request-card{
  display:flex;
  align-items:center;
  justify-content:space-between;
  background:var(--card);
  padding:18px 26px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  margin-bottom:18px;
}

.request-left{
  display:flex;
  align-items:center;
  gap:20px;
}

.club-logo{
  width:60px;
  height:60px;
  border-radius:18px;
  object-fit:cover;
  background:#f3f4f6;
}

.request-text{
  max-width:420px;
  color:var(--ink);
  line-height:1.4;
}

.request-text .club-name{
  font-weight:700;
  margin-bottom:4px;
}

.request-text .applicant-line{
  font-size:.94rem;
  margin-bottom:2px;
}

.request-text .applicant-name{
  font-weight:600;
}

.request-text .desc{
  font-size:.94rem;
  color:var(--muted);
}

.divider{
  width:1px;
  height:50px;
  background:#d2d5db;
}

/* Bigger, more solid button */
.view-btn{
  background:var(--navy);
  color:#fff;
  padding:13px 34px;
  min-width:110px;
  text-align:center;
  border-radius:999px;
  font-weight:600;
  font-size:.98rem;
  text-decoration:none;
  border:none;
  cursor:pointer;
  transition:background .18s ease, transform .12s ease;
}

.view-btn:hover{
  background:#1b1e42;
  transform:translateY(-1px);
}

.empty-msg{
  margin-top:10px;
  color:var(--muted);
  font-size:.96rem;
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-title">Club Creation Requests</div>

  <!-- Search by applicant name -->
  <div class="search-row">
    <input
      type="text"
      id="searchApplicant"
      class="search-input"
      placeholder="Search by applicant name..."
    >
  </div>

  <?php if (empty($requests)): ?>
    <div class="empty-msg">No pending club creation requests right now.</div>
  <?php else: ?>
    <?php foreach($requests as $r): ?>
      <div class="request-card">
        <div class="request-left">
<?php
$logo = trim((string)($r['logo'] ?? ''));
if ($logo === '') {
  $logoSrc = 'assets/club_placeholder.png';
} elseif (strpos($logo, 'uploads/') === 0) {
  $logoSrc = '/project/graduation_project/' . ltrim($logo, '/');
} else {
  $logoSrc = $logo; // لو كان assets/... أو رابط كامل
}
?>
<img src="<?= htmlspecialchars($logoSrc) ?>" alt="Club logo" class="club-logo">

          <div class="request-text">
            <div class="club-name"><?= htmlspecialchars($r['club_name']) ?></div>
            <div class="applicant-line">
              Applicant:
              <span class="applicant-name"><?= htmlspecialchars($r['applicant_name']) ?></span>
            </div>
            <div class="desc">
              <?= htmlspecialchars(substr($r['description'], 0, 80)) ?>…
            </div>
          </div>
        </div>

        <div class="divider"></div>

        <a href="creationform.php?id=<?= (int)$r['request_id'] ?>" class="view-btn">View</a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<script>
// Simple client-side search by applicant name
const searchInput = document.getElementById('searchApplicant');
const cards = document.querySelectorAll('.request-card');

searchInput.addEventListener('input', function () {
  const query = this.value.toLowerCase().trim();

  cards.forEach(card => {
    const applicantEl = card.querySelector('.applicant-name');
    const applicantName = applicantEl ? applicantEl.textContent.toLowerCase() : '';

    if (!query || applicantName.includes(query)) {
      card.style.display = 'flex';
    } else {
      card.style.display = 'none';
    }
  });
});
</script>

</body>
</html>
