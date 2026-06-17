<?php
require_once '../config/db.php';
requireAdminLogin();
$conn = getDB();

$overall    = $conn->query("SELECT COUNT(*) as total, AVG((teaching_quality+subject_knowledge+communication_skills+doubt_clarification+classroom_interaction+punctuality)/6) as avg FROM feedbacks")->fetch_assoc();
$facCount   = $conn->query("SELECT COUNT(DISTINCT faculty_name) as c FROM feedbacks")->fetch_assoc()['c'];
$deptCount  = $conn->query("SELECT COUNT(DISTINCT department) as c FROM feedbacks")->fetch_assoc()['c'];
$collegeCount=$conn->query("SELECT COUNT(DISTINCT college_name) as c FROM feedbacks")->fetch_assoc()['c'];

$facNames=[]; $facOverall=[]; $facTQ=[]; $facSK=[]; $facCS=[];
$fr = $conn->query("SELECT faculty_name, AVG(teaching_quality) as tq, AVG(subject_knowledge) as sk, AVG(communication_skills) as cs, AVG((teaching_quality+subject_knowledge+communication_skills+doubt_clarification+classroom_interaction+punctuality)/6) as ov FROM feedbacks GROUP BY faculty_name ORDER BY ov DESC");
while($r=$fr->fetch_assoc()){ $facNames[]=$r['faculty_name']; $facOverall[]=round($r['ov'],2); $facTQ[]=round($r['tq'],2); $facSK[]=round($r['sk'],2); $facCS[]=round($r['cs'],2); }

$dL=[]; $dD=[];
$dr = $conn->query("SELECT department, COUNT(*) as t FROM feedbacks GROUP BY department ORDER BY t DESC");
while($r=$dr->fetch_assoc()){ $dL[]=$r['department']; $dD[]=$r['t']; }

$subL=[]; $subR=[]; $subC=[];
$sr = $conn->query("SELECT subject_name, COUNT(*) as t, AVG((teaching_quality+subject_knowledge+communication_skills+doubt_clarification+classroom_interaction+punctuality)/6) as avg FROM feedbacks GROUP BY subject_name ORDER BY t DESC LIMIT 10");
while($r=$sr->fetch_assoc()){ $subL[]=$r['subject_name']; $subR[]=round($r['avg'],2); $subC[]=$r['t']; }

$tM=[]; $tC=[];
$tr2 = $conn->query("SELECT DATE_FORMAT(submitted_at,'%b %Y') as m, COUNT(*) as c FROM feedbacks WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY YEAR(submitted_at),MONTH(submitted_at) ORDER BY submitted_at");
while($r=$tr2->fetch_assoc()){ $tM[]=$r['m']; $tC[]=$r['c']; }

