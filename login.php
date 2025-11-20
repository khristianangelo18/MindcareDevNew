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

    #themeIcon {
      transition: transform 0.5s ease;
    }
    #themeIcon.rotate {
      transform: rotate(360deg);
    }

    /* Input field dark mode styling */
    .login-container input {
      background-color: var(--field-bg);
      color: var(--field-text);
      border: 1px solid var(--line);
      transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }


    .login-container input::placeholder {
      color: var(--muted);
      opacity: 0.7;
    }

    

    body.dark-mode .login-container input {
      background-color: var(--field-bg);
      color: var(--field-text);
      border-color: var(--line);
    }

    /* Dark mode email icon - use lighter color */
      body.dark-mode .login-container input[type="email"]{
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23b0b0b0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='14' rx='2' ry='2'/%3E%3Cpolyline points='22,7 12,13 2,7'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 18px 50%;
      }

    body.dark-mode .login-container input::placeholder {
      color: var(--muted);
    }

    /* Dark mode for alert messages */
    body.dark-mode .alert-danger {
      background-color: rgba(244, 67, 54, 0.2);
      color: #ef5350;
      border-color: rgba(244, 67, 54, 0.3);
    }

    /* Toggle password icon dark mode */
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

    /* Dark mode for links */
    body.dark-mode .login-container a {
      color: #90caf9;
    }

    body.dark-mode .login-container a:hover {
      color: #64b5f6;
    }

    body.dark-mode .login-container a.text-primary {
      color: var(--teal-1) !important;
    }

    body.dark-mode .login-container h3 {
      color: var(--field-text);
    }

    body.dark-mode .login-container small {
      color: var(--muted);
    }

    /* Login container button styles */
    .login-container button[type="submit"],
    .login-container .btn-primary {
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
    .info-side img {
      height: 200px;
      width: auto;
      margin: 0 0 24px 0;
      transition: opacity 0.3s ease;
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
  </style>
</head>
<body class="login-page">
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

      <!-- Left Side: Logo + Pre-Assessment -->
      <div class="col-md-6 info-side">
        <img src="images/MindCare.png" alt="MindCare Logo" class="img-fluid" id="logoImage" />
        
        <p class="text-muted fst-italic">Where healing meets understanding.</p>
        <p>Take a a Quick Pre-Assessment</p>
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
          <form method="POST" action="login-handler.php" id="loginForm">
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

    // CRITICAL FIX: Prevent form from opening in new window
    document.addEventListener('DOMContentLoaded', function() {
      const loginForm = document.getElementById('loginForm');
      
      if (loginForm) {
        // Remove any target attribute
        loginForm.removeAttribute('target');
        
        // Intercept form submission
        loginForm.addEventListener('submit', function(e) {
          e.preventDefault(); // Stop default submission
          
          console.log('Form intercepted - submitting via JavaScript');
          
          // Submit the form programmatically in the same window
          const formData = new FormData(this);
          
          fetch(this.action, {
            method: 'POST',
            body: formData
          })
          .then(response => {
            // Get the redirect location from the response
            if (response.redirected) {
              // If PHP redirected, go to that URL in same window
              window.location.href = response.url;
            } else {
              // If no redirect, reload to show error message
              window.location.href = window.location.href;
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Login failed. Please try again.');
          });
          
          return false;
        });
      }
    });

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