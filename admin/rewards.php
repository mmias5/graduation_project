<?php
  // just to know the current page for sidebar active state
  $currentPage = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Rewards management</title>

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
      --shadow:0 20px 46px rgba(15,23,42,.18);
      --radius-lg:26px;
    }

    *{
      box-sizing:border-box;
    }

    body{
      margin:0;
      font-family:"Raleway",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      background:var(--paper);
      color:var(--ink);
    }

    .admin-layout{
      display:flex;
      min-height:100vh;
    }

    /* main area (sidebar style موجود عندك في sidebar.php) */
.admin-main{
  margin-left:var(--sidebarWidth);
  padding:32px 24px;   /* كان 40px */
  width: calc(100% - var(--sidebarWidth));
  min-height:100vh;
  background:radial-gradient(circle at top left,#f4f7ff 0,#eef2f7 55%,#e4e7f3 100%);
}


    .page-header{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:16px;
      margin-bottom:24px;
    }

    .page-title{
      font-size:1.8rem;
      font-weight:800;
      color:var(--navy);
      letter-spacing:.02em;
      margin:0 0 4px;
    }

    .page-subtitle{
      margin:0;
      font-size:.95rem;
      color:var(--muted);
    }

    .page-badge{
      background:#ffeef1;
      color:var(--coral);
      font-size:.8rem;
      font-weight:600;
      padding:6px 14px;
      border-radius:999px;
      box-shadow:0 8px 22px rgba(248,113,113,.28);
      align-self:flex-start;
    }

    .card{
      background:var(--card);
      border-radius:var(--radius-lg);
      box-shadow:var(--shadow);
      padding:22px 24px;
      margin-bottom:24px;
    }

    .card-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:16px;
    }

    .card-title{
      font-size:1.1rem;
      font-weight:700;
      color:var(--navy);
      margin:0;
    }

    .card-subtitle{
      margin:2px 0 0;
      font-size:.86rem;
      color:var(--muted);
    }

    /* form */
    .form-grid{
      display:grid;
      grid-template-columns:2fr 1fr;
      gap:16px 20px;
    }

    .form-group{
      display:flex;
      flex-direction:column;
      gap:6px;
      font-size:.9rem;
    }

    .form-group label{
      font-weight:600;
      color:var(--navy);
    }

    .form-group small{
      font-size:.8rem;
      color:var(--muted);
    }

    .input-text,
    .input-number{
      border-radius:999px;
      border:1px solid #d1d5db;
      padding:9px 14px;
      font-family:inherit;
      font-size:.9rem;
      outline:none;
      background:#f9fafb;
      transition:.16s ease border,.16s ease box-shadow,.16s ease background;
    }

    .input-text:focus,
    .input-number:focus{
      border-color:var(--royal);
      box-shadow:0 0 0 1px rgba(72,113,219,.3);
      background:#ffffff;
    }

    .input-file{
      border-radius:999px;
      border:1px dashed #d1d5db;
      padding:7px 14px;
      font-size:.85rem;
      background:#f9fafb;
      cursor:pointer;
    }

    .btn{
      border:none;
      outline:none;
      font-family:inherit;
      font-size:.9rem;
      font-weight:600;
      border-radius:999px;
      padding:9px 18px;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      gap:6px;
      transition:.16s ease transform,.16s ease box-shadow,.16s ease background;
      text-decoration:none;
    }

    .btn-primary{
      background:linear-gradient(135deg,#ff7b7b,#ff5e5e);
      color:#fff7f8;
      box-shadow:0 12px 30px rgba(248,113,113,.45);
    }

    .btn-primary:hover{
      transform:translateY(-1px);
      box-shadow:0 16px 40px rgba(248,113,113,.65);
    }

    .btn-ghost{
      background:transparent;
      color:var(--royal);
    }

    .btn-ghost:hover{
      background:rgba(72,113,219,.08);
    }

    .btn-outline{
      border:1px solid #e5e7eb;
      background:#ffffff;
      color:var(--navy);
      padding:7px 14px;
      font-size:.8rem;
    }

    .btn-outline:hover{
      background:#f3f4f6;
    }

    .btn-danger{
      border:1px solid rgba(248,113,113,.7);
      color:#b91c1c;
      background:#fff7f7;
      padding:7px 12px;
      font-size:.8rem;
    }

    .btn-danger:hover{
      background:#fee2e2;
    }

    .table-wrapper{
      overflow-x:auto;
    }

    table{
      width:100%;
      border-collapse:collapse;
      font-size:.9rem;
    }

    thead{
      background:#f7f8fd;
    }

    th,
    td{
      padding:10px 12px;
      text-align:left;
      white-space:nowrap;
    }

    th{
      font-size:.78rem;
      text-transform:uppercase;
      letter-spacing:.06em;
      color:#6b7280;
      border-bottom:1px solid #e5e7eb;
    }

    tbody tr{
      background:#ffffff;
      border-bottom:1px solid #edf0f5;
      transition:.12s ease background,.12s ease transform;
    }

    tbody tr:hover{
      background:#f9fafb;
      transform:translateY(-1px);
    }

    .reward-row{
      display:flex;
      align-items:center;
      gap:10px;
    }

    .reward-img{
      width:44px;
      height:44px;
      border-radius:18px;
      object-fit:cover;
      background:#f3f4f6;
      border:1px solid #e5e7eb;
    }

    .reward-name{
      font-weight:600;
      color:var(--navy);
      margin-bottom:2px;
    }

    .reward-meta{
      font-size:.8rem;
      color:var(--muted);
    }

    .points-chip{
      display:inline-flex;
      align-items:center;
      gap:4px;
      padding:4px 11px;
      border-radius:999px;
      background:#fff7e6;
      color:#854d0e;
      font-size:.8rem;
    }

    .code-chip{
      display:inline-flex;
      align-items:center;
      padding:4px 10px;
      border-radius:999px;
      background:#edf2ff;
      color:#1d3a8a;
      font-size:.8rem;
      letter-spacing:.06em;
    }

    .table-actions{
      display:flex;
      align-items:center;
      justify-content:flex-end;
      gap:6px;
    }

    @media (max-width:900px){
      .admin-main{
        margin-left:0;
        padding:20px 16px 28px;
      }
      .form-grid{
        grid-template-columns:1fr;
      }
      .page-header{
        flex-direction:column;
        align-items:flex-start;
      }
    }
  </style>
</head>
<body>

<div class="admin-layout">

  <?php include 'sidebar.php'; ?>

  <main class="admin-main">

    <div class="page-header">
      <div>
        <h1 class="page-title">Rewards management</h1>
        <p class="page-subtitle">Create UniHive rewards and control how many points students need to redeem them.</p>
      </div>
      <div class="page-badge">Frontend only · Demo</div>
    </div>

    <!-- Add Reward Card -->
    <section class="card">
      <div class="card-header">
        <div>
          <h2 class="card-title">Add new reward</h2>
          <p class="card-subtitle">Name the reward, set the points cost, and (optionally) add an image.</p>
        </div>
      </div>

      <!-- frontend only: JS will handle submit -->
      <form id="rewardForm">
        <div class="form-grid">
          <div class="form-group">
            <label for="reward_name">Reward name</label>
            <input id="reward_name" type="text" class="input-text" placeholder="e.g. Free coffee at campus café" required>
          </div>

          <div class="form-group">
            <label for="points_cost">Points cost</label>
            <input id="points_cost" type="number" min="1" class="input-number" placeholder="e.g. 150" required>
          </div>

          <div class="form-group">
            <label for="reward_image">Reward image (optional)</label>
            <input id="reward_image" type="file" class="input-file" accept="image/*">
            <small>Image is only used in this page preview (no upload yet).</small>
          </div>
        </div>

        <div style="margin-top:18px; display:flex; gap:10px; justify-content:flex-end;">
          <button type="reset" class="btn btn-ghost">Clear</button>
          <button type="submit" class="btn btn-primary">Save reward</button>
        </div>
      </form>
    </section>

    <!-- Rewards List Card -->
    <section class="card">
      <div class="card-header">
        <div>
          <h2 class="card-title">All rewards</h2>
          <p class="card-subtitle">This is a frontend demo list. Later you can connect it to your database.</p>
        </div>
      </div>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Reward</th>
              <th>Points</th>
              <th>Code</th>
              <th>Created at</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody id="rewardsTableBody">
            <!-- JS will inject rows here -->
          </tbody>
        </table>
      </div>
    </section>

  </main>
</div>

<script>
  // ========= Dummy initial rewards =========
  const rewards = [
    {
      id: 1,
      name: "Free coffee",
      points: 150,
      code: "UH-CF-9X3A1",
      createdAt: "2025-11-30 19:00",
      imageUrl: "assets/reward-coffee.png"
    },
    {
      id: 2,
      name: "Bookstore 10% discount",
      points: 300,
      code: "UH-BK-7F8Q2",
      createdAt: "2025-11-30 19:10",
      imageUrl: "assets/reward-book.png"
    }
  ];

  let nextId = 3;

  const tbody = document.getElementById("rewardsTableBody");
  const form  = document.getElementById("rewardForm");
  const nameInput   = document.getElementById("reward_name");
  const pointsInput = document.getElementById("points_cost");
  const imageInput  = document.getElementById("reward_image");

  function renderRewards(){
    tbody.innerHTML = "";

    if(rewards.length === 0){
      const tr = document.createElement("tr");
      const td = document.createElement("td");
      td.colSpan = 5;
      td.textContent = "No rewards yet. Use the form above to add one.";
      tr.appendChild(td);
      tbody.appendChild(tr);
      return;
    }

    rewards.forEach(reward => {
      const tr = document.createElement("tr");

      // Reward cell
      const tdReward = document.createElement("td");
      const rowDiv = document.createElement("div");
      rowDiv.className = "reward-row";

      const img = document.createElement("img");
      img.className = "reward-img";
      if(reward.imageUrl){
        img.src = reward.imageUrl;
      }

      const infoDiv = document.createElement("div");
      const titleDiv = document.createElement("div");
      titleDiv.className = "reward-name";
      titleDiv.textContent = reward.name;

      const metaDiv = document.createElement("div");
      metaDiv.className = "reward-meta";
      metaDiv.textContent = "ID #" + reward.id;

      infoDiv.appendChild(titleDiv);
      infoDiv.appendChild(metaDiv);

      rowDiv.appendChild(img);
      rowDiv.appendChild(infoDiv);
      tdReward.appendChild(rowDiv);
      tr.appendChild(tdReward);

      // Points cell
      const tdPoints = document.createElement("td");
      const pointsChip = document.createElement("span");
      pointsChip.className = "points-chip";
      pointsChip.textContent = reward.points + " pts";
      tdPoints.appendChild(pointsChip);
      tr.appendChild(tdPoints);

      // Code cell
      const tdCode = document.createElement("td");
      const codeChip = document.createElement("span");
      codeChip.className = "code-chip";
      codeChip.textContent = reward.code;
      tdCode.appendChild(codeChip);
      tr.appendChild(tdCode);

      // Created at cell
      const tdCreated = document.createElement("td");
      const createdSpan = document.createElement("span");
      createdSpan.className = "reward-meta";
      createdSpan.textContent = reward.createdAt;
      tdCreated.appendChild(createdSpan);
      tr.appendChild(tdCreated);

      // Actions cell
      const tdActions = document.createElement("td");
      const actionsDiv = document.createElement("div");
      actionsDiv.className = "table-actions";

      const editBtn = document.createElement("button");
      editBtn.className = "btn btn-outline";
      editBtn.type = "button";
      editBtn.textContent = "Edit";
      editBtn.onclick = () => alert("Frontend demo only. Later this will open edit_reward.php?id=" + reward.id);

      const deleteBtn = document.createElement("button");
      deleteBtn.className = "btn btn-danger";
      deleteBtn.type = "button";
      deleteBtn.textContent = "Delete";
      deleteBtn.onclick = () => {
        const idx = rewards.findIndex(r => r.id === reward.id);
        if(idx !== -1){
          if(confirm("Delete reward \"" + reward.name + "\"?")){
            rewards.splice(idx, 1);
            renderRewards();
          }
        }
      };

      actionsDiv.appendChild(editBtn);
      actionsDiv.appendChild(deleteBtn);
      tdActions.appendChild(actionsDiv);
      tr.appendChild(tdActions);

      tbody.appendChild(tr);
    });
  }

  function generateRewardCode(){
    const prefix = "UH";
    const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    let middle = "";
    for(let i=0;i<5;i++){
      middle += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return `${prefix}-${middle}`;
  }

  function formatDateTime(d){
    const pad = n => n.toString().padStart(2,"0");
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  form.addEventListener("submit", function(e){
    e.preventDefault();

    const name   = nameInput.value.trim();
    const points = parseInt(pointsInput.value,10);

    if(!name || !points || points <= 0){
      alert("Please enter a valid reward name and points.");
      return;
    }

    const code = generateRewardCode();
    const createdAt = formatDateTime(new Date());
    let imageUrl = "";

    const file = imageInput.files[0];
    if(file){
      imageUrl = URL.createObjectURL(file); // preview only
    }

    rewards.unshift({
      id: nextId++,
      name,
      points,
      code,
      createdAt,
      imageUrl
    });

    form.reset();
    renderRewards();
  });

  // initial render
  renderRewards();
</script>

</body>
</html>
