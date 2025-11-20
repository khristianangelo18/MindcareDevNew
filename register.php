<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register | MindCare</title>
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
      --warning-text: #856404;
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
      --warning-text: #ffb74d;
    }

    body {
      font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      color: var(--field-text);
      min-height: 100vh;
      overflow-x: hidden;
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

    #themeIcon {
      transition: transform 0.5s ease;
    }
    #themeIcon.rotate {
      transform: rotate(360deg);
    }

    .theme-icon {
      font-size: 18px;
      transition: transform 0.5s ease;
    }

    .theme-icon.rotate {
      transform: rotate(360deg);
    }

    .register-page {
      min-height: 100vh;
      overflow: hidden;
      background: var(--bg-white);
    }
    
    .register-page .row {
      min-height: 100vh;
    }

    .info-side {
      position: fixed;
      left: 0;
      top: 0;
      width: 50%;
      height: 100vh;
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

    .register-form-side {
      background: var(--bg-white);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px;
      margin-left: 50%;
      min-height: 100vh;
      overflow-y: auto;
      transition: background-color 0.3s ease;
    }

    .register-container {
      background: transparent;
      box-shadow: none;
      border-radius: 0;
      width: 420px;
      max-width: 90%;
      padding: 0;
      text-align: left;
    }

    .register-container h3 {
      font-size: 28px;
      font-weight: 700;
      color: var(--field-text);
      margin-bottom: 6px;
      transition: color 0.3s ease;
    }
    
    .register-container small {
      color: var(--muted) !important;
      transition: color 0.3s ease;
    }

    .register-container .alert {
      border: none;
      border-radius: 10px;
      margin-bottom: 20px;
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    .register-container .alert-danger {
      background: var(--alert-danger-bg);
      color: var(--alert-danger-text);
    }
    
    .register-container .alert-success {
      background: var(--alert-success-bg);
      color: var(--alert-success-text);
    }

    /* Validation messages */
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

    .register-container input[type="text"],
    .register-container input[type="email"],
    .register-container input[type="password"],
    .register-container input[type="number"],
    .register-container select {
      background-color: var(--field-bg);
      border: 2px solid transparent;
      height: 52px;
      border-radius: 999px;
      padding: 12px 18px;
      font-size: 15px;
      color: var(--field-text);
      box-shadow: 0 1px 0 rgba(0,0,0,0.02), 0 8px 24px rgba(18,38,63,0.03);
      transition: all 0.3s ease;
    }

    .register-container input.error,
    .register-container select.error {
      border-color: var(--error-color);
      background-color: var(--field-bg);
    }

    .register-container input.success,
    .register-container select.success {
      border-color: var(--success-color);
      background-color: var(--field-bg);
    }

    .register-container input::placeholder {
      color: var(--muted);
      opacity: 0.7;
    }

    .register-container input.fullname-input { padding-left: 52px; }
    .register-container input[type="email"] { padding-left: 52px; }
    .register-container input[type="number"] { padding-left: 52px; }
    .register-container select { padding-left: 52px; }
    .password-wrapper input[type="password"],
    .password-wrapper input[type="text"] { 
      padding-left: 52px; 
      padding-right: 48px; 
    }

    .register-container input.fullname-input {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2399A3AE' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='12' cy='7' r='4'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 18px 50%;
    }

    body.dark-mode .register-container input.fullname-input {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23b0b0b0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='12' cy='7' r='4'/%3E%3C/svg%3E");
    }

    .register-container input[type="email"]{
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2399A3AE' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='14' rx='2' ry='2'/%3E%3Cpolyline points='22,7 12,13 2,7'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 18px 50%;
    }

    body.dark-mode .register-container input[type="email"]{
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23b0b0b0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='14' rx='2' ry='2'/%3E%3Cpolyline points='22,7 12,13 2,7'/%3E%3C/svg%3E");
    }

    .register-container select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2399A3AE' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='9' cy='7' r='4'/%3E%3Cpath d='M23 21v-2a4 4 0 0 0-3-3.87'/%3E%3Cpath d='M16 3.13a4 4 0 0 1 0 7.75'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 18px 50%;
      appearance: none;
      cursor: pointer;
    }

    body.dark-mode .register-container select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23b0b0b0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='9' cy='7' r='4'/%3E%3Cpath d='M23 21v-2a4 4 0 0 0-3-3.87'/%3E%3Cpath d='M16 3.13a4 4 0 0 1 0 7.75'/%3E%3C/svg%3E");
      color: var(--field-text);
    }

    body.dark-mode .register-container select option {
      background-color: #2a2a2a;
      color: var(--field-text);
    }

    .register-container input[type="number"] {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2399A3AE' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 18px 50%;
    }

    body.dark-mode .register-container input[type="number"] {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23b0b0b0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E");
    }

    .password-wrapper input[type="password"],
    .password-wrapper input[type="text"] {
      background-image: none !important;
    }

    .password-wrapper { 
      position: relative; 
    }
    
    .password-wrapper::before{
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

    .register-container input:focus,
    .register-container select:focus {
      background-color: var(--field-bg);
      box-shadow: 0 0 0 3px rgba(56,199,163,0.18);
      outline: none;
      border-color: var(--teal-2);
    }

    body.dark-mode .register-container input:focus,
    body.dark-mode .register-container select:focus {
      background-color: #333;
      box-shadow: 0 0 0 3px rgba(90, 208, 190, 0.25);
    }

    .register-container button,
    .register-container .btn-primary {
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
    
    .register-container button:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 28px rgba(48,170,153,.42);
    }

    .register-container button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .register-container a {
      color: #7c8a99;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }
    
    .register-container a:hover { 
      color: #5d6a78; 
      text-decoration: underline; 
    }
    
    .register-container a.text-primary {
      color: var(--teal-2) !important;
      font-weight: 600;
    }

    body.dark-mode .register-container a {
      color: #90caf9 !important;
    }

    body.dark-mode .register-container a:hover {
      color: #64b5f6 !important;
    }

    body.dark-mode .register-container a.text-primary {
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
      .info-side { 
        width: 50%;
        padding: 48px 40px; 
      }
      .info-side img { height: 160px; }
    }
    
    @media (max-width: 768px) {
      .info-side {
        position: relative;
        width: 100%;
        min-height: 44vh;
        height: auto;
        border-right: none;
        padding: 40px 24px;
      }
      .register-form-side {
        margin-left: 0;
        min-height: 56vh;
        padding: 24px;
      }
      .register-container { width: 100%; max-width: 440px; }
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
        <p class="text-muted fst-italic">Start your mental wellness journey today.</p>
        <p>Already have an account?</p>
        <a href="login.php" class="btn btn-outline-primary">Sign In Here</a>
      </div>

      <div class="col-md-6 register-form-side">
        <div class="register-container fade-in">
          <h3 class="mb-1">Create Account</h3>
          <small class="d-block mb-4">Please fill in your details</small>

          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
          <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success">
              Registration successful! You can now <a href="login.php" class="text-primary fw-semibold">log in</a>.
            </div>
          <?php endif; ?>

          <form method="POST" action="register-handler.php" id="registerForm">
            <!-- 1. Full Name -->
            <div class="mb-3">
              <div class="input-wrapper">
                <input type="text" name="fullname" id="fullname" class="form-control fullname-input" placeholder="Full Name" required />
              </div>
              <p class="validation-message" id="fullnameMessage"></p>
            </div>

            <!-- 2. Email -->
            <div class="mb-3">
              <div class="input-wrapper">
                <input type="email" name="email" id="email" class="form-control" placeholder="Email Address" required />
              </div>
              <p class="validation-message" id="emailMessage"></p>
            </div>

            <!-- 3. Password -->
            <div class="mb-3">
              <div class="input-wrapper password-wrapper">
                <input type="password" name="password" id="password" class="form-control" placeholder="Password (min. 6 characters)" required />
                <span class="toggle-password" onclick="togglePassword('password', 'toggleIcon')">
                  <i id="toggleIcon" class="fa-solid fa-eye"></i>
                </span>
              </div>
              <p class="validation-message" id="passwordMessage"></p>
            </div>

            <!-- 4. Confirm Password -->
            <div class="mb-3">
              <div class="input-wrapper password-wrapper">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required />
                <span class="toggle-password" onclick="togglePassword('confirm_password', 'toggleIconConfirm')">
                  <i id="toggleIconConfirm" class="fa-solid fa-eye"></i>
                </span>
              </div>
              <p class="validation-message" id="confirmPasswordMessage"></p>
            </div>

            <!-- 5. Gender -->
            <div class="mb-3">
              <div class="input-wrapper">
                <select name="gender" id="gender" class="form-select" required>
                  <option value="" disabled selected>Select Gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <p class="validation-message" id="genderMessage"></p>
            </div>

            <!-- 6. Age -->
            <div class="mb-3">
              <div class="input-wrapper">
                <input type="number" name="age" id="age" class="form-control" placeholder="Age" min="1" max="120" required />
              </div>
              <p class="validation-message" id="ageMessage"></p>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="submitBtn">Create Account</button>
          </form>

          <div class="mt-3 text-center">
            <small>Already have an account?</small>
            <a href="login.php" class="text-primary fw-semibold small">Sign in!</a>
          </div>
        </div>
      </div>

    </div>
  </div>

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

    // Form elements
    const form = document.getElementById('registerForm');
    const fullnameInput = document.getElementById('fullname');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const genderInput = document.getElementById('gender');
    const ageInput = document.getElementById('age');
    const submitBtn = document.getElementById('submitBtn');

    // Message elements
    const fullnameMessage = document.getElementById('fullnameMessage');
    const emailMessage = document.getElementById('emailMessage');
    const passwordMessage = document.getElementById('passwordMessage');
    const confirmPasswordMessage = document.getElementById('confirmPasswordMessage');
    const genderMessage = document.getElementById('genderMessage');
    const ageMessage = document.getElementById('ageMessage');

    // Validation functions
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

    // Full name validation
    fullnameInput.addEventListener('blur', function() {
      const value = this.value.trim();
      if (value === '') {
        showValidation(this, fullnameMessage, 'Full name is required', true);
      } else if (value.length < 2) {
        showValidation(this, fullnameMessage, 'Name must be at least 2 characters', true);
      } else {
        showValidation(this, fullnameMessage, 'Looks good!', false);
      }
    });

    fullnameInput.addEventListener('input', function() {
      if (this.classList.contains('error') || this.classList.contains('success')) {
        fullnameInput.dispatchEvent(new Event('blur'));
      }
    });

    // Email validation
    function validateEmail(email) {
      return email.endsWith('@example.com');
    }

    emailInput.addEventListener('blur', function() {
      const email = this.value.trim();
      if (email === '') {
        showValidation(this, emailMessage, 'Email is required', true);
      } else if (!email.includes('@')) {
        showValidation(this, emailMessage, 'Please enter a valid email address', true);
      } else if (!validateEmail(email)) {
        showValidation(this, emailMessage, 'Email must end with @example.com', true);
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

    // Gender validation
    genderInput.addEventListener('change', function() {
      if (this.value) {
        showValidation(this, genderMessage, 'Gender selected', false);
      }
    });

    // Age validation
    ageInput.addEventListener('blur', function() {
      const value = parseInt(this.value);
      if (isNaN(value) || this.value === '') {
        showValidation(this, ageMessage, 'Age is required', true);
      } else if (value < 1 || value > 120) {
        showValidation(this, ageMessage, 'Please enter a valid age (1-120)', true);
      } else if (value < 13) {
        showValidation(this, ageMessage, 'You must be at least 13 years old', true);
      } else {
        showValidation(this, ageMessage, 'Valid age', false);
      }
    });

    ageInput.addEventListener('input', function() {
      if (this.classList.contains('error') || this.classList.contains('success')) {
        ageInput.dispatchEvent(new Event('blur'));
      }
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      let isValid = true;

      // Validate all fields
      if (fullnameInput.value.trim() === '' || fullnameInput.value.trim().length < 2) {
        showValidation(fullnameInput, fullnameMessage, 'Please enter a valid full name', true);
        isValid = false;
      }

      const email = emailInput.value.trim();
      if (email === '' || !validateEmail(email)) {
        showValidation(emailInput, emailMessage, 'Email must end with @example.com', true);
        isValid = false;
      }

      if (passwordInput.value.length < 6) {
        showValidation(passwordInput, passwordMessage, 'Password must be at least 6 characters', true);
        isValid = false;
      }

      if (passwordInput.value !== confirmPasswordInput.value) {
        showValidation(confirmPasswordInput, confirmPasswordMessage, 'Passwords do not match', true);
        isValid = false;
      }

      if (genderInput.value === '') {
        showValidation(genderInput, genderMessage, 'Please select a gender', true);
        isValid = false;
      }

      const age = parseInt(ageInput.value);
      if (isNaN(age) || age < 13 || age > 120) {
        showValidation(ageInput, ageMessage, 'Please enter a valid age', true);
        isValid = false;
      }

      if (isValid) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating Account...';
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

    // Theme toggle
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
  </script>
</body>
</html>