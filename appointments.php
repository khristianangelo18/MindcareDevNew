<?php
session_start();
include 'supabase.php';

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['fullname'] ?? 'User';

// FIX: Try multiple methods to fetch appointments with specialist info

// Method 1: Try with PostgREST foreign key syntax (using constraint name)
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
    $allSpecialists = supabaseSelect(
      'users',
      ['id' => ['operator' => 'in', 'value' => '(' . implode(',', $specialistIds) . ')']],
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Appointments - MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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

    /* Main Content */
    .main-content {
      margin-left: 250px;
      padding: 2rem;
      min-height: 100vh;
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
  background: #2a2a2a !important;  /* darker header */
}

body.dark-mode .table tbody tr:hover {
  background: rgba(90, 208, 190, 0.12) !important; /* subtle teal hover */
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

    .alert-info {
      background: #e7f3ff;
      color: #004085;
      border: 1px solid #b8daff;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar {
        width: 0;
        padding: 0;
        overflow: hidden;
      }

      .main-content {
        margin-left: 0;
      }

      .table {
        font-size: 0.8rem;
      }

      .table th, .table td {
        padding: 0.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Logo -->
    <div class="logo-wrapper">
      <img src="images/Mindcare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <!-- Navigation Links -->
    <nav>
      <a class="nav-link" href="dashboard.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
        DASHBOARD
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

    <!-- Dark Mode Toggle -->
    <div class="theme-toggle">
      <button id="themeToggle">
        <span id="themeIcon">🌞</span>
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
    <!-- Page Header -->
    <div class="page-header">
      <h1>My Appointments, <span class="user-name"><?= htmlspecialchars($user_name) ?></span></h1>
      <p class="date-time"><?= date('l, F j, Y') ?></p>
    </div>

    <!-- Appointments Table -->
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
                  <td>
                    <?php if ($apt['status'] !== 'Cancelled' && $apt['status'] !== 'Completed'): ?>
                      <button class="btn btn-outline-secondary btn-sm">Reschedule</button>
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

  <!-- Dark Mode Script -->
  <script>
    // Dark Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeLabel = document.getElementById('themeLabel');
    const body = document.body;

    // Check for saved theme preference
    if (localStorage.getItem('theme') === 'dark') {
      body.classList.add('dark-mode');
      themeIcon.textContent = '🌙';
      themeLabel.textContent = 'Dark Mode';
    }

    themeToggle.addEventListener('click', () => {
      body.classList.toggle('dark-mode');
      
      if (body.classList.contains('dark-mode')) {
        themeIcon.textContent = '🌙';
        themeLabel.textContent = 'Dark Mode';
        localStorage.setItem('theme', 'dark');
      } else {
        themeIcon.textContent = '🌞';
        themeLabel.textContent = 'Light Mode';
        localStorage.setItem('theme', 'light');
      }
    });
  </script>
</body>
</html>