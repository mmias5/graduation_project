<?php
// Dummy data – replace later with DB results for "edit" requests
$editRequests = [
    [
        "id"        => 1,
        "club_name" => "Birds",
        "applicant" => "Sarah Ahmad",
        "logo"      => "assets/club1.png"
    ],
    [
        "id"        => 2,
        "club_name" => "Photography Club",
        "applicant" => "Khaled Youssef",
        "logo"      => "assets/club2.png"
    ],
    [
        "id"        => 3,
        "club_name" => "Music Club",
        "applicant" => "Lama Hassan",
        "logo"      => "assets/club3.png"
    ]
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Club Edit Requests</title>
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

  --sidebarWidth:260px; /* used by sidebar.php */
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
  color:var(--muted);
}

.request-text .applicant-name{
  font-weight:600;
}

.divider{
  width:1px;
  height:50px;
  background:#d2d5db;
}

/* View button */
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
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-title">Club Edit Requests</div>

  <!-- Search by applicant name -->
  <div class="search-row">
    <input
      type="text"
      id="searchApplicant"
      class="search-input"
      placeholder="Search by applicant name..."
    >
  </div>

  <?php foreach($editRequests as $r): ?>
    <div class="request-card">
      <div class="request-left">
        <img src="<?= $r['logo'] ?>" alt="Club logo" class="club-logo">

        <div class="request-text">
          <div class="club-name"><?= $r['club_name'] ?></div>
          <div class="applicant-line">
            Applicant:
            <span class="applicant-name"><?= $r['applicant'] ?></span>
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <a href="editform.php?id=<?= $r['id'] ?>" class="view-btn">View</a>
    </div>
  <?php endforeach; ?>

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
