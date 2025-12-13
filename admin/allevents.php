<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

// ============= Delete Event (Remove button) =============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event_id'])) {
    $eventId = (int) $_POST['delete_event_id'];

    $conn->begin_transaction();

    try {
        // 1) Delete children first (because FKs restrict delete)
        $stmt = $conn->prepare("DELETE FROM attendance WHERE event_id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM rating WHERE event_id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM points_ledger WHERE event_id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM event_edit_request WHERE event_id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $stmt->close();

        // 2) Now delete the event
        $stmtDel = $conn->prepare("DELETE FROM event WHERE event_id = ?");
        $stmtDel->bind_param("i", $eventId);
        $stmtDel->execute();

        // if nothing deleted => event_id not found
        if ($stmtDel->affected_rows === 0) {
            $stmtDel->close();
            $conn->rollback();
            die("Event not found or already deleted.");
        }

        $stmtDel->close();
        $conn->commit();

        header('Location: allevents.php');
        exit;

    } catch (Throwable $e) {
        $conn->rollback();
        die("Delete failed: " . htmlspecialchars($e->getMessage()));
    }
}


// ============= Fetch Events from DB =============
$sql = "
    SELECT
        e.event_id,
        e.event_name,
        e.event_location,
        e.starting_date,
        e.ending_date,
        e.max_attendees,
        c.club_name
    FROM event e
    LEFT JOIN club c ON e.club_id = c.club_id
    ORDER BY e.starting_date DESC
";
$res = $conn->query($sql);

$events = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $events[] = $row;
    }
}

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
      --sidebarWidth:240px;
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

<?php include 'sidebar.php'; ?>

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
          $eventDate = substr($event['starting_date'], 0, 10); // YYYY-mm-dd
          $isUpcoming = $eventDate >= $today;
          $timeStatus = $isUpcoming ? 'upcoming' : 'past';

          $searchText = strtolower(
            $event['event_name'].' '.($event['club_name'] ?? '').' '.($event['event_location'] ?? '')
          );

          $startLabel = $event['starting_date'] ? date('d M Y, h:i A', strtotime($event['starting_date'])) : '—';
          $endLabel   = $event['ending_date']   ? date('d M Y, h:i A', strtotime($event['ending_date']))   : '—';
        ?>
        <article
          class="event-card"
          data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES); ?>"
          data-time-status="<?php echo $timeStatus; ?>"
        >
          <div class="event-main">
            <div class="event-title-row">
              <span class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></span>
              <?php if (!empty($event['club_name'])): ?>
                <span class="event-club">• <?php echo htmlspecialchars($event['club_name']); ?></span>
              <?php endif; ?>

              <?php if ($isUpcoming): ?>
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
              <span><span class="meta-label">Starting:</span>
                <?php echo htmlspecialchars($startLabel); ?>
              </span>
              <span><span class="meta-label">Ending:</span>
                <?php echo htmlspecialchars($endLabel); ?>
              </span>
              <span><span class="meta-label">Location:</span>
                <?php echo htmlspecialchars($event['event_location']); ?>
              </span>
              <span><span class="meta-label">Max attendees:</span>
                <?php echo (int)$event['max_attendees']; ?>
              </span>
            </div>
          </div>

          <div class="event-actions">
            <span class="event-tag">
              <?php echo $isUpcoming ? 'Upcoming event' : 'Past event'; ?>
            </span>

            <?php if ($isUpcoming): ?>
              <form method="post" onsubmit="return confirm('Are you sure you want to remove this event?');">
                <input type="hidden" name="delete_event_id" value="<?php echo (int)$event['event_id']; ?>">
                <button type="submit" class="btn btn-remove">
                  Remove event
                </button>
              </form>
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
</script>

</body>
</html>
