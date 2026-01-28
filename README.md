# FMI Parking System

A web-based parking access control system for universities with QR code scanning, traffic modes, and user management.

## Features

- **QR Code Access** - Users scan QR codes at gates for entry
- **Traffic Modes** - Green (all), Yellow (certificate), Red (schedule)
- **Role Management** - Staff (permanent) vs Part-time (schedule-based)
- **Vacation Blocking** - Users can block their own access dates
- **Admin Dashboard** - User management and mode control

## Quick Start

```bash
# Start server
php -S 0.0.0.0:8000

# Open browser
http://localhost:8000
```

## Default Login

- **Admin**: `admin` / `admin123`

## Documentation

- [Demo Walkthrough](docs/WALKTHROUGH.md)
- [Architecture](docs/ARCHITECTURE.md)
- [API Reference](docs/API.md)
- [Database Schema](docs/DATABASE.md)

## Tech Stack

- PHP 7.4+
- MySQL/MariaDB
- Vanilla JavaScript
- [Nimiq QR Scanner](https://github.com/nicetransition/qr-scanner)
