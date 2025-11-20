<?php
session_start();

// Handle session cleanup after modal has been displayed
if (isset($_GET['clear_session']) && isset($_SESSION['password_reset_success'])) {
    unset($_SESSION['password_reset_success']);
    // Redirect to remove the query parameter
    header("Location: forgot-password.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password | MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    :root{
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
      --info-side-gradient-start: var(--teal-1);
      --info-side-gradient-mid: var(--teal-2);
      --info-side-gradient-end: var(--teal-3);
      --toggle-text-color: #2b2f38;
      --error-color: #dc3545;
      --success-color: #28a745;
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
      --error-color: #ef5350;
      --success-color: #66bb6a;
    }

    body {
      font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      color: var(--field-text);
      min-height: 100vh;
      overflow: hidden;
      background: var(--bg-white);
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

    .theme-icon {
      font-size: 18px;
      transition: transform 0.5s ease;
    }

    .theme-icon.rotate {
      transform: rotate(360deg);
    }

    .forgot-password-page {
      min-height: 100vh;
      overflow: hidden;
      background: var(--bg-white);
    }
    .forgot-password-page .row {
      min-height: 100vh;
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

    .info-side a.btn-outline-primary {
      background: rgba(255,255,255,.20);
      color: #fff;
      border: none;
      padding: 12px 20px;
      border-radius: 999px;
      font-weight: 600;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.25);
      transition: transform .15s ease, background .2s ease;
    }
    .info-side a.btn-outline-primary:hover {
      background: rgba(255,255,255,.28);
      transform: translateY(-1px);
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
    .form-container .alert-success {
      background: var(--alert-success-bg);
      color: var(--alert-success-text);
    }

    /* Validation messages - EXACT COPY from register.php */
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

    /* Input field wrapper */
    .input-wrapper {
      position: relative;
      margin-bottom: 8px;
    }

    .form-container input[type="password"],
    .form-container input[type="text"],
    .form-container input[type="email"] {
      background-color: var(--field-bg);
      border: 2px solid transparent;
      height: 52px;
      border-radius: 999px;
      padding: 12px 18px 12px 52px;
      font-size: 15px;
      color: var(--field-text);
      box-shadow: 0 1px 0 rgba(0,0,0,0.02), 0 8px 24px rgba(18,38,63,0.03);
      transition: all 0.3s ease;
    }

    .form-container input.error {
      border-color: var(--error-color);
      background-color: var(--field-bg);
    }

    .form-container input.success {
      border-color: var(--success-color);
      background-color: var(--field-bg);
    }

    .form-container input::placeholder {
      color: var(--muted);
      opacity: 0.7;
    }

    .password-wrapper input[type="password"],
    .password-wrapper input[type="text"] {
      padding-right: 48px;
      background-image: none !important;
    }

    .form-container input[type="email"]{
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2399A3AE' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='14' rx='2' ry='2'/%3E%3Cpolyline points='22,7 12,13 2,7'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 18px 50%;
    }

    body.dark-mode .form-container input[type="email"]{
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23b0b0b0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='14' rx='2' ry='2'/%3E%3Cpolyline points='22,7 12,13 2,7'/%3E%3C/svg%3E");
    }

    .password-wrapper {
      position: relative;
    }

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

    .form-container button,
    .form-container .btn-primary {
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
      transition: transform .15s ease, box-shadow .2s ease, opacity .2s ease;
    }
    .form-container button:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 28px rgba(48,170,153,.42);
    }

    .form-container button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    #themeIcon {
      transition: transform 0.5s ease;
    }
    #themeIcon.rotate {
      transform: rotate(360deg);
    }

    .form-container a {
      color: #7c8a99;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }
    .form-container a:hover { 
      color: #5d6a78; 
      text-decoration: underline; 
    }
    .form-container a.text-primary {
      color: var(--teal-2) !important;
      font-weight: 600;
    }

    body.dark-mode .form-container a {
      color: #90caf9 !important;
    }

    body.dark-mode .form-container a:hover {
      color: #64b5f6 !important;
    }

    body.dark-mode .form-container a.text-primary {
      color: var(--teal-1) !important;
    }

    .fade-in {
      animation: fadeInUp .9s ease both;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 992px) {
      .info-side { padding: 48px 40px; }
      .info-side img { height: 160px; }
    }
    @media (max-width: 768px) {
      .info-side {
        min-height: 44vh;
        border-right: none;
        padding: 40px 24px;
      }
      .form-side {
        min-height: 56vh;
        padding: 24px;
      }
      .form-container { width: 100%; max-width: 440px; }
    }

    /* Success Modal Styles */
    .modal-content.success-modal-content {
      border: none;
      border-radius: 20px;
      background: var(--bg-white);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      overflow: hidden;
    }

    .success-icon-wrapper {
      display: flex;
      justify-content: center;
      animation: scaleIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) both;
    }

    .success-icon-circle {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--teal-1) 0%, var(--teal-2) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      box-shadow: 0 10px 30px rgba(90, 208, 190, 0.4);
    }

    .success-icon-circle::before {
      content: '';
      position: absolute;
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 3px solid var(--teal-1);
      opacity: 0.3;
      animation: pulse 2s ease-in-out infinite;
    }

    .success-icon {
      font-size: 48px;
      color: white;
    }

    .success-title {
      font-size: 28px;
      font-weight: 700;
      color: var(--field-text);
      animation: fadeInUp 0.6s ease 0.2s both;
    }

    .success-message {
      font-size: 16px;
      color: var(--muted);
      line-height: 1.6;
      animation: fadeInUp 0.6s ease 0.3s both;
    }

    .success-details {
      background: rgba(90, 208, 190, 0.08);
      border-radius: 12px;
      padding: 20px;
      animation: fadeInUp 0.6s ease 0.4s both;
      border: 1px solid rgba(90, 208, 190, 0.2);
    }

    body.dark-mode .success-details {
      background: rgba(90, 208, 190, 0.12);
      border-color: rgba(90, 208, 190, 0.3);
    }

    .detail-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 0;
      color: var(--field-text);
      font-size: 15px;
    }

    .detail-item i {
      color: var(--teal-2);
      font-size: 20px;
      min-width: 24px;
    }

    body.dark-mode .detail-item i {
      color: var(--teal-1);
    }

    .modal .btn-primary {
      animation: fadeInUp 0.6s ease 0.5s both;
    }

    .modal .btn-outline-secondary {
      background: transparent;
      border: 2px solid var(--teal-2);
      color: var(--teal-2);
      font-weight: 600;
      height: 48px;
      border-radius: 999px;
      transition: all 0.2s ease;
      animation: fadeInUp 0.6s ease 0.6s both;
    }

    .modal .btn-outline-secondary:hover {
      background: var(--teal-2);
      color: white;
    }

    body.dark-mode .modal .btn-outline-secondary {
      border-color: var(--teal-1);
      color: var(--teal-1);
    }

    body.dark-mode .modal .btn-outline-secondary:hover {
      background: var(--teal-1);
      color: white;
    }

    .redirect-notice {
      font-size: 14px;
      color: var(--muted);
      animation: fadeInUp 0.6s ease 0.7s both;
    }

    .redirect-notice .countdown {
      font-weight: 700;
      color: var(--teal-2);
    }

    body.dark-mode .redirect-notice .countdown {
      color: var(--teal-1);
    }

    @keyframes scaleIn {
      from {
        opacity: 0;
        transform: scale(0.5);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
        opacity: 0.3;
      }
      50% {
        transform: scale(1.1);
        opacity: 0.1;
      }
    }
  </style>
