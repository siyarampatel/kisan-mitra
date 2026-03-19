<?php
// ============================================================
//  crops.php — Manage Crops
// ============================================================

require_once 'auth.php';
require_once 'db.php';

$action  = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error   = '';

// ── DELETE ──
if ($action === 'delete' && $edit_id) {
    $stmt = $conn->prepare('DELETE FROM crops WHERE id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $stmt->close();
    header('Location: crops.php?success=deleted');
    exit();
}

// ── SAVE (add or edit) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_hi   = trim($_POST['name_hi']   ?? '');
    $name_en   = trim($_POST['name_en']   ?? '');
    $season_hi = trim($_POST['season_hi'] ?? '');
    $season_en = trim($_POST['season_en'] ?? '');
    $sort      = (int)($_POST['sort_order'] ?? 0);
    $active    = isset($_POST['is_active']) ? 1 : 0;

    if ($name_hi === '' || $name_en === '') {
        $error = 'Crop name in both Hindi and English is required.';
    } else {
        if ($edit_id) {
            $stmt = $conn->prepare('UPDATE crops SET name_hi=?, name_en=?, season_hi=?, season_en=?, sort_order=?, is_active=? WHERE id=?');
            $stmt->bind_param('ssssiis', $name_hi, $name_en, $season_hi, $season_en, $sort, $active, $edit_id);
        } else {
            $stmt = $conn->prepare('INSERT INTO crops (name_hi, name_en, season_hi, season_en, sort_order, is_active) VALUES (?,?,?,?,?,?)');
            $stmt->bind_param('ssssii', $name_hi, $name_en, $season_hi, $season_en, $sort, $active);
        }
        $stmt->execute();
        $stmt->close();
        header('Location: crops.php?success=' . ($edit_id ? 'updated' : 'added'));
        exit();
    }
}

