<?php
// models/Schedule.php
require_once __DIR__ . '/../config.php';

class Schedule {
    public static function add($user_id, $day, $start, $end, $room) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO schedule (user_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $day, $start, $end, $room);
        return $stmt->execute();
    }

    public static function getAll() {
        global $conn;
        $sql = "SELECT s.*, u.full_name, u.role FROM schedule s JOIN users u ON s.user_id = u.id ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public static function getForUser($user_id) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM schedule WHERE user_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function isUserInSlot($user_id) {
        // Logic for Person 3 access control, but implemented by Person 2
        // Check if current time is within any slot +/- 30 mins
        // For simulation, we might need a way to mock "Current Time"
        global $conn;
        
        // Real-time check
        $currentDay = date('l'); // Monday, Tuesday...
        $currentTime = date('H:i:s');
        
        // Find slots for this user today
        $stmt = $conn->prepare("SELECT start_time, end_time FROM schedule WHERE user_id = ? AND day_of_week = ?");
        $stmt->bind_param("is", $user_id, $currentDay);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $start = strtotime($row['start_time']) - (30 * 60); // -30 mins
            $end = strtotime($row['end_time']) + (30 * 60); // +30 mins
            $now = strtotime($currentTime);

            if ($now >= $start && $now <= $end) {
                return true;
            }
        }
        return false;
    }
}
?>
