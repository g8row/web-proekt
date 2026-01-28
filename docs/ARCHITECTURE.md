# System Architecture

## Overview

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Browser   │────▶│  PHP Server │────▶│   MySQL DB  │
│  (Scanner)  │     │  (Logic)    │     │  (Data)     │
└─────────────┘     └─────────────┘     └─────────────┘
```

## Request Flow

### QR Scan Flow
```
1. Admin scans QR → admin/scanner.php
2. JS sends QR value → gate/check.php (POST)
3. check.php:
   a. Find user by ID
   b. Check for active blocks
   c. Get traffic mode
   d. Apply access rules
   e. Return JSON {allowed, message}
4. Scanner displays result
```

### Access Decision Tree
```
User scans QR
    │
    ├── User not found? → DENIED
    │
    ├── User has active block? → DENIED
    │
    └── Check traffic mode:
        │
        ├── GREEN → ALLOWED (everyone)
        │
        ├── YELLOW → Has certificate?
        │   ├── Yes → ALLOWED
        │   └── No → DENIED
        │
        └── RED → Is staff?
            ├── Yes → ALLOWED
            └── No → In schedule window?
                ├── Yes → ALLOWED
                └── No → DENIED
```

## File Structure

```
/
├── index.php              # Login page
├── config.php             # DB connection, session
├── logout.php             # Session destroy
│
├── admin/
│   ├── index.php          # Dashboard (users, mode)
│   └── scanner.php        # QR camera scanner
│
├── user/
│   ├── index.php          # QR code display
│   └── vacation.php       # Block date management
│
├── gate/
│   └── check.php          # Access verification API
│
├── models/
│   └── Schedule.php       # Schedule helper class
│
├── includes/
│   ├── header.php         # Navigation (role-based)
│   └── footer.php         # Page footer
│
├── css/
│   └── style.css          # Global styles
│
├── js/
│   ├── qr-scanner.min.js  # Nimiq QR lib
│   └── qr-scanner-worker.min.js
│
└── sql/
    └── schema.sql         # Database schema
```

## Security

- Passwords hashed with `password_hash()` (bcrypt)
- Sessions for authentication
- Role-based access control (admin vs user)
- Prepared statements for SQL
