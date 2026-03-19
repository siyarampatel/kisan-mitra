<?php
// ============================================================
//  problems.php — Manage Problems
// ============================================================

require_once 'auth.php';
require_once 'db.php';

$action  = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error   = '';

// ── DELETE ──
if ($action === 'delete' && $edit_id) {
    $stmt = $conn->prepare('DELETE FROM problems WHERE id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $stmt->close();
    header('Location: problems.php?success=deleted');
    exit();
}

// ── SAVE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crop_id    = (int)($_POST['crop_id'] ?? 0);
    $category   = $_POST['category']   ?? '';
    $name_hi    = trim($_POST['name_hi']    ?? '');
    $name_en    = trim($_POST['name_en']    ?? '');
    $sym_hi     = trim($_POST['symptom_hi'] ?? '');
    $sym_en     = trim($_POST['symptom_en'] ?? '');
    $severity   = $_POST['severity']   ?? 'medium';
    $sort       = (int)($_POST['sort_order'] ?? 0);
    $active     = isset($_POST['is_active']) ? 1 : 0;

    if (!$crop_id || !$category || $name_hi === '' || $name_en === '') {
        $error = 'Crop, category, and both Hindi and English names are required.';
    } else {
        if ($edit_id) {
            $stmt = $conn->prepare('UPDATE problems SET crop_id=?,category=?,name_hi=?,name_en=?,symptom_hi=?,symptom_en=?,severity=?,sort_order=?,is_active=? WHERE id=?');
            $stmt->bind_param('issssssiis', $crop_id,$category,$name_hi,$name_en,$sym_hi,$sym_en,$severity,$sort,$active,$edit_id);
        } else {
            $stmt = $conn->prepare('INSERT INTO problems (crop_id,category,name_hi,name_en,symptom_hi,symptom_en,severity,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('issssssi', $crop_id,$category,$name_hi,$name_en,$sym_hi,$sym_en,$severity,$sort,$active);
        }
        $stmt->execute();
        $stmt->close();
        header('Location: problems.php?success=' . ($edit_id ? 'updated' : 'added'));
        exit();
    }
}

// ── FETCH for edit ──
$edit_data = null;
if ($action === 'edit' && $edit_id) {
    $stmt = $conn->prepare('SELECT * FROM problems WHERE id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── FETCH all crops for dropdown ──
$all_crops = $conn->query('SELECT id, name_hi, name_en FROM crops WHERE is_active=1 ORDER BY sort_order');

// ── FILTER ──
$filter_crop = isset($_GET['crop_id']) ? (int)$_GET['crop_id'] : 0;
$filter_cat  = $_GET['category'] ?? '';
$where       = 'WHERE 1=1';
$params      = [];
$types       = '';
if ($filter_crop) { $where .= ' AND p.crop_id = ?'; $params[] = $filter_crop; $types .= 'i'; }
if ($filter_cat)  { $where .= ' AND p.category = ?'; $params[] = $filter_cat; $types .= 's'; }

$sql = "SELECT p.*, c.name_en as crop_name FROM problems p JOIN crops c ON p.crop_id=c.id $where ORDER BY c.sort_order, p.category, p.sort_order";
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $problems = $stmt->get_result();
    $stmt->close();
} else {
    $problems = $conn->query($sql);
}

$success_msg = match($_GET['success'] ?? '') {
    'added'   => 'Problem added successfully!',
    'updated' => 'Problem updated successfully!',
    'deleted' => 'Problem deleted.',
    default   => ''
};

$categories = ['insect'=>'Insect','disease'=>'Disease','weed'=>'Weed','growth'=>'Growth & Nutrition','other'=>'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Problems — Kisan Mitra Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi&family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root { --bg:#f6f4f0;--white:#fff;--dark:#1a1208;--saffron:#e8560a;--saffron-pale:#fff3ed;--text:#1a1208;--text-light:#9a8a70;--border:#e8e0d0;--green:#1e7c3a;--green-pale:#eaf7ee; }
  body { background:var(--bg);font-family:'Nunito',sans-serif;color:var(--text); }
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
  .main { margin-left:220px;padding:32px; }
  .page-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:24px; }
  .page-title { font-size:26px;font-weight:800; }
  .btn { display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;cursor:pointer;border:none;font-family:'Nunito',sans-serif;transition:all .15s; }
  .btn-primary { background:var(--saffron);color:white; }
  .btn-primary:hover { background:#c04008; }
  .btn-secondary { background:var(--white);color:var(--text);border:1.5px solid var(--border); }
  .btn-secondary:hover { border-color:var(--saffron); }
  .btn-danger { background:#ffe5e5;color:#c0392b;border:1.5px solid #f5c0c0; }
  .btn-sm { padding:6px 14px;font-size:12px; }
  .alert { padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:14px;font-weight:600; }
  .alert-success { background:#eaf7ee;color:var(--green);border:1px solid #a8dab8; }
  .alert-error { background:#ffe5e5;color:#c0392b;border:1px solid #f5c0c0; }
  .card { background:var(--white);border-radius:16px;border:1.5px solid var(--border);overflow:hidden;margin-bottom:24px; }
  .card-header { padding:16px 20px;border-bottom:1px solid var(--border);font-size:15px;font-weight:800; }
  .filter-bar { padding:14px 20px;border-bottom:1px solid var(--border);display:flex;gap:12px;flex-wrap:wrap; }
  .filter-bar select { padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:'Nunito',sans-serif;background:#fafaf7;color:var(--text);outline:none; }
  .filter-bar select:focus { border-color:var(--saffron); }
  table { width:100%;border-collapse:collapse; }
  th { text-align:left;padding:10px 20px;font-size:11px;font-weight:800;color:var(--text-light);letter-spacing:.8px;text-transform:uppercase;background:#fafaf7;border-bottom:1px solid var(--border); }
  td { padding:12px 20px;font-size:14px;border-bottom:1px solid #f4f0e8;vertical-align:middle; }
  tr:last-child td { border-bottom:none; }
  tr:hover td { background:#fafaf7; }
  .cat-pill { display:inline-block;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px; }
  .cat-insect { background:#fff3ed;color:var(--saffron); }
  .cat-disease { background:#f5f0ff;color:#6a2ab0; }
  .cat-weed { background:var(--green-pale);color:var(--green); }
  .cat-growth { background:#fff8e0;color:#d48a00; }
  .cat-other { background:#f0f0f0;color:#555; }
  .sev-high { background:#ffe5e5;color:#c0392b; }
  .sev-medium { background:#fff3cd;color:#8a6200; }
  .sev-low { background:var(--green-pale);color:var(--green); }
  .field { margin-bottom:18px; }
  .field label { display:block;font-size:12px;font-weight:800;color:var(--text-light);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px; }
  .field input,.field select,.field textarea { width:100%;padding:12px 16px;border:1.5px solid var(--border);border-radius:12px;font-size:15px;font-family:'Nunito',sans-serif;color:var(--text);background:#fafaf7;outline:none;transition:border-color .2s; }
  .field input:focus,.field select:focus,.field textarea:focus { border-color:var(--saffron);background:white; }
  .field textarea { resize:vertical;min-height:90px;line-height:1.6; }
  .field-hint { font-size:12px;color:var(--text-light);margin-top:4px;font-weight:600; }
  .form-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
  .checkbox-wrap { display:flex;align-items:center;gap:10px;padding:12px 0; }
  .checkbox-wrap input[type=checkbox] { width:18px;height:18px;cursor:pointer; }
  .checkbox-wrap label { font-size:14px;font-weight:600;cursor:pointer; }
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
  <a href="crops.php" class="nav-link">🌾 Crops</a>
  <a href="problems.php" class="nav-link active">⚠ Problems</a>
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
    <div class="page-title">Problems</div>
    <a href="problems.php?action=add" class="btn btn-primary">+ Add new problem</a>
  </div>

  <?php if ($success_msg): ?><div class="alert alert-success"><?= $success_msg ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header">All problems (<?= $problems->num_rows ?>)</div>

    <!-- FILTERS -->
    <form method="GET" action="problems.php">
      <div class="filter-bar">
        <select name="crop_id" onchange="this.form.submit()">
          <option value="">All crops</option>
          <?php $all_crops->data_seek(0); while ($c = $all_crops->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>" <?= $filter_crop == $c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name_en']) ?> (<?= htmlspecialchars($c['name_hi']) ?>)
          </option>
          <?php endwhile; ?>
        </select>
        <select name="category" onchange="this.form.submit()">
          <option value="">All categories</option>
          <?php foreach ($categories as $k => $v): ?>
          <option value="<?= $k ?>" <?= $filter_cat === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($filter_crop || $filter_cat): ?>
        <a href="problems.php" class="btn btn-secondary btn-sm">Clear filters</a>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($problems->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Hindi name</th>
          <th>English name</th>
          <th>Crop</th>
          <th>Category</th>
          <th>Severity</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($p = $problems->fetch_assoc()): ?>
        <tr>
          <td style="font-family:'Tiro Devanagari Hindi',serif;font-size:17px;"><?= htmlspecialchars($p['name_hi']) ?></td>
          <td style="font-weight:700;"><?= htmlspecialchars($p['name_en']) ?></td>
          <td><?= htmlspecialchars($p['crop_name']) ?></td>
          <td><span class="cat-pill cat-<?= $p['category'] ?>"><?= $categories[$p['category']] ?? $p['category'] ?></span></td>
          <td><span class="cat-pill sev-<?= $p['severity'] ?>"><?= ucfirst($p['severity']) ?></span></td>
          <td>
            <div class="actions-cell">
              <a href="solutions.php?problem_id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Solutions</a>
              <a href="problems.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
              <a href="problems.php?action=delete&id=<?= $p['id'] ?>"
                 onclick="return confirm('Delete <?= htmlspecialchars($p['name_en']) ?>? All solutions and tips for it will also be deleted.')"
                 class="btn btn-danger btn-sm">Delete</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">No problems found. <a href="problems.php?action=add" style="color:var(--saffron);">Add your first problem →</a></div>
    <?php endif; ?>
  </div>

  <?php elseif ($action === 'add' || $action === 'edit'): ?>

  <div class="page-header">
    <div class="page-title"><?= $action === 'edit' ? 'Edit problem' : 'Add new problem' ?></div>
    <a href="problems.php" class="btn btn-secondary">← Back to list</a>
  </div>

  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header"><?= $action === 'edit' ? 'Edit problem details' : 'Problem details' ?></div>
    <div style="padding:24px;">
      <form method="POST" action="problems.php?action=<?= $action ?>&id=<?= $edit_id ?>">

        <div class="form-grid">
          <div class="field">
            <label>Crop *</label>
            <select name="crop_id" required>
              <option value="">Select crop</option>
              <?php $all_crops->data_seek(0); while ($c = $all_crops->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit_data['crop_id'] ?? $_POST['crop_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name_en']) ?> (<?= htmlspecialchars($c['name_hi']) ?>)
              </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="field">
            <label>Category *</label>
            <select name="category" required>
              <option value="">Select category</option>
              <?php foreach ($categories as $k => $v): ?>
              <option value="<?= $k ?>" <?= ($edit_data['category'] ?? $_POST['category'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-grid">
          <div class="field">
            <label>Problem name in Hindi *</label>
            <input type="text" name="name_hi" placeholder="माहू (एफिड)"
                   value="<?= htmlspecialchars($edit_data['name_hi'] ?? $_POST['name_hi'] ?? '') ?>" required>
          </div>
          <div class="field">
            <label>Problem name in English *</label>
            <input type="text" name="name_en" placeholder="Aphids"
                   value="<?= htmlspecialchars($edit_data['name_en'] ?? $_POST['name_en'] ?? '') ?>" required>
          </div>
        </div>

        <div class="field">
          <label>Symptom description in Hindi</label>
          <textarea name="symptom_hi" placeholder="पत्तियों पर छोटे काले या हरे कीड़े दिखते हैं..."><?= htmlspecialchars($edit_data['symptom_hi'] ?? $_POST['symptom_hi'] ?? '') ?></textarea>
          <div class="field-hint">1-2 lines only. Helps farmer confirm they selected the right problem.</div>
        </div>

        <div class="field">
          <label>Symptom description in English</label>
          <textarea name="symptom_en" placeholder="Small black or green insects visible on leaves..."><?= htmlspecialchars($edit_data['symptom_en'] ?? $_POST['symptom_en'] ?? '') ?></textarea>
        </div>

        <div class="form-grid">
          <div class="field">
            <label>Severity</label>
            <select name="severity">
              <?php foreach (['high'=>'High (गंभीर)','medium'=>'Medium (मध्यम)','low'=>'Low (सामान्य)'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= ($edit_data['severity'] ?? $_POST['severity'] ?? 'medium') === $k ? 'selected' : '' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Display order</label>
            <input type="number" name="sort_order" min="0"
                   value="<?= $edit_data['sort_order'] ?? $_POST['sort_order'] ?? 0 ?>">
          </div>
        </div>

        <div class="checkbox-wrap">
          <input type="checkbox" name="is_active" id="is_active" value="1"
                 <?= ($edit_data['is_active'] ?? 1) ? 'checked' : '' ?>>
          <label for="is_active">Active — show this problem to farmers</label>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
          <button type="submit" class="btn btn-primary"><?= $action === 'edit' ? 'Save changes' : 'Add problem' ?></button>
          <a href="problems.php" class="btn btn-secondary">Cancel</a>
        </div>

      </form>
    </div>
  </div>

  <?php endif; ?>
</div>
</body>
</html>