</head>

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

      <div class="col-md-6 info-side">
        <img src="images/MindCare.png" alt="MindCare Logo" class="img-fluid" id="logoImage" />
        <p class="text-muted fst-italic">Reset your password.</p>
        <p>Enter your email address and create a new password. Make sure to choose a strong password to protect your account.</p>
        <a href="login.php" class="btn btn-outline-primary">Back to Login</a>
      </div>

      <div class="col-md-6 form-side">
        <div class="form-container fade-in">
          <h3 class="mb-1">Reset Password</h3>
          <small class="d-block mb-4">Enter your email and new password</small>

          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
              <i class="fa-solid fa-circle-exclamation"></i>
              <?= htmlspecialchars($_GET['error']) ?>
            </div>
          <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success">
              <i class="fa-solid fa-circle-check"></i>
              <?= htmlspecialchars($_GET['success']) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="reset-password-handler.php" id="resetPasswordForm">
            <!-- Email -->
            <div class="mb-3">
              <div class="input-wrapper">
                <input 
                  type="email" 
                  name="email" 
                  id="email" 
                  class="form-control" 
                  placeholder="Email Address" 
                  required
                  autocomplete="email"
                />
              </div>
              <p class="validation-message" id="emailMessage"></p>
            </div>

            <!-- New Password -->
            <div class="mb-3">
              <div class="input-wrapper password-wrapper">
                <input 
                  type="password" 
                  name="new_password" 
                  id="new_password" 
                  class="form-control" 
                  placeholder="New Password (min 6 characters)" 
                  required
                  autocomplete="new-password"
                  minlength="6"
                />
                <span class="toggle-password" onclick="togglePassword('new_password', 'toggleIconNew')">
                  <i id="toggleIconNew" class="fa-solid fa-eye"></i>
                </span>
              </div>
              <p class="validation-message" id="passwordMessage"></p>
            </div>

            <!-- Confirm Password -->
            <div class="mb-3">
              <div class="input-wrapper password-wrapper">
                <input 
                  type="password" 
                  name="confirm_password" 
                  id="confirm_password" 
                  class="form-control" 
                  placeholder="Confirm New Password" 
                  required
                  autocomplete="new-password"
                />
                <span class="toggle-password" onclick="togglePassword('confirm_password', 'toggleIconConfirm')">
                  <i id="toggleIconConfirm" class="fa-solid fa-eye"></i>
                </span>
              </div>
              <p class="validation-message" id="confirmPasswordMessage"></p>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="submitBtn">Reset Password</button>
          </form>

          <div class="mt-3 text-center">
            <small>Remember your password?</small>
            <a href="login.php" class="text-primary fw-semibold small">Sign in here</a>
          </div>

          <div class="mt-4 text-center">
            <small>Don't have an account?</small>
            <a href="register.php" class="text-primary fw-semibold small">Create one now</a>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Success Modal -->
  <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content success-modal-content">
        <div class="modal-body text-center p-5">
          <div class="success-icon-wrapper mb-4">
            <div class="success-icon-circle">
              <i class="fa-solid fa-check success-icon"></i>
            </div>
          </div>
          
          <h3 class="success-title mb-3">Password Reset Successful!</h3>
          
          <p class="success-message mb-4">
            Your password has been successfully updated. You can now log in to your MindCare account with your new password.
          </p>

          <?php if (isset($_SESSION['password_reset_success'])): ?>
          <div class="success-details mb-4">
            <div class="detail-item">
              <i class="fa-solid fa-envelope-circle-check"></i>
              <span><?= htmlspecialchars($_SESSION['password_reset_success']['email']) ?></span>
            </div>
            <div class="detail-item">
              <i class="fa-solid fa-shield-halved"></i>
              <span>Account secured with new password</span>
            </div>
          </div>
          <?php endif; ?>

          <div class="d-grid gap-2">
            <a href="login.php" class="btn btn-primary btn-lg">
              <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>
              Go to Login
            </a>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              Close
            </button>
          </div>

          <div class="redirect-notice mt-4">
            Redirecting to login in <span class="countdown" id="countdown">10</span> seconds...
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
    const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
    
    function togglePassword(fieldId, iconId) {
      const passwordField = document.getElementById(fieldId);
      const toggleIcon = document.getElementById(iconId);
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

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeLabel = document.getElementById('themeLabel');
    const logoImage = document.getElementById('logoImage');

    function updateLogo(isDark) {
      logoImage.src = isDark ? 'images/MindCare.png' : 'images/MindCare.png';
    }

    const prefersDark = localStorage.getItem('dark-mode') === 'true';
    if (prefersDark) {
      document.body.classList.add('dark-mode');
      themeIcon.innerHTML = moonIcon;
      themeLabel.textContent = 'Dark Mode';
      updateLogo(true);
    }

    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode');
      const isDark = document.body.classList.contains('dark-mode');
      localStorage.setItem('dark-mode', isDark);
      
      themeIcon.classList.add('rotate');
      setTimeout(() => themeIcon.classList.remove('rotate'), 500);
      
      themeIcon.innerHTML = isDark ? moonIcon : sunIcon;
      themeLabel.textContent = isDark ? 'Dark Mode' : 'Light Mode';
      updateLogo(isDark);
    });

    // Form elements
    const form = document.getElementById('resetPasswordForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');

    // Message elements
    const emailMessage = document.getElementById('emailMessage');
    const passwordMessage = document.getElementById('passwordMessage');
    const confirmPasswordMessage = document.getElementById('confirmPasswordMessage');

    // Validation functions (EXACT COPY from register.php)
    function showValidation(input, message, text, isError) {
      if (isError) {
        input.classList.remove('success');
        input.classList.add('error');
        message.classList.remove('success');
        message.classList.add('error');
        message.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${text}`;
      } else {
        input.classList.remove('error');
        input.classList.add('success');
        message.classList.remove('error');
        message.classList.add('success');
        message.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${text}`;
      }
    }

    function clearValidation(input, message) {
      input.classList.remove('error', 'success');
      message.classList.remove('error', 'success');
      message.innerHTML = '';
    }

    // Email validation
    emailInput.addEventListener('blur', function() {
      const email = this.value.trim();
      if (email === '') {
        showValidation(this, emailMessage, 'Email is required', true);
      } else if (!email.includes('@')) {
        showValidation(this, emailMessage, 'Please enter a valid email address', true);
      } else {
        showValidation(this, emailMessage, 'Valid email format', false);
      }
    });

    emailInput.addEventListener('input', function() {
      if (this.classList.contains('error') || this.classList.contains('success')) {
        emailInput.dispatchEvent(new Event('blur'));
      }
    });

    // Password validation
    passwordInput.addEventListener('blur', function() {
      const value = this.value;
      if (value === '') {
        showValidation(this, passwordMessage, 'Password is required', true);
      } else if (value.length < 6) {
        showValidation(this, passwordMessage, 'Password must be at least 6 characters', true);
      } else {
        showValidation(this, passwordMessage, 'Strong password', false);
        // Re-validate confirm password if it has value
        if (confirmPasswordInput.value) {
          confirmPasswordInput.dispatchEvent(new Event('blur'));
        }
      }
    });

    passwordInput.addEventListener('input', function() {
      if (this.classList.contains('error') || this.classList.contains('success')) {
        passwordInput.dispatchEvent(new Event('blur'));
      }
      // Real-time check for confirm password
      if (confirmPasswordInput.value) {
        confirmPasswordInput.dispatchEvent(new Event('input'));
      }
    });

    // Confirm password validation
    confirmPasswordInput.addEventListener('blur', function() {
      const password = passwordInput.value;
      const confirmPassword = this.value;
      
      if (confirmPassword === '') {
        showValidation(this, confirmPasswordMessage, 'Please confirm your password', true);
      } else if (password !== confirmPassword) {
        showValidation(this, confirmPasswordMessage, 'Passwords do not match', true);
      } else {
        showValidation(this, confirmPasswordMessage, 'Passwords match', false);
      }
    });

    confirmPasswordInput.addEventListener('input', function() {
      const password = passwordInput.value;
      const confirmPassword = this.value;
      
      if (confirmPassword && password !== confirmPassword) {
        showValidation(this, confirmPasswordMessage, 'Passwords do not match', true);
      } else if (confirmPassword && password === confirmPassword) {
        showValidation(this, confirmPasswordMessage, 'Passwords match', false);
      } else if (confirmPassword === '') {
        clearValidation(this, confirmPasswordMessage);
      }
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      let isValid = true;

      // Validate email
      const email = emailInput.value.trim();
      if (email === '' || !email.includes('@')) {
        showValidation(emailInput, emailMessage, 'Please enter a valid email address', true);
        isValid = false;
      }

      // Validate password
      if (passwordInput.value.length < 6) {
        showValidation(passwordInput, passwordMessage, 'Password must be at least 6 characters', true);
        isValid = false;
      }

      // Validate confirm password
      if (passwordInput.value !== confirmPasswordInput.value) {
        showValidation(confirmPasswordInput, confirmPasswordMessage, 'Passwords do not match', true);
        isValid = false;
      }

      if (isValid) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resetting Password...';
        form.submit();
      } else {
        // Scroll to first error
        const firstError = document.querySelector('.error');
        if (firstError) {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
          firstError.focus();
        }
      }
    });

    // Success Modal Handling
    <?php if (isset($_GET['reset_success']) && isset($_SESSION['password_reset_success'])): ?>
    // Show the modal on page load
    window.addEventListener('DOMContentLoaded', function() {
      const successModal = new bootstrap.Modal(document.getElementById('successModal'));
      const modalElement = document.getElementById('successModal');
      successModal.show();

      // Countdown timer
      let countdown = 10;
      const countdownElement = document.getElementById('countdown');
      
      const countdownInterval = setInterval(function() {
        countdown--;
        countdownElement.textContent = countdown;
        
        if (countdown <= 0) {
          clearInterval(countdownInterval);
          // Clear session before redirect
          window.location.href = 'forgot-password.php?clear_session=1';
          setTimeout(() => {
            window.location.href = 'login.php';
          }, 100);
        }
      }, 1000);

      // Handle manual close - clear session
      modalElement.addEventListener('hidden.bs.modal', function() {
        window.location.href = 'forgot-password.php?clear_session=1';
      });

      // Override "Go to Login" button to clear session first
      document.querySelector('#successModal .btn-primary').addEventListener('click', function(e) {
        e.preventDefault();
        clearInterval(countdownInterval);
        fetch('clear-reset-session.php', { method: 'POST' })
          .finally(() => {
            window.location.href = 'login.php';
          });
      });
    });
    <?php endif; ?>
  </script>
</body>
</html>