<?php
session_start();
include 'supabase.php'; // Ensure this file correctly implements supabaseSelect

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['fullname'] ?? 'User';

// FIX: Try multiple methods to fetch appointments with specialist info

// Method 1: Try with PostgREST foreign key syntax (using constraint name)
// NOTE: Using SERVICE_KEY to bypass Row Level Security (RLS) is generally a security risk 
// if not managed carefully, but it's used here as per the original code's requirement.
$appointments = supabaseSelect(
  'appointments',
  ['user_id' => $user_id],
  'id,specialist_id,appointment_date,appointment_time,status,created_at,users!appointments_specialist_id_fkey(fullname,email)',
  'appointment_date.asc',
  null,
  true  // Use SERVICE_KEY to bypass RLS
);

// Method 2: Fallback - If foreign key doesn't work, fetch appointments and specialists separately
if (empty($appointments) || !isset($appointments[0]['users'])) {
  // Fetch appointments without foreign key
  $appointments = supabaseSelect(
    'appointments',
    ['user_id' => $user_id],
    'id,specialist_id,appointment_date,appointment_time,status,created_at',
    'appointment_date.asc',
    null,
    true
  );
  
  // Fetch all specialists in one query for efficiency
  $specialistIds = array_unique(array_column($appointments, 'specialist_id'));
  $specialists = [];
  
  if (!empty($specialistIds)) {
    // Escape IDs for the 'in' operator in SQL query
    $in_ids = implode(',', array_map(function($id) { return (int)$id; }, $specialistIds));

    $allSpecialists = supabaseSelect(
      'users',
      ['id' => ['operator' => 'in', 'value' => '(' . $in_ids . ')']],
      'id,fullname,email',
      null,
      null,
      true
    );
    
    // Index specialists by ID for quick lookup
    foreach ($allSpecialists as $spec) {
      $specialists[$spec['id']] = $spec;
    }
    
    // Attach specialist info to appointments
    foreach ($appointments as &$apt) {
      $apt['users'] = $specialists[$apt['specialist_id']] ?? [
        'fullname' => 'Unknown Specialist',
        'email' => 'N/A'
      ];
    }
    unset($apt);
  }
}

// --- ADDED: Session Message Display Logic ---
$message = $_SESSION['message'] ?? null;
$message_type = $_SESSION['message_type'] ?? null;
unset($_SESSION['message']);
unset($_SESSION['message_type']);
// ---------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Appointments - MindCare</title>
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

    /* Dark Mode Variables */
    body.dark-mode {
      --bg-light: #1a1a1a;
      --sidebar-bg: #2a2a2a;
      --card-bg: #2d2d2d;
      --text-dark: #f1f1f1;
      --text-muted: #b0b0b0;
      --border-color: #3a3a3a;
    }

    /* Sidebar - Base styles */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      background: var(--sidebar-bg);
      border-right: 1px solid var(--border-color);
      padding: 1.5rem;
      z-index: 1000;
      display: flex;
      flex-direction: column;
      transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
    }

    /* FIX: Wrap desktop sidebar/body padding styles in a media query */
    @media (min-width: 993px) {
        body {
            padding-left: 250px; /* Desktop spacing for sidebar */
        }

        .sidebar {
            width: 250px;
        }
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

    /* Main Content */
    .main-content {
      padding: 2rem;
      min-height: 100vh;
      transition: margin-left 0.3s ease;
    }

    .page-header {
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.25rem;
    }

    .page-header .user-name {
      color: var(--primary-teal);
    }

    .date-time {
      font-size: 0.875rem;
      color: var(--text-muted);
    }

    /* Card */
    .card {
      background: var(--card-bg);
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
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    body.dark-mode .card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }
    
    body.dark-mode .table thead {
      background: #2a2a2a !important;
    }

    body.dark-mode .table tbody tr:hover {
      background: rgba(90, 208, 190, 0.12) !important;
    }

    body.dark-mode .table td,
    body.dark-mode .table th {
      color: var(--text-dark) !important; 
      border-color: var(--border-color) !important;
      background: var(--card-bg) !important;
    }

    body.dark-mode .table {
      background: var(--card-bg);
    }
    
    /* Table */
    .table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }

    .table thead {
      background: var(--bg-light);
    }

    .table th {
      padding: 1rem;
      text-align: left;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--border-color);
      transition: color 0.3s ease;
    }

    .table td {
      padding: 1rem;
      border-bottom: 1px solid var(--border-color);
      font-size: 0.9rem;
      color: var(--text-dark);
      transition: color 0.3s ease;
    }

    .table tbody tr:hover {
      background: rgba(90, 208, 190, 0.05);
    }

    /* Ensure h5 headings in cards are visible in dark mode */
    .card h5 {
      color: var(--text-dark);
      transition: color 0.3s ease;
    }

    /* Status Badges */
    .badge {
      padding: 0.375rem 0.75rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-block;
    }

    .status-confirmed {
      background-color: #28a745;
      color: white;
    }

    .status-pending {
      background-color: #ffc107;
      color: #2b2f38;
    }

    .status-completed {
      background-color: #17a2b8;
      color: white;
    }

    .status-cancelled {
      background-color: #dc3545;
      color: white;
    }

    /* Buttons */
    .btn {
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.875rem;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .btn-primary {
      background: var(--primary-teal);
      color: white;
    }

    .btn-primary:hover {
      background: var(--primary-teal-dark);
    }

    .btn-outline-secondary {
      background: transparent;
      border: 1px solid var(--border-color);
      color: var(--text-muted);
    }

    .btn-outline-secondary:hover {
      background: var(--bg-light);
      color: var(--text-dark);
    }
    
    /* ADDED: Styling for the new Cancel button */
    .btn-danger {
      background: #dc3545;
      color: white;
      /* Adjusted to only apply margin-left if needed by the PHP logic */
    }

    .btn-danger:hover {
      background: #c82333;
    }
    /* END ADDED STYLING */

    .btn-sm {
      padding: 0.375rem 0.75rem;
      font-size: 0.8125rem;
    }

    /* Alert */
    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }
    
    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .alert-info {
      background: #e7f3ff;
      color: #004085;
      border: 1px solid #b8daff;
    }
    
    /* REMOVED: .menu-toggle styles as mobile.js handles this */

    /* Styles for screens up to 992px wide (Mobile/Tablet) */
    @media (max-width: 992px) {
      .main-content {
        /* mobile.css will apply the necessary top padding for the fixed mobile menu button */
      }
      
      /* Mobile styling for action buttons */
      .table td:last-child form {
        display: inline-block; /* Ensure form is not full width */
      }
      .table td:last-child button {
          margin-left: 0.25rem !important; /* Adjust spacing on mobile */
          margin-top: 5px;
      }
    }
  </style>
