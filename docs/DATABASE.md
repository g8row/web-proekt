# Database Schema

## ER Diagram

```
┌─────────────────┐       ┌─────────────────┐
│     users       │       │    schedule     │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │──┐    │ id (PK)         │
│ username        │  │    │ user_id (FK)────┼──┐
│ password        │  │    │ day_of_week     │  │
│ full_name       │  │    │ start_time      │  │
│ role            │  │    │ end_time        │  │
│ license_plate   │  │    │ room            │  │
│ green_cert_valid│  │    └─────────────────┘  │
│ created_at      │  │                         │
└─────────────────┘  │    ┌─────────────────┐  │
                     │    │   user_blocks   │  │
                     │    ├─────────────────┤  │
                     └────┼──user_id (FK)   │──┘
                          │ id (PK)         │
                          │ start_date      │
                          │ end_date        │
                          │ reason          │
                          │ created_at      │
                          └─────────────────┘

┌─────────────────┐
│ system_settings │
├─────────────────┤
│ setting_key (PK)│
│ setting_value   │
└─────────────────┘
```

## Tables

### users
Primary table for all users (admin, staff, part-time).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | INT | NO | AUTO_INCREMENT | Primary key |
| username | VARCHAR(50) | NO | - | Unique login |
| password | VARCHAR(255) | NO | - | Bcrypt hash |
| full_name | VARCHAR(100) | NO | - | Display name |
| role | ENUM | NO | - | admin, teacher_staff, teacher_parttime |
| license_plate | VARCHAR(20) | YES | NULL | Car plate for OCR |
| green_cert_valid | TINYINT(1) | NO | 0 | Has health certificate |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | Creation date |

### schedule
Teaching schedule for part-time teachers (access window).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | INT | NO | AUTO_INCREMENT | Primary key |
| user_id | INT | NO | - | FK → users.id |
| day_of_week | ENUM | NO | - | Monday-Sunday |
| start_time | TIME | NO | - | Class start |
| end_time | TIME | NO | - | Class end |
| room | VARCHAR(20) | NO | - | Room number |

### user_blocks
Vacation/business trip periods when access is denied.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | INT | NO | AUTO_INCREMENT | Primary key |
| user_id | INT | NO | - | FK → users.id |
| start_date | DATE | NO | - | Block start |
| end_date | DATE | NO | - | Block end |
| reason | VARCHAR(100) | YES | 'Vacation' | Block reason |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | Creation date |

### system_settings
Key-value store for system configuration.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| setting_key | VARCHAR(50) | NO | Primary key |
| setting_value | VARCHAR(255) | NO | Setting value |

**Used keys:**
- `traffic_mode`: green, yellow, red

## Setup

```sql
-- Create database
CREATE DATABASE IF NOT EXISTS fmi_parking;
USE fmi_parking;

-- Run schema
SOURCE sql/schema.sql;
```

Or from command line:
```bash
mysql -u root -p fmi_parking < sql/schema.sql
```
