<?php
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Handle Playlist Creation
if (isset($_POST['create_playlist']) && !empty($_POST['playlist_title'])) {
    $title = trim($_POST['playlist_title']);
    $stmt = $pdo->prepare("INSERT INTO playlists (user_id, title, video_ids) VALUES (?, ?, '')");
    $stmt->execute([$user_id, $title]);
    header("Location: playlists.php");
    exit;
}

// 2. Fetch User Playlists
$stmt = $pdo->prepare("SELECT * FROM playlists WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$playlists = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Playlists - MadreTube</title>
    <?php echo $retro_css; ?>
    <style>
        .playlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .playlist-card { background: #f9f9f9; border: 1px solid #e8e8e8; padding: 10px; text-align: center; }
        .playlist-thumb-stack { 
            width: 100%; height: 110px; background: #333; 
            margin-bottom: 8px; position: relative; 
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; border-radius: 2px;
            box-shadow: 3px 3px 0px #ccc;
        }
        .playlist-title { font-weight: bold; color: #167ac6; text-decoration: none; display: block; margin-bottom: 5px; }
        .video-count { font-size: 11px; color: #666; }
        .create-box { background: #f1f1f1; border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; }
        .sidebar-section { margin-bottom: 20px; border-bottom: 1px solid #e8e8e8; padding-bottom: 10px; }
        .sidebar-link { display: block; padding: 5px 0; color: #666; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>

<?php echo $header_html; ?>

<div class="container">
    <div class="sidebar">
        <div class="sidebar-section">
            <a href="index.php" class="sidebar-link">🏠 Home</a>
            <a href="playlists.php" class="sidebar-link" style="font-weight:bold; color:#cc181e;">📁 Playlists</a>
        </div>
        
        <div class="sidebar-section">
            <h3>New Playlist</h3>
            <form method="POST">
                <input type="text" name="playlist_title" placeholder="Enter title..." style="width:90%; padding:5px; font-size:12px;" required>
                <button type="submit" name="create_playlist" class="btn" style="margin-top:5px; width:100%;">Create</button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <h2 style="border-bottom: 1px solid #e8e8e8; padding-bottom: 10px;">My Playlists</h2>

        <?php if (count($playlists) > 0): ?>
            <div class="playlist-grid">
                <?php foreach ($playlists as $pl): 
                    // Count videos in playlist
                    $ids = array_filter(explode(',', $pl['video_ids']));
                    $count = count($ids);
                    
                    // Fetch first video thumbnail for the cover
                    $cover = "default_thumb.png";
                    if($count > 0) {
                        $thumbStmt = $pdo->prepare("SELECT thumbnail_name FROM videos WHERE id = ?");
                        $thumbStmt->execute([$ids[0]]);
                        $vData = $thumbStmt->fetch();
                        if($vData) $cover = $vData['thumbnail_name'];
                    }
                ?>
                    <div class="playlist-card">
                        <a href="view_playlist.php?id=<?php echo $pl['id']; ?>" style="text-decoration:none;">
                            <div class="playlist-thumb-stack" style="background: url('uploads/thumbnails/<?php echo $cover; ?>') center/cover;">
                                <div style="background: rgba(0,0,0,0.6); width: 40%; height: 100%; position: absolute; right: 0; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <span><?php echo $count; ?></span>
                                    <span style="font-size:10px;">VIDEOS</span>
                                </div>
                            </div>
                            <span class="playlist-title"><?php echo htmlspecialchars($pl['title']); ?></span>
                        </a>
                        <span class="video-count">Updated recently</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#666; margin-top:20px;">You haven't created any playlists yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php echo $footer_html; ?>

</body>
</html>
