<?php
session_start();
include 'supabase.php';

// Restrict access to specialists only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Specialist') {
  echo "<script>alert('Access denied.'); window.location.href='login.php';</script>";
  exit;
}

$specialist_id = $_SESSION['user']['id'];
$specialist_name = $_SESSION['user']['fullname'];

// Fetch all appointments for this specialist
$allAppointments = supabaseSelect(
  'appointments',
  ['specialist_id' => $specialist_id],
  'id,user_id,appointment_date,appointment_time,status,notes,created_at',
  'appointment_date.desc,appointment_time.desc',
  null,
  true
);

// Fetch all unique user IDs from appointments
$userIds = array_unique(array_column($allAppointments, 'user_id'));
$users = [];

if (!empty($userIds)) {
  // Fetch user details
  $allUsers = supabaseSelect(
    'users',
    ['id' => ['operator' => 'in', 'value' => '(' . implode(',', $userIds) . ')']],
    'id,fullname,email,gender',
    null,
    null,
    true
  );
  
  // Index users by ID
  foreach ($allUsers as $user) {
    $users[$user['id']] = $user;
  }
}

// Attach user info to appointments
foreach ($allAppointments as &$apt) {
  $apt['users'] = $users[$apt['user_id']] ?? [
    'fullname' => 'Unknown User',
    'email' => 'N/A',
    'gender' => 'N/A'
  ];
}
unset($apt);

// Get recent bookings (last 7 days)
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
$recent_bookings = array_filter($allAppointments, function($apt) use ($sevenDaysAgo) {
  return $apt['created_at'] >= $sevenDaysAgo;
});
$recent_bookings = array_slice($recent_bookings, 0, 10);

// Calculate statistics
$total_appointments = count($allAppointments);
$confirmed = 0;
$pending = 0;
$completed = 0;
$cancelled = 0;

foreach ($allAppointments as $apt) {
  switch ($apt['status']) {
    case 'Confirmed':
      $confirmed++;
      break;
    case 'Pending':
      $pending++;
      break;
    case 'Completed':
      $completed++;
      break;
    case 'Cancelled':
      $cancelled++;
      break;
  }
}