</head>
<body>
  
  <div class="sidebar" id="sidebar">
    <div class="logo-wrapper">
      <img src="images/Mindcare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <nav>
      <a class="nav-link" href="dashboard.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
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
      <a class="nav-link" href="book_appointment.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        BOOK APPOINTMENT
      </a>
      <a class="nav-link active" href="appointments.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
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
      <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
      <line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line>
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

  <div class="main-content">
    <div class="page-header">
      <h1>My Appointments, <span class="user-name"><?= htmlspecialchars($user_name) ?></span></h1>
      <p class="date-time"><?= date('l, F j, Y') ?></p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <div class="card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 style="margin-bottom: 0;">Your Scheduled Appointments</h5>
        <a href="book_appointment.php" class="btn btn-primary btn-sm">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Book New Appointment
        </a>
      </div>

      <?php if (!empty($appointments)): ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Specialist</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($appointments as $apt): ?>
                <tr>
                  <td><?= $apt['id'] ?></td>
                  <td><?= htmlspecialchars($apt['users']['fullname'] ?? 'Unknown') ?></td>
                  <td><?= date('F j, Y', strtotime($apt['appointment_date'])) ?></td>
                  <td><?= date('g:i A', strtotime($apt['appointment_time'])) ?></td>
                  <td>
                    <span class="badge status-<?= strtolower($apt['status']) ?>">
                      <?= $apt['status'] ?>
                    </span>
                  </td>
                  <td data-label="Actions">
                    <?php 
                    // Set variables for action availability
                    $isReschedulable = ($apt['status'] === 'Pending' || $apt['status'] === 'Confirmed');
                    $isCancelable = ($apt['status'] === 'Pending' || $apt['status'] === 'Confirmed');
                    ?>
                    
                    <?php if ($isReschedulable): ?>
                      <form method="GET" action="book_appointment.php" style="display:inline-block;">
                        <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Reschedule</button>
                      </form>
                    <?php endif; ?>
                      
                    <?php if ($isCancelable): ?>
                      <form id="cancelForm_<?= $apt['id'] ?>" method="POST" action="cancel_appointment.php" style="display:inline-block; <?= $isReschedulable ? 'margin-left: 0.5rem;' : '' ?>">
                        <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                        <input type="hidden" name="cancellation_reason" id="reason_<?= $apt['id'] ?>" value=""> 
                        
                        <button type="button" class="btn btn-danger btn-sm" onclick="promptForCancellation(<?= $apt['id'] ?>)">Cancel</button>
                      </form>
                    <?php else: ?>
                      <span class="text-muted small">No action available</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-info">
          You haven't scheduled any appointments yet. 
          <a href="book_appointment.php" style="color: #004085; font-weight: 600;">Book your first appointment</a> to get started with our specialists.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="mobile.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Get elements
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
    

    // NEW JAVASCRIPT FUNCTION FOR CANCELLATION REASON
    function promptForCancellation(appointmentId) {
        // 1. Confirmation Prompt
        if (!confirm('Are you sure you want to cancel appointment #' + appointmentId + '? This action cannot be undone.')) {
            return; 
        }
        
        // 2. Reason Input Prompt
        let reason = prompt('Please briefly state the reason for cancellation (Required):');
        
        // Loop until a reason is provided or the user hits Cancel
        while (reason !== null && reason.trim() === '') {
            reason = prompt('Reason is required to cancel the appointment. Please enter a reason:');
        }

        // 3. Process Submission
        if (reason !== null) {
            // If reason is provided, update the hidden field and submit the form
            const reasonInput = document.getElementById('reason_' + appointmentId);
            const form = document.getElementById('cancelForm_' + appointmentId);
            
            if (reasonInput && form) {
                reasonInput.value = "CLIENT CANCELLATION: " + reason.trim();
                form.submit();
            } else {
                alert("Error: Could not find the form elements for submission.");
            }
        } else {
            // User clicked Cancel on the Reason prompt
            alert('Cancellation process stopped by user.');
        }
    }
  </script>
</body>
</html>