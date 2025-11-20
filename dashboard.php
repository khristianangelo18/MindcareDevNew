<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}
include 'supabase.php';

$user = $_SESSION['user'];
$user_id = $user['id'];
$user_name = $user['fullname'];
$assessmentsRecord = supabaseSelect('assessments', ['user_id' => $user_id], '*', 'created_at.desc', null, true);

// Get current date/time
date_default_timezone_set('Asia/Manila');
$current_date = date('l, F j, Y');
$current_time = date('g:i A');
$greeting = '';
$hour = (int)date('G');

if ($hour >= 5 && $hour < 12) {
  $greeting = 'Good Morning';
} elseif ($hour >= 12 && $hour < 18) {
  $greeting = 'Good Afternoon';
} else {
  $greeting = 'Good Evening';
}


// Get latest assessment
$assessments = supabaseSelect(
  'assessments',
  ['user_id' => $user_id],
  '*',
  'created_at.desc',
  1,
  true
);
$assessment = !empty($assessments) ? $assessments[0] : null;

// Get upcoming appointments (Confirmed or Pending, future dates only)
// FIX: Get upcoming appointments with fallback method
// Method 1: Try with foreign key
$appointments = supabaseSelect(
  'appointments',
  [
    'user_id' => $user_id,
    'appointment_date' => ['operator' => 'gte', 'value' => date('Y-m-d')]
  ],
  'id,specialist_id,appointment_date,appointment_time,status,created_at,users!appointments_specialist_id_fkey(fullname,email)',
  'appointment_date.asc,appointment_time.asc',
  null,
  true  // Use SERVICE_KEY to bypass RLS
);

// Method 2: Fallback if foreign key fails
if (empty($appointments) || !isset($appointments[0]['users'])) {
  // Get appointments without foreign key
  $appointments = supabaseSelect(
    'appointments',
    [
      'user_id' => $user_id,
      'appointment_date' => ['operator' => 'gte', 'value' => date('Y-m-d')]
    ],
    'id,specialist_id,appointment_date,appointment_time,status,created_at',
    'appointment_date.asc,appointment_time.asc',
    null,
    true
  );
  
  // Fetch specialists if we have appointments
  if (!empty($appointments)) {
    $specialistIds = array_unique(array_column($appointments, 'specialist_id'));
    $specialists = [];
    
    if (!empty($specialistIds)) {
      $allSpecialists = supabaseSelect(
        'users',
        ['id' => ['operator' => 'in', 'value' => '(' . implode(',', $specialistIds) . ')']],
        'id,fullname,email',
        null,
        null,
        true
      );
      
      foreach ($allSpecialists as $spec) {
        $specialists[$spec['id']] = $spec;
      }
      
      foreach ($appointments as &$apt) {
        $apt['users'] = $specialists[$apt['specialist_id']] ?? [
          'fullname' => 'Unknown Specialist',
          'email' => 'N/A'
        ];
      }
      unset($apt);
    }
  }
}
// Filter appointments by status
$upcomingAppointments = array_filter($appointments, function($apt) {
  return in_array($apt['status'], ['Confirmed', 'Pending']);
});

// Get unread notifications
$notifications = supabaseSelect(
  'notifications',
  [
    'user_id' => $user_id,
    'is_read' => false
  ],
  '*',
  'created_at.desc',
  5
);

