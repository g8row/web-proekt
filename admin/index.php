<?php
// admin/index.php - Admin Dashboard
require_once '../config.php';
require_once '../models/Schedule.php';

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

$pageTitle = "Admin Dashboard";
$cssPath = "/css/style.css";
$msg = "";
$error = "";

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
    $msg = "Traffic mode updated to " . strtoupper($mode);
}

// Handle Schedule Addition
if (isset($_POST['add_schedule'])) {
    $user_id = intval($_POST['user_id']);
    $day = $_POST['day'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $room = $_POST['room'];
    
    if (Schedule::add($user_id, $day, $start, $end, $room)) {
        $msg = "Schedule entry added";
    } else {
        $error = "Error adding schedule";
    }
}

// Handle Schedule Deletion
if (isset($_GET['delete_schedule'])) {
    $scheduleId = intval($_GET['delete_schedule']);
    $conn->query("DELETE FROM schedule WHERE id = $scheduleId");
    header("Location: /admin/index.php");
    exit();
}

// Handle Block/Vacation Addition
if (isset($_POST['add_block'])) {
    $blockUserId = intval($_POST['block_user_id']);
    $startDate = $_POST['block_start'];
    $endDate = $_POST['block_end'];
    $reason = $_POST['block_reason'] ?: 'Vacation';
    
    if ($blockUserId && $startDate && $endDate && $startDate <= $endDate) {
        $stmt = $conn->prepare("INSERT INTO user_blocks (user_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $blockUserId, $startDate, $endDate, $reason);
        if ($stmt->execute()) {
            $msg = "Block period added";
        } else {
            $error = "Error adding block";
        }
    } else {
        $error = "Invalid block data";
    }
}

// Handle Block Deletion
if (isset($_GET['delete_block'])) {
    $blockId = intval($_GET['delete_block']);
    $conn->query("DELETE FROM user_blocks WHERE id = $blockId");
    header("Location: /admin/index.php");
    exit();
}

// Get current mode
$res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='traffic_mode'");
$current_mode = $res->num_rows > 0 ? $res->fetch_assoc()['setting_value'] : 'green';

// Fetch users (non-admin)
$users = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY full_name");
$usersList = [];
while ($row = $users->fetch_assoc()) {
    $usersList[] = $row;
}

// Fetch schedules
$schedules = Schedule::getAll();

// Fetch all vacations/blocks
$vacationsResult = $conn->query("SELECT ub.*, u.full_name FROM user_blocks ub JOIN users u ON ub.user_id = u.id ORDER BY ub.start_date DESC");
$vacations = [];
while ($v = $vacationsResult->fetch_assoc()) {
    $vacations[] = $v;
}

// Days of week
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

include '../includes/header.php';
?>

<?php if ($msg): ?><div class="alert success"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>

<!-- Traffic Mode Control -->
<div class="card card-compact">
    <div class="card-header">
        <h2>Traffic Mode</h2>
        <span class="mode-badge mode-<?php echo $current_mode; ?>"><?php echo strtoupper($current_mode); ?></span>
    </div>
    <form method="POST" class="mode-form">
        <div class="mode-options">
            <label class="mode-option <?php if($current_mode=='green') echo 'active'; ?>">
                <input type="radio" name="traffic_mode" value="green" <?php if($current_mode=='green') echo 'checked'; ?>>
                <span class="mode-dot green"></span>
                <span class="mode-label">Green</span>
                <span class="mode-desc">Free Entry</span>
            </label>
            <label class="mode-option <?php if($current_mode=='yellow') echo 'active'; ?>">
                <input type="radio" name="traffic_mode" value="yellow" <?php if($current_mode=='yellow') echo 'checked'; ?>>
                <span class="mode-dot yellow"></span>
                <span class="mode-label">Yellow</span>
                <span class="mode-desc">Certificate Required</span>
            </label>
            <label class="mode-option <?php if($current_mode=='red') echo 'active'; ?>">
                <input type="radio" name="traffic_mode" value="red" <?php if($current_mode=='red') echo 'checked'; ?>>
                <span class="mode-dot red"></span>
                <span class="mode-label">Red</span>
                <span class="mode-desc">Schedule Only</span>
            </label>
        </div>
        <button type="submit" name="update_mode" class="btn">Update Mode</button>
    </form>
</div>

<!-- Schedule Calendar -->
<div class="card">
    <h2>Room Schedule</h2>
    
    <!-- Add Schedule Form -->
    <form method="POST" class="schedule-form">
        <div class="form-row">
            <div class="form-group">
                <label>Teacher</label>
                <select name="user_id" required>
                    <option value="">Select...</option>
                    <?php foreach ($usersList as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Day</label>
                <select name="day" required>
                    <?php foreach ($days as $d): ?>
                        <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Room</label>
                <input type="text" name="room" placeholder="e.g. 101" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Start Time</label>
                <input type="time" name="start_time" required>
            </div>
            <div class="form-group">
                <label>End Time</label>
                <input type="time" name="end_time" required>
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <button type="submit" name="add_schedule" class="btn">+ Add</button>
            </div>
        </div>
    </form>
    
    <!-- Calendar Grid -->
    <div class="calendar-grid">
        <?php foreach ($days as $day): ?>
            <div class="day-column">
                <div class="day-header"><?php echo substr($day, 0, 3); ?></div>
                <div class="day-slots">
                    <?php foreach ($schedules as $s): ?>
                        <?php if ($s['day_of_week'] === $day): ?>
                            <div class="slot">
                                <div class="slot-time"><?php echo substr($s['start_time'], 0, 5); ?> - <?php echo substr($s['end_time'], 0, 5); ?></div>
                                <div class="slot-room">Room <?php echo htmlspecialchars($s['room']); ?></div>
                                <div class="slot-teacher"><?php echo htmlspecialchars($s['full_name'] ?? 'Unknown'); ?></div>
                                <a href="?delete_schedule=<?php echo $s['id']; ?>" class="slot-delete" onclick="return confirm('Delete?')">×</a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- User Management -->
<div class="card">
    <h2>Users</h2>
    
    <!-- Add User Form (Collapsible) -->
    <details class="add-user-section">
        <summary class="btn btn-outline">+ Add New User</summary>
        <form method="POST" class="user-form">
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
            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="green_cert">
                    <span>Has Valid Green Certificate</span>
                </label>
            </div>
            <button type="submit" name="add_user" class="btn">Add User</button>
        </form>
    </details>
    
    <!-- User List -->
    <div class="user-grid">
        <?php foreach ($usersList as $row): ?>
            <div class="user-card">
                <div class="user-avatar"><?php echo strtoupper(substr($row['full_name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                    <div class="user-meta">
                        <span class="user-role <?php echo $row['role']; ?>"><?php echo str_replace('teacher_', '', $row['role']); ?></span>
                        <?php if ($row['license_plate']): ?>
                            <span class="user-plate"><?php echo htmlspecialchars($row['license_plate']); ?></span>
                        <?php endif; ?>
                        <?php if ($row['green_cert_valid']): ?>
                            <span class="user-cert">✓ Cert</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Vacations / Blocks -->
<div class="card">
    <h2>Vacations & Business Trips</h2>
    
    <!-- Add Block Form -->
    <form method="POST" class="block-form">
        <div class="form-row">
            <div class="form-group">
                <label>User</label>
                <select name="block_user_id" required>
                    <option value="">Select...</option>
                    <?php foreach ($usersList as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="block_start" required>
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="block_end" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Reason</label>
                <select name="block_reason">
                    <option value="Vacation">Vacation (Отпуск)</option>
                    <option value="Business Trip">Business Trip (Командировка)</option>
                    <option value="Sick Leave">Sick Leave (Болничен)</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <button type="submit" name="add_block" class="btn">+ Add Block</button>
            </div>
        </div>
    </form>
    
    <!-- Block List -->
    <?php if (empty($vacations)): ?>
        <p style="color: #666; margin-top: 1rem;">No vacation periods registered.</p>
    <?php else: ?>
        <div class="vacation-list" style="margin-top: 1rem;">
            <?php 
            $today = date('Y-m-d');
            foreach ($vacations as $v): 
                $isActive = ($v['start_date'] <= $today && $v['end_date'] >= $today);
                $isPast = ($v['end_date'] < $today);
            ?>
                <div class="vacation-item <?php echo $isActive ? 'active' : ($isPast ? 'past' : ''); ?>">
                    <div class="vacation-user"><?php echo htmlspecialchars($v['full_name']); ?></div>
                    <div class="vacation-dates">
                        <?php echo date('M j', strtotime($v['start_date'])); ?> - <?php echo date('M j, Y', strtotime($v['end_date'])); ?>
                    </div>
                    <div class="vacation-reason"><?php echo htmlspecialchars($v['reason']); ?></div>
                    <?php if ($isActive): ?>
                        <span class="vacation-status">ACTIVE</span>
                    <?php endif; ?>
                    <a href="?delete_block=<?php echo $v['id']; ?>" class="btn-delete" onclick="return confirm('Delete this block?')">×</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Alerts */
.alert {
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 1rem;
}
.alert.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.alert.error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

/* Card Compact */
.card-compact { padding: 1rem; }
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}
.card-header h2 { margin: 0; border: none; padding: 0; }

/* Mode Badge */
.mode-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.85rem;
}
.mode-green { background: #c8e6c9; color: #2e7d32; }
.mode-yellow { background: #fff9c4; color: #f57f17; }
.mode-red { background: #ffcdd2; color: #c62828; }

/* Mode Options */
.mode-options {
    display: flex;
    gap: 10px;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.mode-option {
    flex: 1;
    min-width: 100px;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.mode-option:hover { border-color: #00796b; }
.mode-option.active { border-color: #004d40; background: #e0f2f1; }
.mode-option input { display: none; }
.mode-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-block;
    margin: 0 auto 5px;
}
.mode-dot.green { background: #4caf50; }
.mode-dot.yellow { background: #ffc107; }
.mode-dot.red { background: #f44336; }
.mode-label { font-weight: bold; display: block; margin-top: 5px; }
.mode-desc { font-size: 0.75rem; color: #666; display: block; }

/* Checkbox alignment */
.checkbox-group { margin-top: 0.5rem; }
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}
.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
}

/* Form Row */
.form-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
.form-row .form-group {
    flex: 1;
    min-width: 120px;
}

/* Calendar Grid */
.calendar-grid {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 10px 0;
}
.day-column {
    flex: 1;
    min-width: 150px;
    background: #f9f9f9;
    border-radius: 8px;
    overflow: hidden;
}
.day-header {
    background: #004d40;
    color: white;
    text-align: center;
    padding: 10px;
    font-weight: bold;
}
.day-slots {
    padding: 10px;
    min-height: 150px;
}
.slot {
    background: white;
    border-left: 3px solid #00796b;
    padding: 8px 10px;
    margin-bottom: 8px;
    border-radius: 0 6px 6px 0;
    position: relative;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.slot-time { font-weight: bold; color: #004d40; font-size: 0.85rem; }
.slot-room { font-size: 0.8rem; color: #666; }
.slot-teacher { font-size: 0.8rem; color: #333; margin-top: 4px; }
.slot-delete {
    position: absolute;
    top: 5px;
    right: 5px;
    color: #999;
    text-decoration: none;
    font-size: 1.2rem;
    line-height: 1;
}
.slot-delete:hover { color: #c62828; }

/* User Grid */
.user-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin-top: 1rem;
}
.user-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9f9f9;
    border-radius: 8px;
}
.user-avatar {
    width: 45px;
    height: 45px;
    background: #00796b;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}
.user-info { flex: 1; }
.user-name { font-weight: bold; }
.user-meta { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; font-size: 0.8rem; }
.user-role {
    padding: 2px 8px;
    border-radius: 10px;
    background: #e0e0e0;
}
.user-role.teacher_staff { background: #c8e6c9; color: #2e7d32; }
.user-role.teacher_parttime { background: #fff9c4; color: #f57f17; }
.user-plate { color: #666; }
.user-cert { color: #2e7d32; }

/* Add User Section */
.add-user-section {
    margin-bottom: 1rem;
}
.add-user-section summary {
    cursor: pointer;
    margin-bottom: 1rem;
}
.add-user-section[open] summary {
    margin-bottom: 1rem;
}
.user-form {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.btn-outline {
    background: transparent;
    border: 2px solid #00796b;
    color: #00796b;
}
.btn-outline:hover {
    background: #00796b;
    color: white;
}

/* Vacation List */
.vacation-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.vacation-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 15px;
    background: #f9f9f9;
    border-radius: 6px;
    border-left: 4px solid #ff9800;
    flex-wrap: wrap;
}
.vacation-item.active {
    border-left-color: #f44336;
    background: #ffebee;
}
.vacation-item.past {
    opacity: 0.5;
    border-left-color: #9e9e9e;
}
.vacation-user {
    font-weight: bold;
    min-width: 120px;
}
.vacation-dates {
    color: #666;
    font-size: 0.9rem;
}
.vacation-reason {
    flex: 1;
    font-size: 0.85rem;
    color: #888;
}
.vacation-status {
    background: #f44336;
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
}
.btn-delete {
    color: #999;
    text-decoration: none;
    font-size: 1.4rem;
    font-weight: bold;
    line-height: 1;
    padding: 5px;
}
.btn-delete:hover {
    color: #c62828;
}

/* Mobile */
@media (max-width: 768px) {
    .calendar-grid {
        flex-direction: column;
    }
    .day-column {
        min-width: 100%;
    }
    .mode-options {
        flex-direction: column;
    }
}
</style>

<script>
// Live mode selection visual feedback
document.querySelectorAll('.mode-option input').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.mode-option').forEach(opt => opt.classList.remove('active'));
        this.closest('.mode-option').classList.add('active');
    });
});
</script>

<?php include '../includes/footer.php'; ?>
