<?php
// user/vacation.php - Vacation/Block Management
require_once '../config.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') {
    header("Location: /index.php");
    exit();
}

$pageTitle = "Vacation / Blocking";
$cssPath = "/css/style.css";

$userId = $_SESSION['user_id'];
$msg = "";
$error = "";

// Handle Add Block
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_block'])) {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $reason = $_POST['reason'] ?: 'Vacation';
    
    if ($startDate && $endDate && $startDate <= $endDate) {
        $stmt = $conn->prepare("INSERT INTO user_blocks (user_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $startDate, $endDate, $reason);
        if ($stmt->execute()) {
            $msg = "Block period added successfully.";
        } else {
            $error = "Error adding block.";
        }
    } else {
        $error = "Invalid dates.";
    }
}

// Handle Delete Block
if (isset($_GET['delete'])) {
    $blockId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM user_blocks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $blockId, $userId);
    $stmt->execute();
    header("Location: vacation.php");
    exit();
}

// Get existing blocks
$stmt = $conn->prepare("SELECT * FROM user_blocks WHERE user_id = ? ORDER BY start_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$blocks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<?php if ($msg): ?><div class="alert success"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>

<div class="card">
    <h2>Add Vacation / Block Period</h2>
    <p>During these dates, you will <strong>not</strong> be granted access at the gate.</p>
    
    <form method="POST" class="block-form">
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" required>
            </div>
        </div>
        <div class="form-group">
            <label for="reason">Reason (optional)</label>
            <input type="text" id="reason" name="reason" placeholder="e.g. Vacation, Conference, Sick Leave">
        </div>
        <button type="submit" name="add_block" class="btn">Add Block Period</button>
    </form>
</div>

<div class="card">
    <h2>My Block Periods</h2>
    <?php if (empty($blocks)): ?>
        <p style="color: #666;">No block periods set.</p>
    <?php else: ?>
        <div class="block-list">
            <?php foreach ($blocks as $block): ?>
                <div class="block-item">
                    <div class="block-info">
                        <strong><?php echo date('M j, Y', strtotime($block['start_date'])); ?></strong>
                        &rarr;
                        <strong><?php echo date('M j, Y', strtotime($block['end_date'])); ?></strong>
                        <span class="block-reason"><?php echo htmlspecialchars($block['reason']); ?></span>
                    </div>
                    <a href="?delete=<?php echo $block['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Delete this block?')">Delete</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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
    min-width: 140px;
}

.block-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.block-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f9f9f9;
    border-radius: 6px;
    border-left: 4px solid #ff9800;
    flex-wrap: wrap;
    gap: 10px;
}
.block-info {
    flex: 1;
}
.block-reason {
    display: block;
    font-size: 0.9rem;
    color: #666;
    margin-top: 4px;
}
.btn-small {
    padding: 6px 12px;
    font-size: 0.85rem;
}
.btn-danger {
    background: #e53935;
}
.btn-danger:hover {
    background: #c62828;
}
</style>

<?php include '../includes/footer.php'; ?>
