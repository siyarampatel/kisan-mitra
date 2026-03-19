<?php
// ============================================================
//  specific.php — Problem Categories + Specific Problems
//  Handles both: category selection AND problem list in one page
// ============================================================

require_once 'admin/db.php';

$lang    = $_GET['lang']     ?? 'hi';
$lang    = in_array($lang, ['hi', 'en']) ? $lang : 'hi';
$crop_id = isset($_GET['crop']) ? (int)$_GET['crop'] : 0;
$cat     = $_GET['cat'] ?? '';

$valid_cats = ['insect', 'disease', 'weed', 'growth', 'other'];

// Fetch crop details
$crop = null;
if ($crop_id) {
    $stmt = $conn->prepare('SELECT * FROM crops WHERE id = ? AND is_active = 1');
    $stmt->bind_param('i', $crop_id);
    $stmt->execute();
    $crop = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$crop) {
    header('Location: home.php?lang=' . $lang);
    exit();
}

// If category selected — fetch problems for that category
$problems = [];
if ($cat && in_array($cat, $valid_cats)) {
    $stmt = $conn->prepare('SELECT * FROM problems WHERE crop_id = ? AND category = ? AND is_active = 1 ORDER BY sort_order, id');
    $stmt->bind_param('is', $crop_id, $cat);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $problems[] = $row;
    $stmt->close();
}

$cat_meta = [
    'insect'  => ['hi' => 'कीट समस्या',           'en' => 'Insect Problems',   'icon' => '🐛', 'color' => '#e8560a', 'bg' => '#fff3ed'],
    'disease' => ['hi' => 'रोग समस्या',            'en' => 'Disease Problems',  'icon' => '🍂', 'color' => '#7c3aed', 'bg' => '#f5f0ff'],
    'weed'    => ['hi' => 'खरपतवार समस्या',        'en' => 'Weed Problems',     'icon' => '🌿', 'color' => '#1e7c3a', 'bg' => '#eaf7ee'],
    'growth'  => ['hi' => 'पोषण व वृद्धि समस्या',  'en' => 'Growth & Nutrition','icon' => '🌱', 'color' => '#d48a00', 'bg' => '#fff8e0'],
    'other'   => ['hi' => 'अन्य समस्या',           'en' => 'Other Problems',    'icon' => '❓', 'color' => '#2e7faa', 'bg' => '#eef6fb'],
];

$sev_map = [
    'hi' => ['high' => 'गंभीर', 'medium' => 'मध्यम', 'low' => 'सामान्य'],
    'en' => ['high' => 'Severe','medium' => 'Moderate','low' => 'Mild'],
];

$crop_name = $lang === 'hi' ? $crop['name_hi'] : $crop['name_en'];
$page_title = $cat
    ? ($lang === 'hi' ? $cat_meta[$cat]['hi'] : $cat_meta[$cat]['en'])
    : ($lang === 'hi' ? 'समस्या चुनें' : 'Select Problem');
