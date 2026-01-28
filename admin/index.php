<?php
// admin/index.php - Admin Dashboard
require_once '../config.php';

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

$pageTitle = "Admin Dashboard";
$cssPath = "/css/style.css";

// Handle User Addition
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    $plate = $_POST['license_plate'];
    $green_cert = isset($_POST['green_cert']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, license_plate, green_cert_valid) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $username, $password, $full_name, $role, $plate, $green_cert);
    if ($stmt->execute()) {
        $msg = "User added successfully";
    } else {
        $error = "Error adding user: " . $conn->error;
    }
}

// Handle Traffic Mode
if (isset($_POST['update_mode'])) {
    $mode = $_POST['traffic_mode'];
    $conn->query("INSERT INTO system_settings (setting_key, setting_value) VALUES ('traffic_mode', '$mode') ON DUPLICATE KEY UPDATE setting_value='$mode'");
    $msg = "Traffic mode updated to $mode";
}

// Get current mode
$res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='traffic_mode'");
$current_mode = $res->num_rows > 0 ? $res->fetch_assoc()['setting_value'] : 'green';

// Fetch users
$users = $conn->query("SELECT * FROM users WHERE role != 'admin'");

include '../includes/header.php';
?>

<?php if (isset($msg)): ?><div class="alert success"><?php echo $msg; ?></div><?php endif; ?>
<?php if (isset($error)): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>

<!-- Quick Actions -->
<div class="card">
    <h2>Quick Actions</h2>
    <a href="/admin/scanner.php" class="btn" style="margin-right: 10px;">📷 Open Scanner</a>
</div>

<!-- Traffic Mode Control -->
<div class="card">
    <h2>Traffic Control Mode</h2>
    <form method="POST">
        <div class="form-group">
            <label>Current Mode: <strong style="text-transform: uppercase;"><?php echo $current_mode; ?></strong></label>
            <select name="traffic_mode">
                <option value="green" <?php if($current_mode=='green') echo 'selected'; ?>>🟢 Green (Free Entry)</option>
                <option value="yellow" <?php if($current_mode=='yellow') echo 'selected'; ?>>🟡 Yellow (Certificate Required)</option>
                <option value="red" <?php if($current_mode=='red') echo 'selected'; ?>>🔴 Red (Strict Schedule)</option>
            </select>
        </div>
        <button type="submit" name="update_mode" class="btn">Update Mode</button>
    </form>
</div>

<!-- Add User -->
<div class="card">
    <h2>Add New Teacher/Staff</h2>
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
        </div>
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="teacher_staff">Staff (Tenure)</option>
                    <option value="teacher_parttime">Part-time</option>
                </select>
            </div>
            <div class="form-group">
                <label>License Plate</label>
                <input type="text" name="license_plate" placeholder="Optional">
            </div>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="green_cert"> Has Valid Green Certificate
            </label>
        </div>
        <button type="submit" name="add_user" class="btn">Add User</button>
    </form>
</div>

<!-- User List -->
<div class="card">
    <h2>Users</h2>
    <div class="table-responsive">
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Role</th>
                <th>Plate</th>
                <th>Cert</th>
            </tr>
            <?php while($row = $users->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo str_replace('teacher_', '', $row['role']); ?></td>
                <td><?php echo htmlspecialchars($row['license_plate'] ?: '-'); ?></td>
                <td><?php echo $row['green_cert_valid'] ? '✓' : '✗'; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<style>
.alert {
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 1rem;
}
.alert.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.alert.error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

.form-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
.form-row .form-group {
    flex: 1;
    min-width: 150px;
}
.table-responsive {
    overflow-x: auto;
}
</style>

<?php include '../includes/footer.php'; ?>
