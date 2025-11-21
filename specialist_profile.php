<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

include 'supabase.php';

$specialist_id = $_SESSION['user']['id']; 

$specialists = supabaseSelect('users', ['id' => $specialist_id, 'role' => 'Specialist']);
$specialist = !empty($specialists) ? $specialists[0] : null;

if (!$specialist) {
  header("Location: logout.php");
  exit;
}

$_SESSION['user'] = $specialist; // Update session with fresh data

$specialist_name = $specialist['fullname'] ?? 'Specialist';
$initials = strtoupper(substr($specialist_name, 0, 1) . (strpos($specialist_name, ' ') !== false ? substr($specialist_name, strpos($specialist_name, ' ') + 1, 1) : ''));

// Get last completed appointment for statistical reference
$lastCompletedQuery = supabaseSelect(
  'appointments',
  ['specialist_id' => $specialist_id, 'status' => 'Completed'],
  'appointment_date,appointment_time',
  'appointment_date.desc,appointment_time.desc',
  1,
  true  
);
$lastCompleted = !empty($lastCompletedQuery) ? $lastCompletedQuery[0] : null;

// Get next upcoming appointment (Pending or Confirmed, future date)
$today = date('Y-m-d');
$upcomingQuery = supabaseSelect(
  'appointments',
  [
    'specialist_id' => $specialist_id,
    'appointment_date' => ['operator' => 'gte', 'value' => $today]  
  ],
  'appointment_date,appointment_time,status',
  'appointment_date.asc,appointment_time.asc',
  10,
  true  
);

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
  <title>Specialist Profile - MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="mobile.css" />
  <style>
    /* ------------------------------------- */
    /* CSS STYLES (Fixed Centering)          */
    /* ------------------------------------- */
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
      transition: transform 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
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

    /* FIX: Centering and Max-Width */
    .main-wrapper {
      margin-left: 250px;
      padding: 2rem;
      min-height: 100vh;
      transition: margin-left 0.3s ease; 
      display: flex; 
      justify-content: center; 
      align-items: flex-start; 
    }

    .content-inner {
      max-width: 1200px; /* Control max content width */
      width: 100%; 
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

    /* Container for action buttons (Edit and Change Password) */
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
    
    /* Style for the Change Password button */
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
    
    .contact-row > div { 
        min-width: 150px;
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
    
    /* === MOBILE/RESPONSIVE STYLES === */

    .menu-toggle {
        display: none;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1001;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        padding: 0.5rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .menu-toggle:hover {
        background-color: rgba(90, 208, 190, 0.1);
        border-color: var(--primary-teal);
    }

    .menu-toggle svg {
        color: var(--primary-teal);
        width: 24px;
        height: 24px;
    }

    @media (max-width: 992px) { 
        .sidebar {
            transform: translateX(-250px);
        }
        
        .sidebar.show {
            transform: translateX(0);
        }

        .main-wrapper {
            margin-left: 0;
            padding-top: 5rem; 
        }
        
        .menu-toggle {
            display: block;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .profile-actions {
            flex-direction: column;
            width: 100%;
            gap: 0.75rem;
        }

        .profile-actions .btn-edit,
        .profile-actions .btn-change-password {
            width: 100%;
            justify-content: center;
        }
        
        .profile-card {
            padding: 1.5rem;
        }

        .profile-header {
            flex-direction: column;
            gap: 1.5rem;
            align-items: center;
            text-align: center;
            padding-bottom: 1.5rem;
        }

        .profile-info {
            text-align: center;
        }

        .contact-row {
            flex-direction: column;
            gap: 1.5rem;
            align-items: center;
        }

        .contact-row > div {
            min-width: 100%;
        }

        .info-grid,
        .appointment-cards {
            grid-template-columns: 1fr;
        }
    }
  </style>
</head>
<body>
  
  <button class="menu-toggle" id="menuToggle">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="3" y1="12" x2="21" y2="12"></line>
      <line x1="3" y1="6" x2="21" y2="6"></line>
      <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
  </button>
  <div class="sidebar" id="sidebar">
    <div class="logo-wrapper">
      <img src="images/Mindcare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <nav>
      <a href="specialist_dashboard.php" class="nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        DASHBOARD
      </a>
      
      <a href="specialist_profile.php" class="nav-link active">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        PROFILE
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
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
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
        <h1>Specialist Profile</h1>
        <div class="profile-actions">
          <a href="change-password.php" class="btn-change-password">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            Change Password
          </a>
          <a href="edit-specialist-profile.php" class="btn-edit">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            Edit Profile
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
            <div class="profile-name"><?= htmlspecialchars($specialist_name) ?></div>
            <div class="profile-meta">
              <?= htmlspecialchars($specialist['specialty'] ?? 'Mental Health Specialist') ?> 
              • <?= htmlspecialchars($specialist['role'] ?? 'Specialist') ?>
            </div>
            <div class="contact-row">
              <div>
                <div class="contact-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                  Phone
                </div>
                <div class="contact-value">
                  <?php if (!empty($specialist['phone'])): ?>
                    <?= htmlspecialchars($specialist['phone']) ?>
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
                <div class="contact-value"><?= htmlspecialchars($specialist['email']) ?></div>
              </div>
              <div>
                <div class="contact-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                  Location
                </div>
                <div class="contact-value">
                  <?php 
                  // Use the 'address' column which is mapped for the location
                  if (!empty($specialist['address'])): ?>
                    <?= htmlspecialchars($specialist['address']) ?>
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
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
            Last Completed Appointment
          </div>
          <div class="appointment-date">
            <?php if ($lastCompleted): ?>
              <?= date('F d, Y', strtotime($lastCompleted['appointment_date'])) ?>
            <?php else: ?>
              <span class="not-set">No completed appointments yet</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="appointment-card">
          <div class="appointment-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            Next Scheduled Appointment
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
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Professional Details
          </h5>
          <div class="info-item">
            <span class="info-label">Specialty</span>
            <span class="info-value">
              <?php if (!empty($specialist['specialty'])): ?>
                <?= htmlspecialchars($specialist['specialty']) ?>
              <?php else: ?>
                <span class="not-set">Not set</span>
              <?php endif; ?>
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">Years of Experience</span>
            <span class="info-value">
              <?php if (!empty($specialist['experience_years'])): ?>
                <?= htmlspecialchars($specialist['experience_years']) ?> years
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
    // --- UNIFIED THEME LOGIC START ---
    const THEME_KEY = 'dark-mode'; // Reverting key to match system standard (login.php, register.php)
    const DARK_CLASS = 'dark-mode'; 
    
    const toggleBtn = document.getElementById('themeToggle');
    const icon = document.getElementById('themeIcon');
    const label = document.getElementById('themeLabel');
    const body = document.body;

    const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
    const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';

    function applyTheme(isDark) {
        if (isDark) {
            body.classList.add(DARK_CLASS);
            label.textContent = 'Dark Mode';
            icon.innerHTML = moonIcon;
            localStorage.setItem(THEME_KEY, 'true'); // Store 'true' for dark mode
        } else {
            body.classList.remove(DARK_CLASS);
            label.textContent = 'Light Mode';
            icon.innerHTML = sunIcon;
            localStorage.setItem(THEME_KEY, 'false'); // Store 'false' for light mode
        }
    }
    
    // Initialize theme state on load
    function initializeTheme() {
        const currentThemeIsDark = localStorage.getItem(THEME_KEY) === 'true'; 
        applyTheme(currentThemeIsDark);
    }

    toggleBtn.addEventListener('click', () => {
        const isDark = !body.classList.contains(DARK_CLASS);
        applyTheme(isDark);
        
        icon.style.transform = 'rotate(360deg)';
        setTimeout(() => icon.style.transform = 'rotate(0deg)', 500);
    });

    document.addEventListener('DOMContentLoaded', initializeTheme);
    icon.style.transition = 'transform 0.5s ease';

    // --- MOBILE MENU TOGGLE LOGIC ---
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });
    // --- UNIFIED THEME LOGIC END ---
  </script>
</body>
</html>