<?php
// admin/scanner.php - QR Scanner (Admin Only)
require_once '../config.php';

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

$pageTitle = "Gate Scanner";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - FMI Parking</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { 
            margin: 0; 
            background: #000; 
            color: #fff; 
            display: flex; 
            flex-direction: column; 
            height: 100vh; 
            font-family: sans-serif;
        }

        .scan-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            background: #222;
        }

        #camera-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #ui-layer {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 5;
            pointer-events: none;
        }

        #scan-target {
            width: 250px; 
            height: 250px; 
            border: 2px solid rgba(255,255,255,0.6); 
            border-radius: 12px;
            box-shadow: 0 0 0 100vh rgba(0,0,0,0.5);
        }

        .action-btn {
            pointer-events: auto;
            background: #00796b;
            color: white;
            padding: 1rem 2rem;
            font-size: 1.2rem;
            border-radius: 50px;
            border: 2px solid #fff;
            cursor: pointer;
            margin-top: 20px;
            display: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }

        #controls {
            padding: 20px;
            text-align: center;
            background: #fff;
            color: #333;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            z-index: 10;
        }

        .status-msg {
            font-size: 1.2rem;
            font-weight: bold;
            padding: 10px;
            border-radius: 5px;
            background: #eee;
            margin-bottom: 10px;
        }
        .success { background: #e8f5e9; color: #2e7d32; }
        .error { background: #ffebee; color: #c62828; }

        .back-btn { 
            position: absolute; 
            top: 15px; 
            left: 15px; 
            z-index: 100; 
            background: rgba(0,0,0,0.6); 
            color: #fff; 
            border: 1px solid #fff; 
            padding: 8px 12px; 
            border-radius: 4px; 
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <a href="/admin/index.php" class="back-btn">← Dashboard</a>
    
    <div class="scan-container">
        <video id="camera-video"></video>
        
        <div id="ui-layer">
            <div id="scan-target"></div>
            
            <label for="camera-input" id="file-btn" class="action-btn">
                📷 Scan Image
            </label>
            <input type="file" id="camera-input" accept="image/*" capture="environment" style="display:none">
        </div>
    </div>
    
    <div id="controls">
        <div id="status" class="status-msg">Initializing Camera...</div>
        <small id="cam-debug" style="color:#777">Attempting to access camera...</small>
    </div>

    <script type="module">
        import QrScanner from '/js/qr-scanner.min.js';
        QrScanner.WORKER_PATH = '/js/qr-scanner-worker.min.js';

        const video = document.getElementById('camera-video');
        const statusEl = document.getElementById('status');
        const debugEl = document.getElementById('cam-debug');
        const fileBtn = document.getElementById('file-btn');
        const fileInput = document.getElementById('camera-input');
        const scanTarget = document.getElementById('scan-target');

        let scanner;
        let isProcessing = false;

        async function init() {
            try {
                scanner = new QrScanner(video, onScanSuccess, {
                    highlightScanRegion: true,
                    highlightCodeOutline: true,
                });
                
                await scanner.start();
                
                statusEl.textContent = "Point at QR Code";
                debugEl.textContent = "Live Camera Active";
                scanTarget.style.display = 'block';
                fileBtn.style.display = 'none';

            } catch (err) {
                console.warn(err);
                statusEl.textContent = "Camera Unavailable";
                debugEl.textContent = "Use 'Scan Image' button below";
                scanTarget.style.display = 'none';
                fileBtn.style.display = 'block';
            }
        }

        function onScanSuccess(result) {
            const data = (typeof result === 'object' && result.data) ? result.data : result;
            if (isProcessing) return;
            verify(data);
        }

        fileInput.addEventListener('change', async (e) => {
            if (!e.target.files.length) return;
            try {
                statusEl.textContent = "Processing...";
                const result = await QrScanner.scanImage(e.target.files[0]);
                onScanSuccess(result);
            } catch (err) {
                statusEl.textContent = "No QR Found";
                statusEl.className = "status-msg error";
                setTimeout(() => { statusEl.className = "status-msg"; statusEl.textContent = "Try Again"; }, 2000);
            }
        });

        async function verify(qrValue) {
            isProcessing = true;
            if (scanner) scanner.stop();
            
            statusEl.textContent = "Verifying Access...";
            statusEl.className = "status-msg";

            try {
                const res = await fetch('/gate/check.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ type: 'qr', value: qrValue })
                });
                const data = await res.json();

                if (data.allowed) {
                    statusEl.textContent = "OPEN: " + data.message;
                    statusEl.className = "status-msg success";
                } else {
                    statusEl.textContent = "DENIED: " + data.message;
                    statusEl.className = "status-msg error";
                }
            } catch (e) {
                statusEl.textContent = "Network Error";
                statusEl.className = "status-msg error";
            }

            setTimeout(() => {
                isProcessing = false;
                statusEl.textContent = "Point at QR Code";
                statusEl.className = "status-msg";
                if (scanner && fileBtn.style.display === 'none') {
                    scanner.start();
                }
            }, 3000);
        }

        init();
    </script>
</body>
</html>
