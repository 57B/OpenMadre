<?php
require_once 'config.php';

// Redirect to login if the user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle History Clearing
if (isset($_POST['clear_history'])) {
    $stmt = $pdo->prepare("DELETE FROM history WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: history.php");
    exit;
}

// Fetch Watch History
// We join with videos to get metadata and users to get the uploader's name
$stmt = $pdo->prepare("
    SELECT h.viewed_at, v.*, u.username 
    FROM history h
    JOIN videos v ON h.video_id = v.id
    JOIN users u ON v.user_id = u.id
    WHERE h.user_id = ?
    ORDER BY h.viewed_at DESC
");
$stmt->execute([$user_id]);
$history_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Watch History - MadreTube</title>
    <?php echo $retro_css; ?>
    <style>
        .history-item { 
            display: flex; 
            gap: 15px; 
            padding: 10px 0; 
            border-bottom: 1px solid #e8e8e8; 
        }
        .history-thumb { 
            width: 160px; 
            height: 90px; 
            object-fit: cover; 
            border: 1px solid #ccc;
        }
        .history-info h3 { margin: 0 0 5px 0; font-size: 16px; }
        .history-info h3 a { color: #167ac6; text-decoration: none; }
        .history-meta { font-size: 12px; color: #666; line-height: 1.6; }
        .history-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid #ccc; 
            padding-bottom: 10px; 
            margin-bottom: 15px; 
        }
        .sidebar-section { margin-bottom: 20px; border-bottom: 1px solid #e8e8e8; padding-bottom: 10px; }
        .sidebar-link { display: block; padding: 5px 0; color: #666; text-decoration: none; font-size: 13px; }
        .search-box { flex-grow: 0.5; display: flex; margin-left: 25px; width: 700px; }
    </style>
</head>
<body>

<?php echo $header_html; ?>

<div class="container">
    <!-- Shared Sidebar -->
    <div class="sidebar">
        <div class="sidebar-section">
            <a href="index.php" class="sidebar-link">🏠 Home</a>
            <a href="history.php" class="sidebar-link" style="font-weight:bold; color:#cc181e;">🕒 History</a>
        </div>
        <div class="sidebar-section">
            <h3>Manage History</h3>
            <form method="POST" onsubmit="return confirm('Clear your entire watch history?');">
                <button type="submit" name="clear_history" class="btn" style="width:100%; cursor:pointer;">Clear All History</button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="history-header">
            <h2 style="margin:0;">Watch History</h2>
        </div>

        <?php if (count($history_items) > 0): ?>
            <?php foreach ($history_items as $item): ?>
                <div class="history-item">
                    <a href="watch.php?v=<?php echo $item['id']; ?>">
                        <img src="uploads/thumbnails/<?php echo htmlspecialchars($item['thumbnail_name']); ?>" class="history-thumb">
                    </a>
                    <div class="history-info">
                        <h3><a href="watch.php?v=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['title']); ?></a></h3>
                        <div class="history-meta">
                            by <a href="profile.php?id=<?php echo $item['user_id']; ?>" style="color:#666; font-weight:bold;"><?php echo htmlspecialchars($item['username']); ?></a><br>
                            <?php echo number_format($item['views']); ?> views • 
                            Watched on <?php echo date("M j, Y \a\\t g:i a", strtotime($item['viewed_at'])); ?>
                        </div>
                        <p style="font-size:12px; color:#333; margin-top:5px;">
                            <?php echo substr(htmlspecialchars($item['description']), 0, 150) . '...'; ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding: 20px; text-align: center; color: #666;">
                <p>Your watch history is empty.</p>
                <a href="index.php" class="btn">Go Watch Some Videos</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php echo $footer_html; ?>

</body>
</html>
