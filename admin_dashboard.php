<?php
session_start();
include 'supabase.php';

// Restrict access to admins only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
  echo "<script>alert('Access denied.'); window.location.href='admin_login.php';</script>";
  exit;
}

// Fetch all appointments with patient and specialist info
$stmt = $conn->prepare("
  SELECT 
    a.id AS appointment_id,
    u1.fullname AS patient_name,
    u2.fullname AS specialist_name,
    a.appointment_date,
    a.appointment_time,
    a.status
  FROM appointments a
  JOIN users u1 ON a.user_id = u1.id
  JOIN users u2 ON a.specialist_id = u2.id
  ORDER BY a.appointment_date, a.appointment_time
");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin – Appointment Summary</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    :root {
      --teal-1: #5ad0be;
      --teal-2: #1aa592;
      --teal-3: #0a6a74;
      --bg-white: #ffffff;
      --text-dark: #2b2f38;
      --text-muted: #7a828e;
      --border-color: #e9edf5;
      --card-bg: #ffffff;
      --table-header-bg: #2b2f38;
      --table-stripe-bg: #f6f7fb;
    }

    body.dark-mode {
      --bg-white: #1a1a1a;
      --text-dark: #f1f1f1;
      --text-muted: #b0b0b0;
      --border-color: #3a3a3a;
      --card-bg: #2a2a2a;
      --table-header-bg: #0a6a74;
      --table-stripe-bg: #333333;
    }

    body {
      font-family: 'Poppins', system-ui, -apple-system, sans-serif;
      background: var(--bg-white);
      color: var(--text-dark);
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .theme-toggle-btn {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(0, 0, 0, 0.1);
      color: var(--text-dark);
      padding: 10px 16px;
      border-radius: 25px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    body.dark-mode .theme-toggle-btn {
      background: rgba(42, 42, 42, 0.9);
      border-color: rgba(90, 208, 190, 0.3);
      color: var(--teal-1);
    }

    .theme-toggle-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    #themeIcon {
      transition: transform 0.5s ease;
    }

    #themeIcon.rotate {
      transform: rotate(360deg);
    }

    .container {
      max-width: 1200px;
    }

    .dashboard-header {
      background: linear-gradient(135deg, var(--teal-1) 0%, var(--teal-2) 100%);
      padding: 30px;
      border-radius: 15px;
      margin-bottom: 30px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .dashboard-header h3 {
      color: white;
      margin: 0;
      font-weight: 600;
    }

    .table-container {
      background: var(--card-bg);
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
      transition: background-color 0.3s ease;
    }

    .table {
      margin: 0;
      color: var(--text-dark);
    }

    .table thead {
      background: var(--table-header-bg);
      color: white;
    }

    .table thead th {
      border: none;
      padding: 15px;
      font-weight: 600;
    }

    .table tbody td {
      padding: 15px;
      vertical-align: middle;
      border-color: var(--border-color);
    }

    .table-striped tbody tr:nth-of-type(odd) {
      background-color: var(--table-stripe-bg);
    }

    body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
      background-color: var(--table-stripe-bg);
    }

    body.dark-mode .table tbody td {
      border-color: var(--border-color);
    }

    .form-select {
      background-color: var(--card-bg);
      color: var(--text-dark);
      border-color: var(--border-color);
      transition: all 0.3s ease;
    }

    body.dark-mode .form-select {
      background-color: #333;
      color: var(--text-dark);
      border-color: var(--border-color);
    }

    body.dark-mode .form-select option {
      background-color: #333;
      color: var(--text-dark);
    }

    .btn-success {
      background: linear-gradient(135deg, var(--teal-1) 0%, var(--teal-2) 100%);
      border: none;
      font-weight: 600;
      transition: transform 0.2s ease;
    }

    .btn-success:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(90, 208, 190, 0.3);
    }

    .btn-outline-danger {
      border: 2px solid #dc3545;
      color: #dc3545;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .btn-outline-danger:hover {
      background: #dc3545;
      color: white;
      transform: translateY(-2px);
    }

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
    }

    .status-confirmed {
      background: #d4edda;
      color: #155724;
    }

    .status-completed {
      background: #cce5ff;
      color: #004085;
    }

    .status-cancelled {
      background: #f8d7da;
      color: #721c24;
    }

    body.dark-mode .status-confirmed {
      background: rgba(76, 175, 80, 0.2);
      color: #81c784;
    }

    body.dark-mode .status-completed {
      background: rgba(33, 150, 243, 0.2);
      color: #64b5f6;
    }

    body.dark-mode .status-cancelled {
      background: rgba(244, 67, 54, 0.2);
      color: #ef5350;
    }
  </style>
</head>
<body>
  <!-- Dark Mode Toggle -->
  <button class="theme-toggle-btn" id="themeToggle">
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

  <div class="container mt-5">
    <div class="dashboard-header d-flex justify-content-between align-items-center">
      <h3><i class="fas fa-clipboard-list"></i> Appointment Summary</h3>
      <a href="admin_logout.php" class="btn btn-outline-danger">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>

    <div class="table-container">
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Patient</th>
              <th>Specialist</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
              <th>Update</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $row['appointment_id'] ?></td>
                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                <td><?= htmlspecialchars($row['specialist_name']) ?></td>
                <td><?= date('F j, Y', strtotime($row['appointment_date'])) ?></td>
                <td><?= date('g:i A', strtotime($row['appointment_time'])) ?></td>
                <td>
                  <span class="status-badge status-<?= strtolower($row['status']) ?>">
                    <?= $row['status'] ?>
                  </span>
                </td>
                <td>
                  <form method="POST" action="update_status.php" class="d-flex">
                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                    <select name="status" class="form-select me-2">
                      <option value="Confirmed" <?= $row['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                      <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                      <option value="Cancelled" <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="fas fa-check"></i> Update
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
    const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeLabel = document.getElementById('themeLabel');

    // Check for saved theme preference
    const prefersDark = localStorage.getItem('dark-mode') === 'true';
    if (prefersDark) {
      document.body.classList.add('dark-mode');
      themeIcon.innerHTML = moonIcon;
      themeLabel.textContent = 'Dark Mode';
    }

    // Toggle theme
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode');
      const isDark = document.body.classList.contains('dark-mode');
      localStorage.setItem('dark-mode', isDark);
      
      themeIcon.classList.add('rotate');
      setTimeout(() => themeIcon.classList.remove('rotate'), 500);
      
      themeIcon.innerHTML = isDark ? moonIcon : sunIcon;
      themeLabel.textContent = isDark ? 'Dark Mode' : 'Light Mode';
    });
  </script>
</body>
</html>