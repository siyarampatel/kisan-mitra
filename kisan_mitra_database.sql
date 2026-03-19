-- ============================================================
--  KISAN MITRA — DATABASE SETUP
--  Run this file once to create all tables
--  Database: MySQL 5.7+ or MariaDB
-- ============================================================

-- Create and select the database
CREATE DATABASE IF NOT EXISTS kisan_mitra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kisan_mitra;

-- ============================================================
--  TABLE 1: admin_users
--  Stores your login credentials
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,         -- stored as bcrypt hash, never plain text
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE 2: crops
--  Wheat, Rice, Green Peas — and any future crops
-- ============================================================
CREATE TABLE IF NOT EXISTS crops (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name_hi      VARCHAR(100) NOT NULL,          -- गेहूँ
    name_en      VARCHAR(100) NOT NULL,          -- Wheat
    season_hi    VARCHAR(50),                    -- रबी फसल
    season_en    VARCHAR(50),                    -- Rabi Crop
    sort_order   INT DEFAULT 0,                  -- controls display order
    is_active    TINYINT(1) DEFAULT 1,           -- 1 = show, 0 = hide
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE 3: problems
--  Every specific problem for every crop
-- ============================================================
CREATE TABLE IF NOT EXISTS problems (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    crop_id      INT NOT NULL,
    category     ENUM('insect','disease','weed','growth','other') NOT NULL,
    name_hi      VARCHAR(200) NOT NULL,          -- माहू (एफिड)
    name_en      VARCHAR(200) NOT NULL,          -- Aphids
    symptom_hi   TEXT,                           -- 1-2 line symptom description in Hindi
    symptom_en   TEXT,                           -- 1-2 line symptom description in English
    severity     ENUM('high','medium','low') DEFAULT 'medium',
    sort_order   INT DEFAULT 0,
    is_active    TINYINT(1) DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE 4: solutions
--  Product recommendations per problem
-- ============================================================
CREATE TABLE IF NOT EXISTS solutions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    problem_id      INT NOT NULL,
    type            ENUM('pesticide','fungicide','herbicide','fertilizer','booster') NOT NULL,
    brand_names_hi  VARCHAR(300) NOT NULL,       -- Monocil, Dursban, Durmet
    brand_names_en  VARCHAR(300) NOT NULL,       -- Monocil, Dursban, Durmet
    chemical_hi     VARCHAR(200) NOT NULL,       -- क्लोरपायरीफॉस 20% EC
    chemical_en     VARCHAR(200) NOT NULL,       -- Chlorpyrifos 20% EC
    dose_hi         VARCHAR(200) NOT NULL,       -- 500 मिली प्रति एकड़
    dose_en         VARCHAR(200) NOT NULL,       -- 500 ml per acre
    timing_hi       VARCHAR(300) NOT NULL,       -- संक्रमण दिखते ही, सुबह या शाम
    timing_en       VARCHAR(300) NOT NULL,       -- At first sign, morning or evening
    method_hi       TEXT NOT NULL,               -- 200 लीटर पानी में मिलाकर छिड़काव
    method_en       TEXT NOT NULL,               -- Mix in 200 litres water and spray
    warning_hi      TEXT,                        -- मधुमक्खियों के लिए हानिकारक
    warning_en      TEXT,                        -- Harmful to bees
    sort_order      INT DEFAULT 0,               -- Option 1, 2, 3 order
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE 5: tips
--  Extra tips per problem — shown in the green tips box
-- ============================================================
CREATE TABLE IF NOT EXISTS tips (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    problem_id   INT NOT NULL,
    tip_hi       TEXT NOT NULL,                  -- tip in Hindi
    tip_en       TEXT NOT NULL,                  -- tip in English
    sort_order   INT DEFAULT 0,                  -- order of tips within a problem
    is_active    TINYINT(1) DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  SAMPLE DATA — Phase 1 crops
--  Run this to have starter data immediately
-- ============================================================

INSERT INTO crops (name_hi, name_en, season_hi, season_en, sort_order) VALUES
('गेहूँ',   'Wheat',       'रबी फसल',   'Rabi Crop',   1),
('धान',     'Rice',        'खरीफ फसल',  'Kharif Crop', 2),
('मटर',     'Green Peas',  'रबी फसल',   'Rabi Crop',   3);

-- ============================================================
--  SAMPLE PROBLEMS — Wheat Insect (crop_id = 1)
-- ============================================================

INSERT INTO problems (crop_id, category, name_hi, name_en, symptom_hi, symptom_en, severity, sort_order) VALUES
(1, 'insect', 'माहू (एफिड)', 'Aphids',
 'पत्तियों पर छोटे काले या हरे कीड़े दिखते हैं, पत्तियाँ पीली पड़ने लगती हैं।',
 'Small black or green insects on leaves. Leaves turn yellow and curl.',
 'high', 1),

(1, 'insect', 'दीमक', 'Termites',
 'पौधे की जड़ें और तना अंदर से खोखला हो जाता है, पौधा अचानक मुरझा जाता है।',
 'Roots and stem become hollow inside. Plant wilts suddenly.',
 'high', 2),

(1, 'insect', 'तना छेदक', 'Stem Borer',
 'तने में छेद दिखता है, बीच की पत्ती सूख जाती है।',
 'Holes in stem. Central leaf dries up — called dead heart.',
 'high', 3),

(1, 'insect', 'सफेद मक्खी', 'Whitefly',
 'पत्तियों के नीचे छोटे सफेद कीड़े और चिपचिपा पदार्थ दिखता है।',
 'Small white insects under leaves with sticky substance.',
 'medium', 4),

(1, 'insect', 'टिड्डी', 'Locust',
 'झुंड में कीड़े आते हैं और पूरी फसल की पत्तियाँ तेजी से खाते हैं।',
 'Insects come in swarms and rapidly eat all crop leaves.',
 'high', 5);

-- ============================================================
--  SAMPLE PROBLEMS — Wheat Disease (crop_id = 1)
-- ============================================================

INSERT INTO problems (crop_id, category, name_hi, name_en, symptom_hi, symptom_en, severity, sort_order) VALUES
(1, 'disease', 'पीला रतुआ', 'Yellow Rust',
 'पत्तियों पर पीले रंग के पाउडर जैसे धब्बे या धारियाँ दिखती हैं।',
 'Yellow powdery streaks or spots appear on leaves.',
 'high', 1),

(1, 'disease', 'भूरा रतुआ', 'Brown Rust',
 'पत्तियों पर भूरे-नारंगी रंग के छाले जैसे दाने दिखते हैं।',
 'Brown-orange blister-like pustules appear on leaves.',
 'high', 2),

(1, 'disease', 'पाउडरी मिल्ड्यू', 'Powdery Mildew',
 'पत्तियों और तने पर सफेद पाउडर जैसी परत चढ़ जाती है।',
 'White powdery coating covers leaves and stems.',
 'medium', 3);

-- ============================================================
--  SAMPLE SOLUTIONS — Wheat Aphids (problem_id = 1)
-- ============================================================

INSERT INTO solutions (problem_id, type, brand_names_hi, brand_names_en, chemical_hi, chemical_en, dose_hi, dose_en, timing_hi, timing_en, method_hi, method_en, warning_hi, warning_en, sort_order) VALUES
(1, 'pesticide',
 'Monocil, Dursban, Durmet',
 'Monocil, Dursban, Durmet',
 'क्लोरपायरीफॉस 20% EC',
 'Chlorpyrifos 20% EC',
 '500 मिली प्रति एकड़',
 '500 ml per acre',
 'संक्रमण दिखते ही, सुबह या शाम',
 'At first sign of infestation, morning or evening',
 '200 लीटर पानी में मिलाकर छिड़काव करें',
 'Mix in 200 litres of water and spray',
 'मधुमक्खियों के लिए हानिकारक — फूल आने पर न छिड़कें',
 'Harmful to bees — do not spray during flowering',
 1),

(1, 'pesticide',
 'Confidor, Imida, Admire',
 'Confidor, Imida, Admire',
 'इमिडाक्लोप्रिड 17.8% SL',
 'Imidacloprid 17.8% SL',
 '150 मिली प्रति एकड़',
 '150 ml per acre',
 'कीट दिखने पर तुरंत छिड़काव करें',
 'Spray immediately on seeing insects',
 '200 लीटर पानी में मिलाकर',
 'Mix in 200 litres of water',
 'बच्चों और जानवरों को खेत से दूर रखें',
 'Keep children and animals away from field',
 2),

(1, 'pesticide',
 'Mospilan, Acetamip',
 'Mospilan, Acetamip',
 'एसिटामिप्रिड 20% SP',
 'Acetamiprid 20% SP',
 '100 ग्राम प्रति एकड़',
 '100 g per acre',
 'सुबह जल्दी या शाम को',
 'Early morning or evening',
 'पानी में घोलकर पत्तों पर छिड़काव करें',
 'Dissolve in water and spray on leaves',
 'कटाई से कम से कम 7 दिन पहले बंद करें',
 'Stop use at least 7 days before harvest',
 3);

-- ============================================================
--  SAMPLE TIPS — Wheat Aphids (problem_id = 1)
-- ============================================================

INSERT INTO tips (problem_id, tip_hi, tip_en, sort_order) VALUES
(1,
 'छिड़काव सुबह जल्दी करें जब हवा न चल रही हो और तापमान 35°C से कम हो।',
 'Spray early morning when there is no wind and temperature is below 35°C.',
 1),
(1,
 'बारिश के 24 घंटे बाद ही छिड़काव करें, वरना दवाई बह जाएगी।',
 'Spray only 24 hours after rain, otherwise the medicine will wash away.',
 2),
(1,
 'नीम का तेल (5 मिली प्रति लीटर पानी) हल्के संक्रमण में प्राकृतिक विकल्प है।',
 'Neem oil (5 ml per litre water) is a natural option for mild infestation.',
 3);

-- ============================================================
--  ADMIN USER — Change this password before going live!
--  This is a bcrypt hash of the password: admin123
--  Replace with your own password hash from PHP
-- ============================================================

INSERT INTO admin_users (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================================
--  USEFUL QUERIES — for reference
-- ============================================================

-- Get all problems for a crop and category:
-- SELECT * FROM problems WHERE crop_id = 1 AND category = 'insect' AND is_active = 1 ORDER BY sort_order;

-- Get all solutions for a problem:
-- SELECT * FROM solutions WHERE problem_id = 1 AND is_active = 1 ORDER BY sort_order;

-- Get all tips for a problem:
-- SELECT * FROM tips WHERE problem_id = 1 AND is_active = 1 ORDER BY sort_order;

-- Get crop name by id:
-- SELECT name_hi, name_en FROM crops WHERE id = 1;
