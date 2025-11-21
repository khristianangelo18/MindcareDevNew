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
  // We want to show ALL appointments from today onward for filtering, not just Confirmed/Pending
  // The original prompt shows 'Upcoming Appointments' count, but the list should be filterable.
  // We'll keep the list showing only Confirmed/Pending as per the original PHP code logic for the list variable name.
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
        /* FIX: Ensure fixed width and alignment */
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 0.5rem;
      flex-shrink: 0;
      width: 320px; /* Adjust based on the max width needed for buttons + status badge */
    }
    
    .appointment-actions > * {
        min-width: 80px; /* Minimum width for alignment */
        text-align: center;
    }

    .btn-sm {
      padding: 0.375rem 0.75rem;
      font-size: 0.875rem;
      border-radius: 6px;
    }
    
    .btn-outline-secondary, .btn-outline-primary {
        min-width: 85px; /* Ensure buttons have consistent width */
    }


    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      min-width: 100px; /* FIXED WIDTH: Must be large enough for "Confirmed" and "Cancelled" */
      text-align: center;
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
    
    /* Assessment table column width FIXES: 
       We must define explicit widths for columns containing fixed elements (Status and Action) */
    .assessment-table th:nth-child(3) { /* Status column */
        width: 120px; 
    }
    .assessment-table th:nth-child(5) { /* Action column (Print PDF) */
        width: 170px; 
        text-align: center;
    }

    .assessment-table td {
      padding: 1rem;
      border-bottom: 1px solid var(--border-color);
      color: var(--text-dark);
      font-size: 0.9rem;
    }
    
    .assessment-table td:last-child {
        text-align: center; /* Center the action button */
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
      min-width: 80px; /* Ensure status badge is readable */
      text-align: center;
    }
    /* Enforce minimum width for score badges in assessment table */
    .assessment-table .score-badge {
        min-width: 90px; /* Adjusted to fit all statuses */
        text-align: center;
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

    /* NEW STYLES for Search and Filter containers */
    .filter-wrapper {
        display: flex;
        justify-content: flex-end; 
        gap: 1rem;
        margin-bottom: 1rem;
        align-items: center;
        flex-wrap: wrap; 
    }
    
    /* Assessment filter container uses the same class names as Appointments now for consistency */
    .assessment-header-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        width: 100%;
    }
    
    .filter-group { 
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1rem;
    }


    .search-wrapper {
        flex-grow: 0;
        max-width: 300px; 
    }

    .search-input {
        width: 100%;
        padding: 0.625rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--card-bg);
        color: var(--text-dark);
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }

    .status-select {
        padding: 0.625rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--card-bg);
        color: var(--text-dark);
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 120px;
    }

    .search-input:focus, .status-select:focus {
        outline: none;
        border-color: var(--primary-teal);
        box-shadow: 0 0 0 3px rgba(90, 208, 190, 0.1);
    }
    /* End NEW STYLES */


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
      
      .appointment-item {
        flex-direction: column;
        align-items: flex-start;
      }
      .appointment-actions {
        margin-top: 1rem;
        flex-wrap: wrap;
        width: 100%; /* Take full width on mobile for better wrapping */
        justify-content: space-between;
      }
      
      .appointment-actions > * {
          flex-grow: 1;
      }
      
      /* Mobile adjustment for all filters */
      .filter-wrapper,
      .filter-group { 
          flex-direction: column;
          align-items: flex-start;
          gap: 0.5rem;
      }
      
      .filter-wrapper .search-wrapper,
      .filter-wrapper .status-select,
      .filter-group .search-wrapper,
      .filter-group .status-select {
          max-width: 100%;
          width: 100%;
      }
    }
  </style>
</head>
<body>
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

    <div class="theme-toggle">
  <button id="themeToggle">
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
    <span id="themeLabel">Light Mode</span>
  </button>
