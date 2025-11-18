<?php
session_start();
include 'supabase.php';

$user_id = $_SESSION['user']['id'] ?? '';
$user_name = $_SESSION['user']['fullname'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mental Health Assessment - MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="mobile.css" />

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
    }

    /* FIXED: Changed background to match dashboard and book appointment */
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-light); /* FIXED: Changed from default to #f8f9fa */
      color: var(--text-dark);
      overflow-x: hidden;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Dark Mode Variables */
    body.dark-mode {
      --bg-light: #1a1a1a;
      --sidebar-bg: #2a2a2a;
      --card-bg: #2d2d2d;
      --text-dark: #f1f1f1;
      --text-muted: #b0b0b0;
      --border-color: #3a3a3a;
    }

    /* Sidebar */
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
      transition: background-color 0.3s ease;
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

    /* Dark Mode Toggle Button */
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

    /* Main Content Area - FIXED: Match dashboard padding */
    .main-wrapper {
      margin-left: 250px;
      padding: 2rem; /* FIXED: Changed from default to match dashboard */
      width: calc(100% - 250px);
      min-height: 100vh;
    }

    .content-inner {
      max-width: 100%;
      width: 100%;
    }

    /* Header */
    .page-header {
      margin-bottom: 2rem;
      margin-top: 0; /* FIXED: Ensure no top margin */
    }

    .page-header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
      transition: color 0.3s ease;
    }

    .page-header h1 .user-name {
      color: var(--primary-teal);
    }

    .page-header .subtitle {
      color: var(--text-muted);
      font-size: 0.95rem;
      transition: color 0.3s ease;
    }

    /* Section Box */
    .section-box {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 3rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    body.dark-mode .section-box {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .section-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--text-dark);
      margin: 0 0 2rem 0;
      transition: color 0.3s ease;
    }

    /* Question */
    .question-item {
      margin-bottom: 2rem;
    }

    .question-item label {
      display: block;
      font-size: 0.9rem;
      font-weight: 500;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
      transition: color 0.3s ease;
    }

    .question-item label .num {
      color: var(--primary-teal);
      font-weight: 600;
      margin-right: 0.25rem;
    }

    .question-item input,
    .question-item select {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 0.9rem;
      background: var(--bg-light);
      color: var(--text-dark);
      transition: all 0.3s ease;
    }

    .question-item input:focus,
    .question-item select:focus {
      outline: none;
      border-color: var(--primary-teal);
      box-shadow: 0 0 0 3px rgba(90, 208, 190, 0.1);
      background: var(--card-bg);
    }

    /* Submit Button */
    .submit-area {
      margin-top: 2rem;
      padding-top: 2rem;
      border-top: 1px solid var(--border-color);
      text-align: center;
    }

    .btn-submit {
      background: linear-gradient(135deg, var(--primary-teal) 0%, var(--primary-teal-dark) 100%);
      color: #ffffff;
      border: none;
      padding: 0.875rem 3rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(90, 208, 190, 0.3);
      transition: all 0.3s ease;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(90, 208, 190, 0.4);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }
      
      .main-wrapper {
        margin-left: 0;
        width: 100%;
        padding: 1.5rem;
      }

      .section-box {
        padding: 1.5rem;
      }
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
      <a class="nav-link active" href="assessment.php">
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
      <a class="nav-link" href="profile.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        PROFILE
      </a>
      <a class="nav-link" href="faq.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        FAQS
      </a>
    </nav>

    <!-- Dark Mode Toggle -->
    <div class="theme-toggle">
  <button id="themeToggle">
    <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <!-- Sun icon (default for light mode) -->
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

    <!-- Logout Button at Bottom -->
    <a href="logout.php" class="nav-link" style="margin-top: 1rem; color: #ef5350; border-top: 1px solid var(--border-color); padding-top: 1rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      LOGOUT
    </a>
  </div>

  <!-- Main Content -->
  <div class="main-wrapper">
    <div class="content-inner">
      
      <!-- Header -->
      <div class="page-header">
        <h1>Hello, <span class="user-name"><?= htmlspecialchars($user_name) ?></span>!</h1>
        <p class="subtitle">Let's do a Mental Health and Self-Awareness Assessment!</p>
      </div>

      <!-- Form -->
      <form method="POST" action="save_assessment.php">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">

        <!-- Section 1 -->
        <div class="section-box">
          <h5 class="section-title">Orientation and Awareness</h5>
          
          <div class="question-item">
            <label><span class="num">1.</span> Your Full Name</label>
            <input type="text" name="orientation_0" placeholder="Enter your full name" required>
          </div>

          <div class="question-item">
            <label><span class="num">2.</span> Where are you right now?</label>
            <input type="text" name="orientation_1" placeholder="Your current location" required>
          </div>

          <div class="question-item">
            <label><span class="num">3.</span> What time is it?</label>
            <input type="text" name="orientation_2" placeholder="Current time" required>
          </div>

          <div class="question-item">
            <label><span class="num">4.</span> Today's date or approximate date</label>
            <input type="text" name="orientation_3" placeholder="Today's date" required>
          </div>

          <div class="question-item">
            <label><span class="num">5.</span> What brought you here today?</label>
            <input type="text" name="orientation_4" placeholder="Your reason for taking this assessment" required>
          </div>
        </div>

        <!-- Section 2 -->
        <div class="section-box">
          <h5 class="section-title">Emotional Well-Being</h5>
          
          <div class="question-item">
            <label><span class="num">1.</span> Describe your mood right now</label>
            <input type="text" name="emotions_0" placeholder="How are you feeling?" required>
          </div>

          <div class="question-item">
            <label><span class="num">2.</span> Most common feelings this week</label>
            <input type="text" name="emotions_1" placeholder="Your feelings this week" required>
          </div>

          <div class="question-item">
            <label><span class="num">3.</span> Something worrying or upsetting you</label>
            <input type="text" name="emotions_2" placeholder="Share if comfortable" required>
          </div>

          <div class="question-item">
            <label><span class="num">4.</span> When do you feel calm or anxious?</label>
            <input type="text" name="emotions_3" placeholder="Describe the situations" required>
          </div>

          <div class="question-item">
            <label><span class="num">5.</span> Do you feel supported?</label>
            <input type="text" name="emotions_4" placeholder="Your support system" required>
          </div>
        </div>

        <!-- Section 3 -->
        <div class="section-box">
          <h5 class="section-title">Memory and Concentration</h5>
          
          <div class="question-item">
            <label><span class="num">1.</span> Repeat: Leaf – Phone – Chair</label>
            <input type="text" name="memory_initial" placeholder="Type the three words" required>
          </div>

          <div class="question-item">
            <label><span class="num">2.</span> Recall the three words after 5 minutes</label>
            <input type="text" name="memory_recall" placeholder="Enter the words you remember" required>
          </div>
        </div>

        <!-- Section 4 -->
        <div class="section-box">
          <h5 class="section-title">Thought and Perception</h5>
          
          <div class="question-item">
            <label><span class="num">1.</span> Disturbing or hard-to-control thoughts?</label>
            <input type="text" name="thoughts_0" placeholder="Describe if any" required>
          </div>

          <div class="question-item">
            <label><span class="num">2.</span> Feeling others are against you?</label>
            <input type="text" name="thoughts_1" placeholder="Your thoughts" required>
          </div>

          <div class="question-item">
            <label><span class="num">3.</span> Hearing or seeing things others don't?</label>
            <input type="text" name="thoughts_2" placeholder="Describe your experience" required>
          </div>

          <div class="question-item">
            <label><span class="num">4.</span> Feeling disconnected from others?</label>
            <input type="text" name="thoughts_3" placeholder="How do you feel?" required>
          </div>
        </div>

        <!-- Section 5 -->
        <div class="section-box">
          <h5 class="section-title">Decision-Making and Insight</h5>
          
          <div class="question-item">
            <label><span class="num">1.</span> How would you help a crying friend?</label>
            <input type="text" name="decisions_0" placeholder="Your approach" required>
          </div>

          <div class="question-item">
            <label><span class="num">2.</span> What does mental health care mean to you?</label>
            <input type="text" name="decisions_1" placeholder="Your thoughts" required>
          </div>

          <div class="question-item">
            <label><span class="num">3.</span> Are you ready to make changes?</label>
            <input type="text" name="decisions_2" placeholder="Your readiness" required>
          </div>
        </div>

        <!-- Section 6 -->
        <div class="section-box">
          <h5 class="section-title">Core Assessment</h5>
          
          <div class="question-item">
            <label><span class="num">1.</span> How often have you felt anxious this week?</label>
            <select name="q1" required>
              <option value="" disabled selected>Select an option</option>
              <option value="0">Not at all</option>
              <option value="1">Several days</option>
              <option value="2">More than half the days</option>
              <option value="3">Nearly every day</option>
            </select>
          </div>

          <div class="question-item">
            <label><span class="num">2.</span> How often have you felt down or depressed?</label>
            <select name="q2" required>
              <option value="" disabled selected>Select an option</option>
              <option value="0">Not at all</option>
              <option value="1">Several days</option>
              <option value="2">More than half the days</option>
              <option value="3">Nearly every day</option>
            </select>
          </div>

          <!-- Submit -->
          <div class="submit-area">
            <button type="submit" class="btn-submit">Submit Assessment</button>
          </div>
        </div>

      </form>

    </div>
  </div>

  <!-- Scripts -->
  <script src="mobile.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Dark mode toggle
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
  </script>
</body>
</html>