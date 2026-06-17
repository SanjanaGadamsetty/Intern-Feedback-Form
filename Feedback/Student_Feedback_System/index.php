<?php
require_once 'config/db.php';

$success = false;
$submission_id = '';
$errors = [];

$departments = ['AI & Data Science','Computer Science','Information Technology',
                'Electronics & Communication','Mechanical','Civil','Electrical','MBA','MCA', 'Other'];
$years = ['1st','2nd','3rd','4th', 'PG'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDB();

    // ---- Sanitize text inputs ----
    $student_name    = trim(htmlspecialchars($_POST['student_name']    ?? ''));
    $register_number = trim(htmlspecialchars($_POST['register_number'] ?? ''));
    $college_name    = trim(htmlspecialchars($_POST['college_name']    ?? ''));
    $department      = trim(htmlspecialchars($_POST['department']      ?? ''));
    $year            = trim(htmlspecialchars($_POST['year']            ?? ''));
    $section         = strtoupper(trim(htmlspecialchars($_POST['section'] ?? '')));
    $email           = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $phone           = trim(htmlspecialchars($_POST['phone']           ?? ''));
    $faculty_name    = trim(htmlspecialchars($_POST['faculty_name']    ?? ''));
    $subject_name    = trim(htmlspecialchars($_POST['subject_name']    ?? ''));
    $internship_start = trim($_POST['internship_start'] ?? '');
    $internship_end   = trim($_POST['internship_end']   ?? '');
    $strengths       = trim(htmlspecialchars($_POST['strengths']    ?? ''));
    $improvements    = trim(htmlspecialchars($_POST['improvements'] ?? ''));
    $feedback        = trim(htmlspecialchars($_POST['feedback']     ?? ''));
    $suggestions     = trim(htmlspecialchars($_POST['suggestions']  ?? ''));

    // Ratings
    $teaching_quality      = (int)($_POST['teaching_quality']      ?? 0);
    $subject_knowledge     = (int)($_POST['subject_knowledge']     ?? 0);
    $communication_skills  = (int)($_POST['communication_skills']  ?? 0);
    $doubt_clarification   = (int)($_POST['doubt_clarification']   ?? 0);
    $classroom_interaction = (int)($_POST['classroom_interaction'] ?? 0);
    $punctuality           = (int)($_POST['punctuality']           ?? 0);

    // ---- Validation ----
    if (empty($student_name))    $errors[] = 'Student name is required.';
    if (empty($register_number) || !preg_match('/^[0-9A-Za-z]{4,20}$/', $register_number))
        $errors[] = 'Valid register number is required (4–20 alphanumeric characters).';
    if (empty($college_name))    $errors[] = 'College name is required.';
    if (empty($department))      $errors[] = 'Department is required.';
    if (empty($year))            $errors[] = 'Year is required.';
    if (empty($section))         $errors[] = 'Section is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid email address is required.';
    if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', $phone))
        $errors[] = 'Valid 10-digit Indian mobile number is required (starts with 6–9).';
    if (empty($faculty_name))    $errors[] = 'Faculty/Mentor name is required.';
    if (empty($subject_name))    $errors[] = 'Subject/Domain is required.';

    // Date validation
    $duration_days = 0;
    if (empty($internship_start)) {
        $errors[] = 'Internship start date is required.';
    } elseif (empty($internship_end)) {
        $errors[] = 'Internship end date is required.';
    } else {
        $startDt = DateTime::createFromFormat('Y-m-d', $internship_start);
        $endDt   = DateTime::createFromFormat('Y-m-d', $internship_end);
        if (!$startDt || !$endDt) {
            $errors[] = 'Invalid date format.';
        } elseif ($endDt <= $startDt) {
            $errors[] = 'Internship end date must be after start date.';
        } else {
            $duration_days = (int)$startDt->diff($endDt)->days;
            if ($duration_days < 1) $errors[] = 'Internship duration must be at least 1 day.';
        }
    }

    // Ratings validation
    $ratingFields = ['teaching_quality','subject_knowledge','communication_skills',
                     'doubt_clarification','classroom_interaction','punctuality'];
    foreach ($ratingFields as $rf) {
        $v = (int)($_POST[$rf] ?? 0);
        if ($v < 1 || $v > 5) { $errors[] = 'All 6 ratings are required (1–5 stars).'; break; }
    }

    // File uploads (only process if no text errors)
    $photo_path    = null;
    $bonafide_path = null;

    if (empty($errors)) {
        $sid = generateSubmissionId($conn);

        // Photo upload
        $photoResult = handleFileUpload('photo', PHOTO_DIR,
            ['image/jpeg','image/jpg','image/png'], $sid, 'photo');
        if (isset($photoResult['error'])) {
            $errors[] = 'Passport Photo: ' . $photoResult['error'];
        } else {
            $photo_path = $photoResult['path'];
        }

        // Bonafide upload
        $bonafideResult = handleFileUpload('bonafide', BONAFIDE_DIR,
            ['application/pdf'], $sid, 'bonafide');
        if (isset($bonafideResult['error'])) {
            $errors[] = 'Bonafide Certificate: ' . $bonafideResult['error'];
        } else {
            $bonafide_path = $bonafideResult['path'];
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO feedbacks
             (submission_id, student_name, register_number, college_name, department, year,
              section, email, phone, faculty_name, subject_name,
              internship_start, internship_end, internship_duration,
              photo_path, bonafide_path,
              teaching_quality, subject_knowledge, communication_skills,
              doubt_clarification, classroom_interaction, punctuality,
              strengths, improvements, feedback, suggestions)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param(
            'sssssssssssssissiiiiiissss',
            $sid, $student_name, $register_number, $college_name, $department, $year,
            $section, $email, $phone, $faculty_name, $subject_name,
            $internship_start, $internship_end, $duration_days,
            $photo_path, $bonafide_path,
            $teaching_quality, $subject_knowledge, $communication_skills,
            $doubt_clarification, $classroom_interaction, $punctuality,
            $strengths, $improvements, $feedback, $suggestions
        );
        if ($stmt->execute()) {
            $success       = true;
            $submission_id = $sid;
        } else {
            $errors[] = 'Database error. Please try again. (' . $conn->error . ')';
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TNEB Feedback Form</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<style>
.upload-box {
    border: 2px dashed var(--border);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: var(--light-bg);
    position: relative;
}
.upload-box:hover { border-color: var(--primary-light); background: rgba(57,73,171,0.04); }
.upload-box input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.upload-box .upload-icon { font-size: 2rem; color: var(--primary); margin-bottom: 8px; }
.upload-preview { display: none; margin-top: 10px; }
.duration-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
    border: 1px solid #a5d6a7; border-radius: 50px;
    padding: 6px 16px; font-weight: 700; color: #2e7d32;
    font-size: 0.92rem; margin-top: 8px;
}
.tneb-logo { display: flex; align-items: center; gap: 14px; }
.tneb-logo .logo-circle {
    width: 52px; height: 52px; border-radius: 50%;
    background: rgba(255,255,255,0.2); display: flex; align-items: center;
    justify-content: center; font-size: 1.5rem; flex-shrink: 0;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <div class="tneb-logo">
        <div class="logo-circle">⚡</div>
        <div>
          <div style="font-size:1.1rem;font-weight:800;line-height:1.1;">TNEB Internship Feedback</div>
          <div style="font-size:0.7rem;opacity:0.8;font-weight:400;">Tamil Nadu Electricity Board</div>
        </div>
      </div>
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <button class="theme-toggle" id="themeToggle">🌙 Dark</button>
      <a href="admin/login.php" class="btn btn-sm"
         style="background:rgba(255,255,255,0.15);color:white;border:1px solid rgba(255,255,255,0.3);border-radius:50px;padding:6px 16px;">
        <i class="fas fa-lock me-1"></i> Admin
      </a>
    </div>
  </div>
</nav>

<!-- HERO -->
<div class="hero-section">
  <div class="container text-center position-relative">
    <div class="hero-badge">⚡ Tamil Nadu Electricity Board</div>
    <h1>TNEB Feedback Form</h1>
    <p class="mt-3 mb-0">Internship Feedback & Evaluation Portal for Students</p>
  </div>
</div>

<div class="container my-5">

<?php if ($success): ?>
<!-- ---- SUCCESS ---- -->
<div class="ack-box mb-5 fade-in">
  <div style="font-size:3.5rem;">✅</div>
  <h3 style="color:var(--success);font-weight:800;margin:12px 0;">Feedback Submitted Successfully!</h3>
  <p style="color:#555;margin-bottom:6px;">Your unique Submission ID is:</p>
  <div class="ack-number"><?= e($submission_id) ?></div>
  <p style="color:#777;font-size:0.9rem;margin-top:8px;">
    Please save this ID for your records and future reference.
  </p>
  <a href="index.php" class="btn-primary-custom mt-4" style="display:inline-block;">
    <i class="fas fa-plus me-2"></i> Submit Another Feedback
  </a>
</div>

<?php else: ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
  <i class="fas fa-exclamation-triangle me-2"></i>
  <strong>Please fix the following:</strong>
  <ul class="mb-0 mt-2">
    <?php foreach ($errors as $err): ?>
    <li><?= e($err) ?></li>
    <?php endforeach; ?>
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form id="feedbackForm" method="POST" action="index.php" enctype="multipart/form-data" novalidate>

<!-- ===== SECTION 1: PERSONAL INFORMATION ===== -->
<div class="form-section mb-4 fade-in">
  <div class="section-header"><i class="fas fa-user-graduate"></i> Student Information</div>
  <div class="p-4">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label required">Student Name</label>
        <input type="text" class="form-control" id="student_name" name="student_name"
               value="<?= e($_POST['student_name'] ?? '') ?>"
               placeholder="Enter your full name" required>
        <div class="invalid-feedback" id="err_student_name"></div>
      </div>
      <div class="col-md-6">
        <label class="form-label required">Register Number</label>
        <input type="text" class="form-control" id="register_number" name="register_number"
               value="<?= e($_POST['register_number'] ?? '') ?>"
               placeholder="e.g. 22AD001" required>
        <div class="invalid-feedback" id="err_register_number"></div>
      </div>
      <div class="col-md-8">
        <label class="form-label required">College Name</label>
        <input type="text" class="form-control" id="college_name" name="college_name"
               value="<?= e($_POST['college_name'] ?? '') ?>"
               placeholder="e.g. Sri Venkateswara College of Engineering" required>
        <div class="invalid-feedback" id="err_college_name"></div>
      </div>
      <div class="col-md-4">
        <label class="form-label required">Department</label>
        <select class="form-select" id="department" name="department" required>
          <option value="">-- Select --</option>
          <?php foreach ($departments as $d): ?>
          <option value="<?= e($d) ?>" <?= ($_POST['department'] ?? '')===$d?'selected':'' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback" id="err_department"></div>
      </div>
      <div class="col-md-3">
        <label class="form-label required">Year</label>
        <select class="form-select" id="year" name="year" required>
          <option value="">-- Year --</option>
          <?php foreach ($years as $y): ?>
          <option value="<?= e($y) ?>" <?= ($_POST['year'] ?? '')===$y?'selected':'' ?>><?= e($y) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback" id="err_year"></div>
      </div>
      <div class="col-md-3">
        <label class="form-label required">Section</label>
        <input type="text" class="form-control" id="section" name="section" maxlength="3"
               value="<?= e($_POST['section'] ?? '') ?>" placeholder="A / B / C" required>
        <div class="invalid-feedback" id="err_section"></div>
      </div>
      <div class="col-md-6">
        <label class="form-label required">Email Address</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-envelope" style="color:var(--primary)"></i></span>
          <input type="email" class="form-control" id="email" name="email"
                 value="<?= e($_POST['email'] ?? '') ?>"
                 placeholder="student@college.edu" required>
        </div>
        <div class="invalid-feedback" id="err_email"></div>
      </div>
      <div class="col-md-6">
        <label class="form-label required">Phone Number</label>
        <div class="input-group">
          <span class="input-group-text" style="background:var(--light-bg);color:var(--text-main);">+91</span>
          <input type="tel" class="form-control" id="phone" name="phone" maxlength="10"
                 value="<?= e($_POST['phone'] ?? '') ?>"
                 placeholder="10-digit mobile number" required>
        </div>
        <small class="text-muted">Indian mobile number starting with 6, 7, 8 or 9</small>
        <div class="invalid-feedback" id="err_phone"></div>
      </div>
    </div>
  </div>
</div>

<!-- ===== SECTION 2: INTERNSHIP DETAILS ===== -->
<div class="form-section mb-4 fade-in">
  <div class="section-header"><i class="fas fa-industry"></i> TNEB Internship Details</div>
  <div class="p-4">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label required">Mentor / Faculty Name</label>
        <input type="text" class="form-control" id="faculty_name" name="faculty_name"
               value="<?= e($_POST['faculty_name'] ?? '') ?>"
               placeholder="Enter TNEB mentor name" required>
        <div class="invalid-feedback" id="err_faculty_name"></div>
      </div>
      <div class="col-md-6">
        <label class="form-label required">Internship Domain / Subject</label>
        <input type="text" class="form-control" id="subject_name" name="subject_name"
               value="<?= e($_POST['subject_name'] ?? '') ?>"
               placeholder="e.g. Power Systems, Substation Operations" required>
        <div class="invalid-feedback" id="err_subject_name"></div>
      </div>
      <div class="col-md-4">
        <label class="form-label required">Internship Start Date</label>
        <input type="date" class="form-control" id="internship_start" name="internship_start"
               value="<?= e($_POST['internship_start'] ?? '') ?>"
               max="<?= date('Y-m-d') ?>" required>
        <div class="invalid-feedback" id="err_internship_start"></div>
      </div>
      <div class="col-md-4">
        <label class="form-label required">Internship End Date</label>
        <input type="date" class="form-control" id="internship_end" name="internship_end"
               value="<?= e($_POST['internship_end'] ?? '') ?>"
               max="<?= date('Y-m-d') ?>" required>
        <div class="invalid-feedback" id="err_internship_end"></div>
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <div id="durationBox" style="display:none;">
          <label class="form-label" style="color:var(--text-muted)">Auto-calculated Duration</label>
          <div class="duration-badge">
            <i class="fas fa-calendar-check"></i>
            <span id="durationText">-- days</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== SECTION 3: FILE UPLOADS ===== -->
<div class="form-section mb-4 fade-in">
  <div class="section-header"><i class="fas fa-paperclip"></i> Required Documents</div>
  <div class="p-4">
    <div class="row g-4">
      <!-- Passport Photo -->
      <div class="col-md-6">
        <label class="form-label required">Passport Size Photo</label>
        <div class="upload-box" id="photoBox">
          <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/jpg,image/png"
                 onchange="previewFile(this,'photoPreview','photoInfo','photoBox')">
          <div id="photoDefault">
            <div class="upload-icon">📷</div>
            <div style="font-weight:600;color:var(--text-main);">Click to upload photo</div>
            <div style="font-size:0.82rem;color:var(--text-muted);margin-top:4px;">
              JPG / PNG &nbsp;|&nbsp; Max 1 MB
            </div>
          </div>
          <div class="upload-preview" id="photoPreview">
            <img id="photoImg" src="" alt="Preview"
                 style="width:100px;height:120px;object-fit:cover;border-radius:8px;border:2px solid var(--border);">
            <div id="photoInfo" class="mt-2" style="font-size:0.82rem;color:var(--success);font-weight:600;"></div>
          </div>
        </div>
        <div class="invalid-feedback d-block" id="err_photo"></div>
      </div>
      <!-- Bonafide -->
      <div class="col-md-6">
        <label class="form-label required">Bonafide Certificate (PDF)</label>
        <div class="upload-box" id="bonafideBox">
          <input type="file" name="bonafide" id="bonafideInput" accept="application/pdf"
                 onchange="previewFile(this,'bonafidePreview','bonafideInfo','bonafideBox')">
          <div id="bonafideDefault">
            <div class="upload-icon">📄</div>
            <div style="font-weight:600;color:var(--text-main);">Click to upload PDF</div>
            <div style="font-size:0.82rem;color:var(--text-muted);margin-top:4px;">
              PDF only &nbsp;|&nbsp; Max 1 MB
            </div>
          </div>
          <div class="upload-preview" id="bonafidePreview">
            <div style="font-size:2.5rem;">📄</div>
            <div id="bonafideInfo" style="font-size:0.82rem;color:var(--success);font-weight:600;margin-top:4px;"></div>
          </div>
        </div>
        <div class="invalid-feedback d-block" id="err_bonafide"></div>
      </div>
    </div>
  </div>
</div>

<!-- ===== SECTION 4: RATINGS ===== -->
<div class="form-section mb-4 fade-in">
  <div class="section-header"><i class="fas fa-star"></i> Performance Ratings (1–5 Stars)</div>
  <div class="p-4">
    <?php
    $ratingCriteria = [
        ['key'=>'teaching_quality',      'label'=>'Teaching / Training Quality', 'icon'=>'fas fa-book-open'],
        ['key'=>'subject_knowledge',     'label'=>'Subject / Domain Knowledge',  'icon'=>'fas fa-brain'],
        ['key'=>'communication_skills',  'label'=>'Communication Skills',        'icon'=>'fas fa-comments'],
        ['key'=>'doubt_clarification',   'label'=>'Doubt Clarification',         'icon'=>'fas fa-question-circle'],
        ['key'=>'classroom_interaction', 'label'=>'Workplace Interaction',       'icon'=>'fas fa-users'],
        ['key'=>'punctuality',           'label'=>'Punctuality & Discipline',    'icon'=>'fas fa-clock'],
    ];
    foreach ($ratingCriteria as $rc): ?>
    <div class="row align-items-center mb-3 pb-3" style="border-bottom:1px solid var(--border);">
      <div class="col-md-5">
        <label class="form-label mb-0 required">
          <i class="<?= $rc['icon'] ?> me-2" style="color:var(--primary)"></i><?= $rc['label'] ?>
        </label>
      </div>
      <div class="col-md-7">
        <div class="star-rating">
          <?php for ($i=5;$i>=1;$i--):
            $chk = ($_POST[$rc['key']] ?? '')==$i ? 'checked' : ''; ?>
          <input type="radio" name="<?= $rc['key'] ?>" id="<?= $rc['key'] ?>_<?= $i ?>" value="<?= $i ?>" <?= $chk ?>>
          <label for="<?= $rc['key'] ?>_<?= $i ?>" title="<?= $i ?> Star">★</label>
          <?php endfor; ?>
        </div>
        <div class="text-danger" id="err_<?= $rc['key'] ?>" style="font-size:0.8rem;"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ===== SECTION 5: WRITTEN FEEDBACK ===== -->
<div class="form-section mb-4 fade-in">
  <div class="section-header"><i class="fas fa-pen-to-square"></i> Detailed Feedback</div>
  <div class="p-4">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Strengths of Mentor / TNEB</label>
        <textarea class="form-control" name="strengths" rows="3"
          placeholder="What impressed you most?"><?= e($_POST['strengths'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Areas for Improvement</label>
        <textarea class="form-control" name="improvements" rows="3"
          placeholder="What could be improved?"><?= e($_POST['improvements'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Overall Feedback</label>
        <textarea class="form-control" name="feedback" rows="3"
          placeholder="Your overall internship experience..."><?= e($_POST['feedback'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Suggestions</label>
        <textarea class="form-control" name="suggestions" rows="3"
          placeholder="Suggestions for future interns or TNEB..."><?= e($_POST['suggestions'] ?? '') ?></textarea>
      </div>
    </div>
  </div>
</div>

<!-- BUTTONS -->
<div class="d-flex gap-3 justify-content-center mb-5">
  <button type="submit" class="btn-primary-custom" style="padding:14px 52px;font-size:1.05rem;">
    <i class="fas fa-paper-plane me-2"></i> Submit Feedback
  </button>
  <button type="reset" class="btn-accent" onclick="resetForm()" style="padding:14px 40px;">
    <i class="fas fa-rotate-left me-2"></i> Reset
  </button>
</div>

</form>
<?php endif; ?>
</div>

<!-- FOOTER -->
<footer class="footer-custom">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-4">
        <h5>⚡ TNEB Feedback Portal</h5>
        <p style="font-size:0.9rem;">Internship Feedback & Evaluation System for Tamil Nadu Electricity Board student interns.</p>
      </div>
      <div class="col-md-4 mb-4">
        <h5>Quick Links</h5>
        <ul class="list-unstyled">
          <li><a href="index.php"><i class="fas fa-chevron-right me-1"></i> Submit Feedback</a></li>
          <li><a href="admin/login.php"><i class="fas fa-chevron-right me-1"></i> Admin Panel</a></li>
        </ul>
      </div>
      <div class="col-md-4 mb-4">
        <h5>Project Info</h5>
        <p style="font-size:0.9rem;">Built with PHP · MySQL · Bootstrap 5 · Chart.js<br></p>
      </div>
    </div>
    <div class="footer-bottom text-center">
      <p>&copy; <?= date('Y') ?> TNEB Internship Feedback Management System</p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
// ---- Date Duration Auto-Calculation ----
const startInput = document.getElementById('internship_start');
const endInput   = document.getElementById('internship_end');
const durationBox  = document.getElementById('durationBox');
const durationText = document.getElementById('durationText');

function calcDuration() {
    const s = startInput.value, e = endInput.value;
    if (!s || !e) { durationBox.style.display = 'none'; return; }
    const start = new Date(s), end = new Date(e);
    if (end <= start) {
        durationBox.style.display = 'block';
        durationText.textContent = 'End date must be after start';
        durationText.parentElement.style.background = 'linear-gradient(135deg,#ffebee,#fce4ec)';
        durationText.parentElement.style.borderColor = '#ef9a9a';
        durationText.parentElement.style.color = '#c62828';
        return;
    }
    const days = Math.round((end - start) / 86400000);
    durationBox.style.display = 'block';
    durationText.textContent = days + (days === 1 ? ' day' : ' days');
    durationText.parentElement.style.background = 'linear-gradient(135deg,#e8f5e9,#f1f8e9)';
    durationText.parentElement.style.borderColor = '#a5d6a7';
    durationText.parentElement.style.color = '#2e7d32';
}
startInput.addEventListener('change', calcDuration);
endInput.addEventListener('change', calcDuration);

// ---- File Upload Preview ----
function previewFile(input, previewId, infoId, boxId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    const info    = document.getElementById(infoId);
    const errId   = 'err_' + (input.name);
    const errEl   = document.getElementById(errId);
    if (errEl) errEl.textContent = '';

    if (!file) { preview.style.display='none'; return; }

    // Size check client-side
    if (file.size > 1048576) {
        if (errEl) errEl.textContent = 'File must be less than 1 MB. Your file: ' + (file.size/1024/1024).toFixed(2) + ' MB';
        input.value = '';
        preview.style.display = 'none';
        return;
    }

    preview.style.display = 'block';
    const defaultDiv = document.getElementById(input.name === 'photo' ? 'photoDefault' : 'bonafideDefault');
    if (defaultDiv) defaultDiv.style.display = 'none';

    info.textContent = '✅ ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';

    if (input.name === 'photo') {
        const reader = new FileReader();
        reader.onload = e => { document.getElementById('photoImg').src = e.target.result; };
        reader.readAsDataURL(file);
    }
}

// ---- JS Form Validation ----
document.getElementById('feedbackForm').addEventListener('submit', function(e) {
    if (!clientValidate()) e.preventDefault();
});

function clientValidate() {
    let ok = true;
    clearJsErrors();

    const req = [
        {id:'student_name',    label:'Student name'},
        {id:'register_number', label:'Register number'},
        {id:'college_name',    label:'College name'},
        {id:'department',      label:'Department'},
        {id:'year',            label:'Year'},
        {id:'section',         label:'Section'},
        {id:'faculty_name',    label:'Mentor name'},
        {id:'subject_name',    label:'Internship domain'},
    ];
    req.forEach(r => {
        const el = document.getElementById(r.id);
        if (el && !el.value.trim()) { showJsError(r.id, r.label + ' is required.'); ok = false; }
    });

    // Register number format
    const reg = document.getElementById('register_number');
    if (reg && reg.value.trim() && !/^[0-9A-Za-z]{4,20}$/.test(reg.value.trim())) {
        showJsError('register_number', '4–20 alphanumeric characters required.'); ok = false;
    }

    // Email
    const email = document.getElementById('email');
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
        showJsError('email', 'Enter a valid email address.'); ok = false;
    }

    // Phone
    const phone = document.getElementById('phone');
    if (phone && !/^[6-9]\d{9}$/.test(phone.value.trim())) {
        showJsError('phone', 'Enter a valid 10-digit Indian mobile number.'); ok = false;
    }

    // Dates
    const s = document.getElementById('internship_start').value;
    const en = document.getElementById('internship_end').value;
    if (!s) { showJsError('internship_start', 'Start date is required.'); ok = false; }
    if (!en) { showJsError('internship_end', 'End date is required.'); ok = false; }
    if (s && en && new Date(en) <= new Date(s)) {
        showJsError('internship_end', 'End date must be after start date.'); ok = false;
    }

    // Files
    if (!document.getElementById('photoInput').files[0]) {
        document.getElementById('err_photo').textContent = 'Passport photo is required.'; ok = false;
    }
    if (!document.getElementById('bonafideInput').files[0]) {
        document.getElementById('err_bonafide').textContent = 'Bonafide certificate is required.'; ok = false;
    }

    // Ratings
    const ratings = ['teaching_quality','subject_knowledge','communication_skills',
                     'doubt_clarification','classroom_interaction','punctuality'];
    ratings.forEach(r => {
        if (!document.querySelector('input[name="'+r+'"]:checked')) {
            document.getElementById('err_'+r).textContent = 'Rating required.'; ok = false;
        }
    });

    if (!ok) window.scrollTo({top:0, behavior:'smooth'});
    return ok;
}

function showJsError(id, msg) {
    const el = document.getElementById(id);
    const err = document.getElementById('err_' + id);
    if (el) el.classList.add('is-invalid');
    if (err) { err.textContent = msg; err.style.display='block'; }
}

function clearJsErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('[id^="err_"]').forEach(el => { el.textContent=''; el.style.display='none'; });
}

function resetForm() {
    clearJsErrors();
    document.getElementById('photoPreview').style.display='none';
    document.getElementById('photoDefault').style.display='block';
    document.getElementById('bonafidePreview').style.display='none';
    document.getElementById('bonafideDefault').style.display='block';
    document.getElementById('durationBox').style.display='none';
}

// Trigger if dates already filled (after form error)
calcDuration();
</script>
</body>
</html>
