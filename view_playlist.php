<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$playlist_id = $_GET['id'] ?? null;

// Verify playlist ownership
$stmt = $pdo->prepare("SELECT * FROM playlists WHERE id = ? AND user_id = ?");
$stmt->execute([$playlist_id, $user_id]);
$playlist = $stmt->fetch();

if (!$playlist) {
    die("Playlist not found or access denied.");
}

// 1. Handle Adding Video to Playlist
if (isset($_POST['add_video_id'])) {
    $new_video_id = $_POST['add_video_id'];
    $current_ids = array_filter(explode(',', $playlist['video_ids']));
    
    if (!in_array($new_video_id, $current_ids)) {
        $current_ids[] = $new_video_id;
        $updated_ids = implode(',', $current_ids);
        
        $updateStmt = $pdo->prepare("UPDATE playlists SET video_ids = ? WHERE id = ?");
        $updateStmt->execute([$updated_ids, $playlist_id]);
        header("Location: view_playlist.php?id=" . $playlist_id);
        exit;
    }
}

// 2. Handle Removing Video from Playlist
if (isset($_GET['remove'])) {
    $remove_id = $_GET['remove'];
    $current_ids = array_filter(explode(',', $playlist['video_ids']));
    $current_ids = array_diff($current_ids, [$remove_id]);
    
    $updated_ids = implode(',', $current_ids);
    $updateStmt = $pdo->prepare("UPDATE playlists SET video_ids = ? WHERE id = ?");
    $updateStmt->execute([$updated_ids, $playlist_id]);
    header("Location: view_playlist.php?id=" . $playlist_id);
    exit;
}

// Fetch videos currently in the playlist
$playlist_videos = [];
if (!empty($playlist['video_ids'])) {
    // We use FIND_IN_SET to maintain the order of the comma-separated list
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id IN (" . $playlist['video_ids'] . ") ORDER BY FIELD(id, " . $playlist['video_ids'] . ")");
    $stmt->execute();
    $playlist_videos = $stmt->fetchAll();
}

// Fetch all available videos to add (excluding ones already in the playlist)
$exclude_clause = !empty($playlist['video_ids']) ? "WHERE id NOT IN (" . $playlist['video_ids'] . ")" : "";
$all_videos = $pdo->query("SELECT * FROM videos $exclude_clause ORDER BY id DESC LIMIT 20")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($playlist['title']); ?> - MadreTube</title>
    <?php echo $retro_css; ?>
    <style>
        .split-container { display: flex; gap: 30px; margin-top: 20px; }
        .column { flex: 1; }
        .video-item { 
            display: flex; gap: 10px; background: #fff; border: 1px solid #ddd; 
            padding: 5px; margin-bottom: 10px; align-items: center;
        }
        .video-item img { width: 100px; height: 60px; object-fit: cover; }
        .video-info { flex-grow: 1; }
        .video-info a { font-weight: bold; text-decoration: none; color: #167ac6; font-size: 13px; }
        .btn-small { font-size: 11px; padding: 2px 5px; cursor: pointer; }
        .section-title { border-bottom: 2px solid #cc181e; padding-bottom: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>

<?php echo $header_html; ?>

<div class="container">
    <a href="playlists.php" style="font-size: 12px;">&laquo; Back to Playlists</a>
    <h2>Playlist: <?php echo htmlspecialchars($playlist['title']); ?></h2>

    <div class="split-container">
        <!-- Left: Current Playlist Videos -->
        <div class="column">
            <h3 class="section-title">Videos in Playlist</h3>
            <?php if (empty($playlist_videos)): ?>
                <p>This playlist is empty.</p>
            <?php else: ?>
                <?php foreach ($playlist_videos as $v): ?>
                    <div class="video-item">
                        <img src="uploads/thumbnails/<?php echo $v['thumbnail_name']; ?>">
                        <div class="video-info">
                            <a href="watch.php?v=<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['title']); ?></a>
                            <br>
                            <a href="view_playlist.php?id=<?php echo $playlist_id; ?>&remove=<?php echo $v['id']; ?>" style="color:red; font-size:10px;">[Remove]</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Right: Available Videos to Add -->
        <div class="column" style="background: #f4f4f4; padding: 15px; border-radius: 4px;">
            <h3 class="section-title">Add Videos</h3>
            <?php foreach ($all_videos as $v): ?>
                <div class="video-item">
                    <img src="uploads/thumbnails/<?php echo $v['thumbnail_name']; ?>">
                    <div class="video-info">
                        <span style="font-size:12px; font-weight:bold;"><?php echo htmlspecialchars($v['title']); ?></span>
                        <form method="POST" style="margin-top:5px;">
                            <input type="hidden" name="add_video_id" value="<?php echo $v['id']; ?>">
                            <button type="submit" class="btn btn-small">Add to Playlist</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php echo $footer_html; ?>

</body>
</html>
