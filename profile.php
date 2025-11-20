<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

include 'supabase.php';

$user_id = $_SESSION['user']['id'];
$users = supabaseSelect('users', ['id' => $user_id]);
$user = !empty($users) ? $users[0] : null;

if (!$user) {
  header("Location: logout.php");
  exit;
}

$_SESSION['user'] = $user;

// Keep original code
$user_name = $user['fullname'] ?? 'User';
$initials = strtoupper(substr($user_name, 0, 1) . (strpos($user_name, ' ') !== false ? substr($user_name, strpos($user_name, ' ') + 1, 1) : ''));

// Get last completed appointment
$lastVisitQuery = supabaseSelect(
  'appointments',
  ['user_id' => $user_id, 'status' => 'Completed'],
  'appointment_date,appointment_time',
  'appointment_date.desc,appointment_time.desc',
  1,
  true  // ← ADD THIS: Bypass RLS to query appointments
);
$lastVisit = !empty($lastVisitQuery) ? $lastVisitQuery[0] : null;

// Get next upcoming appointment (Pending or Confirmed, future date)
$today = date('Y-m-d');
$upcomingQuery = supabaseSelect(
  'appointments',
  [
    'user_id' => $user_id,
    'appointment_date' => ['operator' => 'gte', 'value' => $today]  // ← Only future dates
  ],
  'appointment_date,appointment_time,status',
  'appointment_date.asc,appointment_time.asc',
  10,
  true  // ← ADD THIS: Bypass RLS
);

