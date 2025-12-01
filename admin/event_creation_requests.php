<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// ===============================
// Dummy Data for UI Testing
// ===============================
$eventRequests = [
    [
        "id" => 1,
        "title" => "Tech Hack Night",
        "location" => "Main Hall / Room 1204",
        "event_date" => "2025-01-10",
        "start_time" => "06:00 PM",
        "end_time" => "10:00 PM",
        "category" => "Technology",
        "sponsor" => "TechCorp",
        "description" => "A night full of coding challenges and teamwork. Students join to compete in hackathon-style problems.",
        "cover_image" => "assets/sample/event1.jpg",
        "club_name" => "Computer Science Club",
        "requested_by" => "Ahmad Yousef",
        "created_at" => "2025-01-05 09:33:00"
    ],
    [
        "id" => 2,
        "title" => "Art & Creativity Day",
        "location" => "Art Center - Building B",
        "event_date" => "2025-02-02",
        "start_time" => "02:00 PM",
        "end_time" => "06:00 PM",
        "category" => "Art",
        "sponsor" => "",
        "description" => "A creative workshop where students participate in live painting, crafting, and photography activities.",
        "cover_image" => "",
        "club_name" => "Arts Club",
        "requested_by" => "Dana Khalil",
        "created_at" => "2025-01-12 13:22:00"
    ],
    [
        "id" => 3,
        "title" => "Business Networking Meetup",
        "location" => "Auditorium C",
        "event_date" => "2025-03-14",
        "start_time" => "11:00 AM",
        "end_time" => "01:00 PM",
        "category" => "Business",
        "sponsor" => "Jordan Bank",
        "description" => "Students meet business professionals, exchange ideas, and learn about entrepreneurship opportunities.",
        "cover_image" => "assets/sample/event2.jpg",
        "club_name" => "Business Club",
        "requested_by" => "Lana Saadeh",
        "created_at" => "2025-01-20 15:17:00"
    ],
];
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Event Creation Requests (Admin)</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --sidebarWidth:240px; /* make sure this matches your global value */
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

    /* ======= Cards ======= */
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
      display:grid;
      grid-template-columns:minmax(0,1fr) auto;
      gap:18px;
    }

    .request-main{
      display:flex;
      flex-direction:column;
      gap:12px;
    }

    .request-header{
      display:flex;
      align-items:center;
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
    }

    .chip{
      padding:4px 10px;
      border-radius:999px;
      background:#f3f4ff;
      font-size:.78rem;
      color:var(--navy);
      display:inline-flex;
      align-items:center;
      gap:6px;
    }

    .chip-icon{
      width:6px;
      height:6px;
      border-radius:50%;
      background:var(--coral);
    }

    .meta-row{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      font-size:.8rem;
      color:var(--muted);
    }

    .meta-label{
      font-weight:600;
      color:var(--ink);
    }

    .description{
      font-size:.86rem;
      line-height:1.45;
      color:var(--ink);
      background:#f9fafb;
      border-radius:14px;
      padding:10px 12px;
      max-height:96px;
      overflow:auto;
    }

    /* Right side: cover + actions */
    .request-side{
      display:flex;
      flex-direction:column;
      align-items:flex-end;
      justify-content:space-between;
      gap:12px;
      min-width:190px;
    }

    .cover-thumb{
      width:100%;
      max-width:220px;
      height:110px;
      border-radius:16px;
      overflow:hidden;
      background:#e5e7eb;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:.78rem;
      color:var(--muted);
    }

    .cover-thumb img{
      width:100%;
      height:100%;
      object-fit:cover;
    }

    .actions{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      justify-content:flex-end;
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
      .request-card{
        grid-template-columns:1fr;
      }
      .request-side{
        align-items:stretch;
      }
      .cover-thumb{
        max-width:100%;
      }
      .actions{
        justify-content:flex-start;
      }
    }
  </style>
</head>
<body>

