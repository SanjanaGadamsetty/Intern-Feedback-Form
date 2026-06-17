<?php
require_once '../config/db.php';
requireAdminLogin();
$conn = getDB();

// ---- DELETE ----
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Get file paths to delete
    $row = $conn->query("SELECT photo_path,bonafide_path FROM feedbacks WHERE id=$id")->fetch_assoc();
    if ($row) {
        foreach (['photo_path','bonafide_path'] as $col) {
            if ($row[$col] && file_exists('../' . $row[$col])) unlink('../' . $row[$col]);
        }
    }
    $stmt = $conn->prepare("DELETE FROM feedbacks WHERE id=?");
    $stmt->bind_param('i', $id); $stmt->execute();
    header('Location: feedbacks.php?msg=deleted'); exit();
}

// ---- EXPORT CSV ----
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="TNEB_Feedbacks_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
    fputcsv($out, [
        'Submission ID','Student Name','Register No','College Name','Department','Year','Section',
        'Email','Phone','Mentor Name','Internship Domain',
        'Start Date','End Date','Duration (Days)',
        'Teaching Quality','Subject Knowledge','Communication','Doubt Clarity',
        'Workplace Interaction','Punctuality','Average Rating',
        'Strengths','Improvements','Overall Feedback','Suggestions',
        'Photo File','Bonafide File','Submitted At'
    ]);
    $all = $conn->query("SELECT * FROM feedbacks ORDER BY submitted_at DESC");
    while ($r = $all->fetch_assoc()) {
        $avg = round(($r['teaching_quality']+$r['subject_knowledge']+$r['communication_skills']+
                      $r['doubt_clarification']+$r['classroom_interaction']+$r['punctuality'])/6, 2);
        fputcsv($out, [
            $r['submission_id'], $r['student_name'], $r['register_number'], $r['college_name'],
            $r['department'], $r['year'], $r['section'], $r['email'], $r['phone'],
            $r['faculty_name'], $r['subject_name'],
            $r['internship_start'], $r['internship_end'], $r['internship_duration'],
            $r['teaching_quality'], $r['subject_knowledge'], $r['communication_skills'],
            $r['doubt_clarification'], $r['classroom_interaction'], $r['punctuality'], $avg,
            $r['strengths'], $r['improvements'], $r['feedback'], $r['suggestions'],
            $r['photo_path'] ?? 'N/A', $r['bonafide_path'] ?? 'N/A', $r['submitted_at']
        ]);
    }
    fclose($out); exit();
}

// ---- EXPORT EXCEL (HTML table with .xls extension, opens in Excel) ----
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="TNEB_Feedbacks_' . date('Ymd_His') . '.xls"');
    $all = $conn->query("SELECT * FROM feedbacks ORDER BY submitted_at DESC");
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
          <Worksheet ss:Name="TNEB Feedbacks"><Table>';
    $headers = ['Submission ID','Student Name','Register No','College Name','Department','Year','Section',
        'Email','Phone','Mentor Name','Internship Domain','Start Date','End Date','Duration (Days)',
        'Teaching Quality','Subject Knowledge','Communication','Doubt Clarity',
        'Workplace Interaction','Punctuality','Average Rating',
        'Strengths','Improvements','Overall Feedback','Suggestions','Submitted At'];
    echo '<Row>';
    foreach ($headers as $h) echo '<Cell ss:StyleID="s1"><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>';
    echo '</Row>';
    while ($r = $all->fetch_assoc()) {
        $avg = round(($r['teaching_quality']+$r['subject_knowledge']+$r['communication_skills']+
                      $r['doubt_clarification']+$r['classroom_interaction']+$r['punctuality'])/6, 2);
        $cols = [
            $r['submission_id'], $r['student_name'], $r['register_number'], $r['college_name'],
            $r['department'], $r['year'], $r['section'], $r['email'], $r['phone'],
            $r['faculty_name'], $r['subject_name'],
            $r['internship_start'], $r['internship_end'], $r['internship_duration'],
            $r['teaching_quality'], $r['subject_knowledge'], $r['communication_skills'],
            $r['doubt_clarification'], $r['classroom_interaction'], $r['punctuality'], $avg,
            $r['strengths'], $r['improvements'], $r['feedback'], $r['suggestions'], $r['submitted_at']
        ];
        echo '<Row>';
        foreach ($cols as $c) echo '<Cell><Data ss:Type="String">' . htmlspecialchars((string)($c ?? '')) . '</Data></Cell>';
        echo '</Row>';
    }
    echo '</Table></Worksheet></Workbook>';
    exit();
}

