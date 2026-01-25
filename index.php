<?php
// index.php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (login($username, $password)) {
        if ($_SESSION['role'] === 'admin') {
            header("Location: admin/index.php");
        } else {
            // For now, redirect others to calendar or a profile page
            header("Location: calendar/index.php");
        }
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FMI Parking - Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>FMI Parking System</h1>
    </header>
    <main>
        <div class="card" style="max-width: 400px; margin: 2rem auto;">
            <h2>Login</h2>
            <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 1rem;"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
            <p style="margin-top: 1rem;">
                <a href="gate/index.php">Go to Gate Simulation</a>
            </p>
        </div>
    </main>
</body>
</html>