<?php
  // include your existing admin sidebar
  // make sure this file defines --sidebarWidth or body margin left will be 0
  include 'sidebar.php';
?>

<div class="page-shell">
  <header class="page-header">
    <div>
      <h1 class="page-title">Event Creation Requests</h1>
      <p class="page-subtitle">
        Review event requests submitted by club presidents and approve or reject them.
      </p>
    </div>
    <span class="badge-pill">
      <span class="chip-icon"></span>
      Pending requests
    </span>
  </header>

  <div class="toolbar">
    <div class="search-input">
      <input type="text" id="searchBox" placeholder="Search by title, club name, or category…" onkeyup="filterRequests()">
    </div>
  </div>

  <section class="requests-grid" id="requestsList">
    <?php if (!empty($eventRequests)): ?>
      <?php foreach($eventRequests as $row): ?>
        <article class="request-card" data-search="<?php 
          echo htmlspecialchars(
            strtolower(
              $row['title'].' '.$row['club_name'].' '.$row['category'].' '.$row['location']
            ),
            ENT_QUOTES
          );
        ?>">
          <div class="request-main">
            <div class="request-header">
              <div>
                <div class="request-title">
                  <?php echo htmlspecialchars($row['title']); ?>
                </div>
                <div class="request-meta-top">
                  <span>Club: <strong><?php echo htmlspecialchars($row['club_name']); ?></strong></span>
                  <?php if(!empty($row['requested_by'])): ?>
                    <span>Requested by: <?php echo htmlspecialchars($row['requested_by']); ?></span>
                  <?php endif; ?>
                  <span>Requested on: <?php echo htmlspecialchars(date('d M Y', strtotime($row['created_at']))); ?></span>
                </div>
              </div>
            </div>

            <div class="meta-row">
              <span><span class="meta-label">Date:</span> <?php echo htmlspecialchars(date('d M Y', strtotime($row['event_date']))); ?></span>
              <span><span class="meta-label">Start:</span> <?php echo htmlspecialchars($row['start_time']); ?></span>
              <span><span class="meta-label">End:</span> <?php echo htmlspecialchars($row['end_time']); ?></span>
              <span><span class="meta-label">Location:</span> <?php echo htmlspecialchars($row['location']); ?></span>
              <span><span class="meta-label">Category:</span> <?php echo htmlspecialchars($row['category']); ?></span>
              <?php if(!empty($row['sponsor'])): ?>
                <span><span class="meta-label">Sponsor:</span> <?php echo htmlspecialchars($row['sponsor']); ?></span>
              <?php endif; ?>
            </div>

            <div class="description">
              <?php echo nl2br(htmlspecialchars($row['description'])); ?>
            </div>
          </div>

          <div class="request-side">
            <div class="cover-thumb">
              <?php if(!empty($row['cover_image'])): ?>
                <img src="<?php echo htmlspecialchars($row['cover_image']); ?>" alt="Cover image">
              <?php else: ?>
                No cover image
              <?php endif; ?>
            </div>

            <form method="post" class="actions">
              <input type="hidden" name="event_id" value="<?php echo (int)$row['id']; ?>">
              <button type="submit" name="action" value="approve" class="btn btn-approve">
                Approve
              </button>
              <button type="submit" name="action" value="reject" class="btn btn-reject"
                      onclick="return confirm('Are you sure you want to reject this event request?');">
                Reject
              </button>
            </form>
          </div>
        </article>
        <?php endforeach; ?>
    <?php else: ?>
      <p class="empty-state">There are no pending event creation requests right now.</p>
    <?php endif; ?>
  </section>
</div>

<script>
  function filterRequests(){
    const q = document.getElementById('searchBox').value.toLowerCase();
    const cards = document.querySelectorAll('#requestsList .request-card');

    cards.forEach(card => {
      const text = card.getAttribute('data-search') || '';
      card.style.display = text.includes(q) ? 'grid' : 'none';
    });
  }
</script>

</body>
</html>