// ── FETCH for edit form ──
$edit_data = null;
if ($action === 'edit' && $edit_id) {
    $stmt = $conn->prepare('SELECT * FROM crops WHERE id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── FETCH all crops for list ──
$crops = $conn->query('SELECT * FROM crops ORDER BY sort_order, id');

$success_msg = match($_GET['success'] ?? '') {
    'added'   => 'Crop added successfully!',
    'updated' => 'Crop updated successfully!',
    'deleted' => 'Crop deleted.',
    default   => ''
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crops — Kisan Mitra Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi&family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg:#f6f4f0;--white:#fff;--dark:#1a1208;--saffron:#e8560a;
    --saffron-pale:#fff3ed;--text:#1a1208;--text-light:#9a8a70;
    --border:#e8e0d0;--green:#1e7c3a;--green-pale:#eaf7ee;
  }
  body { background:var(--bg); font-family:'Nunito',sans-serif; color:var(--text); }
  .sidebar { position:fixed;top:0;left:0;width:220px;height:100vh;background:var(--dark);padding:24px 0;z-index:100; }
  .sidebar-logo { padding:0 20px 24px;border-bottom:1px solid #ffffff18;margin-bottom:16px; }
  .sidebar-logo-title { font-family:'Tiro Devanagari Hindi',serif;font-size:20px;color:white; }
  .sidebar-logo-sub { font-size:10px;font-weight:800;color:var(--saffron);letter-spacing:1.5px;text-transform:uppercase;margin-top:2px; }
  .nav-label { font-size:10px;font-weight:800;color:#5a4a30;letter-spacing:1.5px;text-transform:uppercase;padding:8px 20px 4px; }
  .nav-link { display:flex;align-items:center;gap:10px;padding:10px 20px;color:#9a8a70;text-decoration:none;font-size:14px;font-weight:600;border-left:3px solid transparent;transition:all .15s; }
  .nav-link:hover { color:white;background:#ffffff0a; }
  .nav-link.active { color:white;border-left-color:var(--saffron);background:#ffffff0f; }
  .sidebar-footer { position:absolute;bottom:0;left:0;right:0;padding:16px 20px;border-top:1px solid #ffffff18; }
  .logout-link { color:#9a8a70;text-decoration:none;font-size:13px;font-weight:600; }
  .logout-link:hover { color:#e85d5d; }
  .main { margin-left:220px;padding:32px; }
  .page-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:24px; }
  .page-title { font-size:26px;font-weight:800; }
  .btn { display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;cursor:pointer;border:none;font-family:'Nunito',sans-serif;transition:all .15s; }
  .btn-primary { background:var(--saffron);color:white; }
  .btn-primary:hover { background:#c04008; }
  .btn-secondary { background:var(--white);color:var(--text);border:1.5px solid var(--border); }
  .btn-secondary:hover { border-color:var(--saffron); }
  .btn-danger { background:#ffe5e5;color:#c0392b;border:1.5px solid #f5c0c0; }
  .btn-danger:hover { background:#ffd0d0; }
  .btn-sm { padding:6px 14px;font-size:12px; }
  .alert { padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:14px;font-weight:600; }
  .alert-success { background:#eaf7ee;color:var(--green);border:1px solid #a8dab8; }
  .alert-error { background:#ffe5e5;color:#c0392b;border:1px solid #f5c0c0; }
  .card { background:var(--white);border-radius:16px;border:1.5px solid var(--border);overflow:hidden;margin-bottom:24px; }
  .card-header { padding:16px 20px;border-bottom:1px solid var(--border);font-size:15px;font-weight:800; }
  table { width:100%;border-collapse:collapse; }
  th { text-align:left;padding:10px 20px;font-size:11px;font-weight:800;color:var(--text-light);letter-spacing:.8px;text-transform:uppercase;background:#fafaf7;border-bottom:1px solid var(--border); }
  td { padding:14px 20px;font-size:14px;border-bottom:1px solid #f4f0e8;vertical-align:middle; }
  tr:last-child td { border-bottom:none; }
  tr:hover td { background:#fafaf7; }
  .status-pill { display:inline-block;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px; }
  .status-active { background:var(--green-pale);color:var(--green); }
  .status-hidden { background:#f0f0f0;color:#777; }
  .field { margin-bottom:18px; }
  .field label { display:block;font-size:12px;font-weight:800;color:var(--text-light);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px; }
  .field input, .field select {
    width:100%;padding:12px 16px;border:1.5px solid var(--border);border-radius:12px;
    font-size:15px;font-family:'Nunito',sans-serif;color:var(--text);background:#fafaf7;outline:none;transition:border-color .2s;
  }
  .field input:focus, .field select:focus { border-color:var(--saffron);background:white; }
  .field-hint { font-size:12px;color:var(--text-light);margin-top:4px;font-weight:600; }
  .form-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
  .checkbox-wrap { display:flex;align-items:center;gap:10px;padding:12px 0; }
  .checkbox-wrap input[type=checkbox] { width:18px;height:18px;cursor:pointer; }
  .checkbox-wrap label { font-size:14px;font-weight:600;color:var(--text);cursor:pointer; }
  .empty-state { text-align:center;padding:40px;color:var(--text-light);font-size:14px;font-weight:600; }
  .actions-cell { display:flex;gap:8px; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-title">किसान मित्र</div>
    <div class="sidebar-logo-sub">Admin Panel</div>
  </div>
  <div class="nav-label">Menu</div>
  <a href="dashboard.php" class="nav-link">⊞ Dashboard</a>
  <a href="crops.php" class="nav-link active">🌾 Crops</a>
  <a href="problems.php" class="nav-link">⚠ Problems</a>
  <a href="solutions.php" class="nav-link">💊 Solutions</a>
  <a href="tips.php" class="nav-link">💡 Tips</a>
  <div class="nav-label" style="margin-top:16px;">Website</div>
  <a href="../index.html" target="_blank" class="nav-link">↗ View website</a>
  <div class="sidebar-footer">
    <a href="logout.php" class="logout-link">⎋ Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
  </div>
</div>

<div class="main">

  <?php if ($action === 'list'): ?>

  <div class="page-header">
    <div class="page-title">Crops</div>
    <a href="crops.php?action=add" class="btn btn-primary">+ Add new crop</a>
  </div>

  <?php if ($success_msg): ?><div class="alert alert-success"><?= $success_msg ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header">All crops (<?= $crops->num_rows ?>)</div>
    <?php if ($crops->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Hindi name</th>
          <th>English name</th>
          <th>Season</th>
          <th>Order</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($crop = $crops->fetch_assoc()): ?>
        <tr>
          <td style="font-family:'Tiro Devanagari Hindi',serif;font-size:18px;"><?= htmlspecialchars($crop['name_hi']) ?></td>
          <td style="font-weight:700;"><?= htmlspecialchars($crop['name_en']) ?></td>
          <td><?= htmlspecialchars($crop['season_en']) ?></td>
          <td><?= $crop['sort_order'] ?></td>
          <td><span class="status-pill <?= $crop['is_active'] ? 'status-active' : 'status-hidden' ?>"><?= $crop['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td>
            <div class="actions-cell">
              <a href="crops.php?action=edit&id=<?= $crop['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
              <a href="crops.php?action=delete&id=<?= $crop['id'] ?>"
                 onclick="return confirm('Delete <?= htmlspecialchars($crop['name_en']) ?>? All problems and solutions under it will also be deleted.')"
                 class="btn btn-danger btn-sm">Delete</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">No crops yet. <a href="crops.php?action=add" style="color:var(--saffron);">Add your first crop →</a></div>
    <?php endif; ?>
  </div>

  <?php elseif ($action === 'add' || $action === 'edit'): ?>

  <div class="page-header">
    <div class="page-title"><?= $action === 'edit' ? 'Edit crop' : 'Add new crop' ?></div>
    <a href="crops.php" class="btn btn-secondary">← Back to list</a>
  </div>

  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header"><?= $action === 'edit' ? 'Edit crop details' : 'Crop details' ?></div>
    <div style="padding:24px;">
      <form method="POST" action="crops.php?action=<?= $action ?>&id=<?= $edit_id ?>">

        <div class="form-grid">
          <div class="field">
            <label>Crop name in Hindi *</label>
            <input type="text" name="name_hi" placeholder="गेहूँ"
                   value="<?= htmlspecialchars($edit_data['name_hi'] ?? $_POST['name_hi'] ?? '') ?>" required>
            <div class="field-hint">Hindi name shown to farmers</div>
          </div>
          <div class="field">
            <label>Crop name in English *</label>
            <input type="text" name="name_en" placeholder="Wheat"
                   value="<?= htmlspecialchars($edit_data['name_en'] ?? $_POST['name_en'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-grid">
          <div class="field">
            <label>Season in Hindi</label>
            <input type="text" name="season_hi" placeholder="रबी फसल"
                   value="<?= htmlspecialchars($edit_data['season_hi'] ?? $_POST['season_hi'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Season in English</label>
            <input type="text" name="season_en" placeholder="Rabi Crop"
                   value="<?= htmlspecialchars($edit_data['season_en'] ?? $_POST['season_en'] ?? '') ?>">
          </div>
        </div>

        <div class="field" style="max-width:200px;">
          <label>Display order</label>
          <input type="number" name="sort_order" min="0"
                 value="<?= $edit_data['sort_order'] ?? $_POST['sort_order'] ?? 0 ?>">
          <div class="field-hint">Lower number = shown first</div>
        </div>

        <div class="checkbox-wrap">
          <input type="checkbox" name="is_active" id="is_active" value="1"
                 <?= ($edit_data['is_active'] ?? 1) ? 'checked' : '' ?>>
          <label for="is_active">Active — show this crop to farmers</label>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
          <button type="submit" class="btn btn-primary"><?= $action === 'edit' ? 'Save changes' : 'Add crop' ?></button>
          <a href="crops.php" class="btn btn-secondary">Cancel</a>
        </div>

      </form>
    </div>
  </div>

  <?php endif; ?>

</div>
</body>
</html>
