<?php
require_once '../config/db.php';
requireAdminLogin();
$conn = getDB();

$total      = $conn->query("SELECT COUNT(*) as c FROM feedbacks")->fetch_assoc()['c'];
$avgResult  = $conn->query("SELECT AVG((teaching_quality+subject_knowledge+communication_skills+doubt_clarification+classroom_interaction+punctuality)/6) as avg FROM feedbacks")->fetch_assoc();
$avgRating  = round($avgResult['avg'] ?? 0, 1);
$facCount   = $conn->query("SELECT COUNT(DISTINCT faculty_name) as c FROM feedbacks")->fetch_assoc()['c'];
$todayCount = $conn->query("SELECT COUNT(*) as c FROM feedbacks WHERE DATE(submitted_at)=CURDATE()")->fetch_assoc()['c'];
$collegeCount= $conn->query("SELECT COUNT(DISTINCT college_name) as c FROM feedbacks")->fetch_assoc()['c'];

$facultyStats   = $conn->query("SELECT faculty_name, COUNT(*) as total, AVG((teaching_quality+subject_knowledge+communication_skills+doubt_clarification+classroom_interaction+punctuality)/6) as avg_rating FROM feedbacks GROUP BY faculty_name ORDER BY total DESC LIMIT 5");
$recentFeedbacks= $conn->query("SELECT * FROM feedbacks ORDER BY submitted_at DESC LIMIT 10");

$monthlyData = $conn->query("SELECT DATE_FORMAT(submitted_at,'%b %Y') as month, COUNT(*) as cnt FROM feedbacks WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY YEAR(submitted_at),MONTH(submitted_at) ORDER BY submitted_at");
$months=[]; $monthlyCounts=[];
while ($r=$monthlyData->fetch_assoc()) { $months[]=$r['month']; $monthlyCounts[]=$r['cnt']; }