// Assessment recommendations
$recommendations = [
  'Try guided breathing exercises',
  'Start a daily journal',
  'Talk to a specialist if symptoms persist',
  'Listen to calming music or nature sounds',
  'Take short mindful walks outdoors',
  'Read something uplifting or inspiring',
  'Maintain a consistent sleep schedule',
  'Limit caffeine and sugar intake',
  'Connect with a trusted friend or support group',
  'Practice positive self-talk and affirmations'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="mobile.css" />
  <style>
    :root {
      --primary-teal: #5ad0be;
      --primary-teal-dark: #1aa592;
      --text-dark: #2b2f38;
      --text-muted: #7a828e;
      --bg-light: #f8f9fa;
      --sidebar-bg: #f5f6f7;
      --card-bg: #ffffff;
      --border-color: #e9edf5;
    }

    body {
      background-color: var(--bg-light);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-dark);
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Dark Mode Variables */
    body.dark-mode {
      --bg-light: #1a1a1a;
      --sidebar-bg: #2a2a2a;
      --card-bg: #2d2d2d;
      --text-dark: #f1f1f1;
      --text-muted: #b0b0b0;
      --border-color: #3a3a3a;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: 250px;
      height: 100vh;
      background: var(--sidebar-bg);
      border-right: 1px solid var(--border-color);
      padding: 1.5rem;
      z-index: 1000;
      display: flex;
      flex-direction: column;
      transition: background-color 0.3s ease;
    }

    .sidebar .logo-wrapper {
      text-align: center;
      margin-bottom: 2rem;
    }

    .sidebar .logo-img {
      max-width: 125px;
    }

    .sidebar .nav-link {
      color: var(--text-dark);
      padding: 0.65rem 1rem;
      border-radius: 8px;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 500;
      font-size: 0.625rem;
      text-decoration: none;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
    }

    .sidebar .nav-link:hover {
      background-color: rgba(90, 208, 190, 0.1);
      color: #5ad0be;
    }

    .sidebar .nav-link.active {
      background-color: #5ad0be;
      color: #ffffff;
    }

    /* Dark Mode Toggle Button */
    .theme-toggle {
      margin-top: auto;
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
      margin-left: 250px;
      padding: 2rem;
      min-height: 100vh;
      transition: background-color 0.3s ease;
    }

    /* Header Section */
    .dashboard-header {
      margin-bottom: 2rem;
      margin-top: 0;
    }

    .dashboard-header h1 {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.25rem;
      transition: color 0.3s ease;
    }

    .dashboard-header h1 .user-name {
      color: var(--primary-teal);
    }

    .dashboard-header .date-time {
      color: var(--text-muted);
      font-size: 0.95rem;
      transition: color 0.3s ease;
    }

    /* Cards */
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

    .card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    body.dark-mode .card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .card-header-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border-color);
    }

    .card-title {
      font-size: 0.85rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.25rem;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    .card-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
      transition: color 0.3s ease;
    }

    .card-link {
      color: var(--primary-teal);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
    }

    .card-link:hover {
      color: var(--primary-teal-dark);
    }

    /* Tab Navigation */
    .tab-navigation {
      display: inline-flex;
      background-color: var(--sidebar-bg);
      border-radius: 10px;
      padding: 4px;
      gap: 0;
      border: 1px solid var(--border-color);
      transition: all 0.3s ease;
    }

    .tab-btn {
      padding: 0.5rem 1.5rem;
      border: none;
      background-color: transparent;
      color: var(--primary-teal);
      font-weight: 500;
      font-size: 0.875rem;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
    }

    .tab-btn.active {
      background-color: var(--primary-teal);
      color: #ffffff;
      box-shadow: 0 1px 3px rgba(90, 208, 190, 0.3);
    }

    .tab-btn:hover:not(.active) {
      color: var(--primary-teal-dark);
    }

    /* Appointment List */
    .appointment-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      background-color: var(--bg-light);
      border-radius: 8px;
      margin-bottom: 0.75rem;
      transition: all 0.3s ease;
    }

    .appointment-item:hover {
      background-color: rgba(90, 208, 190, 0.05);
    }

    .appointment-info h6 {
      margin: 0 0 0.25rem 0;
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text-dark);
      transition: color 0.3s ease;
    }

    .appointment-meta {
      display: flex;
      gap: 1rem;
      font-size: 0.85rem;
      color: var(--text-muted);
      transition: color 0.3s ease;
    }

    .appointment-actions {
      display: flex;
      gap: 0.5rem;
    }

    .btn-sm {
      padding: 0.375rem 0.75rem;
      font-size: 0.875rem;
      border-radius: 6px;
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .status-confirmed {
      background-color: #d4edda;
      color: #155724;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-cancelled {
      background-color: #f8d7da;
      color: #721c24;
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
   .table-responsive {
      overflow-x: auto;
    }

    /* Dark mode status badges */
    body.dark-mode .status-confirmed {
      background-color: rgba(34, 139, 34, 0.2);
      color: #90ee90;
    }

    body.dark-mode .status-pending {
      background-color: rgba(255, 215, 0, 0.2);
      color: #ffd700;
    }

    body.dark-mode .status-cancelled {
      background-color: rgba(220, 53, 69, 0.2);
      color: #ff6b6b;
    }

    /* Recommendations List */
    .recommendation-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .recommendation-list li {
      padding: 0.75rem;
      background-color: var(--bg-light);
      border-radius: 6px;
      margin-bottom: 0.5rem;
      color: var(--text-dark);
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }

    /* Ensure all headings and paragraphs adapt to dark mode */
    h1, h2, h3, h4, h5, h6, p {
      color: var(--text-dark);
      transition: color 0.3s ease;
    }

    /* Text muted elements */
    .text-muted {
      color: var(--text-muted) !important;
    }

    /* Center aligned text */
    .text-center {
      text-align: center;
    }

    /* Filter Button */
    .filter-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border: 1px solid var(--border-color);
      background-color: transparent;
      border-radius: 8px;
      color: var(--text-dark);
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .filter-btn:hover {
      background-color: rgba(90, 208, 190, 0.1);
      border-color: var(--primary-teal);
    }

    /* Buttons */
    .btn-primary {
      background-color: var(--primary-teal);
      border-color: var(--primary-teal);
    }

    .btn-primary:hover {
      background-color: var(--primary-teal-dark);
      border-color: var(--primary-teal-dark);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.show {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="logo-wrapper">
      <img src="images/Mindcare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <nav class="nav flex-column" style="flex: 1;">
      <a class="nav-link active" href="dashboard.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        DASHBOARD
      </a>
      <a class="nav-link" href="resources.php">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
          viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>
      </svg>
        RESOURCES
      </a>
      <a class="nav-link" href="assessment.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        ASSESSMENT
      </a>
      <a class="nav-link" href="book_appointment.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        BOOK APPOINTMENT
      </a>
      <a class="nav-link" href="appointments.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
        MY APPOINTMENTS
      </a>
      <a class="nav-link" href="profile.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        PROFILE
      </a>
      <a class="nav-link" href="faq.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        FAQS
      </a>
    </nav>

    <!-- Dark Mode Toggle -->
    <div class="theme-toggle">
  <button id="themeToggle">
    <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <!-- Sun icon (default for light mode) -->
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
    <span id="themeLabel">Light Mode</span>
  </button>
</div>

    <!-- Logout Button at Bottom -->
    <a href="logout.php" class="nav-link" style="margin-top: 1rem; color: #ef5350; border-top: 1px solid var(--border-color); padding-top: 1rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      LOGOUT
    </a>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Header -->
    <div class="dashboard-header">
      <h1><?= $greeting ?>, <span class="user-name"><?= htmlspecialchars($user_name) ?></span>!</h1>
      <div class="date-time">Today is <?= $current_date ?>, <?= $current_time ?></div>
    </div>

    <!-- Summary Cards Row -->
    <div class="row">
      <!-- Upcoming Appointments Card -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header-section">
            <div>
              <div class="card-title">Upcoming Appointments</div>
              <div class="card-value"><?= count($upcomingAppointments) ?> Upcoming Appointments</div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#5ad0be" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          </div>
          <a href="book_appointment.php" class="card-link">
            Book an Appointment
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </a>
        </div>
      </div>
      

      <!-- Quick Assessment Card -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header-section">
            <div>
              <div class="card-title">Quick Assessment Survey</div>
              <div class="card-value"><?= $assessment ? 'Last: ' . htmlspecialchars($assessment['summary']) : 'No Assessment Taken Yet' ?></div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#5ad0be" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
          </div>
          <a href="recommendations.php" class="card-link">
            View Recommendations
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </a>
        </div>
      </div>
    </div>

    <!-- Tab Navigation and Filter Row -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="tab-navigation">
        <button class="tab-btn active" id="appointmentsTab">Appointments</button>
        <button class="tab-btn" id="assessmentTab">Assessment History</button>
      </div>

    </div>

    <!-- Content Sections -->
     <div id="assessmentContent" style="display: none;">
      <div class="card">
        <h5 class="mb-3" style="color: var(--text-dark); transition: color 0.3s ease;">Your Assessment Records </h5>
         <?php if (empty($assessmentsRecord)): ?>
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
              <?php foreach ($assessmentsRecord as $assessmentsRecord): ?>
                <?php
                  $score = $assessmentsRecord['score'];
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
                  $date = date('M j, Y', strtotime($assessmentsRecord['created_at']));
                  $time = date('g:i A', strtotime($assessmentsRecord['created_at']));
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
                  <td><?= htmlspecialchars($assessmentsRecord['summary'] ?? 'No summary') ?></td>
                  <td>
                    <a href="generate_assessment_pdf.php?id=<?= $assessmentsRecord['id'] ?>" class="btn-print" target="_blank">
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

    <div id="appointmentsContent">
      <div class="card">
        <h5 class="mb-3" style="color: var(--text-dark); transition: color 0.3s ease;">Upcoming Appointments</h5>
        <?php if (count($upcomingAppointments) > 0): ?>
          <?php foreach ($upcomingAppointments as $apt): ?>
            <div class="appointment-item">
              <div class="appointment-info">
                <h6><?= htmlspecialchars($apt['users']['fullname'] ?? 'Specialist') ?></h6>
                <div class="appointment-meta">
                  <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <?= date('g:i A', strtotime($apt['appointment_time'])) ?>
                  </span>
                  <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <?= date('F j, Y', strtotime($apt['appointment_date'])) ?>
                  </span>
                </div>
              </div>
              <div class="appointment-actions">
                <button class="btn btn-sm btn-outline-secondary" onclick="rescheduleAppointment(<?= $apt['id'] ?>)">Reschedule</button>
                <button class="btn btn-sm btn-outline-primary" onclick="viewAppointmentDetails(<?= $apt['id'] ?>)">View Details</button>
                <span class="status-badge status-<?= strtolower($apt['status']) ?>"><?= $apt['status'] ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color: var(--text-muted); transition: color 0.3s ease;" class="text-center">No upcoming appointments</p>
        <?php endif; ?>
      </div>
    </div>
    

  <!-- Scripts -->
  <script src="mobile.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Tab switching
    const appointmentsTab = document.getElementById('appointmentsTab');
    const assessmentTab = document.getElementById('assessmentTab');
    const appointmentsContent = document.getElementById('appointmentsContent');
    const assessmentContent = document.getElementById('assessmentContent');


    appointmentsTab.addEventListener('click', () => {
      appointmentsTab.classList.add('active');
      assessmentTab.classList.remove('active');
      appointmentsContent.style.display = 'block';;
      assessmentContent.style.display = 'none';
    });

    assessmentTab.addEventListener('click', () => {
      assessmentTab.classList.add('active');
      appointmentsTab.classList.remove('active');
      appointmentsContent.style.display = 'none';
      assessmentContent.style.display = 'block';
    })

    // Dark mode toggle
    const toggleBtn = document.getElementById('themeToggle');
const icon = document.getElementById('themeIcon');
const label = document.getElementById('themeLabel');

// SVG icon strings
const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';

const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';

// Check for saved theme preference
const prefersDark = localStorage.getItem('dark-mode') === 'true';
if (prefersDark) {
  document.body.classList.add('dark-mode');
  icon.innerHTML = moonIcon;
  label.textContent = 'Dark Mode';
}

// Toggle theme
toggleBtn.addEventListener('click', () => {
  document.body.classList.toggle('dark-mode');
  const isDark = document.body.classList.contains('dark-mode');
  localStorage.setItem('dark-mode', isDark);
  
  // Animate icon
  icon.style.transform = 'rotate(360deg)';
  setTimeout(() => icon.style.transform = 'rotate(0deg)', 500);
  
  // Update icon and label
  icon.innerHTML = isDark ? moonIcon : sunIcon;
  label.textContent = isDark ? 'Dark Mode' : 'Light Mode';
});

// Smooth transition for icon
icon.style.transition = 'transform 0.5s ease';

    function rescheduleAppointment(appointmentId) {
    window.location.href = `reschedule_appointment.php?appointment_id=${appointmentId}`;
  }

  function viewAppointmentDetails(appointmentId) {
    fetch(`get_appointment_details.php?id=${appointmentId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Populate modal fields
          document.getElementById('modalAppointmentId').textContent = data.appointment.id;
          document.getElementById('modalSpecialistName').textContent = data.appointment.specialist_name;
          document.getElementById('modalSpecialistRole').textContent = data.appointment.specialist_role;
          document.getElementById('modalAppointmentDate').textContent = data.appointment.date;
          document.getElementById('modalAppointmentTime').textContent = data.appointment.time;
          document.getElementById('modalAppointmentStatus').textContent = data.appointment.status;
          document.getElementById('modalAppointmentStatus').className = `status-badge status-${data.appointment.status.toLowerCase()}`;
          document.getElementById('modalCreatedAt').textContent = data.appointment.created_at;
          
          // Handle specialist email
          if (data.appointment.specialist_email) {
            document.getElementById('modalSpecialistEmail').textContent = data.appointment.specialist_email;
            document.getElementById('modalSpecialistEmailRow').style.display = 'flex';
          } else {
            document.getElementById('modalSpecialistEmailRow').style.display = 'none';
          }
          
          // Show modal
          const modal = new bootstrap.Modal(document.getElementById('appointmentDetailsModal'));
          modal.show();
        } else {
          alert('Error loading appointment details: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to load appointment details. Please try again.');
      });
  }

  function rescheduleFromModal() {
    const appointmentId = document.getElementById('modalAppointmentId').textContent;
    const modal = bootstrap.Modal.getInstance(document.getElementById('appointmentDetailsModal'));
    modal.hide();
    rescheduleAppointment(appointmentId);
  }
  </script>
     <?php include 'beyond_widget.php'; ?>

     <!-- Appointment Details Modal -->
<div class="modal fade" id="appointmentDetailsModal" tabindex="-1" aria-labelledby="appointmentDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="appointmentDetailsModalLabel">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          Appointment Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Appointment ID -->
        <div class="detail-row">
          <div class="detail-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            Appointment ID
          </div>
          <div class="detail-value" id="modalAppointmentId">-</div>
        </div>

        <!-- Specialist Information -->
        <div class="detail-section">
          <h6 class="detail-section-title">Specialist Information</h6>
          
          <div class="detail-row">
            <div class="detail-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              Name
            </div>
            <div class="detail-value" id="modalSpecialistName">-</div>
          </div>

          <div class="detail-row">
            <div class="detail-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <polyline points="17 11 19 13 23 9"></polyline>
              </svg>
              Role
            </div>
            <div class="detail-value" id="modalSpecialistRole">-</div>
          </div>

          <div class="detail-row" id="modalSpecialistEmailRow" style="display: none;">
            <div class="detail-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
              Email
            </div>
            <div class="detail-value" id="modalSpecialistEmail">-</div>
          </div>
        </div>

        <!-- Appointment Information -->
        <div class="detail-section">
          <h6 class="detail-section-title">Appointment Information</h6>
          
          <div class="detail-row">
            <div class="detail-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
              Date
            </div>
            <div class="detail-value" id="modalAppointmentDate">-</div>
          </div>

          <div class="detail-row">
            <div class="detail-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              Time
            </div>
            <div class="detail-value" id="modalAppointmentTime">-</div>
          </div>

          <div class="detail-row">
            <div class="detail-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
              </svg>
              Status
            </div>
            <span class="status-badge" id="modalAppointmentStatus">-</span>
          </div>

          

          <div class="detail-row">
            <div class="detail-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              Booked At
            </div>
            <div class="detail-value" id="modalCreatedAt">-</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="rescheduleFromModal()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
            <polyline points="23 4 23 10 17 10"></polyline>
            <polyline points="1 20 1 14 7 14"></polyline>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
          </svg>
          Reschedule
        </button>
      </div>
    </div>
  </div>
</div>

<style>
/* Modal Styling */
.modal-content {
  border-radius: 12px;
  border: none;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.modal-header {
  background-color: var(--primary-teal);
  color: white;
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
  padding: 1.25rem 1.5rem;
}

.modal-header .btn-close {
  filter: brightness(0) invert(1);
  opacity: 0.8;
}

.modal-header .btn-close:hover {
  opacity: 1;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
}

.modal-body {
  padding: 1.5rem;
}

.detail-section {
  margin-bottom: 1.5rem;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.detail-section-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--primary-teal);
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid var(--border-color);
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--border-color);
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 0 0 40%;
}

.detail-label svg {
  color: var(--primary-teal);
  flex-shrink: 0;
}

.detail-value {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--text-dark);
  text-align: right;
  flex: 1;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-color);
}

/* Dark Mode Support */
body.dark-mode .modal-content {
  background-color: var(--card-bg);
  color: var(--text-dark);
}

body.dark-mode .modal-header {
  background-color: var(--primary-teal);
}

body.dark-mode .detail-section-title {
  color: var(--primary-teal);
  border-bottom-color: var(--border-color);
}

body.dark-mode .detail-row {
  border-bottom-color: var(--border-color);
}

body.dark-mode .modal-footer {
  border-top-color: var(--border-color);
}

/* Responsive */
@media (max-width: 768px) {
  .detail-row {
    flex-direction: column;
    gap: 0.5rem;
  }

  .detail-label {
    flex: 1;
  }

  .detail-value {
    text-align: left;
  }
}
</style>

<script>
// Function to reschedule from modal
function rescheduleFromModal() {
  const appointmentId = document.getElementById('modalAppointmentId').textContent;
  // Close modal
  const modal = bootstrap.Modal.getInstance(document.getElementById('appointmentDetailsModal'));
  modal.hide();
  // Redirect to reschedule page
  rescheduleAppointment(appointmentId);
}
</script>

</body>
</html>