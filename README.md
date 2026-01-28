# FMI Parking System

A web-based parking access control system for universities with QR code scanning, traffic modes, and user management.

## Prerequisites

- PHP 7.4+ with the `mysqli` extension enabled
- MySQL or MariaDB server installed and running
- Database user `parking_user` with password `parking_pass` created with privileges to create databases

To install the mysqli extension on Arch Linux:
```bash
sudo pacman -S php-mysqli
```
If not available, edit `/etc/php/php.ini` and uncomment `;extension=mysqli`.

To install and set up MariaDB on Arch Linux:
```bash
sudo pacman -S mariadb
sudo mariadb-install-db --user=mysql --datadir=/var/lib/mysql
sudo systemctl start mariadb
sudo mariadb -e "CREATE USER 'parking_user'@'localhost' IDENTIFIED BY 'parking_pass';"
sudo mariadb -e "GRANT ALL PRIVILEGES ON *.* TO 'parking_user'@'localhost' WITH GRANT OPTION;"
sudo mariadb -e "FLUSH PRIVILEGES;"
```

To set up the database:
```bash
mariadb -u parking_user -pparking_pass -e "CREATE DATABASE IF NOT EXISTS fmi_parking;"
mariadb -u parking_user -pparking_pass fmi_parking < sql/schema.sql
```

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
