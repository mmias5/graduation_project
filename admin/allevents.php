<?php
// ===============================
// Dummy Data for All Events UI
// ===============================
$events = [
    [
        "id" => 1,
        "title" => "Tech Hack Night",
        "club_name" => "Computer Science Club",
        "date" => "2025-12-20",
        "start_time" => "06:00 PM",
        "end_time" => "10:00 PM",
        "location" => "Main Hall / Room 1204",
        "category" => "Technology",
        "status" => "Approved"
    ],
    [
        "id" => 2,
        "title" => "Art & Creativity Day",
        "club_name" => "Arts Club",
        "date" => "2025-10-10",
        "start_time" => "02:00 PM",
        "end_time" => "06:00 PM",
        "location" => "Art Center - Building B",
        "category" => "Art",
        "status" => "Completed"
    ],
    [
        "id" => 3,
        "title" => "Business Networking Meetup",
        "club_name" => "Business Club",
        "date" => "2026-01-15",
        "start_time" => "11:00 AM",
        "end_time" => "02:00 PM",
        "location" => "Auditorium C",
        "category" => "Business",
        "status" => "Approved"
    ],
    [
        "id" => 4,
        "title" => "Wellness & Mental Health Talk",
        "club_name" => "Psychology Club",
        "date" => "2025-05-01",
        "start_time" => "01:00 PM",
        "end_time" => "03:00 PM",
        "location" => "Conference Room A",
        "category" => "Wellness",
        "status" => "Completed"
    ],
];

