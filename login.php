<?php
require_once 'config.php';

$error = "";
$success = "";

// Handle Registration or Login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $username = cleanInput($_POST['username']);
    $password = $_POST['password']; // Password not cleaned to allow complex characters for security, but hashed
    
    if ($action == 'register') {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Username or Email already exists.";
        } else {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, pfp) VALUES (?, ?, ?, 'default.png')");
            if ($stmt->execute([$username, $email, $hashed_pass])) {
                $success = "Account created! You can now log in.";
            } else {
                $error = "Error creating account.";
            }
        }
    } elseif ($action == 'login') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In - MadreTube</title>
    <?php echo $retro_css; ?>
    <style>
        .login-container { width: 350px; margin: 50px auto; background: #fff; border: 1px solid #e8e8e8; padding: 25px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .login-container h2 { font-size: 20px; margin-bottom: 20px; text-align: center; color: #333; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; }
        .input-group input { width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box; }
        .toggle-form { text-align: center; margin-top: 15px; font-size: 12px; }
        .toggle-form a { color: #167ac6; text-decoration: none; cursor: pointer; }
    </style>
</head>
<body>

<?php echo $header_html; ?>

<div class="login-container" id="loginBox">
    <h2 id="formTitle">Sign In</h2>
    
    <?php if($error) echo "<p class='error-msg'>$error</p>"; ?>
    <?php if($success) echo "<p style='color:green; font-size:12px;'>$success</p>"; ?>

    <form method="POST" id="authForm">
        <input type="hidden" name="action" id="formAction" value="login">
        
        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>

        <div id="emailField" class="input-group" style="display:none;">
            <label>Email Address</label>
            <input type="email" name="email">
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn" style="width:100%;">Sign In</button>
    </form>

    <div class="toggle-form">
        <span id="toggleText">New to MadreTube?</span> 
        <a onclick="toggleAuth()">Create an account</a>
    </div>
</div>

<script>
function toggleAuth() {
    var title = document.getElementById('formTitle');
    var action = document.getElementById('formAction');
    var emailField = document.getElementById('emailField');
    var toggleText = document.getElementById('toggleText');
    var submitBtn = document.querySelector('.btn');
    var link = document.querySelector('.toggle-form a');

    if (action.value === 'login') {
        title.innerText = 'Create your Account';
        action.value = 'register';
        emailField.style.display = 'block';
        toggleText.innerText = 'Already have an account?';
        link.innerText = 'Sign in';
        submitBtn.innerText = 'Register';
    } else {
        title.innerText = 'Sign In';
        action.value = 'login';
        emailField.style.display = 'none';
        toggleText.innerText = 'New to MadreTube?';
        link.innerText = 'Create an account';
        submitBtn.innerText = 'Sign In';
    }
}
</script>

<?php echo $footer_html; ?>

</body>
</html>
