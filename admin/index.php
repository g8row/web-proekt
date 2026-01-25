<?php
// admin/index.php
require_once '../auth.php';
checkAdmin();

// Handle User Addition
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    $plate = $_POST['license_plate'];
    $green_cert = isset($_POST['green_cert']) ? 1 : 0; // Checkbox

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
$users = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <nav>
            <a href="../calendar/index.php">Calendar</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>
    <main>
        <?php if (isset($msg)) echo "<p style='color:green'>$msg</p>"; ?>
        <?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>

        <!-- Traffic Mode Control -->
        <div class="card">
            <h2>Traffic Control Mode</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Current Mode: <strong><?php echo ucfirst($current_mode); ?></strong></label>
                    <select name="traffic_mode">
                        <option value="green" <?php if($current_mode=='green') echo 'selected'; ?>>Green (Free Entry)</option>
                        <option value="yellow" <?php if($current_mode=='yellow') echo 'selected'; ?>>Yellow (Certificate Required)</option>
                        <option value="red" <?php if($current_mode=='red') echo 'selected'; ?>>Red (Strict Schedule)</option>
                    </select>
                </div>
                <button type="submit" name="update_mode" class="btn">Update Mode</button>
            </form>
        </div>

        <!-- Add User -->
        <div class="card">
            <h2>Add New Teacher/Staff</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="teacher_staff">Staff (Tenure)</option>
                        <option value="teacher_parttime">Part-time</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>License Plate</label>
                    <input type="text" name="license_plate">
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
            <h2>Users List</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Plate</th>
                    <th>Green Cert</th>
                </tr>
                <?php while($row = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo $row['role']; ?></td>
                    <td><?php echo htmlspecialchars($row['license_plate']); ?></td>
                    <td><?php echo $row['green_cert_valid'] ? 'Yes' : 'No'; ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </main>
</body>
</html>
