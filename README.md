# ClassTrack – Subject-Based QR Attendance Monitoring System

**ClassTrack** is a web-based attendance system that uses QR code scanning to automatically record student attendance per subject session.

Developed at **Eastern Visayas State University – Ormoc Campus**.

## 🛠 Tech Stack

- **Backend:** Plain PHP (Native PHP)

- **Frontend:** HTML, CSS, JavaScript

- **Database:** MySQL

- **Server Environment:** XAMPP (Apache + MySQL)

- **QR Scanning:** JavaScript (Webcam-based scanning)

## 📋 System Requirements (Local Development)

### Make sure the following are installed:

- PHP 8.x (via XAMPP)

- MySQL (via XAMPP)

- Apache Server (via XAMPP)

- Web Browser (Chrome / Edge / Firefox)

- Laptop with Webcam (for QR scanning)

- ⚠ Composer and Node.js are NOT required (Plain PHP project).

## 🚀 How to Run the System (Local Setup – XAMPP)

**1. Clone the Repository**
``` bash
git clone https://github.com/jhnncarl/classtrack.git
```

## Move the project folder inside:
``` bash
C:\xampp\htdocs\
```

**Example final path:**
``` bash
C:\xampp\htdocs\classtrack
```
**2️. Start XAMPP**

**Open XAMPP Control Panel and start:**

✅ Apache

✅ MySQL

3️⃣ Create the Database

## Open Command Prompt and run:
``` bash
cd C:\xampp\mysql\bin
mysql -u root 
```

**Then execute:**
``` bash
CREATE DATABASE classtrack_db;
```
```bash
USE classtrack_db;
```

After that, import or execute the provided SQL file to create the required tables.
``` bash
source C:/xampp/htdocs/classtrack/database/classtrack_db.sql;
```

**4️. Configure Database Connection**

**Open:**

**1. config/database.php**

**2. Update database credentials if needed:**
``` bash
**$host = "localhost";
$dbname = "classtrack_db";
$username = "root";
$password = "";*
```

**Save the file.**

**5️. Open the System in Browser**

## Open your browser and go to:

**http://localhost/classtrack*

If setup is correct, the system should load successfully.

## 🧪 Local Testing Notes

- This system is currently configured for local development only.

- Make sure Apache and MySQL are running before accessing the system.

- All testing (registration, login, subject creation, QR scanning) should be done locally during development.

- Hosting or deployment will be done only after system validation and completion of MVP features.

# 👨‍🎓 Basic Usage Guide
## Student

- Register an account.

- System generates a unique QR code.

- Join subject using subject code.

- Present QR code during active attendance session.

- View attendance history in dashboard.

# Teacher

- Register account (requires administrator approval).

- Login to dashboard.

- Create subject (system generates subject code).

- Start attendance session.

- Scan student QR codes using laptop camera.

- End session.

- View attendance report.

# Administrator

- Login as administrator.

- Approve or reject teacher registrations.

- Manage user accounts.

#🏗 Project Structure (Simplified)
``` bash
/config
/includes
/auth
/admin
/teacher
/student
/assets
/uploads/qrcodes
```
