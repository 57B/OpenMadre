<?php
require_once 'config.php';

// Fetch videos from the database
$stmt = $pdo->query("SELECT videos.*, users.username FROM videos 
                     JOIN users ON videos.user_id = users.id 
                     ORDER BY upload_date DESC LIMIT 20");
$videos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MadreTube - Cast Yourself</title>
    <?php echo $retro_css; ?>
    <style>
        .sidebar-section { margin-bottom: 20px; border-bottom: 1px solid #e8e8e8; padding-bottom: 10px; }
        .sidebar-section h3 { font-size: 13px; color: #333; margin-bottom: 10px; text-transform: uppercase; }
        .sidebar-link { display: block; padding: 5px 0; color: #666; text-decoration: none; font-size: 13px; }
        .sidebar-link:hover { color: #167ac6; text-decoration: underline; }
        .user-pfp-small { width: 24px; height: 24px; vertical-align: middle; border-radius: 2px; margin-right: 8px; }
    </style>
</head>
<body>

<?php echo $header_html; ?>

<div class="container">
    <!-- Left Sidebar -->
    <div class="sidebar">
        <div class="sidebar-section">
            <a href="index.php" class="sidebar-link" style="font-weight:bold; color:#cc181e;">🏠 Home</a>
            <a href="#" class="sidebar-link">🔥 Trending</a>
            <a href="terms.php" class="sidebar-link">📜 Terms</a>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="sidebar-section">
            <h3>Library</h3>
            <a href="history.php" class="sidebar-link">🕒 History</a>
            <a href="playlists.php" class="sidebar-link">📁 Playlists</a>
            <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="sidebar-link">📹 My Videos</a>
        </div>
        
        <div class="sidebar-section">
            <h3>Subscriptions</h3>
            <?php
            // Fetch subscriptions
            $subStmt = $pdo->prepare("SELECT users.id, users.username, users.pfp FROM subscriptions 
                                     JOIN users ON subscriptions.channel_id = users.id 
                                     WHERE subscriptions.subscriber_id = ? LIMIT 10");
            $subStmt->execute([$_SESSION['user_id']]);
            $subs = $subStmt->fetchAll();
            
            if (count($subs) > 0) {
                foreach ($subs as $sub) {
                    echo "<a href='profile.php?id=".$sub['id']."' class='sidebar-link'>
                            <img src='uploads/pfps/".htmlspecialchars($sub['pfp'])."' class='user-pfp-small'> 
                            ".htmlspecialchars($sub['username'])."
                          </a>";
                }
            } else {
                echo "<p style='font-size:11px; color:#999;'>No subscriptions yet.</p>";
            }
            ?>
        </div>
        <?php else: ?>
        <div class="sidebar-section">
            <p style="font-size:12px; color:#666;">Sign in now to see your channels and recommendations!</p>
            <a href="login.php" class="btn" style="display:inline-block; text-decoration:none; margin-top:5px; font-size:11px;">Sign In</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Main Video Grid -->
    <div class="main-content">
        <h2 style="font-size: 18px; margin-top: 0; border-bottom: 1px solid #e8e8e8; padding-bottom: 10px;">Recommended</h2>
        
        <div class="video-grid">
            <?php if (count($videos) > 0): ?>
                <?php foreach ($videos as $video): ?>
                    <div class="video-card">
                        <a href="watch.php?v=<?php echo $video['id']; ?>" class="thumb-wrapper">
                            <img src="uploads/thumbnails/<?php echo htmlspecialchars($video['thumbnail_name']); ?>" alt="Thumbnail">
                        </a>
                        <a href="watch.php?v=<?php echo $video['id']; ?>" class="video-title">
                            <?php echo htmlspecialchars($video['title']); ?>
                        </a>
                        <div class="video-meta">
                            by <a href="profile.php?id=<?php echo $video['user_id']; ?>" style="text-decoration:none; color:#666; font-weight:bold;"><?php echo htmlspecialchars($video['username']); ?></a><br>
                            <?php echo number_format($video['views']); ?> views • 
                            <?php echo date("M j, Y", strtotime($video['upload_date'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No videos found. Be the first to upload!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php echo $footer_html; ?>

</body>
</html>
