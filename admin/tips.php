<?php
// ============================================================
//  tips.php — Manage Tips
// ============================================================

require_once 'auth.php';
require_once 'db.php';

$action     = $_GET['action']     ?? 'list';
$edit_id    = isset($_GET['id'])         ? (int)$_GET['id']         : 0;
$filter_pid = isset($_GET['problem_id']) ? (int)$_GET['problem_id'] : 0;
$error      = '';

// ── DELETE ──
if ($action === 'delete' && $edit_id) {
    $stmt = $conn->prepare('DELETE FROM tips WHERE id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $stmt->close();
    header('Location: tips.php?success=deleted' . ($filter_pid ? "&problem_id=$filter_pid" : ''));
    exit();
}

// ── SAVE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid    = (int)($_POST['problem_id'] ?? 0);
    $tip_hi = trim($_POST['tip_hi'] ?? '');
    $tip_en = trim($_POST['tip_en'] ?? '');
    $sort   = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;

    if (!$pid || $tip_hi === '' || $tip_en === '') {
        $error = 'Problem and both Hindi and English tip text are required.';
    } else {
        if ($edit_id) {
            $stmt = $conn->prepare('UPDATE tips SET problem_id=?,tip_hi=?,tip_en=?,sort_order=?,is_active=? WHERE id=?');
            $stmt->bind_param('isssii', $pid,$tip_hi,$tip_en,$sort,$active,$edit_id);
        } else {
            $stmt = $conn->prepare('INSERT INTO tips (problem_id,tip_hi,tip_en,sort_order,is_active) VALUES (?,?,?,?,?)');
            $stmt->bind_param('issii', $pid,$tip_hi,$tip_en,$sort,$active);
        }
        $stmt->execute();
        $stmt->close();
        header('Location: tips.php?success=' . ($edit_id ? 'updated' : 'added') . ($filter_pid ? "&problem_id=$filter_pid" : ''));
        exit();
    }
}

