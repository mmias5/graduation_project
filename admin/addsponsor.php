<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Add Sponsor</title>
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

  --sidebarWidth:260px;
}

*{box-sizing:border-box;margin:0;padding:0}

body{
  margin:0;
  background:var(--paper);
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
}

/* ===== Layout ===== */
.content{
  margin-left:var(--sidebarWidth);
  padding:40px 50px 60px;
}

.page-title{
  font-size:2rem;
  font-weight:800;
  color:var(--ink);
  margin-bottom:8px;
}

.page-subtitle{
  font-size:.96rem;
  color:var(--muted);
  margin-bottom:26px;
}

/* Form shell */
.form-shell{
  background:var(--card);
  padding:30px 32px 34px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  max-width:100%;
}

/* Two-column grid for top fields */
.form-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  column-gap:28px;
  row-gap:18px;
  margin-bottom:22px;
}

.field-label{
  font-weight:700;
  margin-bottom:6px;
  color:var(--ink);
}

.helper-text{
  margin-top:4px;
  font-size:.83rem;
  color:var(--muted);
}

.input-field{
  width:100%;
  padding:11px 14px;
  border-radius:12px;
  border:1px solid #e5e7eb;
  font-size:.95rem;
  outline:none;
}

.input-field:focus{
  border-color:var(--navy);
}

/* Full-width rows */
.full-width{
  grid-column:1 / 3;
}

/* Submit button */
.actions-row{
  margin-top:24px;
  display:flex;
  gap:12px;
}

.primary-btn{
  padding:12px 26px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  font-size:.95rem;
  font-weight:700;
  background:var(--navy);
  color:#ffffff;
}

.primary-btn:hover{
  background:#181b3b;
}

.secondary-link{
  font-size:.9rem;
  color:var(--muted);
  text-decoration:none;
  align-self:center;
}

.secondary-link:hover{
  text-decoration:underline;
}

@media(max-width:900px){
  .form-grid{
    grid-template-columns:1fr;
  }
  .full-width{
    grid-column:1 / 2;
  }
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-title">Add Sponsor</div>
  <div class="page-subtitle">
    Create a new sponsor record and link it to a club or event.  
    When the end date &amp; time passes, their partnership should end.
  </div>

  <!-- For now this posts back to the same page.
       Later you will handle $_POST to insert into the DB and redirect back to sponsors.php -->
  <form method="post" action="addsponsor.php">
    <div class="form-shell">

      <div class="form-grid">

        <div>
          <div class="field-label">Sponsor name</div>
          <input
            type="text"
            name="sponsor_name"
            class="input-field"
            required
            placeholder="e.g., TechCorp"
          >
        </div>

        <div>
          <div class="field-label">Sponsor email</div>
          <input
            type="email"
            name="sponsor_email"
            class="input-field"
            required
            placeholder="name@company.com"
          >
        </div>

        <div class="full-width">
          <div class="field-label">Club or event they’re sponsoring</div>
          <input
            type="text"
            name="sponsoring_for"
            class="input-field"
            required
            placeholder="e.g., AI & Robotics Club or Welcome Week 2025"
          >
          <div class="helper-text">
            This can be a club name or a specific event name.
          </div>
        </div>

        <div>
          <div class="field-label">End date</div>
          <input
            type="date"
            name="end_date"
            class="input-field"
            required
          >
          <div class="helper-text">
            Date when the sponsorship ends.
          </div>
        </div>

        <div>
          <div class="field-label">End time</div>
          <input
            type="time"
            name="end_time"
            class="input-field"
            required
          >
          <div class="helper-text">
            Time on the end date when the sponsorship stops.
          </div>
        </div>

      </div>

      <div class="actions-row">
        <button type="submit" class="primary-btn">
          Save sponsor
        </button>
        <a href="sponsors.php" class="secondary-link">Cancel and go back</a>
      </div>

    </div>
  </form>

  <!--
    BACKEND NOTE (for later):

    - Save sponsor_name, sponsor_email, sponsoring_for, and a combined end_datetime
      into a sponsors table, plus created_at, etc.

    - Set up a scheduled script (cron job) that runs every day/hour:
         DELETE FROM sponsors WHERE end_datetime <= NOW();
      or mark them as inactive instead of deleting.

  -->

</div>

</body>
</html>
