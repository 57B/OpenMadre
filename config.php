<?php
// MadreTube Security & Database Config - 2026
session_start();

$host = 'ENTER YOUR SERVER NAME HERE';
$db   = 'ENTER YOUR DB NAME HERE';
$user = 'ENTER YOUR USERNAME HERE';
$pass = 'ENTER YOUR PASSWORD HERE';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Global Security Function: MAX Protection against XSS and Special Characters
function cleanInput($data) {
    // Only allows text, numbers, and basic punctuation (.,!?)
    $data = preg_replace("/[^a-zA-Z0-9\s\.\,\!\?\-]/", "", $data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Rate Limiting Logic
function checkCooldown($type, $limit, $minutes) {
    $key = "last_" . $type . "_time";
    $count_key = $type . "_count";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = time();
        $_SESSION[$count_key] = 1;
        return true;
    }
    
    if (time() - $_SESSION[$key] < ($minutes * 60)) {
        if ($_SESSION[$count_key] >= $limit) {
            return false; // Cooldown active
        }
        $_SESSION[$count_key]++;
    } else {
        $_SESSION[$key] = time();
        $_SESSION[$count_key] = 1;
    }
    return true;
}

// Shared CSS Variable (Embedded in all pages as requested)
$retro_css = "
<style>
    body { background: #f1f1f1; font-family: Arial, sans-serif; margin: 0; padding: 0; color: #333; }
    header { background: #fff; border-bottom: 1px solid #e8e8e8; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; position: sticky; top:0; z-index:100; }
    .logo-container { display: flex; align-items: center; text-decoration: none; }
    .logo-img { height: 30px; margin-right: 5px; }
    .slogan { color: #666; font-size: 13px; font-weight: bold; margin-top: 5px; }
    .search-box { flex-grow: 0.5; display: flex; margin-left: 50px; }
    .search-box input { width: 100%; padding: 5px; border: 1px solid #ccc; border-radius: 2px 0 0 2px; }
    .search-box button { padding: 5px 15px; background: #f8f8f8; border: 1px solid #ccc; border-left: none; cursor: pointer; }
    .nav-links { display: flex; gap: 15px; font-size: 13px; }
    .nav-links a { color: #333; text-decoration: none; font-weight: bold; }
    .container { width: 1000px; margin: 20px auto; display: flex; }
    .sidebar { width: 200px; padding-right: 20px; }
    .main-content { flex: 1; background: #fff; padding: 15px; border: 1px solid #e8e8e8; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    .video-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
    .video-card { font-size: 12px; }
    .thumb-wrapper { width: 100%; aspect-ratio: 16/9; background: #000; overflow: hidden; border: 1px solid #ccc; }
    .thumb-wrapper img { width: 100%; height: 100%; object-fit: cover; }
    .video-title { font-weight: bold; color: #167ac6; margin-top: 5px; display: block; text-decoration: none; }
    .video-meta { color: #666; font-size: 11px; margin-top: 3px; }
    footer { text-align: center; padding: 20px; font-size: 12px; color: #666; border-top: 1px solid #e8e8e8; margin-top: 50px; }
    .btn { background: #167ac6; color: white; border: none; padding: 8px 15px; border-radius: 2px; cursor: pointer; font-weight: bold; }
    .btn:disabled { background: #ccc; }
    .error-msg { color: red; font-size: 12px; margin-bottom: 10px; }
</style>
";

$header_html = "
<header>
    <div style='display:flex; align-items:center;'>
        <a href='index.php' class='logo-container'>
            <img src='assets/logo.png' alt='MadreTube' class='logo-img'>
            <span class='slogan'>Cast Yourself</span>
        </a>
        <form action='query.php' method='GET' class='search-box'>
            <input type='text' name='q' placeholder='Search' required>
            <button type='submit'>🔍</button>
        </form>
    </div>
    <div class='nav-links'>
        " . (isset($_SESSION['user_id']) ? 
            "<a href='upload.php'>Upload</a> <a href='profile.php?id=".$_SESSION['user_id']."'>".$_SESSION['username']."</a> <a href='logout.php'>Logout</a>" : 
            "<a href='login.php'>Sign In</a>") . "
    </div>
</header>
";

$footer_html = "
<footer>
    2026 - MadreTube - <a href='terms.php'>Terms of Service</a>
</footer>
";
?>
