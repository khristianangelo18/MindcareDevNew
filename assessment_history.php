<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Patient') {
  header("Location: login.php");
  exit;
}
include 'supabase.php';

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['fullname'] ?? 'User';

// Fetch all assessments for this user
$assessments = supabaseSelect('assessments', ['user_id' => $user_id], '*', 'created_at.desc');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assessment History - MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-teal: #5ad0be;
      --primary-teal-dark: #1aa592;
      --bg-light: #f8f9fa;
      --card-bg: #ffffff;
      --text-dark: #333333;
      --text-muted: #6c757d;
      --border-color: #e0e0e0;
      --sidebar-width: 250px;
    }

    body.dark-mode {
      --bg-light: #1a1a1a;
      --card-bg: #2d2d2d;
      --text-dark: #e0e0e0;
      --text-muted: #a0a0a0;
      --border-color: #404040;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-light);
      color: var(--text-dark);
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      width: var(--sidebar-width);
      background-color: var(--card-bg);
      border-right: 1px solid var(--border-color);
      padding: 1.5rem 0;
      display: flex;
      flex-direction: column;
      z-index: 1000;
      transition: all 0.3s ease;
    }

    .sidebar .logo {
      padding: 0 1.5rem;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .sidebar .logo-img {
      width: 100px;
      height: auto;
    }

    .sidebar nav {
      flex: 1;
      overflow-y: auto;
    }

    .sidebar .nav-link {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1.5rem;
      color: var(--text-dark);
      text-decoration: none;
      font-size: 0.625rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
    }

    .sidebar .nav-link:hover {
      background-color: rgba(90, 208, 190, 0.1);
      color: var(--primary-teal);
    }

    .sidebar .nav-link.active {
      background-color: var(--primary-teal);
      color: #ffffff;
    }

    .theme-toggle {
      margin-top: auto;
      padding: 0 1.5rem;
      padding-top: 1rem;
      border-top: 1px solid var(--border-color);
    }

    .theme-toggle button {
      width: 100%;
      padding: 0.65rem 1rem;
      background: transparent;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      color: var(--text-dark);
      font-size: 0.625rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
    }

    .theme-toggle button:hover {
      background-color: rgba(90, 208, 190, 0.1);
      border-color: var(--primary-teal);
      color: var(--primary-teal);
    }

    /* Main Content */
    .main-content {
      margin-left: var(--sidebar-width);
      padding: 2rem;
      min-height: 100vh;
    }

    .dashboard-header {
      margin-bottom: 2rem;
    }

    .dashboard-header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .dashboard-header h1 .user-name {
      color: var(--primary-teal);
    }

    .date-time {
      color: var(--text-muted);
      font-size: 0.95rem;
    }

    /* Card */
    .card {
      background-color: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    body.dark-mode .card {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .card h5 {
      color: var(--text-dark);
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    /* Assessment Table */
    .table-responsive {
      overflow-x: auto;
    }

    .assessment-table {
      width: 100%;
      border-collapse: collapse;
    }

    .assessment-table thead {
      background-color: var(--bg-light);
    }

    .assessment-table th {
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      color: var(--text-dark);
      font-size: 0.875rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--border-color);
    }

    .assessment-table td {
      padding: 1rem;
      border-bottom: 1px solid var(--border-color);
      color: var(--text-dark);
      font-size: 0.9rem;
    }

    .assessment-table tbody tr {
      transition: background-color 0.2s ease;
    }

    .assessment-table tbody tr:hover {
      background-color: rgba(90, 208, 190, 0.05);
    }

    /* Score Badge */
    .score-badge {
      display: inline-block;
      padding: 0.35rem 0.75rem;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.85rem;
    }

    .score-badge.mild {
      background-color: #d4edda;
      color: #155724;
    }

    .score-badge.moderate {
      background-color: #fff3cd;
      color: #856404;
    }

    .score-badge.severe {
      background-color: #f8d7da;
      color: #721c24;
    }

    body.dark-mode .score-badge.mild {
      background-color: #1e4620;
      color: #a8e6a1;
    }

    body.dark-mode .score-badge.moderate {
      background-color: #4a3c0f;
      color: #ffe69c;
    }

    body.dark-mode .score-badge.severe {
      background-color: #4a1719;
      color: #f5c2c7;
    }

    /* Action Buttons */
    .btn-print {
      padding: 0.5rem 1rem;
      background-color: var(--primary-teal);
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
    }

    .btn-print:hover {
      background-color: var(--primary-teal-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(90, 208, 190, 0.3);
      color: white;
    }

    .empty-state {
      text-align: center;
      padding: 3rem 1.5rem;
      color: var(--text-muted);
    }

    .empty-state svg {
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .empty-state h6 {
      color: var(--text-dark);
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
    }

    .empty-state p {
      margin-bottom: 1.5rem;
    }

    .empty-state .btn {
      background-color: var(--primary-teal);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s ease;
    }

    .empty-state .btn:hover {
      background-color: var(--primary-teal-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(90, 208, 190, 0.3);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.show {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
        padding: 1rem;
      }

      .assessment-table {
        font-size: 0.8rem;
      }

      .assessment-table th,
      .assessment-table td {
        padding: 0.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="logo">
      <img src="images/logo.png" alt="MindCare Logo" class="logo-img">
    </div>

    <nav>
      <a class="nav-link" href="dashboard.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        DASHBOARD
      </a>
      <a class="nav-link active" href="assessment_history.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        ASSESSMENT HISTORY
      </a>
      <a class="nav-link" href="recommendations.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
        RECOMMENDATIONS
      </a>
      <a class="nav-link" href="book_appointment.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        BOOK APPOINTMENT
      </a>
      <a class="nav-link" href="appointments.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        MY APPOINTMENTS
      </a>
      <a class="nav-link" href="profile.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        PROFILE
      </a>
      <a class="nav-link" href="faq.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        FAQ
      </a>
    </nav>

    <div class="theme-toggle">
      <button id="themeToggle" onclick="toggleTheme()">
        <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="5"></circle>
          <line x1="12" y1="1" x2="12" y2="3"></line>
          <line x1="12" y1="21" x2="12" y2="23"></line>
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
          <line x1="1" y1="12" x2="3" y2="12"></line>
          <line x1="21" y1="12" x2="23" y2="12"></line>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
          <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
        <span id="themeLabel">LIGHT MODE</span>
      </button>
    </div>

    <a href="logout.php" class="nav-link" style="margin-top: 1rem; color: #ef5350; border-top: 1px solid var(--border-color); padding-top: 1rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      LOGOUT
    </a>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="dashboard-header">
      <h1>Assessment <span class="user-name">History</span></h1>
      <p class="date-time">View and download your mental health assessment records</p>
    </div>

    <div class="card">
      <h5>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        Your Assessment Records
      </h5>

      <?php if (empty($assessments)): ?>
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
          <h6>No Assessments Yet</h6>
          <p>You haven't taken any mental health assessments. Start by taking your first assessment to track your mental wellness.</p>
          <a href="pre_assessment.php" class="btn">Take Assessment</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="assessment-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Score</th>
                <th>Status</th>
                <th>Summary</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assessments as $assessment): ?>
                <?php
                  $score = $assessment['score'];
                  if ($score <= 2) {
                    $badgeClass = 'mild';
                    $statusText = 'Mild';
                  } elseif ($score <= 4) {
                    $badgeClass = 'moderate';
                    $statusText = 'Moderate';
                  } else {
                    $badgeClass = 'severe';
                    $statusText = 'Severe';
                  }
                  $date = date('M j, Y', strtotime($assessment['created_at']));
                  $time = date('g:i A', strtotime($assessment['created_at']));
                ?>
                <tr>
                  <td>
                    <div style="font-weight: 600;"><?= $date ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= $time ?></div>
                  </td>
                  <td>
                    <span style="font-weight: 600; font-size: 1rem;"><?= $score ?>/6</span>
                  </td>
                  <td>
                    <span class="score-badge <?= $badgeClass ?>"><?= $statusText ?></span>
                  </td>
                  <td><?= htmlspecialchars($assessment['summary'] ?? 'No summary') ?></td>
                  <td>
                    <a href="generate_assessment_pdf.php?id=<?= $assessment['id'] ?>" class="btn-print" target="_blank">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                      Print PDF
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Dark Mode Toggle
    function toggleTheme() {
      const body = document.body;
      const themeLabel = document.getElementById('themeLabel');
      const themeIcon = document.getElementById('themeIcon');
      
      body.classList.toggle('dark-mode');
      
      if (body.classList.contains('dark-mode')) {
        themeLabel.textContent = 'DARK MODE';
        localStorage.setItem('theme', 'dark');
        themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
      } else {
        themeLabel.textContent = 'LIGHT MODE';
        localStorage.setItem('theme', 'light');
        themeIcon.innerHTML = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
      }
    }

    // Load saved theme
    window.addEventListener('DOMContentLoaded', () => {
      const savedTheme = localStorage.getItem('theme');
      const themeLabel = document.getElementById('themeLabel');
      const themeIcon = document.getElementById('themeIcon');
      
      if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        themeLabel.textContent = 'DARK MODE';
        themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
      }
    });
  </script>
</body>
</html>