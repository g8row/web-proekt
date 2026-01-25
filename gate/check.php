<?php
// gate/check.php
require_once '../config.php';
require_once '../models/Schedule.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$value = $input['value'] ?? '';

$response = ['allowed' => false, 'message' => 'Denied'];

if (!$type || !$value) {
    echo json_encode($response);
    exit;
}

// 1. Identify User
$user = null;
if ($type === 'qr') {
    // Value assumes "user_id:random_token" or just "user_id" for simulation
    // Let's assume value is just userId for simplicity of simulation
    $userId = intval($value);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} elseif ($type === 'plate') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE license_plate = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

if (!$user) {
    $response['message'] = "Unknown User/Plate";
    echo json_encode($response);
    exit;
}

// 2. Check System Mode
$res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='traffic_mode'");
$mode = $res->num_rows > 0 ? $res->fetch_assoc()['setting_value'] : 'green';

// 3. Apply Logic
$allowed = false;
$reason = "";

// Staff (Tenure) vs Part-time
$isStaff = ($user['role'] === 'teacher_staff' || $user['role'] === 'admin');

if ($mode === 'green') {
    // Everyone enters
    $allowed = true;
    $reason = "Green Mode (Free Entry)";
} elseif ($mode === 'yellow') {
    // Only with Green Cert
    if ($user['green_cert_valid']) {
        $allowed = true;
        $reason = "Valid Green Certificate";
    } else {
        $allowed = false;
        $reason = "Missing Green Certificate (Yellow Mode)";
    }
} elseif ($mode === 'red') {
    // Strict Schedule (or maybe Staff always allowed? Layout implies Schedule check is the core feature)
    // "active +/- 30 min... for part-time". "Staff - permanent".
    // I will interpret: Staff = Always, Part-time = Schedule.
    
    if ($isStaff) {
        $allowed = true;
        $reason = "Staff Access (Permanent)";
    } else {
        if (Schedule::isUserInSlot($user['id'])) {
            $allowed = true;
            $reason = "Scheduled Slot Active";
        } else {
            $allowed = false;
            $reason = "Outside Access Window (Red Mode)";
        }
    }
}

// Specific Staff vs Part-time logic from prompt override?
// Prompt: "if staff -> permanent QR, if not -> temporary... active +/- 30 min"
// This implies the CODE validity itself. 
// For simulation, we check the rules. 
// If I am strictly following the prompt:
// Verification Logic merging Mode + Role:
// If Mode == 'yellow' && !green_cert => DENY regardless of role.
if (!$allowed && $mode !== 'yellow') {
    // re-evaluate based on prompt specific "Code active +/- 30"
    if ($isStaff) {
         $allowed = true; // Permanent code
    } else {
         if (Schedule::isUserInSlot($user['id'])) {
             $allowed = true;
         }
    }
}

// Final Override for Yellow Mode requiring Cert
if ($mode === 'yellow' && !$user['green_cert_valid']) {
    $allowed = false;
    $reason = "Green Cert Required";
}


$response['allowed'] = $allowed;
$response['message'] = $allowed ? "Welcome, " . $user['full_name'] . " ($reason)" : "Access Denied: $reason";
$response['user'] = $user['username'];

echo json_encode($response);
?>
