<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
// ================= Dummy data (replace later with SELECT from database) ================
$pendingSponsors = [
  [
    "name"        => "Bright Future Foundation",
    "phone"       => "+962 7X 555 1234",
    "email"       => "partnerships@brightfuture.org",
    "website"       => "company website",
    "brand_intro" => "Non-profit focused on student leadership programs and social impact events.",
    "submitted_at"=> "2025-11-20 10:32"
  ],
  [
    "name"        => "TechNova Solutions",
    "phone"       => "+962 7X 987 4455",
    "email"       => "hello@technova.com",
    "website"       => "company website",
    "brand_intro" => "Technology company interested in sponsoring hackathons, coding clubs, and innovation challenges.",
    "submitted_at"=> "2025-11-21 15:10"
  ],
  [
    "name"        => "GreenLeaf Café",
    "phone"       => "+962 7X 222 7788",
    "email"       => "contact@greenleafcafe.com",
    "website"       => "company website",
    "brand_intro" => "Local café that wants to support art, music, and book clubs with catering and vouchers.",
    "submitted_at"=> "2025-11-22 18:47"
  ],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>UniHive Admin — Sponsor Requests</title>
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --sidebarWidth:240px; /* remove if already defined globally */
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

    /* ========== Main layout (with sidebar) ========== */
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

    /* ========== Cards list ========== */
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
      transition:opacity .22s ease, transform .22s ease, box-shadow .22s ease;
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
    }

    .submitted-tag{
      font-size:.82rem;
      color:var(--muted);
    }

    /* ======= Tick button ======= */
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
      transition:transform .18s ease, box-shadow .18s ease, opacity .18s ease;
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

    .mark-done-btn:active{
      transform:translateY(0);
      box-shadow:0 6px 16px rgba(22,163,74,.35);
    }

    /* fade out when marked */
    .request-card.card-hidden{
      opacity:0;
      transform:translateY(4px);
      box-shadow:none;
    }

    /* Empty state */
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

    .pill-counter{
      padding:6px 14px;
      border-radius:999px;
      background:rgba(255,92,92,.1);
      color:var(--coral);
      font-size:.85rem;
      font-weight:600;
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
        Mark each request as <strong>processed</strong> once you create their account.
      </p>
    </div>
    <div class="pill-counter">
      Pending: <span id="pending-count"><?php echo count($pendingSponsors); ?></span>
    </div>
  </header>

  <?php if (count($pendingSponsors) === 0): ?>
    <div class="empty-state" id="emptyState">
      ✅ All sponsor requests have been reviewed. New requests will appear here automatically.
    </div>
  <?php else: ?>
    <section class="requests-list" id="requestsList">
      <?php foreach ($pendingSponsors as $req): ?>
        <article class="request-card">
          <div class="card-top">
            <div class="sponsor-main">
              <div class="sponsor-name"><?php echo htmlspecialchars($req["name"]); ?></div>
              <div class="sponsor-meta">
                <span><strong>Phone:</strong> <?php echo htmlspecialchars($req["phone"]); ?></span>
                <span><strong>Email:</strong> <?php echo htmlspecialchars($req["email"]); ?></span>
                <span><strong>Website:</strong> <?php echo htmlspecialchars($req["website"]); ?></span>
              </div>
            </div>
            <div class="submitted-tag">
              <span class="meta-label">Submitted</span><br>
              <?php echo htmlspecialchars($req["submitted_at"]); ?>
            </div>
          </div>

          <div class="card-bottom">
            <p class="brand-intro">
              <?php echo htmlspecialchars($req["brand_intro"]); ?>
            </p>

            <button class="mark-done-btn" type="button">
              <span class="check-icon">✓</span>
              Mark as processed
            </button>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <div class="empty-state" id="emptyState" style="display:none;">
      ✅ All sponsor requests have been reviewed. New requests will appear here automatically.
    </div>
  <?php endif; ?>
</div>

<script>
// Handle "Mark as processed" tick – card disappears
document.querySelectorAll('.mark-done-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    const card   = this.closest('.request-card');
    const list   = document.getElementById('requestsList');
    const countEl= document.getElementById('pending-count');
    const empty  = document.getElementById('emptyState');

    if(!card) return;

    card.classList.add('card-hidden');

    setTimeout(function(){
      card.remove();
      // update counter
      if(countEl){
        const current = parseInt(countEl.textContent || "0",10);
        const nextVal = Math.max(current - 1, 0);
        countEl.textContent = nextVal;
        if(nextVal === 0 && empty){
          empty.style.display = 'block';
        }
      }
    },220);
  });
});
</script>

</body>
</html>
