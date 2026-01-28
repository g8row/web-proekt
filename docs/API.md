# API Reference

## POST /gate/check.php

Verify access for a user.

### Request

```json
{
  "type": "qr",      // or "plate"
  "value": "3"       // user ID or license plate
}
```

### Response

```json
{
  "allowed": true,
  "message": "Welcome, John Doe (Green Mode)",
  "user": "johndoe"
}
```

### Access Rules

| Mode | Rule |
|------|------|
| `green` | Always allowed |
| `yellow` | Only if `green_cert_valid = 1` |
| `red` | Staff: always. Part-time: only during schedule ±30min |

### Error Responses

| Condition | Response |
|-----------|----------|
| User not found | `{"allowed": false, "message": "Unknown User/Plate"}` |
| Active block | `{"allowed": false, "message": "Access Blocked: Vacation (until 2025-02-15)"}` |
| No certificate (yellow) | `{"allowed": false, "message": "Missing Green Certificate (Yellow Mode)"}` |
| Outside schedule (red) | `{"allowed": false, "message": "Outside Access Window (Red Mode)"}` |

---

## Database Tables

### users
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| username | VARCHAR(50) | Login name |
| password | VARCHAR(255) | Bcrypt hash |
| full_name | VARCHAR(100) | Display name |
| role | ENUM | admin, teacher_staff, teacher_parttime |
| license_plate | VARCHAR(20) | Car plate (optional) |
| green_cert_valid | TINYINT | Has health certificate |

### schedule
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| user_id | INT | FK to users |
| day_of_week | ENUM | Monday-Sunday |
| start_time | TIME | Slot start |
| end_time | TIME | Slot end |
| room | VARCHAR(20) | Room number |

### user_blocks
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| user_id | INT | FK to users |
| start_date | DATE | Block start |
| end_date | DATE | Block end |
| reason | VARCHAR(100) | Vacation, Business Trip, etc. |

### system_settings
| Column | Type | Description |
|--------|------|-------------|
| setting_key | VARCHAR(50) | Primary key |
| setting_value | VARCHAR(255) | Value |

Current keys: `traffic_mode` (green/yellow/red)