</div>

    <a href="logout.php" class="nav-link" style="margin-top: 1rem; color: #ef5350; border-top: 1px solid var(--border-color); padding-top: 1rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      LOGOUT
    </a>
  </div>

  <div class="main-content">
    <div class="dashboard-header">
      <h1><?= $greeting ?>, <span class="user-name"><?= htmlspecialchars($user_name) ?></span>!</h1>
      <div class="date-time">Today is <?= $current_date ?>, <?= $current_time ?></div>
    </div>

    <div class="row">
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
      

      <div class="col-md-6">
        <div class="card">
          <div class="card-header-section">
            <div>
              <div class="card-title">Quick Assessment Survey</div>
              <div class="card-value"><?= $assessment ? 'Latest Assessment: ' . htmlspecialchars($assessment['summary']) : 'No Assessment Taken Yet' ?></div>
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

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="tab-navigation">
        <button class="tab-btn active" id="appointmentsTab">Appointments</button>
        <button class="tab-btn" id="assessmentTab">Assessment History</button>
      </div>

    </div>

    <div id="assessmentContent" style="display: none;">
      <div class="card">
        
        <div class="assessment-header-controls">
            <h5 style="color: var(--text-dark); transition: color 0.3s ease; margin: 0;">Your Assessment Records</h5>
            
            <div class="filter-group">
                 <div class="search-wrapper">
                    <input 
                      type="text" 
                      id="assessmentSearchInput" 
                      class="search-input" 
                      placeholder="Search date or summary..." 
                      onkeyup="filterAssessments()"
                    />
                  </div>
                 <select id="assessmentStatusFilter" class="status-select" onchange="filterAssessments()" style="min-width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="mild">Mild</option>
                    <option value="moderate">Moderate</option>
                    <option value="severe">Severe</option>
                </select>
            </div>
        </div>


         <?php if (empty($assessmentsRecord)): ?>
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
          <h6>No Assessments Yet</h6>
          <p>You haven't taken any mental health assessments. Start by taking your first assessment to track your mental wellness.</p>
          <a href="pre_assessment.php" class="btn">Take Assessment</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="assessment-table" id="assessmentTable">
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
              <?php foreach ($assessmentsRecord as $record): ?>
                <?php
                  $score = $record['score'];
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
                  $date = date('M j, Y', strtotime($record['created_at']));
                  $time = date('g:i A', strtotime($record['created_at']));
                ?>
                <tr data-status="<?= $badgeClass ?>" data-date-str="<?= strtolower($date) ?>" data-summary="<?= strtolower(htmlspecialchars($record['summary'] ?? '')) ?>">
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
                  <td><?= htmlspecialchars($record['summary'] ?? 'No summary') ?></td>
                  <td>
                    <a href="generate_assessment_pdf.php?id=<?= $record['id'] ?>" class="btn-print" target="_blank">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                      Print PDF
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p id="noAssessmentFound" style="color: var(--text-muted); transition: color 0.3s ease; display: none;" class="text-center">No assessments found matching your filter.</p>
        </div>
      <?php endif; ?>
        
        
      </div>
    </div>

    <div id="appointmentsContent">
      <div class="card">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
             <h5 style="color: var(--text-dark); transition: color 0.3s ease; margin: 0;">Upcoming Appointments</h5>
            
            <div class="filter-group">
              <div class="search-wrapper">
                <input 
                  type="text" 
                  id="appointmentSearchInput" 
                  class="search-input" 
                  placeholder="Search specialist or date..." 
                  onkeyup="filterAppointments()"
                />
              </div>

              <select id="appointmentStatusFilter" class="status-select" onchange="filterAppointments()">
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Confirmed">Confirmed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
        </div>
        <?php if (count($upcomingAppointments) > 0): ?>
          <div id="appointmentListContainer">
            <?php foreach ($upcomingAppointments as $apt): ?>
              <?php
                // Format date for search/filter comparison
                $formattedDate = date('M j, Y', strtotime($apt['appointment_date']));
              ?>
              <div 
                class="appointment-item" 
                data-specialist="<?= htmlspecialchars($apt['users']['fullname'] ?? 'Specialist') ?>"
                data-date="<?= $formattedDate ?>"
                data-status="<?= $apt['status'] ?>"
              >
                <div class="appointment-info">
                  <h6><?= htmlspecialchars($apt['users']['fullname'] ?? 'Specialist') ?></h6>
                  <div class="appointment-meta">
                    <span>
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                      <?= date('g:i A', strtotime($apt['appointment_time'])) ?>
                    </span>
                    <span>
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                      <?= $formattedDate ?>
                    </span>
                  </div>
                </div>
                <div class="appointment-actions">
                  <button class="btn btn-sm btn-outline-primary" onclick="viewAppointmentDetails(<?= $apt['id'] ?>)">View Details</button>
                  <span class="status-badge status-<?= strtolower($apt['status']) ?>"><?= $apt['status'] ?></span>
                </div>
              </div>
            <?php endforeach; ?>
            <p id="noAppointmentsFound" style="color: var(--text-muted); transition: color 0.3s ease; display: none;" class="text-center">No appointments found matching your filter.</p>
          </div>
        <?php else: ?>
          <p id="noAppointmentsFound" style="color: var(--text-muted); transition: color 0.3s ease;" class="text-center">No upcoming appointments</p>
        <?php endif; ?>
      </div>
    </div>
    

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

    // --- APPOINTMENTS FILTER FUNCTION ---
    function filterAppointments() {
        const input = document.getElementById('appointmentSearchInput');
        const filterText = input.value.toLowerCase();
        const statusSelect = document.getElementById('appointmentStatusFilter');
        const filterStatus = statusSelect.value;
        const container = document.getElementById('appointmentListContainer');
        const noResultsMessage = document.getElementById('noAppointmentsFound');

        // Check if container exists, if not, appointments are empty and we don't need to proceed
        if (!container) return;

        const appointmentItems = container.getElementsByClassName('appointment-item');
        let visibleCount = 0;

        for (let i = 0; i < appointmentItems.length; i++) {
            const item = appointmentItems[i];
            const specialistName = item.getAttribute('data-specialist').toLowerCase();
            const appointmentDate = item.getAttribute('data-date').toLowerCase();
            const currentStatus = item.getAttribute('data-status');

            // 1. Check Text Filter (Specialist Name or Date)
            const textMatch = (specialistName.includes(filterText) || appointmentDate.includes(filterText));
            
            // 2. Check Status Filter
            const statusMatch = (filterStatus === '' || currentStatus === filterStatus);
            
            // Show row if BOTH match
            const isVisible = textMatch && statusMatch;

            if (isVisible) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        }
        
        // Show/Hide "No Appointments Found" message
        if (noResultsMessage) {
            if (visibleCount === 0) {
                noResultsMessage.style.display = 'block';
            } else {
                noResultsMessage.style.display = 'none';
            }
        }
    }
    // --- END APPOINTMENTS FILTER FUNCTION ---
    
    // --- ASSESSMENT FILTER FUNCTION ---
    function filterAssessments() {
        const searchInput = document.getElementById('assessmentSearchInput');
        const filterText = searchInput.value.toLowerCase();
        const statusSelect = document.getElementById('assessmentStatusFilter');
        const filterStatus = statusSelect.value;
        
        const table = document.getElementById('assessmentTable');
        const tbody = table ? table.getElementsByTagName('tbody')[0] : null;
        const noResultsMessage = document.getElementById('noAssessmentFound');

        if (!tbody) return;

        const rows = tbody.getElementsByTagName('tr');
        let visibleCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            
            const currentStatusClass = row.getAttribute('data-status');
            const dateText = row.getAttribute('data-date-str');
            const summaryText = row.getAttribute('data-summary');

            // 1. Check Status Filter
            const statusMatch = (filterStatus === '' || currentStatusClass === filterStatus);
            
            // 2. Check Text Filter (Date or Summary)
            const textMatch = (filterText === '' || dateText.includes(filterText) || summaryText.includes(filterText));

            // Show row if BOTH match
            const isVisible = statusMatch && textMatch;
            
            if (isVisible) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
        
        if (noResultsMessage) {
            if (visibleCount === 0) {
                noResultsMessage.style.display = 'block';
                if (table) table.style.display = 'none';
            } else {
                noResultsMessage.style.display = 'none';
                if (table) table.style.display = 'table';
            }
        }
    }
    // --- END ASSESSMENT FILTER FUNCTION ---


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
</body>
</html>