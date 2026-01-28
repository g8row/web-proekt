# Demo Walkthrough

## Demo Accounts

| Username | Password | Role | Certificate |
|----------|----------|------|-------------|
| `admin` | admin123 | Admin | ✓ |

Create additional users via Admin Dashboard.

---

## Scenarios

### 1. Login & Navigation
1. Go to `http://localhost:8000`
2. Login as `admin`
3. Admin sees: Dashboard, Scanner links
4. Regular users see: My QR, Vacation links

### 2. Change Traffic Mode
1. Admin Dashboard → Traffic Control Mode
2. Select Green/Yellow/Red
3. Click "Update Mode"

**Modes:**
- 🟢 **Green** = Everyone enters
- 🟡 **Yellow** = Only users with green certificate
- 🔴 **Red** = Staff always, Part-time only during schedule

### 3. Add User
1. Admin Dashboard → Add New Teacher/Staff
2. Fill: Username, Password, Full Name
3. Select Role: Staff (Tenure) or Part-time
4. Optional: License Plate, Green Certificate
5. Click "Add User"

### 4. Scan QR Code
1. Admin → Scanner (via Quick Actions)
2. Allow camera or use file upload
3. Point at QR code
4. See OPEN/DENIED result

### 5. User: View QR
1. Login as regular user
2. See your QR code on "My QR" page
3. Show this at the gate

### 6. User: Block Dates
1. User → Vacation page
2. Enter date range and reason
3. Click "Add Block Period"
4. Access denied during those dates

---

## Testing Access Logic

| Mode | Staff | Part-time (scheduled) | Part-time (not scheduled) | No Cert |
|------|-------|----------------------|---------------------------|---------|
| Green | ✅ | ✅ | ✅ | ✅ |
| Yellow | ✅ if cert | ✅ if cert | ✅ if cert | ❌ |
| Red | ✅ | ✅ | ❌ | ✅ if scheduled |
