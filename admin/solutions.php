<?php
// ============================================================
//  solutions.php — Manage Solutions
// ============================================================

require_once 'auth.php';
require_once 'db.php';

$action     = $_GET['action']     ?? 'list';
$edit_id    = isset($_GET['id'])         ? (int)$_GET['id']         : 0;
$filter_pid = isset($_GET['problem_id']) ? (int)$_GET['problem_id'] : 0;
$error      = '';

// ── DELETE ──
if ($action === 'delete' && $edit_id) {
    $stmt = $conn->prepare('DELETE FROM solutions WHERE id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $stmt->close();
    header('Location: solutions.php?success=deleted' . ($filter_pid ? "&problem_id=$filter_pid" : ''));
    exit();
}

// ── SAVE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid       = (int)($_POST['problem_id'] ?? 0);
    $type      = $_POST['type'] ?? '';
    $fields    = ['brand_names_hi','brand_names_en','chemical_hi','chemical_en','dose_hi','dose_en','timing_hi','timing_en','method_hi','method_en','warning_hi','warning_en'];
    $vals      = [];
    foreach ($fields as $f) $vals[$f] = trim($_POST[$f] ?? '');
    $sort      = (int)($_POST['sort_order'] ?? 0);
    $active    = isset($_POST['is_active']) ? 1 : 0;

    if (!$pid || !$type || $vals['brand_names_hi'] === '' || $vals['chemical_en'] === '') {
        $error = 'Problem, type, brand names and chemical name are required.';
    } else {
        if ($edit_id) {
            $stmt = $conn->prepare('UPDATE solutions SET problem_id=?,type=?,brand_names_hi=?,brand_names_en=?,chemical_hi=?,chemical_en=?,dose_hi=?,dose_en=?,timing_hi=?,timing_en=?,method_hi=?,method_en=?,warning_hi=?,warning_en=?,sort_order=?,is_active=? WHERE id=?');
            $stmt->bind_param('isssssssssssssiii', $pid,$type,$vals['brand_names_hi'],$vals['brand_names_en'],$vals['chemical_hi'],$vals['chemical_en'],$vals['dose_hi'],$vals['dose_en'],$vals['timing_hi'],$vals['timing_en'],$vals['method_hi'],$vals['method_en'],$vals['warning_hi'],$vals['warning_en'],$sort,$active,$edit_id);
        } else {
            $stmt = $conn->prepare('INSERT INTO solutions (problem_id,type,brand_names_hi,brand_names_en,chemical_hi,chemical_en,dose_hi,dose_en,timing_hi,timing_en,method_hi,method_en,warning_hi,warning_en,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('isssssssssssssii', $pid,$type,$vals['brand_names_hi'],$vals['brand_names_en'],$vals['chemical_hi'],$vals['chemical_en'],$vals['dose_hi'],$vals['dose_en'],$vals['timing_hi'],$vals['timing_en'],$vals['method_hi'],$vals['method_en'],$vals['warning_hi'],$vals['warning_en'],$sort,$active);
        }
        $stmt->execute();
        $stmt->close();
        header('Location: solutions.php?success=' . ($edit_id ? 'updated' : 'added') . ($filter_pid ? "&problem_id=$filter_pid" : ''));
        exit();
    }
}

// ── FETCH for edit ──
$edit_data = null;
if ($action === 'edit' && $edit_id) {
    $stmt = $conn->prepare('SELECT * FROM solutions WHERE id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$filter_pid) $filter_pid = $edit_data['problem_id'] ?? 0;
}

// ── FETCH all problems for dropdown ──
$all_problems = $conn->query('SELECT p.id, p.name_en, p.name_hi, p.category, c.name_en as crop FROM problems p JOIN crops c ON p.crop_id=c.id WHERE p.is_active=1 ORDER BY c.sort_order, p.category, p.sort_order');

// ── FETCH solutions list ──
if ($filter_pid) {
    $stmt = $conn->prepare('SELECT s.*, p.name_en as prob_en FROM solutions s JOIN problems p ON s.problem_id=p.id WHERE s.problem_id=? ORDER BY s.sort_order');
    $stmt->bind_param('i', $filter_pid);
    $stmt->execute();
    $solutions = $stmt->get_result();
    $stmt->close();
    $stmt2 = $conn->prepare('SELECT name_hi, name_en FROM problems WHERE id=?');
    $stmt2->bind_param('i', $filter_pid);
    $stmt2->execute();
    $filter_prob_name = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
} else {
    $solutions = $conn->query('SELECT s.*, p.name_en as prob_en FROM solutions s JOIN problems p ON s.problem_id=p.id ORDER BY p.id, s.sort_order');
}

$success_msg = match($_GET['success'] ?? '') {
    'added'   => 'Solution added successfully!',
    'updated' => 'Solution updated successfully!',
    'deleted' => 'Solution deleted.',
    default   => ''
};

$types = ['pesticide'=>'Pesticide (कीटनाशक)','fungicide'=>'Fungicide (फफूंदनाशक)','herbicide'=>'Herbicide (खरपतवारनाशक)','fertilizer'=>'Fertilizer (उर्वरक)','booster'=>'Growth Booster (ग्रोथ बूस्टर)'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solutions — Kisan Mitra Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi&family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
  :root{--bg:#f6f4f0;--white:#fff;--dark:#1a1208;--saffron:#e8560a;--saffron-pale:#fff3ed;--text:#1a1208;--text-light:#9a8a70;--border:#e8e0d0;--green:#1e7c3a;--green-pale:#eaf7ee;}
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
  .filter-bar{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;gap:12px;flex-wrap:wrap;align-items:center;}
  .filter-bar select{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:'Nunito',sans-serif;background:#fafaf7;color:var(--text);outline:none;}
  table{width:100%;border-collapse:collapse;}
  th{text-align:left;padding:10px 20px;font-size:11px;font-weight:800;color:var(--text-light);letter-spacing:.8px;text-transform:uppercase;background:#fafaf7;border-bottom:1px solid var(--border);}
  td{padding:12px 20px;font-size:14px;border-bottom:1px solid #f4f0e8;vertical-align:middle;}
  tr:last-child td{border-bottom:none;}
  tr:hover td{background:#fafaf7;}
  .type-pill{display:inline-block;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;}
  .type-pesticide{background:#fff3ed;color:var(--saffron);}
  .type-fungicide{background:#f5f0ff;color:#6a2ab0;}
  .type-herbicide{background:var(--green-pale);color:var(--green);}
  .type-fertilizer{background:#fff8e0;color:#d48a00;}
  .type-booster{background:#eef6fb;color:#2e7faa;}
  .field{margin-bottom:18px;}
  .field label{display:block;font-size:12px;font-weight:800;color:var(--text-light);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px;}
  .field input,.field select,.field textarea{width:100%;padding:12px 16px;border:1.5px solid var(--border);border-radius:12px;font-size:15px;font-family:'Nunito',sans-serif;color:var(--text);background:#fafaf7;outline:none;transition:border-color .2s;}
  .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--saffron);background:white;}
  .field textarea{resize:vertical;min-height:80px;line-height:1.6;}
  .field-hint{font-size:12px;color:var(--text-light);margin-top:4px;font-weight:600;}
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
  .section-divider{font-size:12px;font-weight:800;color:var(--text-light);letter-spacing:1.5px;text-transform:uppercase;padding:16px 0 8px;border-bottom:1px solid var(--border);margin-bottom:18px;}
  .checkbox-wrap{display:flex;align-items:center;gap:10px;padding:12px 0;}
  .checkbox-wrap input[type=checkbox]{width:18px;height:18px;cursor:pointer;}
  .checkbox-wrap label{font-size:14px;font-weight:600;cursor:pointer;}
  .empty-state{text-align:center;padding:40px;color:var(--text-light);font-size:14px;font-weight:600;}
  .actions-cell{display:flex;gap:8px;}
  .prob-filter-badge{background:var(--saffron-pale);color:var(--saffron);border:1px solid #f5c0a0;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;}
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
  <a href="solutions.php" class="nav-link active">💊 Solutions</a>
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
    <div>
      <div class="page-title">Solutions</div>
      <?php if ($filter_pid && isset($filter_prob_name)): ?>
      <div style="margin-top:6px;font-size:14px;color:var(--text-light);font-weight:600;">
        Showing solutions for:
        <span style="font-family:'Tiro Devanagari Hindi',serif;font-size:16px;color:var(--text);"><?= htmlspecialchars($filter_prob_name['name_hi']) ?></span>
        / <?= htmlspecialchars($filter_prob_name['name_en']) ?>
      </div>
      <?php endif; ?>
    </div>
    <a href="solutions.php?action=add<?= $filter_pid ? "&problem_id=$filter_pid" : '' ?>" class="btn btn-primary">+ Add solution</a>
  </div>

  <?php if ($success_msg): ?><div class="alert alert-success"><?= $success_msg ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header">
      <?php if ($filter_pid): ?>
        Solutions for this problem (<?= $solutions->num_rows ?>)
        &nbsp;·&nbsp; <a href="solutions.php" style="font-size:13px;color:var(--saffron);font-weight:700;">View all solutions</a>
      <?php else: ?>
        All solutions (<?= $solutions->num_rows ?>)
      <?php endif; ?>
    </div>

    <?php if (!$filter_pid): ?>
    <form method="GET" action="solutions.php">
      <div class="filter-bar">
        <select name="problem_id" onchange="this.form.submit()">
          <option value="">Filter by problem</option>
          <?php $all_problems->data_seek(0); while ($p = $all_problems->fetch_assoc()): ?>
          <option value="<?= $p['id'] ?>" <?= $filter_pid == $p['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['crop']) ?> → <?= ucfirst($p['category']) ?> → <?= htmlspecialchars($p['name_en']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
    </form>
    <?php endif; ?>

    <?php if ($solutions->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Brand names</th>
          <th>Chemical name</th>
          <th>Type</th>
          <th>Problem</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($s = $solutions->fetch_assoc()): ?>
        <tr>
          <td style="font-weight:700;"><?= htmlspecialchars($s['brand_names_en']) ?></td>
          <td style="color:var(--text-light);"><?= htmlspecialchars($s['chemical_en']) ?></td>
          <td><span class="type-pill type-<?= $s['type'] ?>"><?= ucfirst($s['type']) ?></span></td>
          <td><?= htmlspecialchars($s['prob_en']) ?></td>
          <td>
            <div class="actions-cell">
              <a href="solutions.php?action=edit&id=<?= $s['id'] ?>&problem_id=<?= $s['problem_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
              <a href="solutions.php?action=delete&id=<?= $s['id'] ?>&problem_id=<?= $s['problem_id'] ?>"
                 onclick="return confirm('Delete this solution?')"
                 class="btn btn-danger btn-sm">Delete</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">No solutions yet. <a href="solutions.php?action=add<?= $filter_pid ? "&problem_id=$filter_pid" : '' ?>" style="color:var(--saffron);">Add first solution →</a></div>
    <?php endif; ?>
  </div>

  <?php elseif ($action === 'add' || $action === 'edit'): ?>

  <div class="page-header">
    <div class="page-title"><?= $action === 'edit' ? 'Edit solution' : 'Add new solution' ?></div>
    <a href="solutions.php<?= $filter_pid ? "?problem_id=$filter_pid" : '' ?>" class="btn btn-secondary">← Back</a>
  </div>

  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header">Solution details</div>
    <div style="padding:24px;">
      <form method="POST" action="solutions.php?action=<?= $action ?>&id=<?= $edit_id ?>&problem_id=<?= $filter_pid ?>">

        <div class="form-grid">
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
          <div class="field">
            <label>Product type *</label>
            <select name="type" required>
              <option value="">Select type</option>
              <?php foreach ($types as $k => $v): ?>
              <option value="<?= $k ?>" <?= ($edit_data['type'] ?? $_POST['type'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="section-divider">Brand names — what farmer asks at shop</div>
        <div class="form-grid">
          <div class="field">
            <label>Brand names in Hindi *</label>
            <input type="text" name="brand_names_hi" placeholder="Monocil, Dursban, Durmet"
                   value="<?= htmlspecialchars($edit_data['brand_names_hi'] ?? $_POST['brand_names_hi'] ?? '') ?>" required>
            <div class="field-hint">Separate multiple brands with commas</div>
          </div>
          <div class="field">
            <label>Brand names in English *</label>
            <input type="text" name="brand_names_en" placeholder="Monocil, Dursban, Durmet"
                   value="<?= htmlspecialchars($edit_data['brand_names_en'] ?? $_POST['brand_names_en'] ?? '') ?>" required>
          </div>
        </div>

        <div class="section-divider">Chemical name — for verification at shop</div>
        <div class="form-grid">
          <div class="field">
            <label>Chemical name in Hindi</label>
            <input type="text" name="chemical_hi" placeholder="क्लोरपायरीफॉस 20% EC"
                   value="<?= htmlspecialchars($edit_data['chemical_hi'] ?? $_POST['chemical_hi'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Chemical name in English *</label>
            <input type="text" name="chemical_en" placeholder="Chlorpyrifos 20% EC"
                   value="<?= htmlspecialchars($edit_data['chemical_en'] ?? $_POST['chemical_en'] ?? '') ?>" required>
          </div>
        </div>

        <div class="section-divider">Dose, timing and method</div>
        <div class="form-grid">
          <div class="field">
            <label>Dose in Hindi</label>
            <input type="text" name="dose_hi" placeholder="500 मिली प्रति एकड़"
                   value="<?= htmlspecialchars($edit_data['dose_hi'] ?? $_POST['dose_hi'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Dose in English</label>
            <input type="text" name="dose_en" placeholder="500 ml per acre"
                   value="<?= htmlspecialchars($edit_data['dose_en'] ?? $_POST['dose_en'] ?? '') ?>">
          </div>
        </div>
        <div class="form-grid">
          <div class="field">
            <label>Timing in Hindi</label>
            <input type="text" name="timing_hi" placeholder="संक्रमण दिखते ही, सुबह या शाम"
                   value="<?= htmlspecialchars($edit_data['timing_hi'] ?? $_POST['timing_hi'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Timing in English</label>
            <input type="text" name="timing_en" placeholder="At first sign, morning or evening"
                   value="<?= htmlspecialchars($edit_data['timing_en'] ?? $_POST['timing_en'] ?? '') ?>">
          </div>
        </div>
        <div class="form-grid">
          <div class="field">
            <label>Method in Hindi</label>
            <textarea name="method_hi" placeholder="200 लीटर पानी में मिलाकर छिड़काव करें"><?= htmlspecialchars($edit_data['method_hi'] ?? $_POST['method_hi'] ?? '') ?></textarea>
          </div>
          <div class="field">
            <label>Method in English</label>
            <textarea name="method_en" placeholder="Mix in 200 litres of water and spray"><?= htmlspecialchars($edit_data['method_en'] ?? $_POST['method_en'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="section-divider">Warning</div>
        <div class="form-grid">
          <div class="field">
            <label>Warning in Hindi</label>
            <textarea name="warning_hi" placeholder="मधुमक्खियों के लिए हानिकारक..."><?= htmlspecialchars($edit_data['warning_hi'] ?? $_POST['warning_hi'] ?? '') ?></textarea>
          </div>
          <div class="field">
            <label>Warning in English</label>
            <textarea name="warning_en" placeholder="Harmful to bees..."><?= htmlspecialchars($edit_data['warning_en'] ?? $_POST['warning_en'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="form-grid">
          <div class="field">
            <label>Display order</label>
            <input type="number" name="sort_order" min="0"
                   value="<?= $edit_data['sort_order'] ?? $_POST['sort_order'] ?? 0 ?>">
            <div class="field-hint">Option 1, 2, 3... shown to farmer</div>
          </div>
        </div>

        <div class="checkbox-wrap">
          <input type="checkbox" name="is_active" id="is_active" value="1"
                 <?= ($edit_data['is_active'] ?? 1) ? 'checked' : '' ?>>
          <label for="is_active">Active — show this solution to farmers</label>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
          <button type="submit" class="btn btn-primary"><?= $action === 'edit' ? 'Save changes' : 'Add solution' ?></button>
          <a href="solutions.php<?= $filter_pid ? "?problem_id=$filter_pid" : '' ?>" class="btn btn-secondary">Cancel</a>
        </div>

      </form>
    </div>
  </div>

  <?php endif; ?>
</div>
</body>
</html>
