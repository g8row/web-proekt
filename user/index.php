<?php
// user/index.php - User Dashboard (QR Code Display)
require_once '../config.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') {
    header("Location: /index.php");
    exit();
}

$pageTitle = "My QR Code";
$cssPath = "/css/style.css";
include '../includes/header.php';

$userId = $_SESSION['user_id'];

// Get user info
$stmt = $conn->prepare("SELECT full_name, license_plate FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<div class="card qr-card">
    <h2>My Digital Key</h2>
    <p>Present this QR code at the gate scanner.</p>
    
    <div class="qr-display">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $userId; ?>" alt="QR Code">
    </div>
    
    <div class="user-info">
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
        <p><strong>User ID:</strong> <?php echo $userId; ?></p>
        <?php if ($user['license_plate']): ?>
            <p><strong>License Plate:</strong> <?php echo htmlspecialchars($user['license_plate']); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
.qr-card {
    text-align: center;
    max-width: 400px;
    margin: 0 auto;
}
.qr-display {
    background: #fff;
    padding: 20px;
    border: 3px solid #004d40;
    border-radius: 12px;
    display: inline-block;
    margin: 20px 0;
}
.qr-display img {
    display: block;
}
.user-info {
    text-align: left;
    background: #f5f5f5;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
}
.user-info p {
    margin: 8px 0;
}
</style>

<?php include '../includes/footer.php'; ?>
