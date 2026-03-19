<?php
// ============================================================
//  solutions.php — Solutions Page (loads from database)
// ============================================================

require_once 'admin/db.php';

$lang       = $_GET['lang']    ?? 'hi';
$lang       = in_array($lang, ['hi', 'en']) ? $lang : 'hi';
$problem_id = isset($_GET['problem']) ? (int)$_GET['problem'] : 0;

// Fetch problem details
$problem = null;
if ($problem_id) {
    $stmt = $conn->prepare('
        SELECT p.*, c.name_hi as crop_hi, c.name_en as crop_en, c.id as crop_id
        FROM problems p
        JOIN crops c ON p.crop_id = c.id
        WHERE p.id = ? AND p.is_active = 1
    ');
    $stmt->bind_param('i', $problem_id);
    $stmt->execute();
    $problem = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$problem) {
    header('Location: home.php?lang=' . $lang);
    exit();
}

// Fetch solutions for this problem
$solutions = [];
$stmt = $conn->prepare('SELECT * FROM solutions WHERE problem_id = ? AND is_active = 1 ORDER BY sort_order, id');
$stmt->bind_param('i', $problem_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $solutions[] = $row;
$stmt->close();

// Fetch tips for this problem
$tips = [];
$stmt = $conn->prepare('SELECT * FROM tips WHERE problem_id = ? AND is_active = 1 ORDER BY sort_order, id');
$stmt->bind_param('i', $problem_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $tips[] = $row;
$stmt->close();

$cat_meta = [
    'insect'  => ['hi' => 'कीट समस्या',          'en' => 'Insect Problems'],
    'disease' => ['hi' => 'रोग समस्या',           'en' => 'Disease Problems'],
    'weed'    => ['hi' => 'खरपतवार समस्या',       'en' => 'Weed Problems'],
    'growth'  => ['hi' => 'पोषण व वृद्धि समस्या', 'en' => 'Growth & Nutrition'],
    'other'   => ['hi' => 'अन्य समस्या',          'en' => 'Other Problems'],
];

$type_labels = [
    'pesticide'  => ['hi' => 'कीटनाशक',      'en' => 'Pesticide'],
    'fungicide'  => ['hi' => 'फफूंदनाशक',    'en' => 'Fungicide'],
    'herbicide'  => ['hi' => 'खरपतवारनाशक',  'en' => 'Herbicide'],
    'fertilizer' => ['hi' => 'उर्वरक',        'en' => 'Fertilizer'],
    'booster'    => ['hi' => 'ग्रोथ बूस्टर',  'en' => 'Growth Booster'],
];

$type_pill_class = [
    'pesticide'  => 'pill-pesticide',
    'fungicide'  => 'pill-fungicide',
    'herbicide'  => 'pill-herbicide',
    'fertilizer' => 'pill-fertilizer',
    'booster'    => 'pill-booster',
];

$sev_map = [
    'hi' => ['high' => ['text' => 'गंभीर',   'cls' => 'sev-high'],   'medium' => ['text' => 'मध्यम',   'cls' => 'sev-medium'], 'low' => ['text' => 'सामान्य', 'cls' => 'sev-low']],
    'en' => ['high' => ['text' => 'Severe',  'cls' => 'sev-high'],   'medium' => ['text' => 'Moderate','cls' => 'sev-medium'], 'low' => ['text' => 'Mild',     'cls' => 'sev-low']],
];

$sd       = $sev_map[$lang][$problem['severity']] ?? $sev_map[$lang]['medium'];
$cat      = $problem['category'];
$cat_name = $lang === 'hi' ? ($cat_meta[$cat]['hi'] ?? $cat) : ($cat_meta[$cat]['en'] ?? $cat);
?>
<!DOCTYPE html>
<html lang="<?= $lang === 'hi' ? 'hi' : 'en' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>किसान मित्र — <?= htmlspecialchars($lang === 'hi' ? $problem['name_hi'] : $problem['name_en']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi:ital@0;1&family=Nunito:wght@400;600;800;900&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
  :root{
    --saffron:#e8560a;--saffron-pale:#fff3ed;--saffron-border:#f5c0a0;
    --green:#1e7c3a;--green-pale:#eaf7ee;--green-border:#a8dab8;
    --earth:#7a4e28;--earth-pale:#fdf3e8;--earth-border:#e0c8a0;
    --white:#ffffff;--page-bg:#f6f4f0;--text-dark:#1a1208;--text-mid:#5a4a30;--text-light:#9a8a70;
    --border:#e8e0d0;--topbar-bg:#1a1208;
  }
  body{min-height:100vh;background:var(--page-bg);font-family:'Nunito',sans-serif;color:var(--text-dark);padding-bottom:48px;-webkit-font-smoothing:antialiased;}
  .topbar{background:var(--topbar-bg);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
  .topbar-left{display:flex;align-items:center;gap:10px;text-decoration:none;}
  .back-btn{width:36px;height:36px;background:#ffffff18;border-radius:10px;display:flex;align-items:center;justify-content:center;border:1px solid #ffffff20;flex-shrink:0;}
  .topbar-brand{font-family:'Tiro Devanagari Hindi',serif;font-size:18px;color:white;}
  .topbar-brand-sub{display:block;font-family:'Nunito',sans-serif;font-size:10px;color:var(--saffron);font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-top:1px;}
  .lang-toggle{display:flex;background:#ffffff14;border-radius:22px;overflow:hidden;border:1px solid #ffffff20;}
  .lang-toggle a{padding:7px 14px;color:#c0b090;font-size:12px;font-weight:800;text-decoration:none;transition:all .2s;}
  .lang-toggle a.active{background:var(--saffron);color:white;border-radius:22px;}
  .breadcrumb{display:flex;align-items:center;gap:5px;padding:12px 20px 0;flex-wrap:wrap;}
  .bc{font-size:12px;color:var(--text-light);font-weight:700;text-decoration:none;}
  .bc.active{color:var(--saffron);}
  .bc-sep{font-size:12px;color:var(--border);font-weight:700;}
  .problem-box{margin:14px 20px 0;background:var(--white);border-radius:18px;border:1.5px solid var(--border);padding:18px 20px;}
  .prob-top{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:14px;}
  .prob-name-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:26px;color:var(--text-dark);line-height:1.2;}
  .prob-name-en{font-size:14px;font-weight:700;color:var(--text-mid);margin-top:3px;}
  .sev-badge{font-size:11px;font-weight:800;padding:5px 12px;border-radius:20px;flex-shrink:0;margin-top:4px;}
  .sev-high{background:#ffe5e5;color:#c0392b;}
  .sev-medium{background:#fff3cd;color:#8a6200;}
  .sev-low{background:var(--green-pale);color:var(--green);}
  .symptom-strip{background:var(--page-bg);border-radius:12px;padding:12px 14px;border-left:3px solid var(--saffron);}
  .symptom-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:15px;color:var(--text-dark);line-height:1.6;}
  .symptom-en{font-size:12px;font-weight:600;color:var(--text-mid);margin-top:5px;line-height:1.5;}
  .section-head{padding:20px 20px 14px;display:flex;align-items:flex-end;justify-content:space-between;}
  .section-title-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:24px;color:var(--text-dark);line-height:1.2;}
  .section-title-en{font-size:12px;font-weight:800;color:var(--text-light);letter-spacing:.5px;margin-top:2px;}
  .count-pill{font-size:11px;font-weight:800;padding:4px 12px;border-radius:20px;background:var(--saffron-pale);color:var(--saffron);border:1.5px solid var(--saffron-border);white-space:nowrap;flex-shrink:0;}
  .sol-list{padding:0 20px;display:flex;flex-direction:column;gap:16px;}
  .sol-card{background:var(--white);border-radius:18px;border:1.5px solid var(--border);overflow:hidden;}
  .sol-header{background:var(--topbar-bg);padding:10px 18px;display:flex;align-items:center;justify-content:space-between;}
  .option-label{font-size:12px;font-weight:800;color:#9a8a70;letter-spacing:.8px;text-transform:uppercase;}
  .type-pill{font-size:11px;font-weight:800;padding:3px 12px;border-radius:20px;}
  .pill-pesticide{background:#fff0e8;color:#c04010;}
  .pill-fungicide{background:#f0e8ff;color:#6a2ab0;}
  .pill-herbicide{background:var(--green-pale);color:var(--green);}
  .pill-fertilizer{background:#fff8e0;color:#8a5a00;}
  .pill-booster{background:#e8f4fb;color:#2a5f78;}
  .sol-body{padding:18px 20px;}
  .brand-block{padding-bottom:16px;border-bottom:1px solid var(--border);margin-bottom:16px;}
  .brand-label{font-size:10px;font-weight:800;color:var(--saffron);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px;}
  .brand-name{font-size:22px;font-weight:900;color:var(--text-dark);line-height:1.2;}
  .chemical-label{font-size:10px;font-weight:800;color:var(--text-light);letter-spacing:1px;text-transform:uppercase;margin-top:10px;margin-bottom:3px;}
  .chemical-name{font-size:14px;font-weight:700;color:var(--text-mid);}
  .detail-rows{display:flex;flex-direction:column;}
  .detail-row{display:flex;align-items:flex-start;padding:11px 0;border-bottom:1px solid #f0ece4;gap:14px;}
  .detail-row:last-child{border-bottom:none;padding-bottom:0;}
  .detail-key{min-width:96px;flex-shrink:0;}
  .key-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:16px;color:var(--text-dark);}
  .key-en{font-size:11px;font-weight:800;color:var(--text-light);letter-spacing:.5px;margin-top:1px;}
  .val-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:16px;color:var(--text-dark);line-height:1.5;}
  .val-en{font-size:12px;font-weight:600;color:var(--text-mid);margin-top:3px;line-height:1.4;}
  .warning-box{margin-top:14px;background:#fffbea;border:1.5px solid #f5d060;border-radius:12px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start;}
  .warning-icon{font-size:16px;flex-shrink:0;margin-top:1px;line-height:1;}
  .warn-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:15px;color:#7a5a00;line-height:1.5;}
  .warn-en{font-size:12px;font-weight:600;color:#9a7a20;margin-top:3px;line-height:1.4;}
  .tips-box{margin:16px 20px 0;background:var(--green-pale);border-radius:18px;border:1.5px solid var(--green-border);overflow:hidden;}
  .tips-header{background:var(--green);padding:10px 18px;display:flex;align-items:center;gap:8px;}
  .tips-header-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:16px;color:white;flex:1;}
  .tips-header-en{font-size:11px;font-weight:800;color:#90daa8;letter-spacing:.5px;}
  .tips-body{padding:14px 18px;display:flex;flex-direction:column;gap:12px;}
  .tip-row{display:flex;align-items:flex-start;gap:10px;}
  .tip-dot{width:8px;height:8px;border-radius:50%;background:var(--green);flex-shrink:0;margin-top:7px;}
  .tip-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:15px;color:var(--text-dark);line-height:1.5;}
  .tip-en{font-size:12px;font-weight:600;color:var(--text-mid);margin-top:3px;line-height:1.4;}
  .safety-note{margin:16px 20px 0;background:var(--earth-pale);border-radius:16px;border:1.5px solid var(--earth-border);padding:14px 16px;display:flex;gap:12px;align-items:flex-start;}
  .safety-icon{width:32px;height:32px;background:var(--earth);border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
  .safety-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:14px;color:var(--earth);line-height:1.6;}
  .safety-en{font-size:12px;font-weight:600;color:#9a7050;margin-top:4px;line-height:1.5;}
  .home-btn{display:flex;align-items:center;justify-content:center;gap:10px;margin:20px 20px 0;background:var(--topbar-bg);border-radius:14px;padding:16px 20px;text-decoration:none;transition:opacity .2s;}
  .home-btn:active{opacity:.8;}
  .home-btn-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:18px;color:white;}
  .home-btn-en{font-size:11px;color:#f07030;font-weight:700;letter-spacing:.5px;margin-top:2px;}
  .empty-state{text-align:center;padding:40px 20px;color:var(--text-light);font-size:15px;font-weight:600;}
  .empty-state a{color:var(--saffron);text-decoration:none;font-weight:700;}
  @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  .anim{animation:fadeUp .45s cubic-bezier(.22,1,.36,1) both;}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <a class="topbar-left" href="specific.php?lang=<?= $lang ?>&crop=<?= $problem['crop_id'] ?>&cat=<?= $cat ?>">
    <div class="back-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
        <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <div class="topbar-brand">
      <?= $lang === 'hi' ? 'समाधान' : 'Solutions' ?>
      <span class="topbar-brand-sub">Solutions</span>
    </div>
  </a>
  <div class="lang-toggle">
    <a href="solutions.php?lang=hi&problem=<?= $problem_id ?>" class="<?= $lang === 'hi' ? 'active' : '' ?>">हिं</a>
    <a href="solutions.php?lang=en&problem=<?= $problem_id ?>" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
  </div>
</div>

<!-- BREADCRUMB -->
<div class="breadcrumb anim">
  <a href="home.php?lang=<?= $lang ?>" class="bc"><?= htmlspecialchars($lang === 'hi' ? $problem['crop_hi'] : $problem['crop_en']) ?></a>
  <span class="bc-sep">›</span>
  <a href="specific.php?lang=<?= $lang ?>&crop=<?= $problem['crop_id'] ?>&cat=<?= $cat ?>" class="bc"><?= htmlspecialchars($cat_name) ?></a>
  <span class="bc-sep">›</span>
  <span class="bc active"><?= htmlspecialchars($lang === 'hi' ? $problem['name_hi'] : $problem['name_en']) ?></span>
</div>

<!-- PROBLEM BOX -->
<div class="problem-box anim">
  <div class="prob-top">
    <div>
      <div class="prob-name-hi"><?= htmlspecialchars($lang === 'hi' ? $problem['name_hi'] : $problem['name_en']) ?></div>
      <div class="prob-name-en"><?= htmlspecialchars($lang === 'hi' ? $problem['name_en'] : $problem['name_hi']) ?></div>
    </div>
    <div class="sev-badge <?= $sd['cls'] ?>"><?= $sd['text'] ?></div>
  </div>
  <?php if ($problem['symptom_hi'] || $problem['symptom_en']): ?>
  <div class="symptom-strip">
    <div class="symptom-hi"><?= htmlspecialchars($lang === 'hi' ? $problem['symptom_hi'] : $problem['symptom_en']) ?></div>
    <div class="symptom-en"><?= htmlspecialchars($lang === 'hi' ? $problem['symptom_en'] : $problem['symptom_hi']) ?></div>
  </div>
  <?php endif; ?>
</div>

<!-- SOLUTIONS HEADING -->
<div class="section-head anim">
  <div>
    <div class="section-title-hi"><?= $lang === 'hi' ? 'अनुशंसित समाधान' : 'Recommended Solutions' ?></div>
    <div class="section-title-en"><?= $lang === 'hi' ? 'Recommended Solutions' : 'अनुशंसित समाधान' ?></div>
  </div>
  <div class="count-pill"><?= count($solutions) ?> <?= $lang === 'hi' ? 'विकल्प' : 'Options' ?></div>
</div>

<!-- SOLUTION CARDS -->
<?php if (count($solutions) > 0): ?>
<div class="sol-list">
  <?php foreach ($solutions as $i => $sol):
    $tl = $type_labels[$sol['type']] ?? ['hi' => $sol['type'], 'en' => $sol['type']];
    $pc = $type_pill_class[$sol['type']] ?? 'pill-pesticide';
    $opt_word = $lang === 'hi' ? 'विकल्प' : 'Option';
  ?>
  <div class="sol-card anim" style="animation-delay:<?= $i * 0.1 ?>s;">
    <div class="sol-header">
      <span class="option-label"><?= $opt_word ?> <?= $i + 1 ?></span>
      <span class="type-pill <?= $pc ?>"><?= $lang === 'hi' ? $tl['hi'] : $tl['en'] ?></span>
    </div>
    <div class="sol-body">

      <!-- Brand + Chemical -->
      <div class="brand-block">
        <div class="brand-label"><?= $lang === 'hi' ? 'दुकान पर यह नाम मांगें' : 'Ask at shop by this name' ?></div>
        <div class="brand-name"><?= htmlspecialchars($lang === 'hi' ? $sol['brand_names_hi'] : $sol['brand_names_en']) ?></div>
        <div class="chemical-label"><?= $lang === 'hi' ? 'रासायनिक नाम' : 'Chemical name' ?></div>
        <div class="chemical-name"><?= htmlspecialchars($lang === 'hi' ? $sol['chemical_hi'] : $sol['chemical_en']) ?></div>
      </div>

      <!-- Detail rows -->
      <div class="detail-rows">
        <?php
        $rows = [
          ['hi_key'=>'मात्रा','en_key'=>'Dose',   'hi_val'=>$sol['dose_hi'],  'en_val'=>$sol['dose_en']],
          ['hi_key'=>'समय',   'en_key'=>'Timing', 'hi_val'=>$sol['timing_hi'],'en_val'=>$sol['timing_en']],
          ['hi_key'=>'विधि',  'en_key'=>'Method', 'hi_val'=>$sol['method_hi'],'en_val'=>$sol['method_en']],
        ];
        foreach ($rows as $row):
        ?>
        <div class="detail-row">
          <div class="detail-key">
            <div class="key-hi"><?= $lang === 'hi' ? $row['hi_key'] : $row['en_key'] ?></div>
            <div class="key-en"><?= $lang === 'hi' ? $row['en_key'] : $row['hi_key'] ?></div>
          </div>
          <div>
            <div class="val-hi"><?= htmlspecialchars($lang === 'hi' ? $row['hi_val'] : $row['en_val']) ?></div>
            <div class="val-en"><?= htmlspecialchars($lang === 'hi' ? $row['en_val'] : $row['hi_val']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Warning -->
      <?php if ($sol['warning_hi'] || $sol['warning_en']): ?>
      <div class="warning-box">
        <div class="warning-icon">⚠️</div>
        <div>
          <div class="warn-hi"><?= htmlspecialchars($lang === 'hi' ? $sol['warning_hi'] : $sol['warning_en']) ?></div>
          <div class="warn-en"><?= htmlspecialchars($lang === 'hi' ? $sol['warning_en'] : $sol['warning_hi']) ?></div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else: ?>
<div class="empty-state">
  <?= $lang === 'hi' ? 'इस समस्या के लिए अभी कोई समाधान नहीं जोड़ा गया।' : 'No solutions added for this problem yet.' ?><br>
  <a href="admin/solutions.php?action=add"><?= $lang === 'hi' ? 'Admin panel से जोड़ें →' : 'Add from admin panel →' ?></a>
</div>
<?php endif; ?>

<!-- TIPS BOX — only if tips exist -->
<?php if (count($tips) > 0): ?>
<div class="tips-box anim">
  <div class="tips-header">
    <span style="font-size:16px;line-height:1;">💡</span>
    <div class="tips-header-hi"><?= $lang === 'hi' ? 'किसान सलाह' : 'Farmer Tips' ?></div>
    <div class="tips-header-en"><?= $lang === 'hi' ? 'Farmer Tips' : 'किसान सलाह' ?></div>
  </div>
  <div class="tips-body">
    <?php foreach ($tips as $tip): ?>
    <div class="tip-row">
      <div class="tip-dot"></div>
      <div>
        <div class="tip-hi"><?= htmlspecialchars($lang === 'hi' ? $tip['tip_hi'] : $tip['tip_en']) ?></div>
        <div class="tip-en"><?= htmlspecialchars($lang === 'hi' ? $tip['tip_en'] : $tip['tip_hi']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- SAFETY NOTE -->
<div class="safety-note anim">
  <div class="safety-icon">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
      <path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
  <div>
    <div class="safety-hi">
      <?= $lang === 'hi'
        ? 'दवाई उपयोग से पहले लेबल पढ़ें। सही मात्रा में उपयोग करें। बच्चों और जानवरों की पहुँच से दूर रखें।'
        : 'Always read the label before use. Use the correct dose. Keep away from children and animals.' ?>
    </div>
    <div class="safety-en">
      <?= $lang === 'hi'
        ? 'Always read the label before use. Use correct dose. Keep away from children and animals.'
        : 'दवाई उपयोग से पहले लेबल पढ़ें। सही मात्रा में उपयोग करें।' ?>
    </div>
  </div>
</div>

<!-- HOME BUTTON -->
<a class="home-btn" href="home.php?lang=<?= $lang ?>">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
    <path d="M3 12L12 3L21 12M5 10V20H9V15H15V20H19V10" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
  <div>
    <div class="home-btn-hi"><?= $lang === 'hi' ? 'दूसरी फसल देखें' : 'Check another crop' ?></div>
    <div class="home-btn-en"><?= $lang === 'hi' ? 'Check another crop' : 'दूसरी फसल देखें' ?></div>
  </div>
</a>

</body>
</html>