?>
<!DOCTYPE html>
<html lang="<?= $lang === 'hi' ? 'hi' : 'en' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>किसान मित्र — <?= htmlspecialchars($page_title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi:ital@0;1&family=Nunito:wght@400;600;800;900&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
  :root{
    --saffron:#e8560a;--saffron-pale:#fff3ed;--saffron-border:#f5c0a0;
    --green:#1e7c3a;--green-pale:#eaf7ee;
    --earth:#7a4e28;--earth-pale:#fdf3e8;--earth-border:#e0c8a0;
    --white:#ffffff;--page-bg:#f6f4f0;--text-dark:#1a1208;--text-mid:#5a4a30;--text-light:#9a8a70;
    --border:#e8e0d0;--topbar-bg:#1a1208;
  }
  body{min-height:100vh;background:var(--page-bg);font-family:'Nunito',sans-serif;color:var(--text-dark);padding-bottom:48px;-webkit-font-smoothing:antialiased;}
  .topbar{background:var(--topbar-bg);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
  .topbar-left{display:flex;align-items:center;gap:10px;text-decoration:none;}
  .back-btn{width:36px;height:36px;background:#ffffff18;border-radius:10px;display:flex;align-items:center;justify-content:center;border:1px solid #ffffff20;flex-shrink:0;}
  .topbar-brand{font-family:'Tiro Devanagari Hindi',serif;font-size:18px;color:white;line-height:1.1;}
  .topbar-brand-sub{display:block;font-family:'Nunito',sans-serif;font-size:10px;color:var(--saffron);font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-top:1px;}
  .lang-toggle{display:flex;background:#ffffff14;border-radius:22px;overflow:hidden;border:1px solid #ffffff20;}
  .lang-toggle a{padding:7px 14px;color:#c0b090;font-size:12px;font-weight:800;text-decoration:none;transition:all .2s;}
  .lang-toggle a.active{background:var(--saffron);color:white;border-radius:22px;}
  .breadcrumb{display:flex;align-items:center;gap:6px;padding:14px 20px 0;flex-wrap:wrap;}
  .bc{font-size:12px;color:var(--text-light);font-weight:700;}
  .bc.active{color:var(--saffron);}
  .bc-sep{font-size:12px;color:var(--border);font-weight:700;}
  .crop-badge{margin:14px 20px 0;display:inline-flex;align-items:center;gap:10px;background:var(--white);border:1.5px solid var(--border);border-radius:40px;padding:8px 16px 8px 10px;}
  .crop-badge-icon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;line-height:1;}
  .crop-badge-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:18px;color:var(--text-dark);}
  .crop-badge-en{font-size:11px;font-weight:700;color:var(--text-light);margin-top:1px;}
  .section-head{padding:20px 20px 14px;display:flex;align-items:flex-end;justify-content:space-between;}
  .section-title{font-family:'Tiro Devanagari Hindi',serif;font-size:24px;color:var(--text-dark);}
  .section-en{font-size:12px;font-weight:800;color:var(--text-light);letter-spacing:1px;text-transform:uppercase;margin-top:3px;}
  .count-pill{font-size:11px;font-weight:800;padding:4px 12px;border-radius:20px;background:var(--saffron-pale);color:var(--saffron);border:1.5px solid var(--saffron-border);white-space:nowrap;flex-shrink:0;}

  /* CATEGORY CARDS */
  .cat-list{padding:0 20px;display:flex;flex-direction:column;gap:12px;}
  .cat-card{background:var(--white);border-radius:18px;border:1.5px solid var(--border);padding:16px 18px;display:flex;align-items:center;gap:16px;text-decoration:none;transition:transform .18s,border-color .18s,box-shadow .18s;border-left-width:4px;}
  .cat-card:hover{transform:translateX(4px);box-shadow:0 4px 20px #00000010;}
  .cat-card:active{transform:scale(.98);}
  .cat-icon{width:62px;height:62px;border-radius:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:28px;line-height:1;}
  .cat-text{flex:1;}
  .cat-name-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:22px;color:var(--text-dark);line-height:1.2;}
  .cat-name-en{font-size:12px;font-weight:700;color:var(--text-mid);margin-top:3px;}
  .cat-desc{font-family:'Tiro Devanagari Hindi',serif;font-size:13px;color:var(--text-light);margin-top:4px;line-height:1.4;}
  .cat-arrow{font-size:22px;color:var(--border);transition:transform .2s,color .2s;flex-shrink:0;}
  .cat-card:hover .cat-arrow{transform:translateX(4px);}

  /* PROBLEM CARDS */
  .prob-list{padding:0 20px;display:flex;flex-direction:column;gap:10px;}
  .prob-card{background:var(--white);border-radius:16px;border:1.5px solid var(--border);padding:15px 18px;display:flex;align-items:center;gap:14px;text-decoration:none;transition:transform .18s,border-color .18s,box-shadow .18s;}
  .prob-card:hover{transform:translateX(4px);border-color:var(--saffron);box-shadow:0 4px 16px #e8560a12;}
  .prob-card:active{transform:scale(.98);}
  .prob-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
  .prob-text{flex:1;}
  .prob-name-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:19px;color:var(--text-dark);line-height:1.2;}
  .prob-name-en{font-size:12px;font-weight:700;color:var(--text-mid);margin-top:3px;}
  .sev-pill{font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;flex-shrink:0;}
  .sev-high{background:#ffe5e5;color:#c0392b;}
  .sev-medium{background:#fff3cd;color:#8a6200;}
  .sev-low{background:var(--green-pale);color:var(--green);}
  .prob-arrow{font-size:20px;color:var(--border);transition:transform .2s,color .2s;flex-shrink:0;}
  .prob-card:hover .prob-arrow{transform:translateX(3px);color:var(--saffron);}

  .cat-header{margin:14px 20px 0;border-radius:18px;padding:16px 18px;display:flex;align-items:center;gap:14px;}
  .cat-header-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0;line-height:1;}

  .empty-state{text-align:center;padding:48px 20px;color:var(--text-light);font-size:15px;font-weight:600;}
  .empty-state a{color:var(--saffron);text-decoration:none;font-weight:700;}

  @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  .anim{animation:fadeUp .45s cubic-bezier(.22,1,.36,1) both;}
  .anim-d1{animation-delay:.06s}.anim-d2{animation-delay:.12s}.anim-d3{animation-delay:.18s}.anim-d4{animation-delay:.24s}.anim-d5{animation-delay:.3s}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <a class="topbar-left" href="<?= $cat ? "specific.php?lang=$lang&crop=$crop_id" : "home.php?lang=$lang" ?>">
    <div class="back-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
        <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <div class="topbar-brand">
      <?= htmlspecialchars($page_title) ?>
      <span class="topbar-brand-sub"><?= $cat ? ($lang === 'hi' ? $cat_meta[$cat]['en'] : $cat_meta[$cat]['hi']) : 'Select Problem' ?></span>
    </div>
  </a>
  <div class="lang-toggle">
    <a href="specific.php?lang=hi&crop=<?= $crop_id ?><?= $cat ? "&cat=$cat" : '' ?>" class="<?= $lang === 'hi' ? 'active' : '' ?>">हिं</a>
    <a href="specific.php?lang=en&crop=<?= $crop_id ?><?= $cat ? "&cat=$cat" : '' ?>" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
  </div>
</div>

<!-- BREADCRUMB -->
<div class="breadcrumb anim">
  <a href="home.php?lang=<?= $lang ?>" class="bc" style="text-decoration:none;"><?= htmlspecialchars($crop_name) ?></a>
  <?php if ($cat): ?>
  <span class="bc-sep">›</span>
  <span class="bc active"><?= htmlspecialchars($lang === 'hi' ? $cat_meta[$cat]['hi'] : $cat_meta[$cat]['en']) ?></span>
  <?php endif; ?>
</div>

<!-- CROP BADGE -->
<div style="padding:0 20px;">
  <div class="crop-badge anim">
    <div class="crop-badge-icon" style="background:#fff8e1;">🌾</div>
    <div>
      <div class="crop-badge-hi"><?= htmlspecialchars($lang === 'hi' ? $crop['name_hi'] : $crop['name_en']) ?></div>
      <div class="crop-badge-en"><?= htmlspecialchars($lang === 'hi' ? $crop['name_en'] : $crop['name_hi']) ?></div>
    </div>
  </div>
</div>

<?php if (!$cat): ?>
<!-- ── SHOW CATEGORIES ── -->
<div class="section-head anim">
  <div>
    <div class="section-title"><?= $lang === 'hi' ? 'समस्या की श्रेणी चुनें' : 'Select Problem Category' ?></div>
    <div class="section-en"><?= $lang === 'hi' ? 'What is wrong with your crop?' : 'आपकी फसल में क्या समस्या है?' ?></div>
  </div>
</div>

<div class="cat-list">
  <?php
  $cat_descs = [
    'insect'  => ['hi' => 'माहू, दीमक, तना छेदक, टिड्डी', 'en' => 'Aphids, termites, stem borer, locusts'],
    'disease' => ['hi' => 'झुलसा, जंग, फफूंद, वायरस',    'en' => 'Blight, rust, fungal, viral infections'],
    'weed'    => ['hi' => 'जंगली घास, बथुआ, अनचाहे पौधे', 'en' => 'Wild grass, bathua, unwanted plants'],
    'growth'  => ['hi' => 'पीली पत्तियां, कमज़ोर पौधा',   'en' => 'Yellow leaves, weak plant, poor growth'],
    'other'   => ['hi' => 'पाला, जलभराव, सूखा, पक्षी',    'en' => 'Frost, waterlogging, drought, birds'],
  ];
  $border_colors = ['insect'=>'#e8560a','disease'=>'#7c3aed','weed'=>'#1e7c3a','growth'=>'#d48a00','other'=>'#2e7faa'];
  $delay = 1;
  foreach ($cat_meta as $key => $cm):
  ?>
  <a class="cat-card anim anim-d<?= min($delay,5) ?>"
     href="specific.php?lang=<?= $lang ?>&crop=<?= $crop_id ?>&cat=<?= $key ?>"
     style="border-left-color:<?= $border_colors[$key] ?>;">
    <div class="cat-icon" style="background:<?= $cm['bg'] ?>;"><?= $cm['icon'] ?></div>
    <div class="cat-text">
      <div class="cat-name-hi"><?= $lang === 'hi' ? $cm['hi'] : $cm['en'] ?></div>
      <div class="cat-name-en"><?= $lang === 'hi' ? $cm['en'] : $cm['hi'] ?></div>
      <div class="cat-desc"><?= $lang === 'hi' ? $cat_descs[$key]['hi'] : $cat_descs[$key]['en'] ?></div>
    </div>
    <div class="cat-arrow" style="color:<?= $border_colors[$key] ?>44;">›</div>
  </a>
  <?php $delay++; endforeach; ?>
</div>

<?php else: ?>
<!-- ── SHOW PROBLEMS FOR SELECTED CATEGORY ── -->

<!-- Category header -->
<div class="cat-header anim" style="background:<?= $cat_meta[$cat]['bg'] ?>;">
  <div class="cat-header-icon" style="background:<?= $cat_meta[$cat]['bg'] ?>;filter:brightness(.92);"><?= $cat_meta[$cat]['icon'] ?></div>
  <div>
    <div style="font-family:'Tiro Devanagari Hindi',serif;font-size:22px;color:var(--text-dark);">
      <?= $lang === 'hi' ? $cat_meta[$cat]['hi'] : $cat_meta[$cat]['en'] ?>
    </div>
    <div style="font-size:12px;font-weight:700;color:var(--text-mid);margin-top:2px;">
      <?= $lang === 'hi' ? $cat_meta[$cat]['en'] : $cat_meta[$cat]['hi'] ?>
    </div>
  </div>
</div>

<div class="section-head anim">
  <div>
    <div class="section-title"><?= $lang === 'hi' ? 'समस्या चुनें' : 'Select Problem' ?></div>
  </div>
  <div class="count-pill"><?= count($problems) ?> <?= $lang === 'hi' ? 'समस्याएं' : 'Problems' ?></div>
</div>

<?php if (count($problems) > 0): ?>
<div class="prob-list">
  <?php foreach ($problems as $i => $p): ?>
  <a class="prob-card anim" style="animation-delay:<?= $i * 0.07 ?>s;"
     href="solutions.php?lang=<?= $lang ?>&problem=<?= $p['id'] ?>">
    <div class="prob-dot" style="background:<?= $cat_meta[$cat]['color'] ?>;"></div>
    <div class="prob-text">
      <div class="prob-name-hi"><?= htmlspecialchars($lang === 'hi' ? $p['name_hi'] : $p['name_en']) ?></div>
      <div class="prob-name-en"><?= htmlspecialchars($lang === 'hi' ? $p['name_en'] : $p['name_hi']) ?></div>
    </div>
    <span class="sev-pill sev-<?= $p['severity'] ?>"><?= $sev_map[$lang][$p['severity']] ?></span>
    <div class="prob-arrow">›</div>
  </a>
  <?php endforeach; ?>
</div>

<?php else: ?>
<div class="empty-state">
  <?= $lang === 'hi' ? 'इस श्रेणी में अभी कोई समस्या नहीं जोड़ी गई।' : 'No problems added in this category yet.' ?><br>
  <a href="admin/problems.php?action=add"><?= $lang === 'hi' ? 'Admin panel से जोड़ें →' : 'Add from admin panel →' ?></a>
</div>
<?php endif; ?>

<?php endif; ?>

</body>
</html>