// Avg internship duration
$dur = $conn->query("SELECT AVG(internship_duration) as avg_dur, MIN(internship_duration) as min_dur, MAX(internship_duration) as max_dur FROM feedbacks")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports & Analytics – TNEB Admin</title>
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
    <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="feedbacks.php" class="nav-link"><i class="fas fa-list-alt"></i> Feedbacks</a>
    <a href="reports.php"   class="nav-link active"><i class="fas fa-chart-bar"></i> Reports & Analytics</a>
    <hr style="border-color:rgba(255,255,255,0.1);margin:12px 24px;">
    <a href="../index.php"  class="nav-link" target="_blank"><i class="fas fa-external-link-alt"></i> Feedback Form</a>
    <a href="logout.php"    class="nav-link" style="color:rgba(255,100,100,0.85);"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<div class="admin-content">
  <div class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm" id="sidebarToggle" style="border:1px solid var(--border);background:var(--card-bg);"><i class="fas fa-bars"></i></button>
      <div class="page-title">Reports & Analytics</div>
    </div>
    <div class="d-flex gap-2">
      <button class="theme-toggle" id="themeToggle">🌙 Dark</button>
      <a href="feedbacks.php?export=csv"   class="btn btn-sm" style="background:#2e7d32;color:white;border-radius:50px;padding:6px 16px;"><i class="fas fa-file-csv me-1"></i> CSV</a>
      <a href="feedbacks.php?export=excel" class="btn btn-sm" style="background:#1565c0;color:white;border-radius:50px;padding:6px 16px;"><i class="fas fa-file-excel me-1"></i> Excel</a>
    </div>
  </div>

  <div class="admin-main">
    <!-- SUMMARY CARDS -->
    <div class="row g-3 mb-4">
      <div class="col-md-3"><div class="card text-center p-3">
        <div style="font-size:2rem;font-weight:800;color:var(--primary);"><?= $overall['total'] ?></div>
        <div style="color:var(--text-muted);font-size:0.85rem;">Total Feedbacks</div>
      </div></div>
      <div class="col-md-3"><div class="card text-center p-3">
        <div style="font-size:2rem;font-weight:800;color:#2e7d32;"><?= round($overall['avg'],1) ?>/5</div>
        <div style="color:var(--text-muted);font-size:0.85rem;">Overall Avg Rating</div>
      </div></div>
      <div class="col-md-3"><div class="card text-center p-3">
        <div style="font-size:2rem;font-weight:800;color:#ef6c00;"><?= round($dur['avg_dur']) ?> days</div>
        <div style="color:var(--text-muted);font-size:0.85rem;">Avg Internship Duration</div>
      </div></div>
      <div class="col-md-3"><div class="card text-center p-3">
        <div style="font-size:2rem;font-weight:800;color:#7b1fa2;"><?= $collegeCount ?></div>
        <div style="color:var(--text-muted);font-size:0.85rem;">Colleges Participated</div>
      </div></div>
    </div>

    <!-- CHART ROW 1 -->
    <div class="row g-4 mb-4">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header-custom"><i class="fas fa-chart-bar"></i> Mentor Average Ratings</div>
          <div class="card-body p-4"><canvas id="facBarChart" height="100"></canvas></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header-custom"><i class="fas fa-chart-pie"></i> Department Distribution</div>
          <div class="card-body p-4"><canvas id="deptPieChart"></canvas></div>
        </div>
      </div>
    </div>

    <!-- CHART ROW 2 -->
    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header-custom"><i class="fas fa-bolt"></i> Domain-wise Feedback Count</div>
          <div class="card-body p-4"><canvas id="subjectChart" height="140"></canvas></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header-custom"><i class="fas fa-chart-line"></i> Monthly Trend (12 months)</div>
          <div class="card-body p-4"><canvas id="monthlyChart" height="140"></canvas></div>
        </div>
      </div>
    </div>

    <!-- FACULTY DETAIL TABLE -->
    <div class="card mb-4">
      <div class="card-header-custom"><i class="fas fa-table"></i> Mentor Detailed Rating Report</div>
      <div class="table-responsive">
        <table class="table-custom w-100">
          <thead><tr>
            <th>Mentor Name</th><th>Feedbacks</th><th>Teaching</th><th>Knowledge</th>
            <th>Communication</th><th>Doubt Clarity</th><th>Interaction</th><th>Punctuality</th><th>Overall</th>
          </tr></thead>
          <tbody>
          <?php
          $conn2=getDB();
          $fr2=$conn2->query("SELECT faculty_name, COUNT(*) as total, AVG(teaching_quality) as tq, AVG(subject_knowledge) as sk, AVG(communication_skills) as cs, AVG(doubt_clarification) as dc, AVG(classroom_interaction) as ci, AVG(punctuality) as pu, AVG((teaching_quality+subject_knowledge+communication_skills+doubt_clarification+classroom_interaction+punctuality)/6) as ov FROM feedbacks GROUP BY faculty_name ORDER BY ov DESC");
          while($fr=$fr2->fetch_assoc()):
            $ov=round($fr['ov'],1); ?>
          <tr>
            <td style="font-weight:600;"><?= e($fr['faculty_name']) ?></td>
            <td><span style="background:rgba(57,73,171,0.1);color:var(--primary);padding:2px 10px;border-radius:50px;font-size:0.85rem;"><?= $fr['total'] ?></span></td>
            <td><?= round($fr['tq'],1) ?></td><td><?= round($fr['sk'],1) ?></td>
            <td><?= round($fr['cs'],1) ?></td><td><?= round($fr['dc'],1) ?></td>
            <td><?= round($fr['ci'],1) ?></td><td><?= round($fr['pu'],1) ?></td>
            <td><span class="badge-rating rating-<?= round($ov) ?>">★ <?= $ov ?>/5</span></td>
          </tr>
          <?php endwhile; $conn2->close(); ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
const colors=['#1a237e','#3949ab','#ef6c00','#2e7d32','#7b1fa2','#01579b','#c62828','#00695c','#f57f17','#4e342e'];
new Chart(document.getElementById('facBarChart'),{type:'bar',data:{labels:<?= json_encode($facNames?:['No Data']) ?>,datasets:[
  {label:'Overall',data:<?= json_encode($facOverall?:[0]) ?>,backgroundColor:'#3949ab',borderRadius:6},
  {label:'Teaching',data:<?= json_encode($facTQ?:[0]) ?>,backgroundColor:'#ef6c00',borderRadius:6},
  {label:'Knowledge',data:<?= json_encode($facSK?:[0]) ?>,backgroundColor:'#2e7d32',borderRadius:6}
]},options:{responsive:true,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true,max:5}}}});

new Chart(document.getElementById('deptPieChart'),{type:'doughnut',data:{labels:<?= json_encode($dL?:['No Data']) ?>,datasets:[{data:<?= json_encode($dD?:[1]) ?>,backgroundColor:colors,borderWidth:2}]},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{size:10}}}}}});

new Chart(document.getElementById('subjectChart'),{type:'bar',data:{labels:<?= json_encode($subL?:['No Data']) ?>,datasets:[{label:'Feedbacks',data:<?= json_encode($subC?:[0]) ?>,backgroundColor:colors,borderRadius:6}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true}}}});

new Chart(document.getElementById('monthlyChart'),{type:'line',data:{labels:<?= json_encode($tM?:['No Data']) ?>,datasets:[{label:'Feedbacks',data:<?= json_encode($tC?:[0]) ?>,borderColor:'#3949ab',backgroundColor:'rgba(57,73,171,0.1)',tension:0.4,fill:true,pointRadius:4,pointBackgroundColor:'#1a237e'}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
</script>
</body>
</html>
