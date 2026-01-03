<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}


require_once '../config.php';

function redirect_self() {
    $self = basename($_SERVER['PHP_SELF']);
    header("Location: $self");
    exit;
}

// ===============================
// Handle Approve / Reject (POST)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['event_id'])) {

    $requestId = (int)$_POST['event_id'];   // request_id
    $adminId   = (int)$_SESSION['admin_id'];
    $action    = $_POST['action'];

    $conn->begin_transaction();

    try {

        // Fetch request (make sure exists)
        $stmt = $conn->prepare("
            SELECT
                request_id,
                club_id,
                requested_by_student_id,
                event_name,
                description,
                event_location,
                category,
                sponsor_id,
                max_attendees,
                starting_date,
                ending_date,
                banner_image,
                reviewed_at
            FROM event_creation_request
            WHERE request_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $req = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$req) {
            $conn->rollback();
            redirect_self();
        }

        // Already reviewed? don't re-insert
        if (!empty($req['reviewed_at'])) {
            $conn->commit();
            redirect_self();
        }

        if ($action === 'approve') {

            // Insert into event table (include category + sponsor_id)
            $insert = $conn->prepare("
                INSERT INTO event
                    (event_name, description, event_location, category, sponsor_id, max_attendees,
                     starting_date, ending_date, banner_image, club_id, created_by_student_id, created_by_admin_id)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $eventName   = $req['event_name'];
            $desc        = $req['description'];
            $loc         = $req['event_location'];

            $category    = $req['category'];      // nullable
            $sponsorId   = $req['sponsor_id'];    // nullable

            $maxAtt      = $req['max_attendees']; // nullable
            $startDt     = $req['starting_date'];
            $endDt       = $req['ending_date'];
            $banner      = $req['banner_image'];

            $clubId      = (int)$req['club_id'];
            $createdBySt = (int)$req['requested_by_student_id'];
            $createdByAd = $adminId;

            // Normalize empty strings -> NULL for nullable ints
            if ($sponsorId === '' ) $sponsorId = null;
            if ($maxAtt === '' ) $maxAtt = null;

            $insert->bind_param(
                "ssssiisssiii",
                $eventName,
                $desc,
                $loc,
                $category,
                $sponsorId,
                $maxAtt,
                $startDt,
                $endDt,
                $banner,
                $clubId,
                $createdBySt,
                $createdByAd
            );

            $insert->execute();
            $insert->close();
        }

        // Mark as reviewed (for approve OR reject)
        $upd = $conn->prepare("
            UPDATE event_creation_request
            SET reviewed_at = NOW(),
                review_admin_id = ?
            WHERE request_id = ?
            LIMIT 1
        ");
        $upd->bind_param("ii", $adminId, $requestId);
        $upd->execute();
        $upd->close();

        $conn->commit();
        redirect_self();

    } catch (Throwable $e) {
        $conn->rollback();
        die("Error: " . htmlspecialchars($e->getMessage()));
    }
}

// ===============================
// Fetch Pending Event Requests
// ===============================
$eventRequests = [];

$sql = "
SELECT 
    e.request_id            AS id,
    e.event_name            AS title,
    e.event_location        AS location,
    DATE(e.starting_date)   AS event_date,
    DATE_FORMAT(e.starting_date, '%h:%i %p') AS start_time,
    DATE_FORMAT(e.ending_date, '%h:%i %p')   AS end_time,
    e.category              AS category,
    sp.company_name         AS sponsor,
    e.description           AS description,
    e.banner_image          AS cover_image,
    c.club_name             AS club_name,
    s.student_name          AS requested_by,
    e.submitted_at          AS created_at
FROM event_creation_request e
LEFT JOIN club c 
       ON e.club_id = c.club_id
LEFT JOIN student s 
       ON e.requested_by_student_id = s.student_id
LEFT JOIN sponsor sp
       ON sp.sponsor_id = e.sponsor_id
WHERE e.reviewed_at IS NULL
ORDER BY e.submitted_at DESC
";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
        $eventRequests[] = $row;
    }
}
function assetUrl(string $path): string {
  $path = trim($path);
  if ($path === '') return '';
  if (preg_match('~^https?://~i', $path)) return $path;     
  if ($path[0] === '/') return $path;                      

  // uploads/... -> should go to /graduation_project/uploads/... 
  if (strpos($path, 'uploads/') === 0) {
    return '/graduation_project/' . $path;
  }

  // if none of the above return as is 
  return $path;
}
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
      .page-shell{ margin-left:0; padding:20px 16px 28px; }
      .request-card{ grid-template-columns:1fr; }
      .request-side{ align-items:stretch; }
      .cover-thumb{ max-width:100%; }
      .actions{ justify-content:flex-start; }
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

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
        <?php
          $cat = $row['category'] ?? '';
          $catShow = trim((string)$cat) === '' ? '—' : $cat;

          $sponsorShow = trim((string)($row['sponsor'] ?? '')) === '' ? '' : $row['sponsor'];
        ?>
        <article class="request-card" data-search="<?php
          echo htmlspecialchars(
            strtolower(
              $row['title'].' '.$row['club_name'].' '.$catShow.' '.$row['location'].' '.$sponsorShow
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
              <span><span class="meta-label">Category:</span> <?php echo htmlspecialchars($catShow); ?></span>
              <?php if(!empty($sponsorShow)): ?>
                <span><span class="meta-label">Sponsor:</span> <?php echo htmlspecialchars($sponsorShow); ?></span>
              <?php endif; ?>
            </div>

            <div class="description">
              <?php echo nl2br(htmlspecialchars($row['description'])); ?>
            </div>
          </div>

          <div class="request-side">
            <div class="cover-thumb">
              <?php if(!empty($row['cover_image'])): ?>
<img src="<?php echo htmlspecialchars(assetUrl((string)$row['cover_image'])); ?>" alt="Cover image">
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
