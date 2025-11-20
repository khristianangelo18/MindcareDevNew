<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}
include 'supabase.php';

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['fullname'] ?? 'User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = trim($_POST['fullname']);
  $gender = !empty($_POST['gender']) ? $_POST['gender'] : null;
  $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
  $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
  $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
  $height = !empty($_POST['height']) ? intval($_POST['height']) : null;
  $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
  $blood_group = !empty($_POST['blood_group']) ? trim($_POST['blood_group']) : null;
  $emergency_contact_name = !empty($_POST['emergency_contact_name']) ? trim($_POST['emergency_contact_name']) : null;
  $emergency_contact_relationship = !empty($_POST['emergency_contact_relationship']) ? trim($_POST['emergency_contact_relationship']) : null;
  $emergency_contact_phone = !empty($_POST['emergency_contact_phone']) ? trim($_POST['emergency_contact_phone']) : null;

  $bmi = null;
  if ($height && $weight) {
    $heightInMeters = $height / 100;
    $bmi = round($weight / ($heightInMeters * $heightInMeters), 2);
  }

  $updateData = [
    'fullname' => $fullname,
    'gender' => $gender,
    'age' => $age,
    'phone' => $phone,
    'address' => $address,
    'height' => $height,
    'weight' => $weight,
    'blood_group' => $blood_group,
    'bmi' => $bmi,
    'emergency_contact_name' => $emergency_contact_name,
    'emergency_contact_relationship' => $emergency_contact_relationship,
    'emergency_contact_phone' => $emergency_contact_phone
  ];

  $result = supabaseUpdate('users', ['id' => $user_id], $updateData);

  if (!isset($result['error'])) {
    $_SESSION['user']['fullname'] = $fullname;
    $_SESSION['user']['gender'] = $gender;
    $_SESSION['user']['age'] = $age;
    $_SESSION['user']['phone'] = $phone;
    $_SESSION['user']['address'] = $address;
    $_SESSION['user']['height'] = $height;
    $_SESSION['user']['weight'] = $weight;
    $_SESSION['user']['blood_group'] = $blood_group;
    $_SESSION['user']['bmi'] = $bmi;
    $_SESSION['user']['emergency_contact_name'] = $emergency_contact_name;
    $_SESSION['user']['emergency_contact_relationship'] = $emergency_contact_relationship;
    $_SESSION['user']['emergency_contact_phone'] = $emergency_contact_phone;

    header("Location: profile.php?");
    exit;
  } else {
    $error = "Failed to update profile. Please try again.";
  }
}

$users = supabaseSelect('users', ['id' => $user_id], '*', null, 1, true);

if (is_array($users) && !empty($users)) {
  $user = $users[0];
} else {
  $user = $_SESSION['user'] ?? null;
}

if (!$user || !is_array($user)) {
  error_log("ERROR: Could not fetch user data for user_id: " . $user_id);
  header("Location: logout.php");
  exit;
}