$stats = [
  'total_appointments' => $total_appointments,
  'confirmed' => $confirmed,
  'pending' => $pending,
  'completed' => $completed,
  'cancelled' => $cancelled
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Specialist Dashboard - MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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

    body.dark-mode {
      --bg-light: #1a1a1a;
      --sidebar-bg: #2a2a2a;
      --card-bg: #2d2d2d;
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
      color: #5ad0be;
    }

    .sidebar .nav-link.active {
      background-color: #5ad0be;
      color: #ffffff;
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

    .main-content {
      margin-left: 250px;
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

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      border: 1px solid var(--border-color);
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }

    .stat-card .card-title {
      font-size: 0.875rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.5rem;
      font-weight: 600;
    }

    .stat-card .card-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-dark);
    }

    .card {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-bottom: 1.5rem;
      border: 1px solid var(--border-color);
      transition: all 0.3s ease;
    }

    .card h5 {
      color: var(--text-dark);
      transition: color 0.3s ease;
    }

    .tab-navigation {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
      border-bottom: 2px solid var(--border-color);
    }

    .tab-btn {
      background: none;
      border: none;
      padding: 1rem 1.5rem;
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text-muted);
      cursor: pointer;
      border-bottom: 3px solid transparent;
      transition: all 0.3s ease;
      position: relative;
      top: 2px;
    }

    .tab-btn:hover {
      color: var(--primary-teal);
    }

    .tab-btn.active {
      color: var(--primary-teal);
      border-bottom-color: var(--primary-teal);
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      background: var(--card-bg);
      transition: background-color 0.3s ease;
    }

    .table thead {
      background: var(--bg-light);
      transition: background-color 0.3s ease;
    }
    .table thead th {
      background: var(--card-bg);
      color: var(--text-dark);
      transition: background 0.3s ease, color 0.3s ease;
    }

    .table th {
      padding: 1rem;
      text-align: left;
      font-size: 0.875rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--border-color);
      transition: all 0.3s ease;
    }
    
    .no-results-message {
        text-align: center;
        padding: 2rem;
        color: var(--text-muted);
        font-style: italic;
        background: var(--bg-light);
        border-radius: 8px;
        margin-top: 1rem;
        border: 1px solid var(--border-color);
    }
    .table td {
      padding: 1rem;
      border-bottom: 1px solid var(--border-color);
      font-size: 0.9rem;
      color: var(--text-dark);
      background: var(--card-bg);
      transition: all 0.3s ease;
    }

    .table tbody tr:hover td {
      background: var(--bg-light);
    }

    .badge {
      padding: 0.375rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: inline-block;
    }

    .status-confirmed { background: #28a745; color: white; }
    .status-pending { background: #ffc107; color: #2b2f38; }
    .status-completed { background: #17a2b8; color: white; }
    .status-cancelled { background: #dc3545; color: white; }

    .btn {
      padding: 0.5rem 1rem;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.875rem;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-primary {
      background: var(--primary-teal);
      color: white;
    }

    .btn-primary:hover {
      background: var(--primary-teal-dark);
    }

    .btn-outline-primary {
      background: transparent;
      border: 1px solid var(--primary-teal);
      color: var(--primary-teal);
    }

    .btn-outline-primary:hover {
      background: var(--primary-teal);
      color: white;
    }

    .btn-sm {
      padding: 0.375rem 0.75rem;
      font-size: 0.8125rem;
    }

    .btn-success {
      background: #28a745;
      color: white;
    }

    .btn-success:hover {
      background: #218838;
    }

    .form-select {
      padding: 0.5rem;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      background: var(--card-bg);
      color: var(--text-dark);
      font-size: 0.875rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .form-select:focus {
      outline: none;
      border-color: var(--primary-teal);
      box-shadow: 0 0 0 3px rgba(90, 208, 190, 0.1);
    }

    .form-select-sm {
      padding: 0.375rem 0.5rem;
      font-size: 0.8125rem;
    }

    .form-control {
      padding: 0.5rem;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      background: var(--card-bg);
      color: var(--text-dark);
      font-size: 0.875rem;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary-teal);
      box-shadow: 0 0 0 3px rgba(90, 208, 190, 0.1);
    }

    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      transition: all 0.3s ease;
    }

    .alert-info {
      background: #e7f3ff;
      color: #004085;
      border: 1px solid #b8daff;
    }

    body.dark-mode .alert-info {
      background: #1a3a52;
      color: #9fc9e8;
      border: 1px solid #2a5a7a;
    }

    .d-flex {
      display: flex;
    }

    .justify-content-between {
      justify-content: space-between;
    }

    .align-items-center {
      align-items: center;
    }

    .gap-2 {
      gap: 0.5rem;
    }

    .mb-3 {
      margin-bottom: 1rem;
    }

    .table-responsive {
      overflow-x: auto;
    }

    .text-muted {
      color: var(--text-muted) !important;
    }

    small.text-muted {
      font-size: 0.875rem;
    }
    
    /* Styles for combining search and filter */
    .filter-wrapper {
      display: flex;
      gap: 1rem;
      margin-bottom: 1rem;
      align-items: center;
      flex-wrap: wrap; 
    }

    .search-wrapper {
      flex-grow: 1; 
    }
    
    .search-input {
      width: 100%;
      max-width: 300px;
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
        min-width: 150px;
    }

    .status-select:focus, .search-input:focus {
      outline: none;
      border-color: var(--primary-teal);
      box-shadow: 0 0 0 3px rgba(90, 208, 190, 0.1);
    }
    /* End of search/filter styles */

    /* NEW STYLES for Dashboard Header/Filter Alignment */
    .dashboard-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem; /* Reduced to integrate better with the card padding */
    }

    .dashboard-card-header h5 {
        margin: 0; /* Remove default margin from h5 */
    }
    
    .dashboard-card-header .filter-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="logo-wrapper">
      <img src="images/Mindcare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <nav class="nav flex-column" style="flex: 1;">
      <a class="nav-link active" href="specialist_dashboard.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        DASHBOARD
      </a>
      <a class="nav-link" href="specialist_profile.php">
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

    <a href="admin_logout.php" class="nav-link" style="margin-top: 1rem; color: #ef5350; border-top: 1px solid var(--border-color); padding-top: 1rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      LOGOUT
    </a>
  </div>

  <div class="main-content">
    <div class="dashboard-header">
      <h1>Welcome, <span class="user-name"><?= htmlspecialchars($specialist_name) ?></span></h1>
      <p class="date-time"><?= date('l, F j, Y') ?></p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="card-title">Total Appointments</div>
        <div class="card-value"><?= $stats['total_appointments'] ?></div>
      </div>
      <div class="stat-card">
        <div class="card-title">Confirmed</div>
        <div class="card-value" style="color: #28a745;"><?= $stats['confirmed'] ?></div>
      </div>
      <div class="stat-card">
        <div class="card-title">Pending</div>
        <div class="card-value" style="color: #ffc107;"><?= $stats['pending'] ?></div>
      </div>
      <div class="stat-card">
        <div class="card-title">Completed</div>
        <div class="card-value" style="color: #17a2b8;"><?= $stats['completed'] ?></div>
      </div>
    </div>

    <div class="tab-navigation">
      <button class="tab-btn active" id="dashboardTab">Dashboard</button>
      <button class="tab-btn" id="bookingsTab">Booking Management</button>
    </div>

    <div id="dashboardContent">
      <div class="card">
        
        <div class="dashboard-card-header">
            <h5>Recent Bookings (Last 7 Days)</h5>
            <div class="filter-controls">
                <label for="recentStatusFilter" class="text-muted" style="margin: 0; font-weight: 600;">Filter Status:</label>
                <select id="recentStatusFilter" class="status-select" onchange="filterRecentTable()" style="max-width: 150px;">
                    <option value="">All Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Confirmed">Confirmed</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <?php if (!empty($recent_bookings)): ?>
          <div class="table-responsive">
            <table class="table table-hover" id="recentAppointmentsTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Patient</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Status</th>
                  <th>Booked At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_bookings as $row): ?>
                  <tr data-status="<?= $row['status'] ?>">
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['users']['fullname']) ?></td>
                    <td><?= date('M d, Y', strtotime($row['appointment_date'])) ?></td>
                    <td><?= date('g:i A', strtotime($row['appointment_time'])) ?></td>
                    <td>
                      <span class="badge status-<?= strtolower($row['status']) ?>">
                        <?= $row['status'] ?>
                      </span>
                    </td>
                    <td><?= date('M d, Y g:i A', strtotime($row['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <div id="noRecentResults" class="no-results-message" style="display: none;">No recent appointments found matching the filter.</div>
          </div>
        <?php else: ?>
          <div class="alert alert-info">No recent bookings in the last 7 days.</div>
        <?php endif; ?>
      </div>
    </div>

    <div id="bookingsContent" style="display: none;">
      <div class="card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 style="margin: 0;">All Appointments</h5>
          <button class="btn btn-outline-primary btn-sm" onclick="window.location.reload()">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Refresh
          </button>
        </div>

        <div class="filter-wrapper">
          <div class="search-wrapper">
            <input 
              type="text" 
              id="searchInput" 
              class="search-input" 
              placeholder="Search by patient name, email, or date..." 
              onkeyup="filterTable()"
            />
          </div>

          <select id="statusFilter" class="status-select" onchange="filterTable()">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>

        <?php if (!empty($allAppointments)): ?>
          <div class="table-responsive">
            <table class="table table-hover" id="appointmentsTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Patient</th>
                  <th>Email</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Notes</th>
                  <th>Update Status</th> 
                </tr>
              </thead>
              <tbody>
                <?php foreach ($allAppointments as $row): ?>
                  <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                      <?= htmlspecialchars($row['users']['fullname']) ?>
                      <?php if ($row['users']['gender'] !== 'N/A'): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($row['users']['gender']) ?></small>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['users']['email']) ?></td>
                    <td><?= date('M d, Y', strtotime($row['appointment_date'])) ?></td>
                    <td><?= date('g:i A', strtotime($row['appointment_time'])) ?></td>
                    <td><?= htmlspecialchars($row['notes'] ?? '-') ?></td>
                    <td>
                      <form method="POST" action="update_status.php" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                        <select name="status" class="form-select form-select-sm" style="width: auto; min-width: 130px;">
                          <option value="Pending" <?= $row['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                          <option value="Confirmed" <?= $row['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                          <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                          <option value="Cancelled" <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-success btn-sm">Update</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <div id="noResults" class="no-results-message" style="display: none;">No appointments found.</div>
          </div>
        <?php else: ?>
          <div class="alert alert-info">No appointments found. Patients can book appointments through the booking system.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    // Tab switching
    const dashboardTab = document.getElementById('dashboardTab');
    const bookingsTab = document.getElementById('bookingsTab');
    const dashboardContent = document.getElementById('dashboardContent');
    const bookingsContent = document.getElementById('bookingsContent');

    dashboardTab.addEventListener('click', () => {
      dashboardTab.classList.add('active');
      bookingsTab.classList.remove('active');
      dashboardContent.style.display = 'block';
      bookingsContent.style.display = 'none';
    });

    bookingsTab.addEventListener('click', () => {
      bookingsTab.classList.add('active');
      dashboardTab.classList.remove('active');
      bookingsContent.style.display = 'block';
      dashboardContent.style.display = 'none';
    });

    // --- DASHBOARD: Recent Bookings Filter Function ---
    function filterRecentTable() {
      const statusSelect = document.getElementById('recentStatusFilter');
      const filterStatus = statusSelect.value;
      const table = document.getElementById('recentAppointmentsTable');
      const tbody = table ? table.getElementsByTagName('tbody')[0] : null;
      const noResults = document.getElementById('noRecentResults');
      
      if (!tbody) return;

      const rows = tbody.getElementsByTagName('tr');
      let visibleRowCount = 0;

      for (let i = 0; i < rows.length; i++) {
        // Get the status from the data attribute set in PHP
        const rowStatus = rows[i].getAttribute('data-status');
        
        const isVisible = (filterStatus === '' || rowStatus === filterStatus);

        if (isVisible) {
          rows[i].style.display = '';
          visibleRowCount++;
        } else {
          rows[i].style.display = 'none';
        }
      }
      
      // Show/Hide "No Appointments Found" message
      if (noResults) {
          if (visibleRowCount === 0) {
              noResults.style.display = 'block';
              if (table) table.style.display = 'none';
          } else {
              noResults.style.display = 'none';
              if (table) table.style.display = 'table';
          }
      }
    }


    // --- BOOKING MANAGEMENT: Search/Filter Function ---
    function filterTable() {
      const input = document.getElementById('searchInput');
      const filterText = input.value.toLowerCase();
      const statusSelect = document.getElementById('statusFilter');
      const filterStatus = statusSelect.value;
      
      const table = document.getElementById('appointmentsTable');
      const tbody = table ? table.getElementsByTagName('tbody')[0] : null;
      const noResults = document.getElementById('noResults');
      
      if (!tbody) return;

      const rows = tbody.getElementsByTagName('tr');
      let visibleRowCount = 0;

      for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        
        // Data for text search
        const patientName = cells[1]?.textContent || cells[1]?.innerText || '';
        const email = cells[2]?.textContent || cells[2]?.innerText || '';
        const date = cells[3]?.textContent || cells[3]?.innerText || '';
        
        // Data for status filter - Get the *current* status from the dropdown in the 'Update Status' cell (index 6)
        const statusCell = cells[6];
        const statusDropdown = statusCell ? statusCell.querySelector('select[name="status"]') : null;
        const currentStatus = statusDropdown ? statusDropdown.value : '';


        // 1. Check Text Filter
        const textMatch = (patientName.toLowerCase().indexOf(filterText) > -1 ||
                            email.toLowerCase().indexOf(filterText) > -1 ||
                            date.toLowerCase().indexOf(filterText) > -1);
                            
        // 2. Check Status Filter
        const statusMatch = (filterStatus === '' || currentStatus === filterStatus);
        
        // Show row if BOTH match
        const isVisible = textMatch && statusMatch;

        if (isVisible) {
          rows[i].style.display = '';
          visibleRowCount++;
        } else {
          rows[i].style.display = 'none';
        }
      }
      
      // Show/Hide "No Appointments Found" message
      if (noResults) {
          if (visibleRowCount === 0) {
              noResults.style.display = 'block';
              if (table) table.style.display = 'none';
          } else {
              noResults.style.display = 'none';
              if (table) table.style.display = 'table';
          }
      }
    }
    // End of Booking Management Search/Filter


    // Dark Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeLabel = document.getElementById('themeLabel');
    const body = document.body;

    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
      body.classList.add('dark-mode');
      themeLabel.textContent = 'Dark Mode';
      themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
    }

    themeToggle.addEventListener('click', () => {
      body.classList.toggle('dark-mode');
      
      if (body.classList.contains('dark-mode')) {
        themeLabel.textContent = 'Dark Mode';
        themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
        localStorage.setItem('theme', 'dark');
      } else {
        themeLabel.textContent = 'Light Mode';
        themeIcon.innerHTML = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
        localStorage.setItem('theme', 'light');
      }
    });
  </script>
</body>
</html>