$today = date('Y-m-d');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — All Events (Admin)</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --sidebarWidth:240px; /* نفس اللي بالـ sidebar */
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

    /* Toolbar */
    .toolbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      margin-bottom:24px;
      flex-wrap:wrap;
    }

    .search-input{
      flex:1;
      min-width:220px;
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

    .filter-group{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
    }

    .filter-pill{
      padding:7px 14px;
      border-radius:999px;
      border:1px solid rgba(15,23,42,.08);
      background:#ffffff;
      font-size:.82rem;
      font-weight:600;
      color:var(--muted);
      cursor:pointer;
      transition:.16s ease all;
    }

    .filter-pill.active{
      background:var(--coral);
      color:#ffffff;
      border-color:transparent;
      box-shadow:0 8px 20px rgba(255,94,94,.3);
    }

    .filter-pill:hover:not(.active){
      background:#f9fafb;
    }

    /* Cards */
    .events-grid{
      display:flex;
      flex-direction:column;
      gap:18px;
    }

    .event-card{
      background:var(--card);
      border-radius:var(--radius-lg);
      box-shadow:var(--shadow);
      padding:16px 18px;
      display:flex;
      justify-content:space-between;
      gap:14px;
      align-items:flex-start;
    }

    .event-main{
      display:flex;
      flex-direction:column;
      gap:8px;
      min-width:0;
    }

    .event-title-row{
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
    }

    .event-title{
      font-size:1.02rem;
      font-weight:700;
      color:var(--navy);
    }

    .event-club{
      font-size:.82rem;
      color:var(--muted);
    }

    .badge-status{
      padding:4px 10px;
      border-radius:999px;
      font-size:.75rem;
      font-weight:600;
      display:inline-flex;
      align-items:center;
      gap:6px;
    }

    .badge-approved{
      background:rgba(72,113,219,.08);
      color:var(--royal);
    }

    .badge-completed{
      background:#e5e7eb;
      color:#4b5563;
    }

    .badge-dot{
      width:6px;
      height:6px;
      border-radius:50%;
      background:currentColor;
    }

    .event-meta{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      font-size:.82rem;
      color:var(--muted);
    }

    .meta-label{
      font-weight:600;
      color:var(--ink);
    }

    .event-actions{
      display:flex;
      flex-direction:column;
      gap:8px;
      align-items:flex-end;
      min-width:150px;
    }

    .event-tag{
      font-size:.78rem;
      color:var(--muted);
    }

    .btn{
      padding:7px 16px;
      border-radius:var(--radius-pill);
      border:1px solid transparent;
      font-size:.82rem;
      font-weight:600;
      cursor:pointer;
      transition:.16s ease all;
      font-family:inherit;
    }

    .btn-remove{
      background:#ffffff;
      color:#b91c1c;
      border-color:rgba(185,28,28,.45);
    }

    .btn-remove:hover{
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
      .event-card{
        flex-direction:column;
        align-items:flex-start;
      }
      .event-actions{
        align-items:flex-start;
      }
    }
  </style>
</head>
<body>

<?php
  // نفس السايدبار تبع الـ admin عندك
  include 'sidebar.php';
?>

<div class="page-shell">
  <header class="page-header">
    <div>
      <h1 class="page-title">All Events</h1>
      <p class="page-subtitle">
        Browse all approved events and manage upcoming and past activities.
      </p>
    </div>
  </header>

  <div class="toolbar">
    <div class="search-input">
      <input type="text" id="searchBox" placeholder="Search by event title or club name…" onkeyup="applyFilters()">
    </div>

    <div class="filter-group">
      <button type="button" class="filter-pill active" data-filter="all" onclick="setFilter('all', this)">All</button>
      <button type="button" class="filter-pill" data-filter="upcoming" onclick="setFilter('upcoming', this)">Upcoming</button>
      <button type="button" class="filter-pill" data-filter="past" onclick="setFilter('past', this)">Past</button>
    </div>
  </div>

  <section class="events-grid" id="eventsList">
    <?php if (!empty($events)): ?>
      <?php foreach($events as $event): ?>
        <?php
          $isUpcoming = $event['date'] >= $today;
          $timeStatus = $isUpcoming ? 'upcoming' : 'past';
          $searchText = strtolower($event['title'].' '.$event['club_name'].' '.$event['category'].' '.$event['location']);
        ?>
        <article
          class="event-card"
          data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES); ?>"
          data-time-status="<?php echo $timeStatus; ?>"
        >
          <div class="event-main">
            <div class="event-title-row">
              <span class="event-title"><?php echo htmlspecialchars($event['title']); ?></span>
              <span class="event-club">• <?php echo htmlspecialchars($event['club_name']); ?></span>
              <?php if ($event['status'] === 'Approved'): ?>
                <span class="badge-status badge-approved">
                  <span class="badge-dot"></span> Approved
                </span>
              <?php else: ?>
                <span class="badge-status badge-completed">
                  <span class="badge-dot"></span> Completed
                </span>
              <?php endif; ?>
            </div>

            <div class="event-meta">
              <span><span class="meta-label">Date:</span>
                <?php echo htmlspecialchars(date('d M Y', strtotime($event['date']))); ?>
              </span>
              <span><span class="meta-label">Time:</span>
                <?php echo htmlspecialchars($event['start_time'].' – '.$event['end_time']); ?>
              </span>
              <span><span class="meta-label">Location:</span>
                <?php echo htmlspecialchars($event['location']); ?>
              </span>
              <span><span class="meta-label">Category:</span>
                <?php echo htmlspecialchars($event['category']); ?>
              </span>
            </div>
          </div>

          <div class="event-actions">
            <span class="event-tag">
              <?php echo $isUpcoming ? 'Upcoming event' : 'Past event'; ?>
            </span>

            <?php if ($isUpcoming): ?>
              <!-- زر الحذف يظهر فقط للأحداث القادمة -->
              <button type="button" class="btn btn-remove" onclick="removeEventCard(this)">
                Remove event
              </button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="empty-state">No events found.</p>
    <?php endif; ?>
  </section>
</div>

<script>
  let currentFilter = 'all';

  function setFilter(filter, btn){
    currentFilter = filter;

    // active state on buttons
    document.querySelectorAll('.filter-pill').forEach(pill => {
      pill.classList.toggle('active', pill.getAttribute('data-filter') === filter);
    });

    applyFilters();
  }

  function applyFilters(){
    const q = document.getElementById('searchBox').value.toLowerCase();
    const cards = document.querySelectorAll('#eventsList .event-card');

    cards.forEach(card => {
      const text = card.getAttribute('data-search') || '';
      const status = card.getAttribute('data-time-status');
      const matchesSearch = text.includes(q);
      const matchesFilter = (currentFilter === 'all') || (status === currentFilter);

      card.style.display = (matchesSearch && matchesFilter) ? 'flex' : 'none';
    });
  }

  function removeEventCard(button){
    if (!confirm('Are you sure you want to remove this event?')) return;
    const card = button.closest('.event-card');
    if (card) card.remove();
  }
</script>

</body>
</html>