$user = array_merge([
  'id' => $user_id,
  'fullname' => '',
  'email' => '',
  'gender' => null,
  'age' => null,
  'phone' => null,
  'address' => null,
  'height' => null,
  'weight' => null,
  'blood_group' => null,
  'bmi' => null,
  'emergency_contact_name' => null,
  'emergency_contact_relationship' => null,
  'emergency_contact_phone' => null
], $user);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Profile - MindCare</title>
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
      --input-text: #2b2f38; /* NEW: Input text color for light mode */
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
      --input-text: #f1f1f1; /* NEW: Input text color for dark mode */
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
      transition: background-color 0.2s ease, color 0.2s ease;
    }

    .sidebar .nav-link:hover {
      background-color: rgba(90, 208, 190, 0.1);
      color: var(--primary-teal);
    }

    .sidebar .nav-link.active {
      background-color: #5ad0be;
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
      max-width: 1400px;
    }

    .page-header {
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .subtitle {
      color: var(--text-muted);
      font-size: 0.95rem;
    }

    .form-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: background-color 0.3s ease, border-color 0.3s ease;
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
    }

    .form-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 1.5rem;
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
    }

    .form-label-optional {
      font-size: 0.75rem;
      font-weight: 400;
      color: var(--text-muted);
      margin-left: 0.25rem;
    }

    /* FIXED: Input and select text colors */
    .form-control,
    .form-select {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 0.95rem;
      background: var(--bg-light);
      color: var(--input-text); /* Uses the color from CSS variables */
      transition: all 0.3s ease;
    }

    /* FIXED: Placeholder colors */
    .form-control::placeholder,
    .form-select::placeholder {
      color: var(--text-muted);
      opacity: 0.7;
    }

    /* FIXED: Textarea placeholder */
    textarea.form-control::placeholder {
      color: var(--text-muted);
      opacity: 0.7;
    }

    .form-control:focus,
    .form-select:focus {
      outline: none;
      border-color: var(--primary-teal);
      box-shadow: 0 0 0 3px rgba(90, 208, 190, 0.1);
      background: var(--card-bg);
      color: var(--input-text); /* Maintain text color on focus */
    }

    /* FIXED: Dark mode specific styles */
    body.dark-mode .form-control,
    body.dark-mode .form-select {
      background: #1a1a1a;
      border-color: var(--border-color);
      color: var(--input-text);
    }

    body.dark-mode .form-control:focus,
    body.dark-mode .form-select:focus {
      background: #2a2a2a;
      color: var(--input-text);
    }

    /* FIXED: Select option colors in dark mode */
    body.dark-mode .form-select option {
      background: #2a2a2a;
      color: #f1f1f1;
    }

    .button-group {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
      padding-top: 2rem;
      border-top: 1px solid var(--border-color);
    }

    .btn-save {
      background: linear-gradient(135deg, var(--primary-teal) 0%, var(--primary-teal-dark) 100%);
      color: white;
      border: none;
      padding: 0.875rem 2rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(90, 208, 190, 0.3);
      transition: all 0.3s ease;
    }

    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(90, 208, 190, 0.4);
    }

    .btn-cancel {
      background: transparent;
      color: var(--text-muted);
      border: 1px solid var(--border-color);
      padding: 0.875rem 2rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s ease;
    }

    .btn-cancel:hover {
      background: var(--bg-light);
      color: var(--text-dark);
      border-color: var(--text-muted);
    }

    .alert {
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 1.5rem;
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
      <a class="nav-link" href="dashboard.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        DASHBOARD
      </a>
      <a class="nav-link" href="resources.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

  <!-- Main Content -->
  <div class="main-wrapper">
    <div class="content-inner">
      
      <div class="page-header">
        <h1>Edit Profile</h1>
        <p class="subtitle">Update your personal information</p>
      </div>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <div class="form-card">
        <form method="POST">
          
          <div class="form-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Basic Information
          </div>

          <div class="form-group">
            <label for="fullname" class="form-label">
              Full Name <span class="form-label-optional">(required)</span>
            </label>
            <input 
              type="text" 
              id="fullname"
              name="fullname" 
              class="form-control" 
              value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" 
              placeholder="Enter your full name"
              required 
            />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="gender" class="form-label">
                Gender <span class="form-label-optional">(optional)</span>
              </label>
              <select id="gender" name="gender" class="form-select">
                <option value="">Select gender</option>
                <option value="Male" <?= ($user['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= ($user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= ($user['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
              </select>
            </div>

            <div class="form-group">
              <label for="age" class="form-label">
                Age <span class="form-label-optional">(optional)</span>
              </label>
              <input 
                type="number" 
                id="age"
                name="age" 
                class="form-control" 
                value="<?= htmlspecialchars($user['age'] ?? '') ?>" 
                placeholder="Enter your age"
                min="1"
                max="120"
              />
            </div>
          </div>

          <div class="form-section-title" style="margin-top: 2rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
            Contact Information
          </div>

          <div class="form-group">
            <label for="phone" class="form-label">
              Phone Number <span class="form-label-optional">(optional)</span>
            </label>
            <input 
              type="text" 
              id="phone"
              name="phone" 
              class="form-control" 
              value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
              placeholder="+63 912 345 6780"
            />
          </div>

          <div class="form-group">
            <label for="address" class="form-label">
              Address <span class="form-label-optional">(optional)</span>
            </label>
            <textarea 
              id="address"
              name="address" 
              class="form-control" 
              rows="3"
              placeholder="Enter your complete address"
            ><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
          </div>

          <div class="form-section-title" style="margin-top: 2rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
            Health Information
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="height" class="form-label">
                Height (cm) <span class="form-label-optional">(optional)</span>
              </label>
              <input 
                type="number" 
                id="height"
                name="height" 
                class="form-control" 
                value="<?= htmlspecialchars($user['height'] ?? '') ?>" 
                placeholder="165"
                min="1"
              />
            </div>

            <div class="form-group">
              <label for="weight" class="form-label">
                Weight (kg) <span class="form-label-optional">(optional)</span>
              </label>
              <input 
                type="number" 
                id="weight"
                name="weight" 
                class="form-control" 
                value="<?= htmlspecialchars($user['weight'] ?? '') ?>" 
                placeholder="62"
                step="0.01"
                min="1"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="blood_group" class="form-label">
              Blood Group <span class="form-label-optional">(optional)</span>
            </label>
            <select id="blood_group" name="blood_group" class="form-select">
              <option value="">Select blood group</option>
              <option value="A+" <?= ($user['blood_group'] ?? '') === 'A+' ? 'selected' : '' ?>>A+</option>
              <option value="A-" <?= ($user['blood_group'] ?? '') === 'A-' ? 'selected' : '' ?>>A-</option>
              <option value="B+" <?= ($user['blood_group'] ?? '') === 'B+' ? 'selected' : '' ?>>B+</option>
              <option value="B-" <?= ($user['blood_group'] ?? '') === 'B-' ? 'selected' : '' ?>>B-</option>
              <option value="O+" <?= ($user['blood_group'] ?? '') === 'O+' ? 'selected' : '' ?>>O+</option>
              <option value="O-" <?= ($user['blood_group'] ?? '') === 'O-' ? 'selected' : '' ?>>O-</option>
              <option value="AB+" <?= ($user['blood_group'] ?? '') === 'AB+' ? 'selected' : '' ?>>AB+</option>
              <option value="AB-" <?= ($user['blood_group'] ?? '') === 'AB-' ? 'selected' : '' ?>>AB-</option>
            </select>
          </div>

          <?php if (!empty($user['bmi'])): ?>
          <div class="form-group">
            <label class="form-label">BMI (Calculated Automatically)</label>
            <input 
              type="text" 
              class="form-control" 
              value="<?= htmlspecialchars($user['bmi']) ?>" 
              readonly
              style="background: var(--bg-light); cursor: not-allowed;"
            />
          </div>
          <?php endif; ?>

          <div class="form-section-title" style="margin-top: 2rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            Emergency Contact
          </div>

          <div class="form-group">
            <label for="emergency_contact_name" class="form-label">
              Contact Name <span class="form-label-optional">(optional)</span>
            </label>
            <input 
              type="text" 
              id="emergency_contact_name"
              name="emergency_contact_name" 
              class="form-control" 
              value="<?= htmlspecialchars($user['emergency_contact_name'] ?? '') ?>" 
              placeholder="Enter emergency contact name"
            />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="emergency_contact_relationship" class="form-label">
                Relationship <span class="form-label-optional">(optional)</span>
              </label>
              <input 
                type="text" 
                id="emergency_contact_relationship"
                name="emergency_contact_relationship" 
                class="form-control" 
                value="<?= htmlspecialchars($user['emergency_contact_relationship'] ?? '') ?>" 
                placeholder="Spouse, Parent, Sibling, etc."
              />
            </div>

            <div class="form-group">
              <label for="emergency_contact_phone" class="form-label">
                Contact Phone <span class="form-label-optional">(optional)</span>
              </label>
              <input 
                type="text" 
                id="emergency_contact_phone"
                name="emergency_contact_phone" 
                class="form-control" 
                value="<?= htmlspecialchars($user['emergency_contact_phone'] ?? '') ?>" 
                placeholder="+63 912 345 6789"
              />
            </div>
          </div>

          <div class="button-group">
            <button type="submit" class="btn-save">Save Changes</button>
            <a href="profile.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
   const toggleBtn = document.getElementById('themeToggle');
const icon = document.getElementById('themeIcon');
const label = document.getElementById('themeLabel');

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
  </script>
</body>
</html>