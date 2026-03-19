<?php
// ============================================================
//  dashboard.php — Admin Dashboard
// ============================================================

require_once 'auth.php';
require_once 'db.php';

// Get counts for summary cards
$crops_count     = $conn->query('SELECT COUNT(*) as c FROM crops')->fetch_assoc()['c'];
$problems_count  = $conn->query('SELECT COUNT(*) as c FROM problems')->fetch_assoc()['c'];
$solutions_count = $conn->query('SELECT COUNT(*) as c FROM solutions')->fetch_assoc()['c'];
$tips_count      = $conn->query('SELECT COUNT(*) as c FROM tips')->fetch_assoc()['c'];

// Get recent problems added
$recent = $conn->query('
    SELECT p.name_hi, p.name_en, p.category, p.created_at, c.name_en as crop_name
    FROM problems p
    JOIN crops c ON p.crop_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 5
');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>किसान मित्र — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi&family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg: #f6f4f0;
    --white: #ffffff;
    --dark: #1a1208;
    --saffron: #e8560a;
    --saffron-pale: #fff3ed;
    --text: #1a1208;
    --text-mid: #5a4a30;
    --text-light: #9a8a70;
    --border: #e8e0d0;
    --green: #1e7c3a;
    --green-pale: #eaf7ee;
    --amber: #d48a00;
    --amber-pale: #fff8e0;
    --purple: #6a2ab0;
    --purple-pale: #f5f0ff;
  }

  body { background: var(--bg); font-family: 'Nunito', sans-serif; color: var(--text); min-height: 100vh; }

  /* ── SIDEBAR ── */
  .sidebar {
    position: fixed;
    top: 0; left: 0;
    width: 220px;
    height: 100vh;
    background: var(--dark);
    padding: 24px 0;
    overflow-y: auto;
    z-index: 100;
  }

  .sidebar-logo {
    padding: 0 20px 24px;
    border-bottom: 1px solid #ffffff18;
    margin-bottom: 16px;
  }

  .sidebar-logo-title {
    font-family: 'Tiro Devanagari Hindi', serif;
    font-size: 20px;
    color: white;
    line-height: 1.1;
  }

  .sidebar-logo-sub {
    font-size: 10px;
    font-weight: 800;
    color: var(--saffron);
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-top: 2px;
  }

  .nav-label {
    font-size: 10px;
    font-weight: 800;
    color: #5a4a30;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 8px 20px 4px;
  }

  .nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    color: #9a8a70;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.15s;
    border-left: 3px solid transparent;
  }

  .nav-link:hover { color: white; background: #ffffff0a; }
  .nav-link.active { color: white; border-left-color: var(--saffron); background: #ffffff0f; }

  .nav-link-icon { font-size: 16px; line-height: 1; width: 20px; text-align: center; }

  .sidebar-footer {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    padding: 16px 20px;
    border-top: 1px solid #ffffff18;
  }

  .logout-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #9a8a70;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: color 0.15s;
  }

  .logout-link:hover { color: #e85d5d; }

  /* ── MAIN CONTENT ── */
  .main {
    margin-left: 220px;
    padding: 32px;
    min-height: 100vh;
  }

  .page-header {
    margin-bottom: 28px;
  }

  .page-title {
    font-size: 26px;
    font-weight: 800;
    color: var(--text);
    line-height: 1.2;
  }

  .page-subtitle {
    font-size: 14px;
    color: var(--text-light);
    margin-top: 4px;
    font-weight: 600;
  }

  /* ── STAT CARDS ── */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 32px;
  }

  .stat-card {
    background: var(--white);
    border-radius: 16px;
    border: 1.5px solid var(--border);
    padding: 20px;
    text-decoration: none;
    display: block;
    transition: transform 0.15s, border-color 0.15s;
  }

  .stat-card:hover { transform: translateY(-2px); }

  .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 12px;
    line-height: 1;
  }

  .stat-number {
    font-size: 32px;
    font-weight: 900;
    color: var(--text);
    line-height: 1;
    margin-bottom: 4px;
  }

  .stat-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-light);
    letter-spacing: 0.5px;
  }

  .stat-crops     .stat-icon { background: var(--green-pale); }
  .stat-problems  .stat-icon { background: var(--amber-pale); }
  .stat-solutions .stat-icon { background: var(--saffron-pale); }
  .stat-tips      .stat-icon { background: var(--purple-pale); }

  .stat-crops:hover     { border-color: var(--green); }
  .stat-problems:hover  { border-color: var(--amber); }
  .stat-solutions:hover { border-color: var(--saffron); }
  .stat-tips:hover      { border-color: var(--purple); }

  /* ── QUICK ACTIONS ── */
  .section-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--text);
    margin-bottom: 14px;
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    margin-bottom: 32px;
  }

  .action-card {
    background: var(--white);
    border-radius: 14px;
    border: 1.5px solid var(--border);
    padding: 18px 20px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.15s;
  }

  .action-card:hover { border-color: var(--saffron); box-shadow: 0 4px 16px #e8560a10; transform: translateY(-1px); }

  .action-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    line-height: 1;
  }

  .action-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--text);
    line-height: 1.2;
  }

  .action-sub {
    font-size: 12px;
    color: var(--text-light);
    font-weight: 600;
    margin-top: 2px;
  }

  /* ── RECENT TABLE ── */
  .card {
    background: var(--white);
    border-radius: 16px;
    border: 1.5px solid var(--border);
    overflow: hidden;
  }

  .card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .card-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--text);
  }

  .card-link {
    font-size: 12px;
    font-weight: 700;
    color: var(--saffron);
    text-decoration: none;
  }

  table { width: 100%; border-collapse: collapse; }

  th {
    text-align: left;
    padding: 10px 20px;
    font-size: 11px;
    font-weight: 800;
    color: var(--text-light);
    letter-spacing: 0.8px;
    text-transform: uppercase;
    background: #fafaf7;
    border-bottom: 1px solid var(--border);
  }

  td {
    padding: 12px 20px;
    font-size: 14px;
    color: var(--text);
    border-bottom: 1px solid #f4f0e8;
    vertical-align: middle;
  }

  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #fafaf7; }

  .cat-pill {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 20px;
  }

  .cat-insect  { background: #fff3ed; color: var(--saffron); }
  .cat-disease { background: var(--purple-pale); color: var(--purple); }
  .cat-weed    { background: var(--green-pale); color: var(--green); }
  .cat-growth  { background: var(--amber-pale); color: var(--amber); }
  .cat-other   { background: #f0f0f0; color: #555; }

  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-light);
    font-size: 14px;
    font-weight: 600;
  }

  /* Responsive */
  @media (max-width: 900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .actions-grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-title">किसान मित्र</div>
    <div class="sidebar-logo-sub">Admin Panel</div>
  </div>

  <div class="nav-label">Menu</div>

  <a href="dashboard.php" class="nav-link active">
    <span class="nav-link-icon">⊞</span> Dashboard
  </a>
  <a href="crops.php" class="nav-link">
    <span class="nav-link-icon">🌾</span> Crops
  </a>
  <a href="problems.php" class="nav-link">
    <span class="nav-link-icon">⚠</span> Problems
  </a>
  <a href="solutions.php" class="nav-link">
    <span class="nav-link-icon">💊</span> Solutions
  </a>
  <a href="tips.php" class="nav-link">
    <span class="nav-link-icon">💡</span> Tips
  </a>

  <div class="nav-label" style="margin-top:16px;">Website</div>
  <a href="../index.html" target="_blank" class="nav-link">
    <span class="nav-link-icon">↗</span> View website
  </a>

  <div class="sidebar-footer">
    <a href="logout.php" class="logout-link">
      <span>⎋</span> Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)
    </a>
  </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">

  <div class="page-header">
    <div class="page-title">Dashboard</div>
    <div class="page-subtitle">Welcome back — here is your content overview</div>
  </div>

  <!-- STAT CARDS -->
  <div class="stats-grid">
    <a href="crops.php" class="stat-card stat-crops">
      <div class="stat-icon">🌾</div>
      <div class="stat-number"><?= $crops_count ?></div>
      <div class="stat-label">Total Crops</div>
    </a>
    <a href="problems.php" class="stat-card stat-problems">
      <div class="stat-icon">⚠</div>
      <div class="stat-number"><?= $problems_count ?></div>
      <div class="stat-label">Total Problems</div>
    </a>
    <a href="solutions.php" class="stat-card stat-solutions">
      <div class="stat-icon">💊</div>
      <div class="stat-number"><?= $solutions_count ?></div>
      <div class="stat-label">Total Solutions</div>
    </a>
    <a href="tips.php" class="stat-card stat-tips">
      <div class="stat-icon">💡</div>
      <div class="stat-number"><?= $tips_count ?></div>
      <div class="stat-label">Total Tips</div>
    </a>
  </div>

  <!-- QUICK ACTIONS -->
  <div class="section-title">Quick actions</div>
  <div class="actions-grid">
    <a href="crops.php?action=add" class="action-card">
      <div class="action-icon" style="background:#eaf7ee;">🌾</div>
      <div>
        <div class="action-title">Add new crop</div>
        <div class="action-sub">Add a new crop to the platform</div>
      </div>
    </a>
    <a href="problems.php?action=add" class="action-card">
      <div class="action-icon" style="background:#fff8e0;">⚠</div>
      <div>
        <div class="action-title">Add new problem</div>
        <div class="action-sub">Add a problem for any crop</div>
      </div>
    </a>
    <a href="solutions.php?action=add" class="action-card">
      <div class="action-icon" style="background:#fff3ed;">💊</div>
      <div>
        <div class="action-title">Add new solution</div>
        <div class="action-sub">Add a product recommendation</div>
      </div>
    </a>
    <a href="tips.php?action=add" class="action-card">
      <div class="action-icon" style="background:#f5f0ff;">💡</div>
      <div>
        <div class="action-title">Add new tip</div>
        <div class="action-sub">Add a farmer tip for a problem</div>
      </div>
    </a>
  </div>

  <!-- RECENT PROBLEMS -->
  <div class="section-title">Recently added problems</div>
  <div class="card">
    <div class="card-header">
      <div class="card-title">Latest problems</div>
      <a href="problems.php" class="card-link">View all →</a>
    </div>
    <?php if ($recent->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Problem (Hindi)</th>
          <th>Problem (English)</th>
          <th>Crop</th>
          <th>Category</th>
          <th>Added on</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $recent->fetch_assoc()): ?>
        <tr>
          <td style="font-family:'Tiro Devanagari Hindi',serif;font-size:16px;"><?= htmlspecialchars($row['name_hi']) ?></td>
          <td><?= htmlspecialchars($row['name_en']) ?></td>
          <td><?= htmlspecialchars($row['crop_name']) ?></td>
          <td><span class="cat-pill cat-<?= $row['category'] ?>"><?= ucfirst($row['category']) ?></span></td>
          <td style="color:var(--text-light);"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">No problems added yet. <a href="problems.php?action=add" style="color:var(--saffron);">Add your first problem →</a></div>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
