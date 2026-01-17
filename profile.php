<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: 404.php");
    exit;
}

$profile_id = (int)$_GET['id'];
$is_owner = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_id);

// Fetch User Info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profile_id]);
$user = $stmt->fetch();

if (!$user) { header("Location: 404.php"); exit; }

$error = "";
$success = "";

// Handle PFP Update
if ($is_owner && isset($_FILES['new_pfp'])) {
    $file = $_FILES['new_pfp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($ext !== 'png') {
        $error = "Profile picture must be a .png file.";
    } elseif ($file['size'] > 1 * 1024 * 1024) {
        $error = "File size must be under 1MB.";
    } else {
        $newName = bin2hex(random_bytes(10)) . ".png";
        if (move_uploaded_file($file['tmp_name'], "uploads/pfps/" . $newName)) {
            $stmt = $pdo->prepare("UPDATE users SET pfp = ? WHERE id = ?");
            $stmt->execute([$newName, $profile_id]);
            $user['pfp'] = $newName; // Update local variable for display
            $success = "Profile picture updated!";
        }
    }
}

// Handle Account Deletion
if ($is_owner && isset($_POST['delete_account'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$profile_id]);
    session_destroy();
    header("Location: index.php");
    exit;
}

// Fetch User's Videos
$vStmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? ORDER BY upload_date DESC");
$vStmt->execute([$profile_id]);
$videos = $vStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Channel - MadreTube</title>
    <?php echo $retro_css; ?>
    <style>
        .profile-header { background: #fff; border: 1px solid #e8e8e8; padding: 20px; display: flex; align-items: center; gap: 20px; margin-bottom: 20px; }
        .profile-pfp-large { width: 120px; height: 120px; border: 1px solid #ccc; object-fit: cover; }
        .profile-nav { border-bottom: 1px solid #e8e8e8; margin-bottom: 20px; display: flex; gap: 20px; }
        .nav-tab { padding: 10px; cursor: pointer; font-weight: bold; color: #666; text-decoration: none; border-bottom: 3px solid transparent; }
        .nav-tab.active { color: #333; border-bottom-color: #167ac6; }
        .manager-box { background: #fff; border: 1px solid #e8e8e8; padding: 15px; margin-top: 20px; border-top: 3px solid #cc181e; }
        .search-box { flex-grow: 0.5; display: flex; margin-left: 25px; width: 700px; }
    </style>
</head>
<body>

<?php echo $header_html; ?>

<div class="container" style="display:block;">
    <div class="profile-header">
        <img src="uploads/pfps/<?php echo $user['pfp']; ?>" class="profile-pfp-large">
        <div>
            <h1 style="margin:0; font-size:24px;"><?php echo htmlspecialchars($user['username']); ?></h1>
            <p style="color:#666; font-size:13px;">Joined <?php echo date("M Y", strtotime($user['created_at'])); ?></p>
            <?php if (!$is_owner): ?>
                <button class="btn">Subscribe</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-nav">
        <a href="?id=<?php echo $profile_id; ?>" class="nav-tab active">Videos</a>
        <?php if ($is_owner): ?>
            <a href="?id=<?php echo $profile_id; ?>&tab=manage" class="nav-tab">Account Manager</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['tab']) && $_GET['tab'] == 'manage' && $is_owner): ?>
        <div class="manager-box">
            <h3>Account Settings</h3>
            <?php if($error) echo "<p class='error-msg'>$error</p>"; ?>
            <?php if($success) echo "<p style='color:green;'>$success</p>"; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <label style="font-size:13px; font-weight:bold;">Change Profile Picture (.png, < 1MB):</label><br><br>
                <input type="file" name="new_pfp" accept=".png" required>
                <button type="submit" class="btn">Update PFP</button>
            </form>
            
            <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">
            
            <form method="POST" onsubmit="return confirm('WARNING: This will permanently delete your account and all videos. Proceed?');">
                <label style="color:#cc181e; font-weight:bold;">Danger Zone</label><br><br>
                <input type="hidden" name="delete_account" value="1">
                <button type="submit" class="btn" style="background:#cc181e;">Delete My Account</button>
            </form>
        </div>
    <?php else: ?>
        <div class="video-grid">
            <?php if (count($videos) > 0): ?>
                <?php foreach ($videos as $video): ?>
                    <div class="video-card">
                        <a href="watch.php?v=<?php echo $video['id']; ?>" class="thumb-wrapper">
                            <img src="uploads/thumbnails/<?php echo htmlspecialchars($video['thumbnail_name']); ?>">
                        </a>
                        <a href="watch.php?v=<?php echo $video['id']; ?>" class="video-title">
                            <?php echo htmlspecialchars($video['title']); ?>
                        </a>
                        <div class="video-meta">
                            <?php echo number_format($video['views']); ?> views<br>
                            Uploaded <?php echo date("M j, Y", strtotime($video['upload_date'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>This user has no videos.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php echo $footer_html; ?>

</body>
</html>
