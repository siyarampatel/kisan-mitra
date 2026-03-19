<?php
// ============================================================
//  home.php — Crop Selection (loads from database)
// ============================================================

require_once 'admin/db.php';

$lang = $_GET['lang'] ?? 'hi';
$lang = in_array($lang, ['hi', 'en']) ? $lang : 'hi';

// Fetch all active crops from database
$crops = $conn->query('SELECT * FROM crops WHERE is_active = 1 ORDER BY sort_order, id');
?>
<!DOCTYPE html>
<html lang="<?= $lang === 'hi' ? 'hi' : 'en' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $lang === 'hi' ? 'किसान मित्र — फसल चुनें' : 'Kisan Mitra — Select Crop' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi:ital@0;1&family=Nunito:wght@400;600;800;900&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
  :root{
    --saffron:#e8560a;--saffron-mid:#f07030;--saffron-pale:#fff3ed;--saffron-border:#f5c0a0;
    --green:#1e7c3a;--green-mid:#28a04a;--green-pale:#eaf7ee;--green-border:#a8dab8;
    --earth:#7a4e28;--earth-pale:#fdf3e8;--earth-border:#e0c8a0;
    --white:#ffffff;--page-bg:#f6f4f0;--text-dark:#1a1208;--text-mid:#5a4a30;--text-light:#9a8a70;
    --border:#e8e0d0;--topbar-bg:#1a1208;
  }
  body{min-height:100vh;background:var(--page-bg);font-family:'Nunito',sans-serif;color:var(--text-dark);padding-bottom:48px;-webkit-font-smoothing:antialiased;}
  .topbar{background:var(--topbar-bg);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
  .topbar-left{display:flex;align-items:center;gap:10px;text-decoration:none;}
  .logo-box{width:36px;height:36px;background:var(--saffron);border-radius:10px;display:flex;align-items:center;justify-content:center;}
  .topbar-brand{font-family:'Tiro Devanagari Hindi',serif;font-size:19px;color:white;line-height:1.1;}
  .topbar-brand-sub{display:block;font-family:'Nunito',sans-serif;font-size:10px;color:var(--saffron);font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-top:1px;}
  .lang-toggle{display:flex;background:#ffffff14;border-radius:22px;overflow:hidden;border:1px solid #ffffff20;}
  .lang-toggle a{padding:7px 14px;color:#c0b090;font-size:12px;font-weight:800;text-decoration:none;transition:all .2s;letter-spacing:.5px;}
  .lang-toggle a.active{background:var(--saffron);color:white;border-radius:22px;}
  .hero{background:var(--topbar-bg);padding:28px 20px 52px;position:relative;overflow:hidden;}
  .hero::after{content:'';position:absolute;bottom:-1px;left:0;right:0;height:36px;background:var(--page-bg);border-radius:36px 36px 0 0;}
  .hero::before{content:'';position:absolute;top:-60px;right:-60px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,#e8560a18 0%,transparent 70%);}
  .hero-greeting{font-size:12px;font-weight:800;color:var(--saffron);letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;}
  .hero-title{font-family:'Tiro Devanagari Hindi',serif;font-size:32px;color:white;line-height:1.2;margin-bottom:4px;}
  .hero-sub{font-size:14px;color:#9a8a70;font-weight:600;}
  .section-head{padding:24px 20px 16px;}
  .section-title{font-family:'Tiro Devanagari Hindi',serif;font-size:24px;color:var(--text-dark);line-height:1.2;}
  .section-en{font-size:12px;font-weight:800;color:var(--text-light);letter-spacing:1px;text-transform:uppercase;margin-top:3px;}
  .crop-list{padding:0 20px;display:flex;flex-direction:column;gap:14px;}
  .crop-card{background:var(--white);border-radius:18px;border:1.5px solid var(--border);padding:18px 20px;display:flex;align-items:center;gap:16px;text-decoration:none;transition:transform .18s ease,border-color .18s,box-shadow .18s;}
  .crop-card:hover{transform:translateY(-2px);border-color:var(--saffron);box-shadow:0 6px 24px #e8560a14;}
  .crop-card:active{transform:scale(.98);}
  .crop-illus{width:68px;height:68px;border-radius:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:36px;line-height:1;}
  .crop-info{flex:1;}
  .crop-name-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:26px;color:var(--text-dark);line-height:1.1;}
  .crop-name-en{font-size:13px;font-weight:700;color:var(--text-mid);margin-top:2px;}
  .crop-season{display:inline-block;margin-top:6px;font-size:11px;font-weight:800;padding:3px 10px;border-radius:20px;}
  .season-rabi{background:#fff3ed;color:var(--saffron);}
  .season-kharif{background:var(--green-pale);color:var(--green);}
  .crop-arrow{font-size:22px;color:var(--border);transition:transform .2s,color .2s;flex-shrink:0;}
  .crop-card:hover .crop-arrow{transform:translateX(4px);color:var(--saffron);}
  .crop-more{background:var(--page-bg);border-style:dashed;justify-content:center;gap:10px;cursor:default;}
  .crop-more:hover{transform:none;border-color:var(--border);box-shadow:none;}
  .more-text-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:16px;color:var(--text-light);}
  .more-text-en{font-size:11px;font-weight:700;color:var(--text-light);margin-top:2px;}
  .help-note{margin:20px 20px 0;background:var(--earth-pale);border-radius:14px;border:1.5px solid var(--earth-border);padding:14px 16px;display:flex;align-items:flex-start;gap:12px;}
  .help-icon{width:32px;height:32px;background:var(--earth);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .help-hi{font-family:'Tiro Devanagari Hindi',serif;font-size:14px;color:var(--earth);line-height:1.5;}
  .help-en{font-size:12px;font-weight:600;color:#9a7050;margin-top:3px;line-height:1.4;}
  @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  .anim{animation:fadeUp .45s cubic-bezier(.22,1,.36,1) both;}
  .anim-d1{animation-delay:.08s}.anim-d2{animation-delay:.16s}.anim-d3{animation-delay:.24s}.anim-d4{animation-delay:.32s}.anim-d5{animation-delay:.4s}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <a class="topbar-left" href="index.html">
    <div class="logo-box">
      <svg width="20" height="20" viewBox="0 0 36 36" fill="none">
        <path d="M18 4C18 4 10 10 10 20C10 26 13 30 18 32C23 30 26 26 26 20C26 10 18 4 18 4Z" fill="white"/>
        <path d="M18 10L18 32M18 18C18 18 22 14 26 15" stroke="#e8560a" stroke-width="1.5" stroke-linecap="round" fill="none"/>
      </svg>
    </div>
    <div class="topbar-brand">किसान मित्र<span class="topbar-brand-sub">Kisan Mitra</span></div>
  </a>
  <div class="lang-toggle">
    <a href="home.php?lang=hi" class="<?= $lang === 'hi' ? 'active' : '' ?>">हिं</a>
    <a href="home.php?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
  </div>
</div>

<!-- HERO -->
<div class="hero">
  <div class="hero-greeting"><?= $lang === 'hi' ? 'नमस्ते किसान भाई' : 'Hello Farmer' ?></div>
  <div class="hero-title"><?= $lang === 'hi' ? 'अपनी फसल चुनें' : 'Select Your Crop' ?></div>
  <div class="hero-sub"><?= $lang === 'hi' ? 'समस्या जानने के लिए फसल चुनें' : 'Choose your crop to find solutions' ?></div>
</div>

<!-- SECTION HEAD -->
<div class="section-head anim">
  <div class="section-title"><?= $lang === 'hi' ? 'फसलें' : 'Crops' ?></div>
  <div class="section-en"><?= $lang === 'hi' ? 'Select your crop' : 'अपनी फसल चुनें' ?></div>
</div>

<!-- CROP CARDS from database -->
<div class="crop-list">
  <?php
  $delay = 1;
  $icons = ['🌾', '🌿', '🫛', '🌽', '🥦', '🧅', '🌻'];
  $icon_idx = 0;
  $kharif_keywords = ['rice', 'paddy', 'maize', 'cotton', 'soybean', 'dhaan', 'dhan'];

  while ($crop = $crops->fetch_assoc()):
    $is_kharif = false;
    foreach ($kharif_keywords as $kw) {
      if (stripos($crop['name_en'], $kw) !== false || stripos($crop['season_en'], 'kharif') !== false) {
        $is_kharif = true; break;
      }
    }
    $icon = $icons[$icon_idx % count($icons)];
    $icon_idx++;
  ?>
  <a class="crop-card anim anim-d<?= min($delay, 5) ?>"
     href="specific.php?lang=<?= $lang ?>&crop=<?= $crop['id'] ?>">
    <div class="crop-illus" style="background:<?= $is_kharif ? '#e8f5e9' : '#fff8e1' ?>;">
      <?= $icon ?>
    </div>
    <div class="crop-info">
      <div class="crop-name-hi"><?= htmlspecialchars($lang === 'hi' ? $crop['name_hi'] : $crop['name_en']) ?></div>
      <div class="crop-name-en"><?= htmlspecialchars($lang === 'hi' ? $crop['name_en'] : $crop['name_hi']) ?></div>
      <?php if ($crop['season_hi'] || $crop['season_en']): ?>
      <span class="crop-season <?= $is_kharif ? 'season-kharif' : 'season-rabi' ?>">
        <?= htmlspecialchars($lang === 'hi' ? $crop['season_hi'] : $crop['season_en']) ?>
      </span>
      <?php endif; ?>
    </div>
    <div class="crop-arrow">›</div>
  </a>
  <?php $delay++; endwhile; ?>

  <!-- More crops coming -->
  <div class="crop-card crop-more anim anim-d<?= min($delay, 5) ?>">
    <span style="font-size:22px;">🌱</span>
    <div>
      <div class="more-text-hi"><?= $lang === 'hi' ? 'और फसलें जल्द आएंगी' : 'More crops coming soon' ?></div>
      <div class="more-text-en"><?= $lang === 'hi' ? 'More crops coming soon' : 'और फसलें जल्द आएंगी' ?></div>
    </div>
  </div>
</div>

<!-- HELP NOTE -->
<div class="help-note">
  <div class="help-icon">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/>
      <path d="M12 8v4M12 16h.01" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
  </div>
  <div>
    <div class="help-hi"><?= $lang === 'hi' ? 'फसल चुनें → समस्या बताएं → सही दवाई पाएं' : 'Select crop → Choose problem → Get the right solution' ?></div>
    <div class="help-en"><?= $lang === 'hi' ? 'Select crop → Choose problem → Get the right solution' : 'फसल चुनें → समस्या बताएं → सही दवाई पाएं' ?></div>
  </div>
</div>

</body>
</html>