// ── FETCH for edit ──
$edit_data = null;
if ($action === 'edit' && $edit_id) {
    $stmt = $conn->prepare('SELECT * FROM tips WHERE id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$filter_pid) $filter_pid = $edit_data['problem_id'] ?? 0;
}

// ── FETCH all problems for dropdown ──
$all_problems = $conn->query('SELECT p.id, p.name_en, p.name_hi, p.category, c.name_en as crop FROM problems p JOIN crops c ON p.crop_id=c.id WHERE p.is_active=1 ORDER BY c.sort_order, p.category, p.sort_order');

// ── FETCH tips list ──
if ($filter_pid) {
    $stmt = $conn->prepare('SELECT t.*, p.name_en as prob_en, p.name_hi as prob_hi FROM tips t JOIN problems p ON t.problem_id=p.id WHERE t.problem_id=? ORDER BY t.sort_order');
    $stmt->bind_param('i', $filter_pid);
    $stmt->execute();
    $tips = $stmt->get_result();
    $stmt->close();
} else {
    $tips = $conn->query('SELECT t.*, p.name_en as prob_en, p.name_hi as prob_hi FROM tips t JOIN problems p ON t.problem_id=p.id ORDER BY p.id, t.sort_order');
}

$success_msg = match($_GET['success'] ?? '') {
    'added'   => 'Tip added successfully!',
    'updated' => 'Tip updated successfully!',
    'deleted' => 'Tip deleted.',
    default   => ''
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tips — Kisan Mitra Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi&family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
  :root{--bg:#f6f4f0;--white:#fff;--dark:#1a1208;--saffron:#e8560a;--saffron-pale:#fff3ed;--text:#1a1208;--text-light:#9a8a70;--border:#e8e0d0;--green:#1e7c3a;--green-pale:#eaf7ee;--purple:#6a2ab0;--purple-pale:#f5f0ff;}
  body{background:var(--bg);font-family:'Nunito',sans-serif;color:var(--text);}
  .sidebar{position:fixed;top:0;left:0;width:220px;height:100vh;background:var(--dark);padding:24px 0;z-index:100;}
  .sidebar-logo{padding:0 20px 24px;border-bottom:1px solid #ffffff18;margin-bottom:16px;}
  .sidebar-logo-title{font-family:'Tiro Devanagari Hindi',serif;font-size:20px;color:white;}
  .sidebar-logo-sub{font-size:10px;font-weight:800;color:var(--saffron);letter-spacing:1.5px;text-transform:uppercase;margin-top:2px;}
  .nav-label{font-size:10px;font-weight:800;color:#5a4a30;letter-spacing:1.5px;text-transform:uppercase;padding:8px 20px 4px;}
  .nav-link{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#9a8a70;text-decoration:none;font-size:14px;font-weight:600;border-left:3px solid transparent;transition:all .15s;}
  .nav-link:hover{color:white;background:#ffffff0a;}
  .nav-link.active{color:white;border-left-color:var(--saffron);background:#ffffff0f;}
  .sidebar-footer{position:absolute;bottom:0;left:0;right:0;padding:16px 20px;border-top:1px solid #ffffff18;}
  .logout-link{color:#9a8a70;text-decoration:none;font-size:13px;font-weight:600;}
  .main{margin-left:220px;padding:32px;}
  .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}
  .page-title{font-size:26px;font-weight:800;}
  .btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;cursor:pointer;border:none;font-family:'Nunito',sans-serif;transition:all .15s;}
  .btn-primary{background:var(--saffron);color:white;}
  .btn-primary:hover{background:#c04008;}
  .btn-secondary{background:var(--white);color:var(--text);border:1.5px solid var(--border);}
  .btn-secondary:hover{border-color:var(--saffron);}
  .btn-danger{background:#ffe5e5;color:#c0392b;border:1.5px solid #f5c0c0;}
  .btn-sm{padding:6px 14px;font-size:12px;}
  .alert{padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:14px;font-weight:600;}
  .alert-success{background:#eaf7ee;color:var(--green);border:1px solid #a8dab8;}
  .alert-error{background:#ffe5e5;color:#c0392b;border:1px solid #f5c0c0;}
  .card{background:var(--white);border-radius:16px;border:1.5px solid var(--border);overflow:hidden;margin-bottom:24px;}
  .card-header{padding:16px 20px;border-bottom:1px solid var(--border);font-size:15px;font-weight:800;}
  .filter-bar{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;gap:12px;}
  .filter-bar select{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:'Nunito',sans-serif;background:#fafaf7;color:var(--text);outline:none;}
  table{width:100%;border-collapse:collapse;}
  th{text-align:left;padding:10px 20px;font-size:11px;font-weight:800;color:var(--text-light);letter-spacing:.8px;text-transform:uppercase;background:#fafaf7;border-bottom:1px solid var(--border);}
  td{padding:12px 20px;font-size:14px;border-bottom:1px solid #f4f0e8;vertical-align:top;}
  tr:last-child td{border-bottom:none;}
  tr:hover td{background:#fafaf7;}
  .tip-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:16px;color:var(--text);line-height:1.5;margin-bottom:4px;}
  .tip-en{font-size:13px;color:var(--text-light);font-weight:600;line-height:1.4;}
  .field{margin-bottom:18px;}
  .field label{display:block;font-size:12px;font-weight:800;color:var(--text-light);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px;}
  .field input,.field select,.field textarea{width:100%;padding:12px 16px;border:1.5px solid var(--border);border-radius:12px;font-size:15px;font-family:'Nunito',sans-serif;color:var(--text);background:#fafaf7;outline:none;transition:border-color .2s;}
  .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--saffron);background:white;}
  .field textarea{resize:vertical;min-height:100px;line-height:1.6;}
  .field-hint{font-size:12px;color:var(--text-light);margin-top:4px;font-weight:600;}
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
  .checkbox-wrap{display:flex;align-items:center;gap:10px;padding:12px 0;}
  .checkbox-wrap input[type=checkbox]{width:18px;height:18px;cursor:pointer;}
  .checkbox-wrap label{font-size:14px;font-weight:600;cursor:pointer;}
  .empty-state{text-align:center;padding:40px;color:var(--text-light);font-size:14px;font-weight:600;}
  .actions-cell{display:flex;gap:8px;margin-top:6px;}
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
  <a href="problems.php" class="nav-link">⚠ Problems</a>
  <a href="solutions.php" class="nav-link">💊 Solutions</a>
  <a href="tips.php" class="nav-link active">💡 Tips</a>
  <div class="nav-label" style="margin-top:16px;">Website</div>
  <a href="../index.html" target="_blank" class="nav-link">↗ View website</a>
  <div class="sidebar-footer">
    <a href="logout.php" class="logout-link">⎋ Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
  </div>
</div>

<div class="main">

  <?php if ($action === 'list'): ?>

  <div class="page-header">
    <div class="page-title">Tips</div>
    <a href="tips.php?action=add<?= $filter_pid ? "&problem_id=$filter_pid" : '' ?>" class="btn btn-primary">+ Add tip</a>
  </div>

  <?php if ($success_msg): ?><div class="alert alert-success"><?= $success_msg ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header">All tips (<?= $tips->num_rows ?>)</div>

    <form method="GET" action="tips.php">
      <div class="filter-bar">
        <select name="problem_id" onchange="this.form.submit()">
          <option value="">Filter by problem</option>
          <?php $all_problems->data_seek(0); while ($p = $all_problems->fetch_assoc()): ?>
          <option value="<?= $p['id'] ?>" <?= $filter_pid == $p['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['crop']) ?> → <?= ucfirst($p['category']) ?> → <?= htmlspecialchars($p['name_en']) ?>
          </option>
          <?php endwhile; ?>
        </select>
        <?php if ($filter_pid): ?><a href="tips.php" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
      </div>
    </form>

    <?php if ($tips->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Tip content</th>
          <th>Problem</th>
          <th>Order</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($t = $tips->fetch_assoc()): ?>
        <tr>
          <td>
            <div class="tip-hi"><?= htmlspecialchars($t['tip_hi']) ?></div>
            <div class="tip-en"><?= htmlspecialchars($t['tip_en']) ?></div>
          </td>
          <td>
            <span style="font-family:'Tiro Devanagari Hindi',serif;font-size:15px;"><?= htmlspecialchars($t['prob_hi']) ?></span><br>
            <span style="font-size:12px;color:var(--text-light);font-weight:600;"><?= htmlspecialchars($t['prob_en']) ?></span>
          </td>
          <td><?= $t['sort_order'] ?></td>
          <td>
            <div class="actions-cell">
              <a href="tips.php?action=edit&id=<?= $t['id'] ?>&problem_id=<?= $t['problem_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
              <a href="tips.php?action=delete&id=<?= $t['id'] ?>&problem_id=<?= $t['problem_id'] ?>"
                 onclick="return confirm('Delete this tip?')"
                 class="btn btn-danger btn-sm">Delete</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">No tips yet. <a href="tips.php?action=add" style="color:var(--saffron);">Add your first tip →</a></div>
    <?php endif; ?>
  </div>

  <?php elseif ($action === 'add' || $action === 'edit'): ?>

  <div class="page-header">
    <div class="page-title"><?= $action === 'edit' ? 'Edit tip' : 'Add new tip' ?></div>
    <a href="tips.php<?= $filter_pid ? "?problem_id=$filter_pid" : '' ?>" class="btn btn-secondary">← Back</a>
  </div>

  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header">Tip details</div>
    <div style="padding:24px;">
      <form method="POST" action="tips.php?action=<?= $action ?>&id=<?= $edit_id ?>&problem_id=<?= $filter_pid ?>">

        <div class="field">
          <label>Problem *</label>
          <select name="problem_id" required>
            <option value="">Select problem</option>
            <?php $all_problems->data_seek(0); while ($p = $all_problems->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>"
              <?= ($edit_data['problem_id'] ?? $_POST['problem_id'] ?? $filter_pid) == $p['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['crop']) ?> → <?= ucfirst($p['category']) ?> → <?= htmlspecialchars($p['name_en']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-grid">
          <div class="field">
            <label>Tip in Hindi *</label>
            <textarea name="tip_hi" placeholder="छिड़काव सुबह जल्दी करें जब हवा न चल रही हो..."><?= htmlspecialchars($edit_data['tip_hi'] ?? $_POST['tip_hi'] ?? '') ?></textarea>
            <div class="field-hint">Write in simple Hindi that any farmer can understand</div>
          </div>
          <div class="field">
            <label>Tip in English *</label>
            <textarea name="tip_en" placeholder="Spray early morning when there is no wind..."><?= htmlspecialchars($edit_data['tip_en'] ?? $_POST['tip_en'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="field" style="max-width:200px;">
          <label>Display order</label>
          <input type="number" name="sort_order" min="0"
                 value="<?= $edit_data['sort_order'] ?? $_POST['sort_order'] ?? 0 ?>">
          <div class="field-hint">Lower = shown first</div>
        </div>

        <div class="checkbox-wrap">
          <input type="checkbox" name="is_active" id="is_active" value="1"
                 <?= ($edit_data['is_active'] ?? 1) ? 'checked' : '' ?>>
          <label for="is_active">Active — show this tip to farmers</label>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
          <button type="submit" class="btn btn-primary"><?= $action === 'edit' ? 'Save changes' : 'Add tip' ?></button>
          <a href="tips.php<?= $filter_pid ? "?problem_id=$filter_pid" : '' ?>" class="btn btn-secondary">Cancel</a>
        </div>

      </form>
    </div>
  </div>

  <?php endif; ?>
</div>
</body>
</html>
