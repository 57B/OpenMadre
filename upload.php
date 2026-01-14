<?php
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Rate Limiting: 3 videos per hour
    if (!checkCooldown('upload', 3, 60)) {
        $error = "Cooldown active: You can only upload 3 videos every hour.";
    } else {
        $title = cleanInput($_POST['title']);
        $description = cleanInput($_POST['description']);
        $user_id = $_SESSION['user_id'];

        // File checks
        $videoFile = $_FILES['video'];
        $thumbFile = $_FILES['thumbnail'];

        $videoExt = strtolower(pathinfo($videoFile['name'], PATHINFO_EXTENSION));
        $thumbExt = strtolower(pathinfo($thumbFile['name'], PATHINFO_EXTENSION));

        // 2. Format & Size Validation
        if ($videoExt !== 'mp4' || $thumbExt !== 'png') {
            $error = "Invalid format: Video must be .mp4 and Thumbnail must be .png";
        } elseif ($videoFile['size'] > 15 * 1024 * 1024) {
            $error = "Video exceeds 15MB limit.";
        } elseif ($thumbFile['size'] > 1 * 1024 * 1024) {
            $error = "Thumbnail exceeds 1MB limit.";
        } else {
            // 3. Duplicate Prevention (Hash check)
            $videoHash = hash_file('sha256', $videoFile['tmp_name']);
            $stmt = $pdo->prepare("SELECT id FROM videos WHERE file_name = ?");
            $stmt->execute([$videoHash . ".mp4"]);
            
            if ($stmt->rowCount() > 0) {
                $error = "You cannot upload the same video twice.";
            } else {
                $videoName = $videoHash . ".mp4";
                $thumbName = bin2hex(random_bytes(10)) . ".png";

                if (move_uploaded_file($videoFile['tmp_name'], "uploads/videos/" . $videoName) &&
                    move_uploaded_file($thumbFile['tmp_name'], "uploads/thumbnails/" . $thumbName)) {
                    
                    $stmt = $pdo->prepare("INSERT INTO videos (user_id, title, description, file_name, thumbnail_name) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $title, $description, $videoName, $thumbName]);
                    
                    $success = "Video uploaded successfully!";
                } else {
                    $error = "Server error during file transfer.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Video - MadreTube</title>
    <?php echo $retro_css; ?>
    <style>
        .upload-container { background: #fff; border: 1px solid #e8e8e8; padding: 20px; width: 600px; margin: 20px auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; font-size: 13px; margin-bottom: 5px; }
        .form-group input[type="text"], .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box; }
        .form-group textarea { height: 100px; resize: none; }
        .requirements { font-size: 11px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>

<?php echo $header_html; ?>

<div class="upload-container">
    <h2 style="font-size: 18px; border-bottom: 1px solid #e8e8e8; padding-bottom: 10px; margin-top: 0;">Upload Video</h2>
    
    <?php if($error) echo "<p class='error-msg'>$error</p>"; ?>
    <?php if($success) echo "<p style='color:green; font-weight:bold;'>$success</p>"; ?>

    <form method="POST" enctype="multipart/form-data" onsubmit="document.getElementById('submitBtn').disabled=true;">
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" required placeholder="No special characters">
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" required placeholder="No special characters"></textarea>
        </div>

        <div class="form-group">
            <label>Video File (.mp4 only, max 15MB)</label>
            <input type="file" name="video" accept=".mp4" required>
        </div>

        <div class="form-group">
            <label>Thumbnail Image (.png only, max 1MB)</label>
            <input type="file" name="thumbnail" accept=".png" required>
        </div>

        <button type="submit" id="submitBtn" class="btn">Upload Video</button>
        <p class="requirements">By clicking upload, you agree to our Terms of Service.</p>
    </form>
</div>

<?php echo $footer_html; ?>

</body>
</html>
