<?php
// ============================================================
//  Database Configuration - TNEB Feedback System
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_feedback_system');

// Upload settings
define('UPLOAD_DIR',     __DIR__ . '/../uploads/');
define('PHOTO_DIR',      __DIR__ . '/../uploads/photos/');
define('BONAFIDE_DIR',   __DIR__ . '/../uploads/bonafide/');
define('MAX_FILE_SIZE',  1048576); // 1 MB in bytes

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('<div style="font-family:sans-serif;color:red;padding:20px;">
             <h3>Database Error</h3><p>' . htmlspecialchars($conn->connect_error) . '</p>
             <p>Please check your XAMPP MySQL service is running and the database is imported.</p></div>');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
}

function requireAdminLogin() {
    startSecureSession();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit();
    }
}

// XSS-safe output
function e($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

// Generate unique submission ID: TNEB{YEAR}-{5-digit seq}
function generateSubmissionId($conn) {
    $year = date('Y');
    $prefix = 'TNEB' . $year . '-';
    // Get last sequence for this year
    $stmt = $conn->prepare("SELECT submission_id FROM feedbacks WHERE submission_id LIKE ? ORDER BY id DESC LIMIT 1");
    $like = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $last = (int)substr($row['submission_id'], -5);
        $next = $last + 1;
    } else {
        $next = 1;
    }
    return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
}

// Handle file upload — returns saved path or error string
function handleFileUpload($fileKey, $destDir, $allowedTypes, $submissionId, $suffix) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'File is required.'];
    }
    $file = $_FILES[$fileKey];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload error code: ' . $file['error']];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'File must be less than 1 MB.'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowedTypes)) {
        return ['error' => 'Invalid file type: ' . $mime];
    }
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = preg_replace('/[^a-zA-Z0-9\-]/', '', $submissionId) . '_' . $suffix . '.' . strtolower($ext);
    $destPath = $destDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['error' => 'Failed to save file. Check folder permissions.'];
    }
    return ['path' => 'uploads/' . basename($destDir) . '/' . $filename];
}
