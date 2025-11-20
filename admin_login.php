<?php
session_start();
include 'supabase.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  // Validate input
  if (empty($email) || empty($password)) {
    $error = "Email and password are required.";
  } else {
    // Debug: Log the email being searched
    error_log("Admin login attempt for email: " . $email);

    // Get user by email using Supabase REST API with RLS bypass
    $users = supabaseSelect('users', ['email' => $email, 'role' => 'Specialist'], '*', null, null, true);

    // Debug: Log the response
    error_log("Supabase response count: " . count($users));

    if (empty($users)) {
      error_log("No admin/specialist found for email: " . $email);
      $error = "Admin account not found.";
    } else {
      $user = $users[0];

      // Verify password using bcrypt
      if (!password_verify($password, $user['password'])) {
        error_log("Incorrect password for email: " . $email);
        $error = "Incorrect password.";
      } else {
        // Set session data
        $_SESSION['user'] = [
          'id' => $user['id'],
          'fullname' => $user['fullname'],
          'email' => $user['email'],
          'role' => $user['role']
        ];

        error_log("Admin login successful for user ID: " . $user['id']);
        
        // Redirect to specialist dashboard
        header("Location: specialist_dashboard.php");
        exit;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login | MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    :root {
      --teal-1: #5ad0be;
      --teal-2: #1aa592;
      --teal-3: #0a6a74;
      --line: #e9edf5;
      --field-bg: #f6f7fb;
      --field-text: #2b2f38;
      --muted: #7a828e;
      --btn-from: #38c7a3;
      --btn-to: #2fb29c;
      --bg-white: #ffffff;
      --alert-danger-bg: #ffe6e8;
      --alert-danger-text: #9b1c1f;
      --info-side-gradient-start: var(--teal-1);
      --info-side-gradient-mid: var(--teal-2);
      --info-side-gradient-end: var(--teal-3);
      --toggle-text-color: #2b2f38;
    }

    body.dark-mode {
      --field-bg: #2a2a2a;
      --field-text: #f1f1f1;
      --muted: #b0b0b0;
      --line: #3a3a3a;
      --bg-white: #1a1a1a;
      --alert-danger-bg: rgba(244, 67, 54, 0.2);
      --alert-danger-text: #ef5350;
      --info-side-gradient-start: #0a6a74;
      --info-side-gradient-mid: #1aa592;
      --info-side-gradient-end: #2e7d32;
      --toggle-text-color: #5ad0be;
    }

    body {
      font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      color: var(--field-text);
      min-height: 100vh;
      overflow: hidden;
      background: var(--bg-white);
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .admin-login-page {
      min-height: 100vh;
      overflow: hidden;
      background: var(--bg-white);
    }

    .admin-login-page .row {
      min-height: 100vh;
    }

    .theme-toggle-btn {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(0, 0, 0, 0.1);
      color: var(--toggle-text-color);
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

    .info-side {
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      text-align: left;
      padding: 0 72px;
      color: #fff;
      background:
        radial-gradient(900px 500px at -10% 115%, rgba(255,255,255,.15) 0%, transparent 60%),
        linear-gradient(135deg, var(--info-side-gradient-start) 0%, var(--info-side-gradient-mid) 48%, var(--info-side-gradient-end) 100%);
      border-right: 1px solid var(--line);
      overflow: hidden;
      transition: background 0.5s ease;
    }

    .info-side::before,
    .info-side::after {
      content: '';
      position: absolute;
      bottom: -180px;
      left: -180px;
      border-radius: 50%;
      border: 1px solid rgba(255,255,255,.25);
      pointer-events: none;
    }
    
    .info-side::before {
      width: 520px; 
      height: 520px;
    }
    
    .info-side::after {
      width: 700px; 
      height: 700px;
      border-color: rgba(255,255,255,.15);
    }

    .info-side img {
      height: 200px;
      width: auto;
      margin: 0 0 24px 0;
      transition: opacity 0.3s ease;
    }

    .info-side h4 {
      font-size: 40px;
      line-height: 1.1;
      font-weight: 700;
      margin: 8px 0 10px;
      color: #fff;
    }

    .info-side p {
      font-size: 16px;
      color: rgba(255,255,255,.9);
      margin-bottom: 18px;
    }

    .info-side .text-muted {
      color: rgba(255,255,255,.85) !important;
      font-weight: 500;
    }

    .admin-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 20px;
      background: rgba(255,255,255,.20);
      color: #fff;
      border-radius: 999px;
      font-size: 0.95rem;
      font-weight: 600;
      margin-bottom: 1rem;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.25);
    }

    .form-side {
      background: var(--bg-white);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px;
      transition: background-color 0.3s ease;
    }

    .form-container {
      background: transparent;
      box-shadow: none;
      border-radius: 0;
      width: 420px;
      max-width: 90%;
      padding: 0;
      text-align: left;
    }

    .form-container h3 {
      font-size: 28px;
      font-weight: 700;
      color: var(--field-text);
      margin-bottom: 6px;
      transition: color 0.3s ease;
    }

    .form-container small {
      color: var(--muted) !important;
      transition: color 0.3s ease;
    }

    .form-container .alert {
      border: none;
      border-radius: 10px;
      margin-bottom: 20px;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .form-container .alert-danger {
      background: var(--alert-danger-bg);
      color: var(--alert-danger-text);
    }

    .form-container input {
      background-color: var(--field-bg);
      color: var(--field-text);
      border: 2px solid transparent;
      height: 52px;
      border-radius: 999px;
      padding: 12px 18px;
      font-size: 15px;
      box-shadow: 0 1px 0 rgba(0,0,0,0.02), 0 8px 24px rgba(18,38,63,0.03);
      transition: all 0.3s ease;
    }

    body.dark-mode .form-container input {
      background-color: var(--field-bg);
      color: var(--field-text);
    }

    .form-container input::placeholder {
      color: var(--muted);
      opacity: 0.7;
    }

    .form-container input:focus {
      background-color: var(--field-bg);
      box-shadow: 0 0 0 3px rgba(56,199,163,0.18);
      outline: none;
      border-color: var(--teal-2);
    }

    body.dark-mode .form-container input:focus {
      background-color: #333;
      box-shadow: 0 0 0 3px rgba(90, 208, 190, 0.25);
    }

    .password-wrapper {
      position: relative;
    }

    .password-wrapper input {
      padding-right: 48px;
    }

    .toggle-password {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      cursor: pointer;
      z-index: 2;
      transition: color 0.3s ease;
    }

    body.dark-mode .toggle-password {
      color: var(--muted);
    }

    .toggle-password:hover {
      color: var(--teal-2);
    }

    body.dark-mode .toggle-password:hover {
      color: var(--teal-1);
    }

    .form-container button {
      background: linear-gradient(135deg, var(--btn-from) 0%, var(--btn-to) 100%);
      border: none;
      height: 56px;
      border-radius: 999px;
      font-weight: 600;
      font-size: 16px;
      letter-spacing: .2px;
      color: #fff;
      width: 100%;
      box-shadow: 0 10px 24px rgba(48,170,153,.35);
      transition: transform .15s ease, box-shadow .2s ease;
    }

    .form-container button:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 28px rgba(48,170,153,.42);
    }

    .form-container a {
      color: #7c8a99;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
      font-size: 14px;
    }

    .form-container a:hover {
      color: #5d6a78;
      text-decoration: underline;
    }

    body.dark-mode .form-container a {
      color: #90caf9 !important;
    }

    body.dark-mode .form-container a:hover {
      color: #64b5f6 !important;
    }

    .fade-in {
      animation: fadeInUp .9s ease both;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body class="admin-login-page">
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

  <div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">

      <!-- Left Side: Logo + Info -->
      <div class="col-md-6 info-side">
        <img src="images/MindCare.png" alt="MindCare Logo" class="img-fluid" id="logoImage" />
        <p class="text-muted fst-italic">Where healing meets understanding.</p>
        
        <div class="admin-badge">
          <i class="fas fa-user-shield"></i>
          <span>Specialist Portal</span>
        </div>
        
        <p>Secure access for mental health professionals</p>
      </div>

      <!-- Right Side: Admin Login Form -->
      <div class="col-md-6 form-side">
        <div class="form-container fade-in">
          <h3 class="mb-1 fw-bold text-start">Admin Login</h3>
          <small class="d-block mb-4 text-start">Access specialist dashboard</small>
          
          <?php if (isset($error)): ?>
            <div class="alert alert-danger text-start">
              <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <form method="POST" id="adminLoginForm">
            <input type="email" name="email" class="form-control mb-3" placeholder="Email Address" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            <div class="mb-3 password-wrapper">
              <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
              <span class="toggle-password" onclick="togglePassword()">
                <i id="toggleIcon" class="fa-solid fa-eye"></i>
              </span>
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-sign-in-alt"></i> Login as Admin
            </button>
          </form>
          
          <div class="mt-3 text-center">
            <a href="login.php">
              <i class="fas fa-arrow-left"></i> Back to patient login
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script>
    const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
    const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';

    // Password toggle function
    function togglePassword() {
      const passwordField = document.getElementById("password");
      const toggleIcon = document.getElementById("toggleIcon");
      if (passwordField.type === "password") {
        passwordField.type = "text";
        toggleIcon.classList.remove("fa-eye");
        toggleIcon.classList.add("fa-eye-slash");
      } else {
        passwordField.type = "password";
        toggleIcon.classList.remove("fa-eye-slash");
        toggleIcon.classList.add("fa-eye");
      }
    }

    // Dark Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeLabel = document.getElementById('themeLabel');
    const logoImage = document.getElementById('logoImage');

    // Function to update logo based on theme
    function updateLogo(isDark) {
      if (isDark) {
        logoImage.src = 'images/MindCare.png';
      } else {
        logoImage.src = 'images/MindCare.png';
      }
    }

    // Check for saved theme preference
    const prefersDark = localStorage.getItem('dark-mode') === 'true';
    if (prefersDark) {
      document.body.classList.add('dark-mode');
      themeIcon.innerHTML = moonIcon;
      themeLabel.textContent = 'Dark Mode';
      updateLogo(true);
    }

    // Toggle theme
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode');
      const isDark = document.body.classList.contains('dark-mode');
      localStorage.setItem('dark-mode', isDark);
      
      // Animate icon
      themeIcon.classList.add('rotate');
      setTimeout(() => themeIcon.classList.remove('rotate'), 500);
      
      // Update icon and label
      themeIcon.innerHTML = isDark ? moonIcon : sunIcon;
      themeLabel.textContent = isDark ? 'Dark Mode' : 'Light Mode';
      
      // Update logo
      updateLogo(isDark);
    });
  </script>
</body>
</html>