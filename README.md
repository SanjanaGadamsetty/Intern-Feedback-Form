
================================================================
  FOLDER STRUCTURE
================================================================
Student_Feedback_System/
├── index.php                    ← TNEB Feedback Form (public)
├── submit_feedback.php          ← Redirect shim
├── config/
│   └── db.php                   ← DB config + helpers
├── admin/
│   ├── login.php                ← Admin Login (admin/admin123)
│   ├── dashboard.php            ← Dashboard + Stats + Charts
│   ├── feedbacks.php            ← View / Search / Delete / Export
│   ├── reports.php              ← Analytics charts + tables
│   └── logout.php               ← Logout
├── assets/
│   ├── css/style.css            ← Main stylesheet
│   └── js/main.js               ← Client-side JS + validation
├── uploads/
│   ├── photos/                  ← Passport photos stored here
│   ├── bonafide/                ← Bonafide PDFs stored here
│   └── .htaccess                ← Prevents direct PHP execution
├── database/
│   └── student_feedback_system.sql
└── README.txt
================================================================
  SETUP INSTRUCTIONS (XAMPP)
================================================================
STEP 1 – Start XAMPP
  Open XAMPP Control Panel → Start Apache + MySQL
STEP 2 – Copy Project
  Copy "Student_Feedback_System" folder to:
  Windows  →  C:\xampp\htdocs\
  Mac/Linux → /opt/lampp/htdocs/
STEP 3 – Import Database
  1. Open http://localhost/phpmyadmin
  2. Click "New" → create DB: student_feedback_system
  3. Click the new DB → Import tab
  4. Choose: database/student_feedback_system.sql → Go
STEP 4 – Set Upload Folder Permissions (Linux/Mac only)
  chmod 755 uploads/photos/
  chmod 755 uploads/bonafide/
STEP 5 – Open in Browser
  Feedback Form : http://localhost/Student_Feedback_System/
  Admin Panel   : http://localhost/Student_Feedback_System/admin/login.php
================================================================
  LOGIN CREDENTIALS
================================================================
  Username : admin
  Password : admin123
================================================================
  DATABASE FIELDS (feedbacks table)
================================================================
  id, submission_id, student_name, register_number,
  college_name, department, year, section,
  email, phone, faculty_name, subject_name,
  internship_start, internship_end, internship_duration,
  photo_path, bonafide_path,
  teaching_quality, subject_knowledge, communication_skills,
  doubt_clarification, classroom_interaction, punctuality,
  strengths, improvements, feedback, suggestions,
  submitted_at
================================================================
  SUBMISSION ID FORMAT
================================================================
  Format  : TNEB{YEAR}-{SEQUENCE}
  Example : TNEB2024-00001
  Logic   : Auto-increments per year, padded to 5 digits.
            Generated in config/db.php → generateSubmissionId()
================================================================
  FILE UPLOAD SPECS
================================================================
  Passport Photo : JPG / PNG / JPEG · Max 1 MB
                   Stored in: uploads/photos/
  Bonafide Cert  : PDF only · Max 1 MB
                   Stored in: uploads/bonafide/
  Naming         : {SUBMISSION_ID}_photo.jpg
                   {SUBMISSION_ID}_bonafide.pdf
================================================================
  VALIDATION RULES
================================================================
  Student Name    : Required, non-empty
  Register Number : 4–20 alphanumeric chars
  College Name    : Required
  Department      : Required (dropdown)
  Year            : Required (dropdown)
  Section         : Required
  Email           : Valid format, server + client validated
  Phone           : 10-digit Indian mobile (starts with 6–9)
  Start Date      : Required, cannot be future date
  End Date        : Required, must be AFTER start date
  Duration        : Auto-calculated in days (displayed live)
  Photo           : Required, JPG/PNG, max 1 MB
  Bonafide        : Required, PDF, max 1 MB
  Ratings (×6)    : All required, 1–5 stars
================================================================
  EXPORT FORMATS
================================================================
  CSV   : UTF-8 with BOM (opens perfectly in Excel)
          Fields: All 26 columns including file paths
  Excel : XML Spreadsheet (.xls) format
          Same fields, opens in MS Excel / LibreOffice
================================================================
  TROUBLESHOOTING
================================================================
  Q: Files not uploading?
  A: Check uploads/photos/ and uploads/bonafide/ folders exist
     and are writable. On Windows XAMPP they usually are.
     On Linux: chmod 755 uploads/photos/ uploads/bonafide/
  Q: Database error?
  A: Ensure MySQL is running and SQL file is imported.
     Check config/db.php credentials.
  Q: Admin password not working?
  A: The login.php has a plain-text fallback for 'admin123'.
     It auto-updates the hash on first successful login.
  Q: Duration not calculating?
  A: Make sure both date fields are filled. JavaScript
     calculates instantly on date change.
================================================================
