<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

/* ===============================
   Handle Approve / Reject actions
   =============================== */
if (isset($_GET['action'], $_GET['id'])) {
    $requestId = (int) $_GET['id'];
    $action = $_GET['action'];

    if ($requestId > 0 && ($action === 'approve' || $action === 'reject')) {

        // Fetch request row
        $sqlReq = "SELECT * FROM event_edit_request WHERE request_id = $requestId LIMIT 1";
        $resReq = $conn->query($sqlReq);

        if ($resReq && $resReq->num_rows === 1) {
            $req = $resReq->fetch_assoc();
            $eventId = (int) ($req['event_id'] ?? 0);
            $adminId = (int) $_SESSION['admin_id'];

            if ($eventId > 0) {

                if ($action === 'approve') {
                    // Build UPDATE for event table using proposed values
                    $updates = [];

                    if (!empty($req['new_event_name'])) {
                        $name = $conn->real_escape_string($req['new_event_name']);
                        $updates[] = "event_name = '$name'";
                    }

                    if (!empty($req['new_event_location'])) {
                        $loc = $conn->real_escape_string($req['new_event_location']);
                        $updates[] = "event_location = '$loc'";
                    }

                    if ($req['new_max_attendees'] !== null && $req['new_max_attendees'] !== '') {
                        $max = (int) $req['new_max_attendees'];
                        $updates[] = "max_attendees = $max";
                    }

                    if (!empty($req['new_starting_date'])) {
                        $start = $conn->real_escape_string($req['new_starting_date']);
                        $updates[] = "starting_date = '$start'";
                    }

                    if (!empty($req['new_ending_date'])) {
                        $end = $conn->real_escape_string($req['new_ending_date']);
                        $updates[] = "ending_date = '$end'";
                    }

                    if (!empty($req['new_description'])) {
                        $desc = $conn->real_escape_string($req['new_description']);
                        $updates[] = "description = '$desc'";
                    }

                    // Apply banner image if provided
                    if (!empty($req['new_banner_image'])) {
                        $img = $conn->real_escape_string($req['new_banner_image']);
                        $updates[] = "banner_image = '$img'";
                    }

                    // ✅ NEW: category
                    if (!empty($req['new_category'])) {
                        $cat = $conn->real_escape_string($req['new_category']);
                        $updates[] = "category = '$cat'";
                    }

                    // ✅ NEW: sponsor
                    if ($req['new_sponsor_id'] !== null && $req['new_sponsor_id'] !== '') {
                        $sid = (int)$req['new_sponsor_id'];
                        $updates[] = "sponsor_id = $sid";
                    }

                    if (!empty($updates)) {
                        $sqlUpdate = "UPDATE event SET " . implode(', ', $updates) . " WHERE event_id = $eventId";
                        $conn->query($sqlUpdate);
                    }

                    // Mark request approved
                    $conn->query("
                        UPDATE event_edit_request
                        SET reviewed_at = NOW(),
                            review_admin_id = $adminId,
                            status = 'Approved'
                        WHERE request_id = $requestId
                    ");

                    header('Location: ' . basename(__FILE__) . '?msg=approved');
                    exit;
                }

                if ($action === 'reject') {
                    // Mark request rejected
                    $conn->query("
                        UPDATE event_edit_request
                        SET reviewed_at = NOW(),
                            review_admin_id = $adminId,
                            status = 'Rejected'
                        WHERE request_id = $requestId
                    ");

                    header('Location: ' . basename(__FILE__) . '?msg=rejected');
                    exit;
                }
            }
        }

        header('Location: ' . basename(__FILE__) . '?msg=error');
        exit;
    }
}

/* ===============================
   Fetch PENDING edit requests
   =============================== */
$editRequests = [];

$sql = "
    SELECT
        eer.*,
        e.event_name       AS current_event_name,
        e.description      AS current_description,
        e.event_location   AS current_location,
        e.max_attendees    AS current_max_attendees,
        e.starting_date    AS current_starting_date,
        e.ending_date      AS current_ending_date,
        e.banner_image     AS current_banner_image,
        e.category         AS current_category,
        sp.company_name    AS current_sponsor_name,
        sp2.company_name   AS requested_sponsor_name,
        c.club_name,
        s.student_name     AS requested_by_name
    FROM event_edit_request eer
    JOIN event   e ON e.event_id = eer.event_id
    LEFT JOIN club    c ON c.club_id    = eer.club_id
    LEFT JOIN student s ON s.student_id = eer.requested_by_student_id
    LEFT JOIN sponsor sp  ON sp.sponsor_id  = e.sponsor_id
    LEFT JOIN sponsor sp2 ON sp2.sponsor_id = eer.new_sponsor_id
    WHERE eer.status = 'Pending'
    ORDER BY eer.submitted_at DESC
";

$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $editRequests[] = $row;
    }
}
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
      flex-wrap:wrap;
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
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
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

    .top-msg{
      margin-bottom:14px;
      padding:10px 12px;
      border-radius:14px;
      font-weight:700;
      background:#ffffff;
      border:1px solid rgba(15,23,42,.08);
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

<?php include 'sidebar.php'; ?>

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

  <?php if (isset($_GET['msg'])): ?>
    <div class="top-msg">
      <?php
        if ($_GET['msg'] === 'approved') echo "✅ Request approved and applied to the event.";
        elseif ($_GET['msg'] === 'rejected') echo "🛑 Request rejected.";
        else echo "⚠️ Something went wrong.";
      ?>
    </div>
  <?php endif; ?>

  <div class="toolbar">
    <div class="search-input">
      <input type="text" id="searchBox" placeholder="Search by event title or club name…" onkeyup="filterRequests()">
    </div>
  </div>

  <section class="requests-grid" id="requestsList">
    <?php if (!empty($editRequests)): ?>
      <?php foreach($editRequests as $row): ?>
        <?php
          $cardTitle   = $row['new_event_name'] ?: $row['current_event_name'];
          $clubName    = $row['club_name'] ?: '—';
          $requestedBy = $row['requested_by_name'] ?: ('Student #'.$row['requested_by_student_id']);
          $searchText  = strtolower($cardTitle.' '.$clubName.' '.$requestedBy);

          // Original values
          $origName     = $row['current_event_name'];
          $origLocation = $row['current_location'];
          $origMax      = $row['current_max_attendees'];
          $origStart    = $row['current_starting_date'];
          $origEnd      = $row['current_ending_date'];
          $origDesc     = $row['current_description'];
          $origBanner   = $row['current_banner_image'];

          $origCategory = $row['current_category'];
          $origSponsor  = $row['current_sponsor_name'];

          // Proposed values
          $propName     = $row['new_event_name']     ?: $origName;
          $propLocation = $row['new_event_location'] ?: $origLocation;
          $propMax      = ($row['new_max_attendees'] !== null && $row['new_max_attendees'] !== '')
                          ? $row['new_max_attendees'] : $origMax;
          $propStart    = $row['new_starting_date']  ?: $origStart;
          $propEnd      = $row['new_ending_date']    ?: $origEnd;
          $propDesc     = $row['new_description']    ?: $origDesc;
          $propBanner   = $row['new_banner_image']   ?: $origBanner;

          $propCategory = $row['new_category'] ?: $origCategory;
          $propSponsor  = ($row['requested_sponsor_name'] ?: $origSponsor);

          // Change flags
          $nameChanged = (!empty($row['new_event_name']) && $row['new_event_name'] !== $origName);
          $locChanged  = (!empty($row['new_event_location']) && $row['new_event_location'] !== $origLocation);
          $maxChanged  = ($row['new_max_attendees'] !== null && $row['new_max_attendees'] !== '' &&
                          (int)$row['new_max_attendees'] !== (int)$origMax);

          $dateChanged = (!empty($row['new_starting_date']) && $row['new_starting_date'] !== $origStart) ||
                         (!empty($row['new_ending_date'])   && $row['new_ending_date']   !== $origEnd);

          $descChanged = (!empty($row['new_description']) && $row['new_description'] !== $origDesc);
          $bannerChanged = (!empty($row['new_banner_image']) && $row['new_banner_image'] !== $origBanner);

          $catChanged = (!empty($row['new_category']) && $row['new_category'] !== $origCategory);
          $sponsorChanged = ($row['new_sponsor_id'] !== null && $row['new_sponsor_id'] !== '' &&
                             (string)$row['new_sponsor_id'] !== (string)($row['sponsor_id'] ?? ''));

          $origCatShow = trim((string)$origCategory) === '' ? '—' : $origCategory;
          $propCatShow = trim((string)$propCategory) === '' ? '—' : $propCategory;

          $origSponsorShow = trim((string)$origSponsor) === '' ? '—' : $origSponsor;
          $propSponsorShow = trim((string)$propSponsor) === '' ? '—' : $propSponsor;
        ?>
        <article class="request-card" data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES); ?>">
          <div class="request-header">
            <div>
              <div class="request-title"><?php echo htmlspecialchars($cardTitle); ?></div>
              <div class="request-meta-top">
                <span>Club: <strong><?php echo htmlspecialchars($clubName); ?></strong></span>
                <span>Requested by: <?php echo htmlspecialchars($requestedBy); ?></span>
                <span>Requested on: <?php echo htmlspecialchars(date('d M Y', strtotime($row['submitted_at']))); ?></span>
              </div>
            </div>
          </div>

          <div class="compare-grid">
            <!-- Current Event -->
            <div class="compare-column">
              <div class="column-title">Current Event</div>

              <div class="field-row <?php echo $nameChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Event name</span>
                <span class="field-value"><?php echo htmlspecialchars($origName); ?></span>
              </div>

              <div class="field-row <?php echo $locChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Location</span>
                <span class="field-value"><?php echo htmlspecialchars($origLocation); ?></span>
              </div>

              <div class="field-row <?php echo $catChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Category</span>
                <span class="field-value"><?php echo htmlspecialchars($origCatShow); ?></span>
              </div>

              <div class="field-row <?php echo $sponsorChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Sponsored by</span>
                <span class="field-value"><?php echo htmlspecialchars($origSponsorShow); ?></span>
              </div>

              <div class="field-row <?php echo $maxChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Max attendees</span>
                <span class="field-value"><?php echo htmlspecialchars((string)$origMax); ?></span>
              </div>

              <div class="field-row <?php echo $dateChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Date & Time</span>
                <span class="field-value">
                  <?php
                    echo htmlspecialchars(
                      date('d M Y', strtotime($origStart)) . ' • ' .
                      date('h:i A', strtotime($origStart)) . ' – ' .
                      date('h:i A', strtotime($origEnd))
                    );
                  ?>
                </span>
              </div>

              <div class="field-row description-block <?php echo $descChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Description</span>
                <span class="field-value"><?php echo nl2br(htmlspecialchars($origDesc)); ?></span>
              </div>

              <div class="field-row <?php echo $bannerChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Banner image</span>
                <span class="field-value"><?php echo htmlspecialchars($origBanner ?: '—'); ?></span>
              </div>
            </div>

            <!-- Requested Changes -->
            <div class="compare-column">
              <div class="column-title">Requested Changes</div>

              <div class="field-row <?php echo $nameChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Event name</span>
                <span class="field-value"><?php echo htmlspecialchars($propName); ?></span>
              </div>

              <div class="field-row <?php echo $locChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Location</span>
                <span class="field-value"><?php echo htmlspecialchars($propLocation); ?></span>
              </div>

              <div class="field-row <?php echo $catChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Category</span>
                <span class="field-value"><?php echo htmlspecialchars($propCatShow); ?></span>
              </div>

              <div class="field-row <?php echo $sponsorChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Sponsored by</span>
                <span class="field-value"><?php echo htmlspecialchars($propSponsorShow); ?></span>
              </div>

              <div class="field-row <?php echo $maxChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Max attendees</span>
                <span class="field-value"><?php echo htmlspecialchars((string)$propMax); ?></span>
              </div>

              <div class="field-row <?php echo $dateChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Date & Time</span>
                <span class="field-value">
                  <?php
                    echo htmlspecialchars(
                      date('d M Y', strtotime($propStart)) . ' • ' .
                      date('h:i A', strtotime($propStart)) . ' – ' .
                      date('h:i A', strtotime($propEnd))
                    );
                  ?>
                </span>
              </div>

              <div class="field-row description-block <?php echo $descChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Description</span>
                <span class="field-value"><?php echo nl2br(htmlspecialchars($propDesc)); ?></span>
              </div>

              <div class="field-row <?php echo $bannerChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Banner image</span>
                <span class="field-value"><?php echo htmlspecialchars($propBanner ?: '—'); ?></span>
              </div>
            </div>
          </div>

          <div class="actions-row">
            <a href="<?php echo basename(__FILE__); ?>?action=approve&id=<?php echo (int)$row['request_id']; ?>"
               class="btn btn-approve">
              Approve edit
            </a>
            <a href="<?php echo basename(__FILE__); ?>?action=reject&id=<?php echo (int)$row['request_id']; ?>"
               class="btn btn-reject"
               onclick="return confirm('Are you sure you want to reject this edit request?');">
              Reject edit
            </a>
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
      const text = (card.getAttribute('data-search') || '').toLowerCase();
      card.style.display = text.includes(q) ? 'flex' : 'none';
    });
  }
</script>

</body>
</html>
