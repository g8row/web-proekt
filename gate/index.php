<?php
// gate/index.php
require_once '../auth.php';
// We don't require login to VIEW the gate (it's a physical device), but to Generate MY QR code I need to be logged in.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FMI Parking - Gate</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .gate-status {
            width: 100%;
            height: 100px;
            background: #f44336; /* Closed/Red */
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 2rem;
            transition: background 0.5s;
        }
        .gate-status.open {
            background: #4caf50; /* Open/Green */
        }
        .qr-display {
            border: 5px solid #000;
            padding: 20px;
            display: inline-block;
            margin: 20px;
            background: #fff;
        }
    </style>
</head>
<body>
    <header>
        <h1>Gate Simulation</h1>
        <nav>
            <a href="../index.php">Home</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="../calendar/index.php">Calendar</a>
            <?php else: ?>
                <a href="../index.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <div id="uic-gate-bar" class="gate-status">BARRIER CLOSED</div>
        <div id="uic-message" style="text-align:center; font-weight:bold; margin-bottom:1rem;"></div>

        <div style="display: flex; justify-content: space-around; flex-wrap: wrap;">
            <!-- Simulation: Camera/OCR -->
            <div class="card" style="flex:1; min-width: 300px;">
                <h2>Scanner / OCR Mock</h2>
                <div class="form-group">
                    <label>License Plate (OCR)</label>
                    <input type="text" id="plate_input" placeholder="CA 1234 AB">
                    <button onclick="checkPlate()" class="btn">Simulate Plate Read</button>
                </div>
                <hr>
                <div class="form-group">
                    <label>Scan QR Code (Simulate)</label>
                    <!-- In a real app this would be a camera stream. Here we paste/input the user ID or code -->
                    <input type="text" id="qr_input" placeholder="User ID">
                    <button onclick="checkQR()" class="btn">Simulate QR Scan</button>
                </div>
            </div>

            <!-- User's Phone UI -->
            <?php if(isset($_SESSION['user_id'])): ?>
            <div class="card" style="flex:1; min-width: 300px; text-align: center;">
                <h2>My Digital Key</h2>
                <p>Present this to the scanner</p>
                <div class="qr-display">
                    <!-- Simple Mock QR -->
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo $_SESSION['user_id']; ?>" alt="QR Code">
                    <br>
                    <small>User ID: <?php echo $_SESSION['user_id']; ?></small>
                </div>
                <p>
                    <button onclick="document.getElementById('qr_input').value='<?php echo $_SESSION['user_id']; ?>'" class="btn">Test My Code</button>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        async function checkAccess(type, value) {
            const msgEl = document.getElementById('uic-message');
            const gateEl = document.getElementById('uic-gate-bar');
            
            msgEl.textContent = "Verifying...";
            
            try {
                const res = await fetch('check.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ type, value })
                });
                const data = await res.json();
                
                msgEl.textContent = data.message;
                if (data.allowed) {
                    gateEl.textContent = "BARRIER OPEN";
                    gateEl.classList.add('open');
                    setTimeout(() => {
                        gateEl.textContent = "BARRIER CLOSED";
                        gateEl.classList.remove('open');
                        msgEl.textContent = "";
                    }, 5000); // Open for 5 seconds
                } else {
                    gateEl.classList.remove('open');
                }
            } catch (e) {
                console.error(e);
                msgEl.textContent = "System Error";
            }
        }

        function checkPlate() {
            const val = document.getElementById('plate_input').value;
            if(val) checkAccess('plate', val);
        }

        function checkQR() {
            const val = document.getElementById('qr_input').value;
            if(val) checkAccess('qr', val);
        }
    </script>
</body>
</html>
