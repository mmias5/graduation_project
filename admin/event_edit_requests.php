<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// ===============================
// Dummy Data for Edit Requests UI
// ===============================
$editRequests = [
    [
        "id" => 1,
        "title" => "Tech Hack Night",
        "club_name" => "Computer Science Club",
        "requested_by" => "Ahmad Yousef",
        "created_at" => "2025-01-08 11:20:00",
        "original" => [
            "date" => "2025-01-12",
            "start_time" => "06:00 PM",
            "end_time" => "10:00 PM",
            "location" => "Main Hall / Room 1204",
            "category" => "Technology",
            "sponsor" => "TechCorp",
            "description" => "A night full of coding challenges and teamwork."
        ],
        "proposed" => [
            "date" => "2025-01-13",
            "start_time" => "05:30 PM",
            "end_time" => "10:30 PM",
            "location" => "Innovation Lab / Room 220",
            "category" => "Technology",
            "sponsor" => "TechCorp",
            "description" => "Extended hack night with more teams and a short keynote at the beginning."
        ]
    ],
    [
        "id" => 2,
        "title" => "Art & Creativity Day",
        "club_name" => "Arts Club",
        "requested_by" => "Dana Khalil",
        "created_at" => "2025-01-18 15:05:00",
        "original" => [
            "date" => "2025-02-02",
            "start_time" => "02:00 PM",
            "end_time" => "06:00 PM",
            "location" => "Art Center - Building B",
            "category" => "Art",
            "sponsor" => "",
            "description" => "Students showcase their drawings and photography."
        ],
        "proposed" => [
            "date" => "2025-02-02",
            "start_time" => "03:00 PM",
            "end_time" => "07:00 PM",
            "location" => "Art Center - Building B",
            "category" => "Art & Culture",
            "sponsor" => "Creative Studio",
            "description" => "Updated agenda with live painting corner and a small sponsorship booth."
        ]
    ],
    [
        "id" => 3,
        "title" => "Business Networking Meetup",
        "club_name" => "Business Club",
        "requested_by" => "Lana Saadeh",
        "created_at" => "2025-01-22 10:40:00",
        "original" => [
            "date" => "2025-03-14",
            "start_time" => "11:00 AM",
            "end_time" => "01:00 PM",
            "location" => "Auditorium C",
            "category" => "Business",
            "sponsor" => "Jordan Bank",
            "description" => "Students meet business professionals and listen to a panel discussion."
        ],
        "proposed" => [
            "date" => "2025-03-14",
            "start_time" => "11:00 AM",
            "end_time" => "02:00 PM",
            "location" => "Auditorium C",
            "category" => "Business",
            "sponsor" => "Jordan Bank",
            "description" => "Panel discussion plus an extra networking coffee break at the end."
        ]
    ],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Event Edit Requests (Admin)</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --sidebarWidth:240px; /* تأكدي إنها نفس القيمة العامة عندك */
      --navy:#242751;
      --royal:#4871db;
      --coral:#ff5e5e;
      --gold:#e5b758;
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

    /* ======= Layout with sidebar ======= */
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
      margin-bottom:24px;
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
      font-weight:600;
      background:rgba(255,94,94,.06);
      color:var(--coral);
    }

    .chip-icon{
      width:6px;
      height:6px;
      border-radius:50%;
      background:var(--coral);
    }

    /* Filter/search row */
    .toolbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      margin-bottom:24px;
    }

    .search-input{
      flex:1;
      position:relative;
    }

    .search-input input{
      width:100%;
      padding:10px 14px;
      border-radius:999px;
      border:1px solid rgba(15,23,42,.08);
      background:#f9fafb;
      font-size:.93rem;
      outline:none;
    }

    .search-input input:focus{
      border-color:var(--coral);
      box-shadow:0 0 0 1px rgba(255,94,94,.18);
      background:#ffffff;
    }

    /* ======= Cards layout ======= */
    .requests-grid{
      display:flex;
      flex-direction:column;
      gap:18px;
    }

    .request-card{
      background:var(--card);
      border-radius:var(--radius-lg);
      box-shadow:var(--shadow);
      padding:18px 20px 16px;
      display:flex;
      flex-direction:column;
      gap:14px;
    }

    .request-header{
      display:flex;
      justify-content:space-between;
      gap:10px;
    }

    .request-title{
      font-size:1.05rem;
      font-weight:700;
      color:var(--navy);
    }

    .request-meta-top{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      font-size:.8rem;
      color:var(--muted);
      margin-top:4px;
    }

    .request-meta-top span strong{
      font-weight:700;
      color:var(--ink);
    }

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
      font-weight:700;
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
      font-weight:600;
      color:var(--muted);
      display:block;
      margin-bottom:2px;
    }

    .field-value{
      font-size:.84rem;
      color:var(--ink);
    }

    .changed-field{
      background:#fff1f2;
      border-left:3px solid var(--coral);
    }

    .description-block{
      margin-top:4px;
    }

    .actions-row{
      display:flex;
      justify-content:flex-end;
      gap:10px;
    }

    .btn{
      padding:8px 18px;
      border-radius:var(--radius-pill);
      border:1px solid transparent;
      font-size:.86rem;
      font-weight:600;
      cursor:pointer;
      transition:.18s ease all;
      font-family:inherit;
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

    .btn-reject:hover{
      background:#fef2f2;
    }

    .empty-state{
      text-align:center;
      margin-top:40px;
      color:var(--muted);
      font-size:.95rem;
    }

    @media (max-width:900px){
      .page-shell{
        margin-left:0;
        padding:20px 16px 28px;
      }
      .compare-grid{
        grid-template-columns:1fr;
      }
      .actions-row{
        justify-content:flex-start;
      }
    }
  </style>
</head>
<body>

<?php
  // استدعي نفس السايدبار تبع admin
  include 'sidebar.php';
?>

<div class="page-shell">
  <header class="page-header">
    <div>
      <h1 class="page-title">Event Edit Requests</h1>
      <p class="page-subtitle">
        Review requested edits to existing events and approve or reject the changes.
      </p>
    </div>
    <span class="badge-pill">
      <span class="chip-icon"></span>
      Pending edit requests
    </span>
  </header>

  <div class="toolbar">
    <div class="search-input">
      <input type="text" id="searchBox" placeholder="Search by event title or club name…" onkeyup="filterRequests()">
    </div>
  </div>

  <section class="requests-grid" id="requestsList">
    <?php if (!empty($editRequests)): ?>
      <?php foreach($editRequests as $row): ?>
        <?php
          $searchText = strtolower(
            $row['title'].' '.$row['club_name'].' '.$row['requested_by']
          );
        ?>
        <article class="request-card" data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES); ?>">
          <div class="request-header">
            <div>
              <div class="request-title"><?php echo htmlspecialchars($row['title']); ?></div>
              <div class="request-meta-top">
                <span>Club: <strong><?php echo htmlspecialchars($row['club_name']); ?></strong></span>
                <span>Requested by: <?php echo htmlspecialchars($row['requested_by']); ?></span>
                <span>Requested on: <?php echo htmlspecialchars(date('d M Y', strtotime($row['created_at']))); ?></span>
              </div>
            </div>
          </div>

          <div class="compare-grid">
            <!-- Current Event -->
            <div class="compare-column">
              <div class="column-title">Current Event</div>

              <?php
                $o = $row['original'];
                $p = $row['proposed'];
              ?>

              <div class="field-row <?php echo ($o['date'] !== $p['date']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Date</span>
                <span class="field-value"><?php echo htmlspecialchars(date('d M Y', strtotime($o['date']))); ?></span>
              </div>

              <div class="field-row <?php echo ($o['start_time'] !== $p['start_time'] || $o['end_time'] !== $p['end_time']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Time</span>
                <span class="field-value">
                  <?php echo htmlspecialchars($o['start_time'].' – '.$o['end_time']); ?>
                </span>
              </div>

              <div class="field-row <?php echo ($o['location'] !== $p['location']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Location</span>
                <span class="field-value"><?php echo htmlspecialchars($o['location']); ?></span>
              </div>

              <div class="field-row <?php echo ($o['category'] !== $p['category']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Category</span>
                <span class="field-value"><?php echo htmlspecialchars($o['category']); ?></span>
              </div>

              <div class="field-row <?php echo ($o['sponsor'] !== $p['sponsor']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Sponsor</span>
                <span class="field-value">
                  <?php echo $o['sponsor'] ? htmlspecialchars($o['sponsor']) : '—'; ?>
                </span>
              </div>

              <div class="field-row description-block <?php echo ($o['description'] !== $p['description']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Description</span>
                <span class="field-value"><?php echo nl2br(htmlspecialchars($o['description'])); ?></span>
              </div>
            </div>

            <!-- Requested Changes -->
            <div class="compare-column">
              <div class="column-title">Requested Changes</div>

              <div class="field-row <?php echo ($o['date'] !== $p['date']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Date</span>
                <span class="field-value"><?php echo htmlspecialchars(date('d M Y', strtotime($p['date']))); ?></span>
              </div>

              <div class="field-row <?php echo ($o['start_time'] !== $p['start_time'] || $o['end_time'] !== $p['end_time']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Time</span>
                <span class="field-value">
                  <?php echo htmlspecialchars($p['start_time'].' – '.$p['end_time']); ?>
                </span>
              </div>

              <div class="field-row <?php echo ($o['location'] !== $p['location']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Location</span>
                <span class="field-value"><?php echo htmlspecialchars($p['location']); ?></span>
              </div>

              <div class="field-row <?php echo ($o['category'] !== $p['category']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Category</span>
                <span class="field-value"><?php echo htmlspecialchars($p['category']); ?></span>
              </div>

              <div class="field-row <?php echo ($o['sponsor'] !== $p['sponsor']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Sponsor</span>
                <span class="field-value">
                  <?php echo $p['sponsor'] ? htmlspecialchars($p['sponsor']) : '—'; ?>
                </span>
              </div>

              <div class="field-row description-block <?php echo ($o['description'] !== $p['description']) ? 'changed-field' : ''; ?>">
                <span class="field-label">Description</span>
                <span class="field-value"><?php echo nl2br(htmlspecialchars($p['description'])); ?></span>
              </div>
            </div>
          </div>

          <div class="actions-row">
            <button type="button" class="btn btn-approve">
              Approve edit
            </button>
            <button type="button" class="btn btn-reject">
              Reject edit
            </button>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="empty-state">There are no pending event edit requests right now.</p>
    <?php endif; ?>
  </section>
</div>

<script>
  function filterRequests(){
    const q = document.getElementById('searchBox').value.toLowerCase();
    const cards = document.querySelectorAll('#requestsList .request-card');

    cards.forEach(card => {
      const text = card.getAttribute('data-search') || '';
      card.style.display = text.includes(q) ? 'flex' : 'none';
    });
  }
</script>

</body>
</html>