// ---- FILTERS ----
$search  = trim($_GET['search']  ?? '');
$dept    = trim($_GET['dept']    ?? '');
$faculty = trim($_GET['faculty'] ?? '');
$sort    = in_array($_GET['sort'] ?? '', ['asc','desc']) ? $_GET['sort'] : 'desc';

$where = ['1=1']; $params = []; $types = '';
if ($search) {
    $s = "%$search%";
    $where[] = '(student_name LIKE ? OR faculty_name LIKE ? OR submission_id LIKE ? OR college_name LIKE ? OR register_number LIKE ?)';
    $params = array_merge($params, [$s,$s,$s,$s,$s]); $types .= 'sssss';
}
if ($dept)   { $where[] = 'department=?';   $params[] = $dept;    $types .= 's'; }
if ($faculty){ $where[] = 'faculty_name=?'; $params[] = $faculty; $types .= 's'; }
$whereStr = implode(' AND ', $where);

$perPage = 15;
$page    = max(1,(int)($_GET['page'] ?? 1));
$offset  = ($page-1)*$perPage;

$cStmt = $conn->prepare("SELECT COUNT(*) as c FROM feedbacks WHERE $whereStr");
if ($types) $cStmt->bind_param($types, ...$params);
$cStmt->execute();
$totalRows  = $cStmt->get_result()->fetch_assoc()['c'];
$totalPages = ceil($totalRows/$perPage);

$fStmt = $conn->prepare("SELECT * FROM feedbacks WHERE $whereStr ORDER BY submitted_at $sort LIMIT $perPage OFFSET $offset");
if ($types) $fStmt->bind_param($types, ...$params);
$fStmt->execute();
$feedbacks = $fStmt->get_result();