$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – TNEB Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="admin-sidebar">
  <div class="sidebar-brand"><span style="font-size:1.4rem;">⚡</span>
    <div><div>TNEB Feedback</div><div style="font-size:0.7rem;opacity:0.7;font-weight:400;">Admin Panel</div></div>
  </div>
  <nav class="mt-3">
    <a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="feedbacks.php" class="nav-link"><i class="fas fa-list-alt"></i> Feedbacks</a>
    <a href="reports.php"   class="nav-link"><i class="fas fa-chart-bar"></i> Reports & Analytics</a>
    <hr style="border-color:rgba(255,255,255,0.1);margin:12px 24px;">
    <a href="../index.php"  class="nav-link" target="_blank"><i class="fas fa-external-link-alt"></i> Feedback Form</a>
    <a href="logout.php"    class="nav-link" style="color:rgba(255,100,100,0.85);"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<div class="admin-content">
  <div class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm" id="sidebarToggle" style="border:1px solid var(--border);background:var(--card-bg);"><i class="fas fa-bars"></i></button>
      <div><div class="page-title">Dashboard</div>
        <div style="font-size:0.8rem;color:var(--text-muted);"><?= date('l, d F Y') ?></div></div>
    </div>
    <div class="d-flex align-items-center gap-3">
      <button class="theme-toggle" id="themeToggle">🌙 Dark</button>
      <div class="d-flex align-items-center gap-2">
        <div style="width:36px;height:36px;background:linear-gradient(135deg,#1a237e,#3949ab);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;"><?= strtoupper(substr($adminName,0,1)) ?></div>
        <strong style="font-size:0.88rem;"><?= e($adminName) ?></strong>
      </div>
    </div>
  </div>

  <div class="admin-main">
    <!-- STAT CARDS -->
    <div class="row g-4 mb-5">
      <div class="col-sm-6 col-xl-4">
        <div class="stat-card bg-grad-blue">
          <span class="stat-icon"><i class="fas fa-comments"></i></span>
          <div class="stat-number" data-target="<?= $total ?>"><?= $total ?></div>
          <div class="stat-label">Total Feedbacks Received</div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-4">
        <div class="stat-card bg-grad-green">
          <span class="stat-icon"><i class="fas fa-star"></i></span>
          <div class="stat-number decimal" data-target="<?= $avgRating ?>"><?= $avgRating ?></div>
          <div class="stat-label">Average Rating (out of 5)</div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-4">
        <div class="stat-card bg-grad-orange">
          <span class="stat-icon"><i class="fas fa-calendar-day"></i></span>
          <div class="stat-number" data-target="<?= $todayCount ?>"><?= $todayCount ?></div>
          <div class="stat-label">Today's Submissions</div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-6">
        <div class="stat-card bg-grad-purple">
          <span class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></span>
          <div class="stat-number" data-target="<?= $facCount ?>"><?= $facCount ?></div>
          <div class="stat-label">Mentors Evaluated</div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-6">
        <div class="stat-card" style="background:linear-gradient(135deg,#00695c,#00897b);">
          <span class="stat-icon"><i class="fas fa-university"></i></span>
          <div class="stat-number" data-target="<?= $collegeCount ?>"><?= $collegeCount ?></div>
          <div class="stat-label">Colleges Participated</div>
        </div>
      </div>
    </div>

    <!-- CHARTS -->
    <div class="row g-4 mb-4">
      <div class="col-lg-8">
        <div class="card h-100">
          <div class="card-header-custom"><i class="fas fa-chart-line"></i> Monthly Submission Trend</div>
          <div class="card-body p-4"><canvas id="monthlyChart" height="80"></canvas></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header-custom"><i class="fas fa-chart-pie"></i> Department Distribution</div>
          <div class="card-body p-4"><canvas id="deptChart"></canvas></div>
        </div>
      </div>
    </div>

    <!-- FACULTY + RECENT -->
    <div class="row g-4 mb-4">
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header-custom"><i class="fas fa-medal"></i> Top Mentors by Feedback</div>
          <div class="card-body p-3">
            <?php while ($fac=$facultyStats->fetch_assoc()):
              $pct = ($fac['avg_rating']/5)*100; ?>
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-1">
                <span style="font-weight:600;font-size:0.88rem;"><?= e($fac['faculty_name']) ?></span>
                <span class="badge-rating rating-<?= round($fac['avg_rating']) ?>">★ <?= number_format($fac['avg_rating'],1) ?> (<?= $fac['total'] ?>)</span>
              </div>
              <div class="progress-custom"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
            </div>
            <?php endwhile; ?>
          </div>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="card h-100">
          <div class="card-header-custom">
            <i class="fas fa-clock"></i> Recent Submissions
            <a href="feedbacks.php" class="ms-auto btn btn-sm" style="background:rgba(255,255,255,0.2);color:white;border-radius:50px;padding:3px 12px;font-size:0.8rem;">View All</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table-custom w-100">
                <thead><tr><th>ID</th><th>Student</th><th>College</th><th>Mentor</th><th>★ Avg</th><th>Date</th></tr></thead>
                <tbody>
                <?php while ($fb=$recentFeedbacks->fetch_assoc()):
                  $avg=round(($fb['teaching_quality']+$fb['subject_knowledge']+$fb['communication_skills']+$fb['doubt_clarification']+$fb['classroom_interaction']+$fb['punctuality'])/6,1); ?>
                <tr>
                  <td><code style="font-size:0.75rem;"><?= e($fb['submission_id']) ?></code></td>
                  <td><div style="font-weight:600;font-size:0.85rem;"><?= e($fb['student_name']) ?></div></td>
                  <td style="font-size:0.8rem;max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($fb['college_name']) ?></td>
                  <td style="font-size:0.85rem;"><?= e($fb['faculty_name']) ?></td>
                  <td><span class="badge-rating rating-<?= round($avg) ?>">★ <?= $avg ?></span></td>
                  <td style="font-size:0.8rem;color:var(--text-muted);"><?= date('d M', strtotime($fb['submitted_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- EXPORT BUTTONS -->
    <div class="card p-4 mb-4">
      <h6 style="color:var(--primary);font-weight:700;margin-bottom:16px;"><i class="fas fa-download me-2"></i>Export All Data</h6>
      <div class="d-flex gap-3 flex-wrap">
        <a href="feedbacks.php?export=csv" class="btn" style="background:linear-gradient(135deg,#2e7d32,#43a047);color:white;border-radius:50px;padding:10px 28px;font-weight:600;">
          <i class="fas fa-file-csv me-2"></i> Export as CSV
        </a>
        <a href="feedbacks.php?export=excel" class="btn" style="background:linear-gradient(135deg,#1565c0,#1976d2);color:white;border-radius:50px;padding:10px 28px;font-weight:600;">
          <i class="fas fa-file-excel me-2"></i> Export as Excel (.xls)
        </a>
        <a href="reports.php" class="btn" style="background:linear-gradient(135deg,#4a148c,#7b1fa2);color:white;border-radius:50px;padding:10px 28px;font-weight:600;">
          <i class="fas fa-chart-bar me-2"></i> View Analytics
        </a>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
new Chart(document.getElementById('monthlyChart'), {
  type:'line',
  data:{ labels:<?= json_encode($months?:['No Data']) ?>, datasets:[{
    label:'Feedbacks', data:<?= json_encode($monthlyCounts?:[0]) ?>,
    borderColor:'#3949ab', backgroundColor:'rgba(57,73,171,0.1)',
    tension:0.4, fill:true, pointBackgroundColor:'#1a237e', pointRadius:5
  }]},
  options:{responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}
});

const dL=[], dD=[];
<?php
$conn2=getDB();
$ds=$conn2->query("SELECT department,COUNT(*) as t FROM feedbacks GROUP BY department ORDER BY t DESC LIMIT 7");
while($r=$ds->fetch_assoc()):?>
dL.push('<?= addslashes($r['department']) ?>'); dD.push(<?= $r['t'] ?>);
<?php endwhile; $conn2->close(); ?>
new Chart(document.getElementById('deptChart'),{
  type:'doughnut',
  data:{ labels:dL, datasets:[{data:dD,backgroundColor:['#1a237e','#3949ab','#ef6c00','#2e7d32','#7b1fa2','#01579b','#00695c'],borderWidth:2}]},
  options:{responsive:true, plugins:{legend:{position:'bottom',labels:{font:{size:10}}}}}
});
</script>
</body>
</html>
