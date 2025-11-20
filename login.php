<?php
session_start();
if (isset($_SESSION['user']))
  header("Location: dashboard.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>MindCare Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    /* Dark Mode Variables */
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
      --alert-success-bg: #d4edda;
      --alert-success-text: #155724;
      /* FIXED: Added gradient variables */
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
      --alert-success-bg: rgba(76, 175, 80, 0.2);
      --alert-success-text: #81c784;
      /* FIXED: Added dark mode gradient adjustments */
      --info-side-gradient-start: #0a6a74;
      --info-side-gradient-mid: #1aa592;
      --info-side-gradient-end: #2e7d32;
      --toggle-text-color: #5ad0be;
    }

    body {
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    body.dark-mode {
      background: #1a1a1a !important;
    }

    /* Dark Mode Toggle Button */
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
      color: var(--toggle-text-color);
    }

    .theme-toggle-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    .theme-icon {
      font-size: 18px;
      transition: transform 0.5s ease;
    }

    .theme-icon.rotate {
      transform: rotate(360deg);
    }

    /* Info Side Gradient - FIXED: Now uses CSS variables */
    .info-side {
      background:
        radial-gradient(900px 500px at -10% 115%, rgba(255,255,255,.15) 0%, transparent 60%),
        linear-gradient(135deg, var(--info-side-gradient-start) 0%, var(--info-side-gradient-mid) 48%, var(--info-side-gradient-end) 100%);
      transition: background 0.5s ease; /* FIXED: Added smooth transition */
    }

    /* Logo Image - FIXED SIZE */
    .info-side .logo-img {
      height: 200px;
      width: auto;
      margin: 0 0 24px 0;
      transition: opacity 0.3s ease;
    }

    /* Login Form Side - Match Register styling */
    .login-form-side {
      background: var(--bg-white);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px;
      transition: background-color 0.3s ease;
    }

    body.dark-mode .login-form-side {
      background: #1a1a1a !important;
    }

    /* Login Container - Match Register.php styling */
    .login-container {
      background: transparent;
      box-shadow: none;
      border-radius: 0;
      width: 420px;
      max-width: 90%;
      padding: 0;
      text-align: left;
    }

    /* Login Container */
    .login-container h3 {
      color: var(--field-text);
      transition: color 0.3s ease;
    }

    /* FIX: "Welcome Back" text must be visible in dark mode */
    .login-container small {
      color: var(--muted) !important;
      transition: color 0.3s ease;
    }

    /* Input Fields - Match Register.php styling */
    .login-container input[type="email"],
    .login-container input[type="password"],
    .login-container input[type="text"] {
      background-color: var(--field-bg);
      border: none;
      height: 52px;
      border-radius: 999px;
      padding: 12px 18px;
      font-size: 15px;
      color: var(--field-text);
      box-shadow: 0 1px 0 rgba(0,0,0,0.02), 0 8px 24px rgba(18,38,63,0.03);
      transition: all 0.3s ease;
    }

    .login-container input[type="email"]::placeholder,
    .login-container input[type="password"]::placeholder,
    .login-container input[type="text"]::placeholder {
      color: var(--muted);
      opacity: 0.7;
    }

    body.dark-mode .login-container input[type="email"]::placeholder,
    body.dark-mode .login-container input[type="password"]::placeholder,
    body.dark-mode .login-container input[type="text"]::placeholder {
      color: #b0b0b0 !important;
      opacity: 0.7;
    }

    /* FIX: Email icon visible in BOTH light and dark mode */
    .login-container input[type="email"] {
      padding-left: 52px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2399A3AE' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='14' rx='2' ry='2'/%3E%3Cpolyline points='22,7 12,13 2,7'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 18px center;
      background-size: 18px 18px;
    }

    body.dark-mode .login-container input[type="email"] {
      background-color: #2a2a2a !important;
      color: #f1f1f1 !important;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23b0b0b0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='14' rx='2' ry='2'/%3E%3Cpolyline points='22,7 12,13 2,7'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 18px center;
      background-size: 18px 18px;
    }

    /* Dark mode password fields */
    body.dark-mode .login-container input[type="password"],
    body.dark-mode .login-container input[type="text"] {
      background-color: #2a2a2a !important;
      color: #f1f1f1 !important;
    }

    /* Password wrapper */
    .password-wrapper {
      position: relative;
    }

    .password-wrapper input[type="password"],
    .password-wrapper input[type="text"] {
      padding-left: 52px !important;
      padding-right: 48px !important;
    }

    /* Password lock icon */
    .password-wrapper::before {
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

    body.dark-mode .password-wrapper::before {
      color: #b0b0b0;
    }

    .toggle-password {
      position: absolute;
      right: 14px;
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

    body.dark-mode .login-container input:focus {
      background-color: #333 !important;
      color: #f1f1f1 !important;
      box-shadow: 0 0 0 3px rgba(90, 208, 190, 0.25);
    }

    /* Alert - Dark Mode */
    .login-container .alert {
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    body.dark-mode .login-container .alert {
      background: rgba(244, 67, 54, 0.2);
      color: #ef5350;
    }

    /* FIX: Links must be visible in dark mode */
    .login-container a {
      color: #7c8a99;
      text-decoration: none;
      transition: color 0.3s ease;
    }

    .login-container a:hover {
      text-decoration: underline;
    }

    body.dark-mode .login-container a {
      color: #90caf9 !important;
    }

    body.dark-mode .login-container a:hover {
      color: #64b5f6 !important;
    }

    .login-container a.text-primary {
      color: var(--teal-2) !important;
      font-weight: 600;
    }

    body.dark-mode .login-container a.text-primary {
      color: var(--teal-1) !important;
    }

    /* Login Button with Gradient */
    .login-container button[type="submit"],
    .login-container .btn-primary {
      background: linear-gradient(135deg, var(--btn-from) 0%, var(--btn-to) 100%) !important;
      border: none !important;
      height: 56px;
      border-radius: 999px;
      font-weight: 600;
      font-size: 16px;
      letter-spacing: .2px;
      color: #fff !important;
      width: 100%;
      box-shadow: 0 10px 24px rgba(48,170,153,.35);
      transition: transform .15s ease, box-shadow .2s ease, opacity .2s ease;
    }

    .login-container button[type="submit"]:hover,
    .login-container .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 28px rgba(48,170,153,.42);
      background: linear-gradient(135deg, var(--btn-from) 0%, var(--btn-to) 100%) !important;
    }

    /* Password wrapper styles */
    .password-wrapper {
      position: relative;
    }

    .password-wrapper input {
      padding-right: 48px;
    }
  </style>
</head>
<body class="login-page">
  <!-- Dark Mode Toggle -->
  <button class="theme-toggle-btn" id="themeToggle">
    <span class="theme-icon" id="themeIcon">🌞</span>
    <span id="themeLabel">Light</span>
  </button>

  <div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">

      <!-- Left Side: Logo + Pre-Assessment -->
      <div class="col-md-6 info-side">
        <img src="images/MindCare.png" alt="MindCare Logo" class="img-fluid logo-img" id="logoImage" />
        <p class="text-muted text-center fst-italic">Where healing meets understanding.</p>
        <p>Take a Quick Pre-Assessment</p>
        <a href="pre_assessment.php" class="btn btn-outline-primary">Start Here</a>
      </div>

      <!-- Right Side: Login Form -->
      <div class="col-md-6 login-form-side">
        <div class="login-container fade-in text-center">
          <h3 class="mb-1 fw-bold text-start">Hello Again!</h3>
          <small class="d-block mb-4 text-start">Welcome Back</small>
          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
          <?php endif; ?>
          <form method="POST" action="login-handler.php">
            <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
            <div class="mb-3 password-wrapper">
              <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
              <span class="toggle-password" onclick="togglePassword()">
                <i id="toggleIcon" class="fa-solid fa-eye"></i>
              </span>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>
          <div class="mt-3">
            <a href="forgot-password.php">Forgot Password?</a>
            <div class="mt-2">
              <small>No account yet?</small>
              <a href="register.php" class="text-primary fw-semibold small">Sign up!</a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script>
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
      themeIcon.textContent = '🌙';
      themeLabel.textContent = 'Dark';
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
      themeIcon.textContent = isDark ? '🌙' : '🌞';
      themeLabel.textContent = isDark ? 'Dark' : 'Light';
      
      // Update logo
      updateLogo(isDark);
    });
  </script>
</body>
</html>