<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

include 'supabase.php';

$user_id = $_SESSION['user']['id'];
$user_email = $_SESSION['user']['email'];
$user_role = $_SESSION['user']['role'] ?? 'Patient'; // Default to Patient
$message = '';
$message_type = '';

// Determine sidebar and profile redirect based on role
$is_specialist = ($user_role === 'Specialist');
$dashboard_link = $is_specialist ? 'specialist_dashboard.php' : 'dashboard.php';
$profile_link = $is_specialist ? 'specialist_profile.php' : 'profile.php';
$logout_link = $is_specialist ? 'admin_logout.php' : 'logout.php'; // Assuming specialist uses admin logout

// Check for redirection message from a failed POST attempt
$redirect_message = $_GET['message'] ?? '';
$redirect_type = $_GET['type'] ?? '';
$target_field = $_GET['field'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Server-side Validation
    $validation_message = '';
    $validation_field = '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $validation_message = "All password fields must be filled out.";
    } elseif ($new_password !== $confirm_password) {
        $validation_message = "New password and confirmation password do not match.";
        $validation_field = 'confirm_password';
    } elseif (strlen($new_password) < 6) {
        $validation_message = "New password must be at least 6 characters long.";
        $validation_field = 'new_password';
    } elseif ($new_password === $current_password) {
        $validation_message = "New password must be different from the current password.";
        $validation_field = 'new_password';
    }

    if ($validation_message) {
        // Redirect back with validation error message and field to highlight
        header("Location: change-password.php?message=" . urlencode($validation_message) . "&type=danger&field=" . urlencode($validation_field));
        exit;
    }
    
    // 2. Verification and Update (Database Interaction) 

    // Fetch the user's current hashed password from the database
    $users = supabaseSelect('users', ['id' => $user_id], 'id,password', null, 1, true);

    if (empty($users)) {
        session_destroy();
        header("Location: login.php?error_message=" . urlencode("Authentication error. Please log in again."));
        exit;
    }

    $user = $users[0];
    $current_hashed_password = $user['password'];

    // Verify the current password provided by the user against the stored hash
    if (!password_verify($current_password, $current_hashed_password)) {
        // Redirect back with a SPECIFIC error message for the current password field
        header("Location: change-password.php?message=" . urlencode("Incorrect current password.") . "&type=danger&field=current_password");
        exit;
    } 
    
    // --- If Verification is Successful, Proceed with Update ---
    
    // Hash New Password 
    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password in database
    $result = supabaseUpdate(
        'users',
        ['id' => $user_id],
        ['password' => $hashed_new_password],
        true // Bypass RLS
    );

    if (isset($result['error'])) {
        error_log("Password change error: " . json_encode($result));
        header("Location: change-password.php?message=" . urlencode("Failed to update password due to a system error. Please try again.") . "&type=danger");
        exit;
    }

    // Success - Redirect to profile page (user stays logged in)
    header("Location: " . $profile_link . "?success=" . urlencode("Your password has been successfully updated."));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Change Password - MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="mobile.css" /> <style>
    /* Reuse styles from profile/edit-profile for consistency */
    :root {
      --primary-teal: #5ad0be;
      --primary-teal-dark: #1aa592;
      --text-dark: #2b2f38;
      --text-muted: #7a828e;
      --bg-light: #f8f9fa;
      --sidebar-bg: #f5f6f7;
      --card-bg: #ffffff;
      --border-color: #e9edf5;
      --input-text: #2b2f38;
      
      /* --- REGISTER.PHP VALIDATION STYLES (COPIED) --- */
      --teal-2: #1aa592;
      --field-bg: #f6f7fb;
      --field-text: #2b2f38;
      --muted: #7a828e;
      --btn-from: #38c7a3;
      --btn-to: #2fb29c;
      --error-color: #dc3545;
      --success-color: #28a745;
      --alert-danger-bg: #ffe6e8;
      --alert-danger-text: #9b1c1f;
      --alert-success-bg: #d4edda;
      --alert-success-text: #155724;
    }

    body.dark-mode {
      --bg-light: #1a1a1a;
      --sidebar-bg: #2a2a2a;
      --card-bg: #2a2a2a;
      --text-dark: #f1f1f1;
      --text-muted: #b0b0b0;
      --border-color: #3a3a3a;
      --input-text: #f1f1f1;
      
      /* --- REGISTER.PHP VALIDATION STYLES (COPIED) --- */
      --field-bg: #2a2a2a;
      --field-text: #f1f1f1;
      --muted: #b0b0b0;
      --error-color: #ef5350;
      --success-color: #66bb6a;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-light);
      color: var(--text-dark);
      transition: background-color 0.3s ease, color 0.3s ease;
    }

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
      transition: transform 0.3s ease; 
    }
    
    @media (min-width: 993px) {
        body {
            padding-left: 250px;
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
      text-transform: uppercase;
      letter-spacing: 0.5px;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .sidebar .nav-link:hover {
      background-color: rgba(90, 208, 190, 0.1);
      color: var(--primary-teal);
    }

    .sidebar .nav-link.active {
      background-color: #5ad0be;
      color: white;
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
      padding: 2rem;
      max-width: 700px;
      margin: 0 auto;
      transition: padding-left 0.3s ease; 
    }
    
    @media (max-width: 992px) { /* Tablet and Mobile */
        body {
            padding-left: 0; 
            padding-top: 5rem; 
        }
        .sidebar {
            transform: translateX(-250px);
            width: 250px;
        }
        .main-wrapper {
            padding-top: 1.5rem; 
        }
    }
    
    .page-header h1 {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .form-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    body.dark-mode .form-card {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
    
    .form-section-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 1.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 2px solid var(--primary-teal);
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: color 0.3s ease;
    }
    
    body.dark-mode .form-section-title {
        color: var(--text-dark); /* Ensure visibility in dark mode */
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-label {
      display: block;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
      transition: color 0.3s ease;
    }
    
    /* --- INPUT STYLING FOR CONSISTENCY --- */
    
    .input-wrapper {
      position: relative;
    }
    
    .form-control {
      background-color: var(--field-bg);
      border: 2px solid transparent;
      height: 52px;
      border-radius: 999px;
      padding: 12px 18px;
      font-size: 15px;
      color: var(--field-text);
      box-shadow: 0 1px 0 rgba(0,0,0,0.02), 0 8px 24px rgba(18,38,63,0.03);
      transition: all 0.3s ease;
      width: 100%;
      padding-left: 52px; /* For lock icon space */
      padding-right: 48px; /* For eye icon space */
    }
    
    body.dark-mode .form-control {
        border-color: rgba(255, 255, 255, 0.1); 
        background-color: var(--field-bg);
        color: var(--input-text) !important; 
    }
    
    .form-control::placeholder {
      color: var(--muted);
      opacity: 0.7;
    }
    
    .form-control:focus {
      background-color: var(--field-bg);
      box-shadow: 0 0 0 3px rgba(56,199,163,0.18);
      outline: none;
      border-color: var(--teal-2);
    }
    
    body.dark-mode .form-control:focus {
      background-color: #333;
      box-shadow: 0 0 0 3px rgba(90, 208, 190, 0.25);
    }
    
    /* Password lock icon styling */
    .input-wrapper::before{
      content: "\f023";
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
      font-size: 16px;
      color: #99A3AE;
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      z-index: 1;
      pointer-events: none;
    }
    
    body.dark-mode .input-wrapper::before {
      color: #b0b0b0;
    }

    /* Eye toggle styling */
    .toggle-password {
      position: absolute;
      right: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #99A3AE;
      cursor: pointer;
      z-index: 2;
      transition: color 0.3s ease;
    }

    body.dark-mode .toggle-password {
      color: #b0b0b0;
    }

    /* Input error/success state */
    .form-control.error {
      border-color: var(--error-color);
      background-color: var(--field-bg);
    }

    .form-control.success {
      border-color: var(--success-color);
      background-color: var(--field-bg);
    }
    
    /* Validation Message box */
    .validation-message {
      font-size: 13px;
      margin-top: 6px;
      margin-bottom: 0;
      padding-left: 4px;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.3s ease;
    }

    .validation-message.error {
      color: var(--error-color);
    }

    .validation-message.success {
      color: var(--success-color);
    }

    .validation-message i {
      font-size: 12px;
    }
    
    /* --- END INPUT STYLING --- */
    
    .button-group {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
      padding-top: 2rem;
      border-top: 1px solid var(--border-color);
      align-items: center; 
    }

    .btn-save {
      background: linear-gradient(135deg, var(--btn-from), var(--btn-to));
      border: none;
      height: 56px;
      border-radius: 8px; 
      font-weight: 600;
      color: white;
      padding: 0.875rem 2rem;
      cursor: pointer;
      box-shadow: 0 10px 24px rgba(48,170,153,.35);
      transition: all 0.3s ease;
      flex-grow: 1; 
      line-height: 1.5; 
    }
    
    .btn-save:hover {
        transform: translateY(-1px);
    }

    .btn-cancel {
      display: flex; 
      align-items: center; 
      justify-content: center; 
      height: 56px; 
      
      background: transparent;
      color: var(--text-muted);
      border: 1px solid var(--border-color);
      padding: 0.875rem 2rem;
      border-radius: 8px; 
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      flex-grow: 1; 
      
    }
    
    .alert {
        margin-top: 2rem;
    }
  </style>
</head>
<body>
  
  <div class="sidebar" id="sidebar">
    <div class="logo-wrapper">
      <img src="images/Mindcare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <nav class="nav flex-column" style="flex: 1;">
      
      <?php if (!$is_specialist): ?>
        <a class="nav-link" href="dashboard.php">
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
        <a class="nav-link active" href="profile.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          PROFILE
        </a>
        <a class="nav-link" href="faq.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          FAQS
        </a>
        
      <?php else: ?>
        <a href="specialist_dashboard.php" class="nav-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          DASHBOARD
        </a>
        <a href="specialist_profile.php" class="nav-link active">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          PROFILE
        </a>
      <?php endif; ?>
      
    </nav>

    <div class="theme-toggle">
      <button id="themeToggle">
        <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line>
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
          <line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
        <span id="themeLabel">Light Mode</span>
      </button>
    </div>

    <a href="<?= $logout_link ?>" class="nav-link" style="margin-top: 1rem; color: #ef5350; border-top: 1px solid var(--border-color); padding-top: 1rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      LOGOUT
    </a>
  </div>

  <div class="main-wrapper">
    <div class="page-header">
        <h1>Change Password</h1>
    </div>
    
    <?php if ($redirect_message && $redirect_type === 'danger' && $target_field === ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($redirect_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" id="passwordForm">
            <div class="form-section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Update Your Security Credentials
            </div>

            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="current_password"
                        name="current_password" 
                        class="form-control" 
                        placeholder="Enter your current password"
                        required 
                        autocomplete="current-password"
                    />
                    <span class="toggle-password" onclick="togglePasswordVisibility('current_password', this)">
                        <i id="toggleCurrent" class="fa-solid fa-eye"></i>
                    </span>
                </div>
                <p class="validation-message" id="current_password_error">
                  <?php if ($redirect_message && $target_field === 'current_password'): ?>
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($redirect_message) ?>
                  <?php endif; ?>
                </p>
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label">New Password (Min 6 characters)</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="new_password"
                        name="new_password" 
                        class="form-control" 
                        placeholder="Enter new password"
                        required 
                        autocomplete="new-password"
                    />
                     <span class="toggle-password" onclick="togglePasswordVisibility('new_password', this)">
                        <i id="toggleNew" class="fa-solid fa-eye"></i>
                    </span>
                </div>
                 <p class="validation-message" id="new_password_error">
                    <?php if ($redirect_message && $target_field === 'new_password'): ?>
                      <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($redirect_message) ?>
                    <?php endif; ?>
                 </p>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="confirm_password"
                        name="confirm_password" 
                        class="form-control" 
                        placeholder="Confirm new password"
                        required 
                        autocomplete="new-password"
                    />
                    <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)">
                        <i id="toggleConfirm" class="fa-solid fa-eye"></i>
                    </span>
                </div>
                 <p class="validation-message" id="confirm_password_error">
                     <?php if ($redirect_message && $target_field === 'confirm_password'): ?>
                      <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($redirect_message) ?>
                    <?php endif; ?>
                 </p>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-save" id="submitBtn">Update Password</button>
                <a href="<?= $profile_link ?>" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
  </div>

  <script src="mobile.js"></script> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // --- Dark Mode Toggle (Reused) ---
    const toggleBtn = document.getElementById('themeToggle');
    const icon = document.getElementById('themeIcon');
    const label = document.getElementById('themeLabel');
    const sidebar = document.getElementById('sidebar'); 

    const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
    const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';

    const prefersDark = localStorage.getItem('dark-mode') === 'true';
    if (prefersDark) {
      document.body.classList.add('dark-mode');
      icon.innerHTML = moonIcon;
      label.textContent = 'Dark Mode';
    }

    toggleBtn.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode');
      const isDark = document.body.classList.contains('dark-mode');
      localStorage.setItem('dark-mode', isDark);
      
      icon.style.transform = 'rotate(360deg)';
      setTimeout(() => icon.style.transform = 'rotate(0deg)', 500);
      
      icon.innerHTML = isDark ? moonIcon : sunIcon;
      label.textContent = isDark ? 'Dark Mode' : 'Light Mode';
    });
    icon.style.transition = 'transform 0.5s ease';
    
    
    // --- Eye Toggle Functionality ---
    function togglePasswordVisibility(fieldId, iconElement) {
        const field = document.getElementById(fieldId);
        const icon = iconElement.querySelector('i');
        
        if (field.type === "password") {
            field.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            field.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
    window.togglePasswordVisibility = togglePasswordVisibility;
    
    // --- Validation Functions (Copied from register.php) ---
    function showValidation(input, message, text, isError) {
      // Clear previous states
      input.classList.remove('success', 'error');
      message.classList.remove('success', 'error');
      
      if (isError) {
        input.classList.add('error');
        message.classList.add('error');
        message.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${text}`;
      } else {
        input.classList.add('success');
        message.classList.add('success');
        message.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${text}`;
      }
    }

    function clearValidation(input, message) {
      input.classList.remove('error', 'success');
      message.classList.remove('error', 'success');
      message.innerHTML = '';
    }

    // --- Form elements and validation event listeners ---
    const form = document.getElementById('passwordForm');
    const submitBtn = document.getElementById('submitBtn');
    
    const currentPass = document.getElementById('current_password');
    const newPass = document.getElementById('new_password');
    const confirmPass = document.getElementById('confirm_password');
    
    const currentError = document.getElementById('current_password_error');
    const newError = document.getElementById('new_password_error');
    const confirmError = document.getElementById('confirm_password_error');


    // Validation helper
    function validatePasswordFields(inputField = null, isSubmission = false) {
        let isValid = true;
        
        const currentVal = currentPass.value;
        const newVal = newPass.value;
        const confirmVal = confirmPass.value;

        // Function to run validation on a field only if it's the target or non-empty/submission
        const shouldValidate = (field) => isSubmission || field.value.length > 0;
        
        // --- Current Password (Required Check) ---
        if (inputField === currentPass || isSubmission) {
            if (currentVal.length === 0) {
                 showValidation(currentPass, currentError, 'Current password is required.', true);
                 isValid = false;
            } else if (currentPass.value.length > 0 && !isSubmission) {
                 showValidation(currentPass, currentError, 'Ready for verification.', false);
                 // Clear redirect error
                 currentError.innerHTML = '';
            }
        } else if (!shouldValidate(currentPass)) {
             clearValidation(currentPass, currentError);
        }

        // --- New Password Length/Required Check ---
        if (inputField === newPass || isSubmission || newVal.length > 0) {
            if (newVal.length === 0) {
                showValidation(newPass, newError, 'New password is required.', true);
                isValid = false;
            } else if (newVal.length < 6) {
                showValidation(newPass, newError, 'Password must be at least 6 characters.', true);
                isValid = false;
            } else {
                showValidation(newPass, newError, 'Strong password.', false);
                // Clear redirect error
                newError.innerHTML = '';
            }
        } else if (!shouldValidate(newPass)) {
            clearValidation(newPass, newError);
        }

        // --- New Password Different from Current (Run only if both are filled and new is valid length) ---
        if (newVal.length >= 6 && currentVal.length > 0 && newVal === currentVal) {
            showValidation(newPass, newError, 'New password must be different from the current password.', true);
            isValid = false;
        }


        // --- Passwords Match Check --- 
        if (inputField === confirmPass || isSubmission || confirmVal.length > 0) {
            if (newVal.length > 0) {
                if (confirmVal.length === 0) {
                    showValidation(confirmPass, confirmError, 'Please confirm your new password.', true);
                    isValid = false;
                } else if (newVal !== confirmVal) {
                    showValidation(confirmPass, confirmError, 'Passwords do not match.', true);
                    isValid = false;
                } else if (newVal.length >= 6) {
                    showValidation(confirmPass, confirmError, 'Passwords match.', false);
                    // Clear redirect error
                    confirmError.innerHTML = '';
                }
            } else if (confirmVal.length > 0) {
                showValidation(confirmPass, confirmError, 'New password is required first.', true);
                isValid = false;
            }
        } else if (!shouldValidate(confirmPass)) {
            clearValidation(confirmPass, confirmError);
        }
        
        // Final check to see if all required fields were filled without errors
        if (isSubmission && (!currentPass.value || !newPass.value || !confirmPass.value)) {
            isValid = false;
        }

        return isValid;
    }


    // Attach validation events
    currentPass.addEventListener('blur', () => validatePasswordFields(currentPass));
    newPass.addEventListener('blur', () => validatePasswordFields(newPass));
    confirmPass.addEventListener('blur', () => validatePasswordFields(confirmPass));
    currentPass.addEventListener('input', () => validatePasswordFields(currentPass));
    newPass.addEventListener('input', () => validatePasswordFields(newPass));
    confirmPass.addEventListener('input', () => validatePasswordFields(confirmPass));


    // Form submission validation
    form.addEventListener('submit', function(e) {
      
      let isValid = validatePasswordFields(null, true); 

      if (!isValid) {
        e.preventDefault();
        // Scroll to first error
        const firstError = document.querySelector('.validation-message.error');
        if (firstError) {
          firstError.closest('.mb-3').scrollIntoView({ behavior: 'smooth', block: 'center' });
          firstError.closest('.mb-3').querySelector('.form-control').focus();
        }
      } else {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
      }
    });

    // --- Server-side error display cleanup ---
    // The PHP block handles displaying the initial error message after redirect.
    // We only need to remove the client-side class additions here.

    const initialErrorCheck = (fieldId, errorContainerId) => {
        const errorContainer = document.getElementById(errorContainerId);
        const input = document.getElementById(fieldId);
        if (errorContainer.innerHTML.trim() !== '') {
            input.classList.add('error');
        }
    };
    
    initialErrorCheck('current_password', 'current_password_error');
    initialErrorCheck('new_password', 'new_password_error');
    initialErrorCheck('confirm_password', 'confirm_password_error');

  </script>
</body>
</html>