<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Specialist') {
  header("Location: login.php");
  exit;
}

include 'supabase.php';

$specialist_id = $_SESSION['user']['id'];

// 1. Fetch current specialist data
// Note: We assume the 'specialty' and 'experience_years' columns exist on the 'users' table.
$specialists = supabaseSelect('users', ['id' => $specialist_id, 'role' => 'Specialist']);
$specialist = !empty($specialists) ? $specialists[0] : null;

if (!$specialist) {
  header("Location: logout.php");
  exit;
}

// 2. Handle Form Submission
$message = '';
$message_type = '';
$updateSuccessful = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize inputs
    $updateData = [
        'fullname' => trim($_POST['fullname']),
        'phone' => trim($_POST['phone']),
        'specialty' => trim($_POST['specialty']),
        'experience_years' => (int)($_POST['experience_years']),
        'clinic_address' => trim($_POST['clinic_address']), // Temporary key for form input
    ];
    
    // Validate required fields
    if (empty($updateData['fullname']) || empty($updateData['specialty'])) {
        $message = 'Full Name and Specialty are required fields.';
        $message_type = 'danger';
    } else {
        
        // Prepare data for the Supabase database call
        $dbUpdateData = [
            'fullname' => $updateData['fullname'],
            'phone' => $updateData['phone'],
            'specialty' => $updateData['specialty'], 
            'experience_years' => $updateData['experience_years'], 
            'address' => $updateData['clinic_address'], // Use 'address' as per the users table schema
        ];

        // Execute the update
        $result = supabaseUpdate('users', ['id' => $specialist_id], $dbUpdateData);

        // Check the result for an error key/message
        if (!isset($result['error'])) {
            $updateSuccessful = true;
            
            // Prepare session data (use friendly PHP key 'clinic_address' for display later)
            $sessionUpdateData = $dbUpdateData;
            $sessionUpdateData['clinic_address'] = $dbUpdateData['address']; 
            unset($sessionUpdateData['address']);
            
            // Update the session data only after the database commit is successful
            $_SESSION['user'] = array_merge($_SESSION['user'], $sessionUpdateData);
        } else {
            $message = 'Error: The profile could not be updated in the database. Details: ' . ($result['error'] ?? 'Unknown error');
            $message_type = 'danger';
            error_log("Supabase Update Error: " . print_r($result, true));
        }
    }

    if ($updateSuccessful) {
        // Redirect to profile page with success message
        header("Location: specialist_profile.php?success=" . urlencode("Profile updated successfully."));
        exit;
    }
}

// Ensure the profile data used in the form is the most recent (session or fetched)
$data = $specialist; // Start with fetched data
// Use the 'address' column as 'clinic_address' for display uniformity
$data['clinic_address'] = $specialist['address'] ?? ''; 

// If POST failed, use POST data for display in the form for user correction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$updateSuccessful && isset($_POST)) {
    // Merge failed POST data back into $data for form display
    $data = array_merge($data, $_POST);
}

$specialist_name = $data['fullname'];
$initials = strtoupper(substr($specialist_name, 0, 1) . (strpos($specialist_name, ' ') !== false ? substr($specialist_name, strpos($specialist_name, ' ') + 1, 1) : ''));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Profile - MindCare Specialist</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="mobile.css" />
  <style>
    /* ------------------------------------- */
    /* CSS STYLES (Copied for consistency)  */
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
      transition: margin-left 0.3s ease; 
    }
    .content-inner {
      max-width: 900px; 
      margin: auto;
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

    /* Form Styles */
    .form-label {
        font-weight: 600;
        color: var(--text-dark);
        font-size: 0.9rem;
    }

    .form-control, .form-select {
        background-color: var(--bg-light);
        border: 1px solid var(--border-color);
        color: var(--text-dark);
        padding: 0.75rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    body.dark-mode .form-control, body.dark-mode .form-select {
        background-color: #383838;
        border-color: #4a4a4a;
        color: #f1f1f1;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-teal);
        box-shadow: 0 0 0 0.25rem rgba(90, 208, 190, 0.25);
        background-color: var(--card-bg);
    }
    
    /* Section Separation */
    .form-section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary-teal);
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--border-color);
    }
    
    body.dark-mode .form-section-title {
        border-bottom-color: #4a4a4a;
    }

    /* Buttons */
    .btn-save {
      background: linear-gradient(135deg, var(--primary-teal), var(--primary-teal-dark));
      color: white;
      border: none;
      padding: 0.75rem 2rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(90, 208, 190, 0.3);
      width: 100%;
    }

    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(90, 208, 190, 0.4);
      color: white;
      background: var(--primary-teal-dark);
    }
    
    .btn-cancel {
        background: transparent;
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        padding: 0.75rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        width: 100%;
        margin-top: 0.5rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }
    
    .btn-cancel:hover {
        background-color: rgba(90, 208, 190, 0.1);
        color: var(--primary-teal);
        border-color: var(--primary-teal);
    }

    /* Responsive */
    @media (max-width: 992px) { 
        .sidebar { transform: translateX(-250px); }
        .sidebar.show { transform: translateX(0); }
        .main-wrapper { margin-left: 0; padding-top: 5rem; }
        /* Removed manual menu-toggle display: block; logic */
        .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
    }
  </style>
</head>
<body>
  
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
        <h1>Edit Profile</h1>
      </div>

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($message) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <div class="profile-card">
        <form method="POST" action="edit-specialist-profile.php">

          <div class="form-section-title">Personal & Contact Information</div>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="fullname" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="fullname" name="fullname" value="<?= htmlspecialchars($data['fullname'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label for="email" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>" disabled title="Email cannot be changed directly">
              <small class="text-muted">Email serves as your unique identifier.</small>
            </div>
            <div class="col-md-6">
              <label for="phone" class="form-label">Phone Number</label>
              <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($data['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label for="clinic_address" class="form-label">Location / Address</label>
              <input type="text" class="form-control" id="clinic_address" name="clinic_address" value="<?= htmlspecialchars($data['address'] ?? '') ?>">
            </div>
          </div>

          <div class="form-section-title">Professional Details</div>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="specialty" class="form-label">Specialty</label>
              <input type="text" class="form-control" id="specialty" name="specialty" value="<?= htmlspecialchars($data['specialty'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label for="experience_years" class="form-label">Years of Experience</label>
              <input type="number" class="form-control" id="experience_years" name="experience_years" value="<?= htmlspecialchars($data['experience_years'] ?? '') ?>" min="0">
            </div>
          </div>

          <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
            <button type="submit" class="btn-save">Save Changes</button>
          </div>
          <a href="specialist_profile.php" class="btn-cancel">Cancel</a>
        </form>
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

// --- MOBILE MENU TOGGLE LOGIC ---
const menuToggle = document.getElementById('menuToggle'); // This variable is now intentionally null or handled by mobile.js
const sidebar = document.getElementById('sidebar');

// This manual function is no longer required if mobile.js handles the toggle button creation/logic.
/*
menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('show');
});
*/
  </script>
</body>
</html>