<?php
// admin/sponsors.php
require_once '../config.php';
require_once 'admin_auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);

$message = $_GET['msg'] ?? '';
$messageType = $_GET['type'] ?? 'success';

// نحسب الـ active sponsorships فقط: start_date <= اليوم <= end_date
$sql = "
  SELECT
    s.sponsor_id,
    s.company_name,
    s.email,
    s.phone,
    GROUP_CONCAT(DISTINCT c.club_name SEPARATOR ', ') AS active_clubs
  FROM sponsor s
  LEFT JOIN sponsor_club_support scs
    ON scs.sponsor_id = s.sponsor_id
   AND CURDATE() BETWEEN scs.start_date AND scs.end_date
  LEFT JOIN club c ON c.club_id = scs.club_id
  GROUP BY s.sponsor_id
  ORDER BY s.company_name
";

$result = $conn->query($sql);
$sponsors = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sponsors[] = $row;
    }
}
$totalSponsors = count($sponsors);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Sponsors</title>
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

  --sidebarWidth:240px;
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

.header-row{
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
  gap:16px;
  margin-bottom:20px;
}

.page-title{
  font-size:2rem;
  font-weight:800;
  color:var(--ink);
}

.total-count{
  font-size:.95rem;
  color:var(--muted);
}

/* alerts */
.alert{
  padding:10px 14px;
  border-radius:12px;
  font-size:.9rem;
  margin-bottom:16px;
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

/* Search + buttons */
.top-controls{
  display:flex;
  gap:14px;
  align-items:center;
  margin-bottom:26px;
}

.search-wrapper{
  flex:1;
  background:#ffffff;
  padding:14px 16px;
  border-radius:999px;
  box-shadow:0 10px 26px rgba(15,23,42,.18);
}

.search-input{
  width:100%;
  border:none;
  outline:none;
  font-size:.96rem;
  color:var(--ink);
}

.search-input::placeholder{
  color:#9ca3af;
}

.btn{
  padding:12px 20px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  font-size:.9rem;
  font-weight:700;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  box-shadow:none;
}

.btn-primary{
  background:var(--coral);
  color:#ffffff;
}
.btn-primary:hover{
  background:#ff4949;
}

.btn-ghost{
  background:#ffffff;
  color:var(--navy);
  border:1px solid #e5e7eb;
}
.btn-ghost:hover{
  background:#f3f4f6;
}

/* grid */
.sponsors-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(360px,1fr));
  gap:18px 20px;
}

.sponsor-card{
  background:var(--card);
  border-radius:20px;
  box-shadow:0 16px 34px rgba(15,23,42,.16);
  padding:16px 18px;
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:14px;
}

.sponsor-left{
  display:flex;
  flex-direction:column;
  gap:4px;
}

.sponsor-name{
  font-weight:800;
  font-size:1rem;
  color:var(--ink);
}

.sponsor-email{
  font-size:.9rem;
  color:var(--muted);
}

.sponsor-target{
  font-size:.9rem;
  color:var(--ink);
}

.sponsor-target span{
  font-weight:600;
}

/* right part */
.sponsor-right{
  display:flex;
  flex-direction:column;
  align-items:flex-end;
  gap:8px;
  font-size:.8rem;
  color:var(--muted);
}

.actions{
  display:flex;
  gap:6px;
}

.btn-small{
  padding:7px 12px;
  font-size:.8rem;
}

.btn-danger{
  background:#fff7f7;
  color:#b91c1c;
  border:1px solid #fecaca;
}
.btn-danger:hover{
  background:#fee2e2;
}

@media(max-width:900px){
  .sponsors-grid{
    grid-template-columns:1fr;
  }
  .content{
    margin-left:0;
    padding:24px 18px 40px;
  }
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">

  <div class="header-row">
    <div class="page-title">Sponsors</div>
    <div class="total-count"><?= $totalSponsors ?> total</div>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success'; ?>">
      <?= htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <div class="top-controls">
    <div class="search-wrapper">
      <input
        type="text"
        id="searchSponsors"
        class="search-input"
        placeholder="Search by sponsor name..."
      >
    </div>

    <a href="addsponsor.php" class="btn btn-primary">
      + Add sponsor
    </a>
    <a href="registrationrequests.php" class="btn btn-ghost">
      Registration requests
    </a>
  </div>

  <div class="sponsors-grid" id="sponsorsGrid">
    <?php if (empty($sponsors)): ?>
      <p style="font-size:.9rem;color:var(--muted);">
        No sponsors found yet.
      </p>
    <?php else: ?>
      <?php foreach($sponsors as $s): ?>
        <?php
          $name   = htmlspecialchars($s['company_name']);
          $email  = htmlspecialchars($s['email']);
          $phone  = htmlspecialchars($s['phone']);
          $clubs  = $s['active_clubs']
                    ? htmlspecialchars($s['active_clubs'])
                    : 'Not sponsoring any club or event';
        ?>
        <div
          class="sponsor-card"
          data-name="<?= strtolower($s['company_name']); ?>"
        >
          <div class="sponsor-left">
            <div class="sponsor-name"><?= $name; ?></div>
            <div class="sponsor-email"><?= $email; ?></div>
            <?php if ($phone): ?>
              <div class="sponsor-email"><?= $phone; ?></div>
            <?php endif; ?>
            <div class="sponsor-target">
              <span>Currently sponsoring:</span> <?= $clubs; ?>
            </div>
          </div>

          <div class="sponsor-right">
            <div>ID <?= (int)$s['sponsor_id']; ?></div>
            <div class="actions">
              <a href="editsponsor.php?id=<?= (int)$s['sponsor_id']; ?>" class="btn btn-small btn-ghost">
                Edit
              </a>
              <form method="post" action="deletesponsor.php" onsubmit="return confirm('Delete this sponsor?');">
                <input type="hidden" name="sponsor_id" value="<?= (int)$s['sponsor_id']; ?>">
                <button type="submit" class="btn btn-small btn-danger">
                  Delete
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<script>
const searchInput = document.getElementById('searchSponsors');
const sponsorCards = document.querySelectorAll('.sponsor-card');

searchInput.addEventListener('input', () => {
  const q = searchInput.value.toLowerCase().trim();
  sponsorCards.forEach(card => {
    const name = card.dataset.name;
    card.style.display = !q || name.includes(q) ? 'flex' : 'none';
  });
});
</script>

</body>
</html>
