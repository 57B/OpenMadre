<?php
require_once 'config.php';

if (!isset($_GET['v']) || !is_numeric($_GET['v'])) {
    header("Location: 404.php");
    exit;
}

$video_id = (int)$_GET['v'];

// Increment View Count
$pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?")->execute([$video_id]);

// Fetch Video Details
$stmt = $pdo->prepare("SELECT videos.*, users.username, users.pfp FROM videos JOIN users ON videos.user_id = users.id WHERE videos.id = ?");
$stmt->execute([$video_id]);
$video = $stmt->fetch();

if (!$video) { header("Location: 404.php"); exit; }

// Log to History if logged in
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("INSERT INTO history (user_id, video_id) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $video_id]);
}

// Handle Comment Submission
$comment_error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_body'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php"); exit;
    }
    
    if (!checkCooldown('comment', 3, 15)) {
        $comment_error = "Cooldown: 3 comments max per 15 minutes.";
    } else {
        $text = cleanInput(substr($_POST['comment_body'], 0, 200));
        $parent = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
        
        if (!empty($text)) {
            $stmt = $pdo->prepare("INSERT INTO comments (video_id, user_id, comment_text, parent_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$video_id, $_SESSION['user_id'], $text, $parent]);
            header("Location: watch.php?v=$video_id"); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($video['title']); ?> - MadreTube</title>
    <?php echo $retro_css; ?>
    <style>
        .watch-container { width: 960px; margin: 20px auto; display: flex; gap: 20px; }
        .video-player-area { flex: 2; }
        .sidebar-area { flex: 1; }
        .video-box { background: #000; width: 100%; aspect-ratio: 16/9; }
        .video-info { background: #fff; padding: 15px; border: 1px solid #e8e8e8; margin-top: 10px; }
        .video-info h1 { font-size: 18px; margin: 0 0 10px 0; }
        .user-bar { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .comment-section { margin-top: 20px; background: #fff; padding: 15px; border: 1px solid #e8e8e8; }
        .comment-item { display: flex; gap: 10px; margin-bottom: 15px; border-bottom: 1px solid #f9f9f9; padding-bottom: 10px; }
        .comment-pfp { width: 48px; height: 48px; object-fit: cover; }
        .star-rating { color: #f1c40f; font-size: 14px; }
        .reply-box { margin-left: 58px; font-size: 12px; }
    </style>
</head>
<body>

<?php echo $header_html; ?>

<div class="watch-container">
    <div class="video-player-area">
        <div class="video-box">
            <video width="100%" height="100%" controls controlsList="nodownload">
                <source src="uploads/videos/<?php echo $video['file_name']; ?>" type="video/mp4">
            </video>
        </div>

        <div class="video-info">
            <h1><?php echo htmlspecialchars($video['title']); ?></h1>
            <div class="user-bar">
                <div style="display:flex; align-items:center;">
                    <a href="profile.php?id=<?php echo $video['user_id']; ?>">
                        <img src="uploads/pfps/<?php echo $video['pfp']; ?>" style="width:40px; height:40px; margin-right:10px;">
                    </a>
                    <div>
                        <a href="profile.php?id=<?php echo $video['user_id']; ?>" style="font-weight:bold; text-decoration:none; color:#333;">
                            <?php echo htmlspecialchars($video['username']); ?>
                        </a><br>
                        <button class="btn" style="padding:4px 8px; font-size:11px; margin-top:3px;">Subscribe</button>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:18px; font-weight:bold;"><?php echo number_format($video['views']); ?> views</div>
                    <div class="star-rating">★★★★★ (Rating system active)</div>
                </div>
            </div>
            <p style="font-size:13px; line-height:1.4;"><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
        </div>

        <div class="comment-section">
            <h3>Comments</h3>
            <?php if ($comment_error) echo "<p class='error-msg'>$comment_error</p>"; ?>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST" onsubmit="document.getElementById('cBtn').disabled=true;">
                <textarea name="comment_body" maxlength="200" style="width:100%; height:50px;" placeholder="Add a public comment..." required></textarea>
                <button type="submit" id="cBtn" class="btn" style="margin-top:5px;">Post Comment</button>
            </form>
            <?php else: ?>
                <p style="font-size:12px;"><a href="login.php">Sign in</a> to leave a comment.</p>
            <?php endif; ?>

            <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">

            <?php
            $cStmt = $pdo->prepare("SELECT comments.*, users.username, users.pfp FROM comments JOIN users ON comments.user_id = users.id WHERE video_id = ? ORDER BY created_at DESC");
            $cStmt->execute([$video_id]);
            while ($c = $cStmt->fetch()): ?>
                <div class="comment-item">
                    <img src="uploads/pfps/<?php echo $c['pfp']; ?>" class="comment-pfp">
                    <div>
                        <a href="profile.php?id=<?php echo $c['user_id']; ?>" style="font-weight:bold; font-size:13px; text-decoration:none; color:#167ac6;">
                            <?php echo htmlspecialchars($c['username']); ?>
                        </a>
                        <span style="font-size:11px; color:#999; margin-left:10px;"><?php echo $c['created_at']; ?></span>
                        <p style="font-size:13px; margin:5px 0;"><?php echo htmlspecialchars($c['comment_text']); ?></p>
                        <div class="star-rating" style="font-size:10px;">Rating: ★★★★★ | <a href="#" style="color:#666; text-decoration:none;">Reply</a></div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="sidebar-area">
        <h4 style="margin-top:0;">Suggested Videos</h4>
        <!-- Suggested videos logic would go here -->
        <p style="font-size:11px; color:#666;">Up next...</p>
    </div>
</div>

<?php echo $footer_html; ?>

</body>
</html>