$depts    = $conn->query("SELECT DISTINCT department FROM feedbacks ORDER BY department");
$faculties= $conn->query("SELECT DISTINCT faculty_name FROM feedbacks ORDER BY faculty_name");
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Feedbacks – TNEB Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- SIDEBAR -->
<div class="admin-sidebar">
  <div class="sidebar-brand"><span style="font-size:1.4rem;">⚡</span>
    <div><div>TNEB Feedback</div><div style="font-size:0.7rem;opacity:0.7;font-weight:400;">Admin Panel</div></div>
  </div>
  <nav class="mt-3">
    <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="feedbacks.php" class="nav-link active"><i class="fas fa-list-alt"></i> Feedbacks</a>
    <a href="reports.php"   class="nav-link"><i class="fas fa-chart-bar"></i> Reports & Analytics</a>
    <hr style="border-color:rgba(255,255,255,0.1);margin:12px 24px;">
    <a href="../index.php" class="nav-link" target="_blank"><i class="fas fa-external-link-alt"></i> Feedback Form</a>
    <a href="logout.php"   class="nav-link" style="color:rgba(255,100,100,0.85);"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<div class="admin-content">
  <div class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm" id="sidebarToggle" style="border:1px solid var(--border);background:var(--card-bg);"><i class="fas fa-bars"></i></button>
      <div class="page-title">Manage Feedbacks</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button class="theme-toggle" id="themeToggle">🌙 Dark</button>
      <a href="?export=csv&<?= http_build_query(array_filter(['search'=>$search,'dept'=>$dept,'faculty'=>$faculty])) ?>"
         class="btn btn-sm" style="background:#2e7d32;color:white;border-radius:50px;padding:6px 16px;">
        <i class="fas fa-file-csv me-1"></i> CSV
      </a>
      <a href="?export=excel&<?= http_build_query(array_filter(['search'=>$search,'dept'=>$dept,'faculty'=>$faculty])) ?>"
         class="btn btn-sm" style="background:#1565c0;color:white;border-radius:50px;padding:6px 16px;">
        <i class="fas fa-file-excel me-1"></i> Excel
      </a>
    </div>
  </div>

  <div class="admin-main">
    <?php if (isset($_GET['msg']) && $_GET['msg']==='deleted'): ?>
    <div class="alert alert-success alert-auto-dismiss"><i class="fas fa-check-circle me-2"></i>Feedback deleted.</div>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <div class="filter-bar mb-4">
      <form method="GET" action="feedbacks.php">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label fw-600" style="font-size:0.85rem;">Search</label>
            <input type="text" class="form-control form-control-sm" name="search"
                   placeholder="Name, ID, College, Faculty..." value="<?= e($search) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-600" style="font-size:0.85rem;">Department</label>
            <select class="form-select form-select-sm" name="dept">
              <option value="">All Departments</option>
              <?php while ($d=$depts->fetch_assoc()): ?>
              <option value="<?= e($d['department']) ?>" <?= $dept===$d['department']?'selected':'' ?>><?= e($d['department']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-600" style="font-size:0.85rem;">Mentor</label>
            <select class="form-select form-select-sm" name="faculty">
              <option value="">All Mentors</option>
              <?php while ($f=$faculties->fetch_assoc()): ?>
              <option value="<?= e($f['faculty_name']) ?>" <?= $faculty===$f['faculty_name']?'selected':'' ?>><?= e($f['faculty_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-1">
            <label class="form-label fw-600" style="font-size:0.85rem;">Sort</label>
            <select class="form-select form-select-sm" name="sort">
              <option value="desc" <?= $sort==='desc'?'selected':'' ?>>Newest</option>
              <option value="asc"  <?= $sort==='asc' ?'selected':'' ?>>Oldest</option>
            </select>
          </div>
          <div class="col-md-1 d-flex gap-2">
            <button type="submit" class="btn btn-sm w-100" style="background:var(--primary);color:white;border-radius:8px;">
              <i class="fas fa-filter"></i>
            </button>
            <a href="feedbacks.php" class="btn btn-sm" style="border:1px solid var(--border);border-radius:8px;">✕</a>
          </div>
        </div>
      </form>
    </div>

    <!-- COUNT -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div style="color:var(--text-muted);font-size:0.9rem;">
        Showing <strong><?= min($offset+1,$totalRows) ?>–<?= min($offset+$perPage,$totalRows) ?></strong>
        of <strong><?= $totalRows ?></strong> records
      </div>
    </div>

    <!-- TABLE -->
    <div class="card">
      <div class="table-responsive">
        <table class="table-custom w-100">
          <thead>
            <tr>
              <th>Submission ID</th>
              <th>Student</th>
              <th>College</th>
              <th>Dept / Year</th>
              <th>Contact</th>
              <th>Mentor</th>
              <th>Duration</th>
              <th>Avg ★</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($feedbacks->num_rows === 0): ?>
          <tr><td colspan="10" class="text-center py-5" style="color:var(--text-muted);">
            <i class="fas fa-inbox fa-2x d-block mb-2"></i>No feedbacks found.
          </td></tr>
          <?php else: while ($fb = $feedbacks->fetch_assoc()):
            $avg = round(($fb['teaching_quality']+$fb['subject_knowledge']+$fb['communication_skills']+$fb['doubt_clarification']+$fb['classroom_interaction']+$fb['punctuality'])/6,1);
          ?>
          <tr>
            <td>
              <code style="font-size:0.78rem;color:var(--primary);font-weight:700;"><?= e($fb['submission_id']) ?></code>
            </td>
            <td>
              <div style="font-weight:600;"><?= e($fb['student_name']) ?></div>
              <div style="font-size:0.78rem;color:var(--text-muted);"><?= e($fb['register_number']) ?></div>
            </td>
            <td style="font-size:0.85rem;max-width:140px;white-space:normal;"><?= e($fb['college_name']) ?></td>
            <td>
              <span style="font-size:0.78rem;background:rgba(57,73,171,0.1);color:var(--primary);padding:2px 8px;border-radius:50px;"><?= e($fb['department']) ?></span>
              <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;"><?= e($fb['year']) ?> – <?= e($fb['section']) ?></div>
            </td>
            <td style="font-size:0.82rem;">
              <div><i class="fas fa-envelope" style="color:var(--primary);width:14px;"></i> <?= e($fb['email']) ?></div>
              <div><i class="fas fa-phone"    style="color:var(--primary);width:14px;"></i> <?= e($fb['phone']) ?></div>
            </td>
            <td>
              <div style="font-weight:600;"><?= e($fb['faculty_name']) ?></div>
              <div style="font-size:0.78rem;color:var(--text-muted);"><?= e($fb['subject_name']) ?></div>
            </td>
            <td style="font-size:0.82rem;white-space:nowrap;">
              <div><?= date('d M Y', strtotime($fb['internship_start'])) ?></div>
              <div style="color:var(--text-muted);">to <?= date('d M Y', strtotime($fb['internship_end'])) ?></div>
              <span style="font-weight:700;color:var(--primary);"><?= $fb['internship_duration'] ?> days</span>
            </td>
            <td><span class="badge-rating rating-<?= round($avg) ?>">★ <?= $avg ?></span></td>
            <td style="font-size:0.82rem;"><?= date('d M Y', strtotime($fb['submitted_at'])) ?></td>
            <td>
              <button class="btn btn-sm mb-1" style="background:rgba(57,73,171,0.1);color:var(--primary);border-radius:6px;white-space:nowrap;"
                      data-bs-toggle="modal" data-bs-target="#m<?= $fb['id'] ?>">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn btn-sm" style="background:rgba(198,40,40,0.1);color:#c62828;border-radius:6px;"
                      onclick="confirmDelete(<?= $fb['id'] ?>)">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>

          <!-- DETAIL MODAL -->
          <div class="modal fade" id="m<?= $fb['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
              <div class="modal-content" style="border-radius:16px;overflow:hidden;background:var(--card-bg);">
                <div class="modal-header" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:white;border:none;">
                  <h5 class="modal-title"><i class="fas fa-comment-dots me-2"></i>
                    Feedback Detail &nbsp;|&nbsp; <code style="font-size:0.85rem;"><?= e($fb['submission_id']) ?></code>
                  </h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                  <!-- Personal Info -->
                  <h6 class="fw-700 mb-3" style="color:var(--primary);">👤 Student Information</h6>
                  <div class="row g-3 mb-3">
                    <div class="col-md-4"><strong>Name:</strong> <?= e($fb['student_name']) ?></div>
                    <div class="col-md-4"><strong>Register No:</strong> <?= e($fb['register_number']) ?></div>
                    <div class="col-md-4"><strong>College:</strong> <?= e($fb['college_name']) ?></div>
                    <div class="col-md-4"><strong>Department:</strong> <?= e($fb['department']) ?></div>
                    <div class="col-md-2"><strong>Year:</strong> <?= e($fb['year']) ?></div>
                    <div class="col-md-2"><strong>Section:</strong> <?= e($fb['section']) ?></div>
                    <div class="col-md-4"><strong>Email:</strong> <?= e($fb['email']) ?></div>
                    <div class="col-md-4"><strong>Phone:</strong> <?= e($fb['phone']) ?></div>
                  </div>
                  <hr>
                  <h6 class="fw-700 mb-3" style="color:var(--primary);">⚡ Internship Details</h6>
                  <div class="row g-3 mb-3">
                    <div class="col-md-4"><strong>Mentor:</strong> <?= e($fb['faculty_name']) ?></div>
                    <div class="col-md-4"><strong>Domain:</strong> <?= e($fb['subject_name']) ?></div>
                    <div class="col-md-4"><strong>Duration:</strong>
                      <?= date('d M Y', strtotime($fb['internship_start'])) ?> –
                      <?= date('d M Y', strtotime($fb['internship_end'])) ?>
                      (<strong><?= $fb['internship_duration'] ?> days</strong>)
                    </div>
                  </div>
                  <hr>
                  <!-- Files -->
                  <h6 class="fw-700 mb-3" style="color:var(--primary);">📎 Uploaded Documents</h6>
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <strong>Passport Photo:</strong><br>
                      <?php if ($fb['photo_path']): ?>
                        <img src="../<?= e($fb['photo_path']) ?>" alt="Photo"
                             style="width:80px;height:100px;object-fit:cover;border-radius:8px;border:2px solid var(--border);margin-top:6px;">
                        <br><a href="../<?= e($fb['photo_path']) ?>" target="_blank" class="btn btn-sm mt-1"
                             style="background:rgba(57,73,171,0.1);color:var(--primary);border-radius:50px;font-size:0.8rem;">
                          <i class="fas fa-download"></i> View Photo
                        </a>
                      <?php else: ?><span style="color:var(--text-muted);">Not uploaded</span><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                      <strong>Bonafide Certificate:</strong><br>
                      <?php if ($fb['bonafide_path']): ?>
                        <a href="../<?= e($fb['bonafide_path']) ?>" target="_blank" class="btn mt-2"
                           style="background:rgba(198,40,40,0.1);color:#c62828;border-radius:50px;font-size:0.85rem;padding:8px 20px;">
                          <i class="fas fa-file-pdf me-2"></i> View PDF
                        </a>
                      <?php else: ?><span style="color:var(--text-muted);">Not uploaded</span><?php endif; ?>
                    </div>
                  </div>
                  <hr>
                  <!-- Ratings -->
                  <h6 class="fw-700 mb-3" style="color:var(--primary);">⭐ Ratings</h6>
                  <div class="row g-2 mb-3">
                    <?php
                    $rMap = [
                        'Teaching Quality'     => $fb['teaching_quality'],
                        'Subject Knowledge'    => $fb['subject_knowledge'],
                        'Communication'        => $fb['communication_skills'],
                        'Doubt Clarity'        => $fb['doubt_clarification'],
                        'Workplace Interaction'=> $fb['classroom_interaction'],
                        'Punctuality'          => $fb['punctuality'],
                    ];
                    foreach ($rMap as $rk => $rv): ?>
                    <div class="col-md-4">
                      <div style="background:var(--light-bg);border-radius:8px;padding:10px 14px;">
                        <div style="font-size:0.78rem;color:var(--text-muted);"><?= $rk ?></div>
                        <div><?php for ($s=1;$s<=5;$s++) echo $s<=$rv?'⭐':'☆'; ?>
                          <strong style="color:var(--primary);margin-left:4px;"><?= $rv ?>/5</strong>
                        </div>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <!-- Text feedback -->
                  <?php if ($fb['strengths']): ?><div class="mb-2"><strong>Strengths:</strong><p class="mb-0"><?= e($fb['strengths']) ?></p></div><?php endif; ?>
                  <?php if ($fb['improvements']): ?><div class="mb-2"><strong>Improvements:</strong><p class="mb-0"><?= e($fb['improvements']) ?></p></div><?php endif; ?>
                  <?php if ($fb['feedback']): ?><div class="mb-2"><strong>Overall Feedback:</strong><p class="mb-0"><?= e($fb['feedback']) ?></p></div><?php endif; ?>
                  <?php if ($fb['suggestions']): ?><div class="mb-2"><strong>Suggestions:</strong><p class="mb-0"><?= e($fb['suggestions']) ?></p></div><?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-4"><ul class="pagination justify-content-center flex-wrap">
      <?php for ($p=1;$p<=$totalPages;$p++):
        $q = http_build_query(array_filter(['search'=>$search,'dept'=>$dept,'faculty'=>$faculty,'sort'=>$sort,'page'=>$p])); ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="?<?= $q ?>"
           style="<?= $p===$page?'background:var(--primary);border-color:var(--primary);color:white;':'' ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
