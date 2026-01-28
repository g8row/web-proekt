<?php
// includes/header.php
// Shared header with role-based navigation

if (!isset($_SESSION)) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['role'] === 'admin';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'FMI Parking'; ?></title>
    <link rel="stylesheet" href="<?php echo $cssPath ?? '/css/style.css'; ?>">
</head>
<body>
    <header>
        <h1>FMI Parking</h1>
        <nav>
            <?php if ($isAdmin): ?>
                <a href="/admin/index.php" <?php if($currentPage == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) echo 'class="active"'; ?>>Dashboard</a>
                <a href="/admin/scanner.php" <?php if($currentPage == 'scanner.php') echo 'class="active"'; ?>>Scanner</a>
                <a href="/logout.php">Logout</a>
            <?php elseif ($isLoggedIn): ?>
                <a href="/user/index.php" <?php if($currentPage == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/user/') !== false) echo 'class="active"'; ?>>My QR</a>
                <a href="/user/vacation.php" <?php if($currentPage == 'vacation.php') echo 'class="active"'; ?>>Vacation</a>
                <a href="/logout.php">Logout</a>
            <?php else: ?>
                <a href="/index.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