// Filter for future appointments with Pending or Confirmed status
$nextAppointment = null;
if (!empty($upcomingQuery)) {
  foreach ($upcomingQuery as $apt) {
    if ($apt['status'] === 'Pending' || $apt['status'] === 'Confirmed') {
      $nextAppointment = $apt;
      break;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile - MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="mobile.css" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

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
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-light);
      color: var(--text-dark);
      overflow-x: hidden;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    body.dark-mode {
      --bg-light: #1a1a1a;
      --sidebar-bg: #2a2a2a;
      --card-bg: #2a2a2a;
      --text-dark: #f1f1f1;
      --text-muted: #b0b0b0;
      --border-color: #3a3a3a;
    }

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
      transition: background-color 0.3s ease, border-color 0.3s ease;
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
      color: var(--primary-teal);
    }

    .sidebar .nav-link.active {
      background-color: var(--primary-teal);
      color: white;
    }

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

    .main-wrapper {
      margin-left: 250px;
      padding: 2rem;
      min-height: 100vh;
    }

    .content-inner {
      max-width: 1200px;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-dark);
    }

    /* NEW: Container for action buttons (Edit and Change Password) */
    .profile-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .btn-edit {
      background: linear-gradient(135deg, var(--primary-teal), var(--primary-teal-dark));
      color: white;
      border: none;
      padding: 0.65rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 4px 12px rgba(90, 208, 190, 0.3);
    }

    .btn-edit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(90, 208, 190, 0.4);
      color: white;
    }
    
    /* NEW: Style for the Change Password button */
    .btn-change-password {
        background: transparent;
        color: var(--primary-teal);
        border: 1px solid var(--primary-teal);
        padding: 0.65rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-change-password:hover {
        background: var(--primary-teal);
        color: white;
    }

    .alert {
      border-radius: 8px;
      border: none;
      margin-bottom: 2rem;
    }

    .profile-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    body.dark-mode .profile-card {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .profile-header {
      display: flex;
      gap: 2rem;
      padding-bottom: 2rem;
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 2rem;
    }

    .avatar-circle {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary-teal), var(--primary-teal-dark));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      font-weight: 700;
      color: white;
      flex-shrink: 0;
    }

    .profile-info {
      flex: 1;
    }

    .profile-name {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.25rem;
    }

    .profile-meta {
      color: var(--text-muted);
      font-size: 0.95rem;
      margin-bottom: 1.5rem;
    }

    .contact-row {
      display: flex;
      gap: 3rem;
      flex-wrap: wrap;
    }

    .contact-label {
      font-size: 0.75rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.25rem;
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    .contact-label svg {
        color: var(--primary-teal);
        width: 16px;
        height: 16px;
    }

    .contact-value {
      font-size: 0.95rem;
      color: var(--text-dark);
      font-weight: 500;
    }

    .not-set {
      color: var(--text-muted);
      font-style: italic;
      font-weight: 400;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .info-section {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    body.dark-mode .info-section {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .section-title {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 1.25rem;
    }

    .section-title svg {
      width: 20px;
      height: 20px;
      color: var(--primary-teal);
    }

    .info-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px solid var(--border-color);
    }

    .info-item:last-child {
      border-bottom: none;
    }

    .info-label {
      font-size: 0.875rem;
      color: var(--text-muted);
      font-weight: 500;
    }

    .info-value {
      font-size: 0.95rem;
      color: var(--text-dark);
      font-weight: 600;
    }

    .appointment-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .appointment-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .appointment-label {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.875rem;
      color: var(--text-muted);
      margin-bottom: 0.5rem;
    }

    .appointment-label svg {
        color: var(--primary-teal);
        width: 18px;
        height: 18px;
    }

    .appointment-date {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--text-dark);
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="logo-wrapper">
      <img src="images/Mindcare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <nav>
      <a href="dashboard.php" class="nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        DASHBOARD
      </a>
      <a class="nav-link" href="resources.php">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
          viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>
      </svg>        RESOURCES
      </a>
      <a class="nav-link" href="assessment.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        ASSESSMENT
      </a>
      <a href="book_appointment.php" class="nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        BOOK APPOINTMENT
      </a>
      <a class="nav-link" href="appointments.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
        MY APPOINTMENTS
      </a>
      <a href="profile.php" class="nav-link active">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        PROFILE
      </a>
      <a href="faq.php" class="nav-link">
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

  <div class="main-wrapper">
    <div class="content-inner">
      
      <div class="page-header">
        <h1>Profile</h1>
        <div class="profile-actions">
          <a href="change-password.php" class="btn-change-password">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            Change Password
          </a>
          <a href="edit-profile.php" class="btn-edit">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            Edit
          </a>
        </div>
      </div>

      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($_GET['success']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <div class="profile-card">
        <div class="profile-header">
          <div class="avatar-circle"><?= $initials ?></div>
          <div class="profile-info">
            <div class="profile-name"><?= htmlspecialchars($user_name) ?></div>
            <div class="profile-meta">
              <?= htmlspecialchars($user['gender'] ?? 'Not specified') ?> 
              <?php if (!empty($user['age'])): ?>
                • <?= htmlspecialchars($user['age']) ?> years old
              <?php endif; ?>
              • <?= htmlspecialchars($user['role'] ?? 'Patient') ?>
            </div>
            <div class="contact-row">
              <div>
                <div class="contact-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                  Phone
                </div>
                <div class="contact-value">
                  <?php if (!empty($user['phone'])): ?>
                    <?= htmlspecialchars($user['phone']) ?>
                  <?php else: ?>
                    <span class="not-set">Not set</span>
                  <?php endif; ?>
                </div>
              </div>
              <div>
                <div class="contact-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                  Email
                </div>
                <div class="contact-value"><?= htmlspecialchars($user['email']) ?></div>
              </div>
              <div>
                <div class="contact-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                  Address
                </div>
                <div class="contact-value">
                  <?php if (!empty($user['address'])): ?>
                    <?= htmlspecialchars($user['address']) ?>
                  <?php else: ?>
                    <span class="not-set">Not set</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="appointment-cards">
        <div class="appointment-card">
          <div class="appointment-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Last Visit
          </div>
          <div class="appointment-date">
            <?php if ($lastVisit): ?>
              <?= date('F d, Y', strtotime($lastVisit['appointment_date'])) ?>
            <?php else: ?>
              <span class="not-set">No visits yet</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="appointment-card">
          <div class="appointment-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            Next Appointment
          </div>
          <div class="appointment-date">
            <?php if ($nextAppointment): ?>
              <?= date('F d, Y', strtotime($nextAppointment['appointment_date'])) ?>
            <?php else: ?>
              <span class="not-set">No upcoming appointments</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="info-grid">
        <div class="info-section">
          <h5 class="section-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Personal Information
          </h5>
          <div class="info-item">
            <span class="info-label">Height</span>
            <span class="info-value">
              <?php if (!empty($user['height'])): ?>
                <?= htmlspecialchars($user['height']) ?> cm
              <?php else: ?>
                <span class="not-set">Not set</span>
              <?php endif; ?>
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">Weight</span>
            <span class="info-value">
              <?php if (!empty($user['weight'])): ?>
                <?= htmlspecialchars($user['weight']) ?> kg
              <?php else: ?>
                <span class="not-set">Not set</span>
              <?php endif; ?>
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">Blood Group</span>
            <span class="info-value">
              <?php if (!empty($user['blood_group'])): ?>
                <?= htmlspecialchars($user['blood_group']) ?>
              <?php else: ?>
                <span class="not-set">Not set</span>
              <?php endif; ?>
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">BMI</span>
            <span class="info-value">
              <?php if (!empty($user['bmi'])): ?>
                <?= htmlspecialchars($user['bmi']) ?>
              <?php else: ?>
                <span class="not-set">Not set</span>
              <?php endif; ?>
            </span>
          </div>
        </div>

        <div class="info-section">
          <h5 class="section-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            Emergency Contact
          </h5>
          <div class="info-item">
            <span class="info-label">Name</span>
            <span class="info-value">
              <?php if (!empty($user['emergency_contact_name'])): ?>
                <?= htmlspecialchars($user['emergency_contact_name']) ?>
              <?php else: ?>
                <span class="not-set">Not set</span>
              <?php endif; ?>
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">Relationship</span>
            <span class="info-value">
              <?php if (!empty($user['emergency_contact_relationship'])): ?>
                <?= htmlspecialchars($user['emergency_contact_relationship']) ?>
              <?php else: ?>
                <span class="not-set">Not set</span>
              <?php endif; ?>
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">Phone</span>
            <span class="info-value">
              <?php if (!empty($user['emergency_contact_phone'])): ?>
                <?= htmlspecialchars($user['emergency_contact_phone']) ?>
              <?php else: ?>
                <span class="not-set">Not set</span>
              <?php endif; ?>
            </span>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="mobile.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
 // Dark mode toggle with SVG icons
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
  </script>
</body>
</html>