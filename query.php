<?php
require_once 'config.php';

$search_query = isset($_GET['q']) ? cleanInput($_GET['q']) : '';

$stmt = $pdo->prepare("SELECT videos.*, users.username FROM videos 
                       JOIN users ON videos.user_id = users.id 
                       WHERE videos.title LIKE ? OR videos.description LIKE ? 
                       ORDER BY upload_date DESC");
$searchTerm = "%$search_query%";
$stmt->execute([$searchTerm, $searchTerm]);
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search results for "<?php echo htmlspecialchars($search_query); ?>" - MadreTube</title>
    <?php echo $retro_css; ?>
</head>
<body>
<?php echo $header_html; ?>

<div class="container" style="display:block;">
    <h2 style="font-size: 16px; color: #666; margin-bottom: 20px;">
        About <?php echo count($results); ?> results for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
    </h2>

    <?php if (count($results) > 0): ?>
        <?php foreach ($results as $video): ?>
            <div style="display: flex; gap: 15px; margin-bottom: 20px; border-bottom: 1px solid #e8e8e8; padding-bottom: 15px;">
                <div style="width: 200px;">
                    <a href="watch.php?v=<?php echo $video['id']; ?>" class="thumb-wrapper">
                        <img src="uploads/thumbnails/<?php echo $video['thumbnail_name']; ?>">
                    </a>
                </div>
                <div>
                    <a href="watch.php?v=<?php echo $video['id']; ?>" class="video-title" style="font-size: 16px;">
                        <?php echo htmlspecialchars($video['title']); ?>
                    </a>
                    <div class="video-meta" style="font-size: 12px; margin: 5px 0;">
                        by <a href="profile.php?id=<?php echo $video['user_id']; ?>" style="color:#666; font-weight:bold;"><?php echo htmlspecialchars($video['username']); ?></a> 
                        • <?php echo number_format($video['views']); ?> views
                    </div>
                    <p style="font-size: 13px; color: #666;"><?php echo substr(htmlspecialchars($video['description']), 0, 150); ?>...</p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No results found for your search.</p>
    <?php endif; ?>
</div>

<?php echo $footer_html; ?>
</body>
</html>
