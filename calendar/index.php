<?php
// calendar/index.php
require_once '../auth.php';
require_once '../models/Schedule.php';

// Ensure login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$msg = "";
// Handle Add Event (if admin or self?)
// For simplicity, let's say Admin can add for anyone, Teacher for themselves
$can_edit = ($_SESSION['role'] === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $user_id = $_POST['user_id'];
    $day = $_POST['day'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $room = $_POST['room'];
    
    if (Schedule::add($user_id, $day, $start, $end, $room)) {
        $msg = "Event added!";
    } else {
        $msg = "Error adding event.";
    }
}

$events = Schedule::getAll();
$users = $conn->query("SELECT id, full_name FROM users WHERE role != 'admin'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FMI Parking - Calendar</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        .day-column {
            background: #fff;
            padding: 10px;
            border: 1px solid #ddd;
            min-height: 400px;
        }
        .event-card {
            background: #e0f2f1;
            padding: 5px;
            margin-bottom: 5px;
            border-left: 3px solid var(--secondary-color);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>Weekly Schedule</h1>
        <nav>
            <?php if($_SESSION['role'] === 'admin'): ?>
                <a href="../admin/index.php">Admin Panel</a>
            <?php endif; ?>
            <a href="../gate/index.php">Gate Simulation</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>
    <main>
        <?php if($msg): ?><p style="color:green"><?php echo $msg; ?></p><?php endif; ?>

        <?php if($can_edit): ?>
        <div class="card">
            <h3>Add Class Schedule</h3>
            <form method="POST" style="display: flex; gap: 10px; align-items: flex-end;">
                <div>
                    <label>Teacher</label>
                    <select name="user_id">
                        <?php while($u = $users->fetch_assoc()): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo $u['full_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label>Day</label>
                    <select name="day">
                        <option>Monday</option>
                        <option>Tuesday</option>
                        <option>Wednesday</option>
                        <option>Thursday</option>
                        <option>Friday</option>
                        <option>Saturday</option>
                        <option>Sunday</option>
                    </select>
                </div>
                <div>
                    <label>Start</label>
                    <input type="time" name="start_time" required>
                </div>
                <div>
                    <label>End</label>
                    <input type="time" name="end_time" required>
                </div>
                <div>
                    <label>Room</label>
                    <input type="text" name="room" size="5" required>
                </div>
                <button type="submit" class="btn">Add</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="calendar-grid">
            <?php 
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach($days as $day): 
            ?>
            <div class="day-column">
                <h3><?php echo $day; ?></h3>
                <?php foreach($events as $ev): ?>
                    <?php if($ev['day_of_week'] === $day): ?>
                        <div class="event-card">
                            <strong><?php echo substr($ev['start_time'],0,5) . '-' . substr($ev['end_time'],0,5); ?></strong><br>
                            <?php echo htmlspecialchars($ev['full_name']); ?><br>
                            Room: <?php echo htmlspecialchars($ev['room']); ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
