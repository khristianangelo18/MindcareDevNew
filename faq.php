<?php
session_start();
include 'supabase.php';
$user_name = $_SESSION['user']['fullname'] ?? 'User';
$is_logged_in = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FAQ - MindCare</title>
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
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-light);
      color: var(--text-dark);
      overflow-x: hidden;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Dark Mode Variables */
    body.dark-mode {
      --bg-light: #1a1a1a;
      --sidebar-bg: #2a2a2a;
      --card-bg: #2a2a2a;
      --text-dark: #f1f1f1;
      --text-muted: #b0b0b0;
      --border-color: #3a3a3a;
    }

    /* Sidebar */
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
      transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
    }

    /* Ensure desktop styles are wrapped in media query */
    @media (min-width: 993px) {
        body {
            padding-left: 250px; /* Desktop spacing */
        }
        .sidebar {
            width: 250px;
        }
        /* FIX: Ensure the main content wrapper has adequate top padding on desktop */
        .main-wrapper {
             width: 100%;
             padding-top: 2rem; /* Restore normal desktop padding */
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

    /* Main Content Area */
    .main-wrapper {
      margin-left: 0;
      padding: 2rem;
      width: 100%;
      min-height: 100vh;
      transition: all 0.3s ease;
    }

    .content-inner {
      max-width: 900px;
      margin: 0 auto;
    }

    /* Header */
    .page-header {
      margin-bottom: 2rem;
      text-align: center;
    }

    .page-header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .page-header h1 .user-name {
      color: var(--primary-teal);
    }

    .page-header .subtitle {
      color: var(--text-muted);
      font-size: 0.95rem;
    }

    /* FAQ Section */
    .faq-container {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    body.dark-mode .faq-container {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .section-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--text-dark);
      margin: 0 0 1.5rem 0;
      text-align: center;
    }

    /* Accordion Styling */
    .faq-accordion {
      margin-top: 1.5rem;
    }

    .faq-item {
      background: var(--bg-light);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      margin-bottom: 1rem;
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .faq-item:hover {
      box-shadow: 0 2px 8px rgba(90, 208, 190, 0.1);
    }

    body.dark-mode .faq-item:hover {
      box-shadow: 0 2px 8px rgba(90, 208, 190, 0.2);
    }

    .faq-question {
      width: 100%;
      padding: 1.25rem 1.5rem;
      background: transparent;
      border: none;
      text-align: left;
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text-dark);
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: all 0.3s ease;
    }

    .faq-question:hover {
      color: var(--primary-teal);
    }

    .faq-question::after {
      content: '+';
      font-size: 1.5rem;
      font-weight: 400;
      color: var(--primary-teal);
      transition: transform 0.3s ease;
      flex-shrink: 0;
      margin-left: 1rem;
    }

    .faq-question.active::after {
      content: '−';
      transform: rotate(180deg);
    }

    .faq-answer {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease, padding 0.3s ease, opacity 0.3s ease; 
      opacity: 0; /* FIX: Set opacity to 0 when collapsed */
    }

    .faq-answer.show {
      max-height: 500px;
      padding-bottom: 1.25rem; 
      opacity: 1; /* FIX: Set opacity to 1 when shown */
    }

    .faq-answer-content {
      /* Ensure zero vertical padding/margin for complete hiding */
      padding: 0 1.5rem 0 1.5rem; 
      margin: 0;
      color: var(--text-muted);
      font-size: 0.9rem;
      line-height: 1.6;
    }

    .faq-answer-content a {
      color: var(--primary-teal);
      text-decoration: none;
      font-weight: 500;
    }

    .faq-answer-content a:hover {
      text-decoration: underline;
    }

    /* Back Button */
    .back-section {
      text-align: center;
      margin-top: 2rem;
    }

    .btn-back {
      background: transparent;
      border: 1px solid var(--border-color);
      color: var(--text-muted);
      padding: 0.75rem 2rem;
      border-radius: 8px;
      font-weight: 500;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .btn-back:hover {
      background: var(--bg-light);
      border-color: var(--primary-teal);
      color: var(--primary-teal);
    }

    /* --- MOBILE RESPONSIVENESS FIXES --- */

    @media (max-width: 992px) {
      body {
        padding-top: 5rem; 
        padding-left: 0;
      }
      
      .sidebar {
        width: 280px; 
        transform: translateX(-100%);
      }
      
      .sidebar.show {
        transform: translateX(0);
      }
      
      /* FIX: Ensure mobile padding is consistent with desktop padding, but leaves room for menu */
      .main-wrapper {
          padding-top: 0; /* Remove extra top padding on mobile, relying on body padding */
          padding-left: 1.5rem;
          padding-right: 1.5rem;
          padding-bottom: 2rem;
      }
    }
  </style>
  <link rel="stylesheet" href="mobile.css" />
</head>
<body>

  <div class="sidebar" id="sidebar">
    <div class="logo-wrapper">
      <img src="images/Mindcare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <nav class="nav flex-column" style="flex: 1;">
      
      <?php if ($is_logged_in): ?>
        <a class="nav-link" href="dashboard.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          DASHBOARD
        </a>
        <a class="nav-link" href="resources.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/></svg>
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
        <a class="nav-link" href="profile.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          PROFILE
        </a>
      <?php else: ?>
        <a class="nav-link" href="pre_assessment.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
          </svg>
          PRE ASSESSMENT
        </a>
        <a class="nav-link" href="login.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
            <polyline points="10 17 15 12 10 7"></polyline>
            <line x1="15" y1="12" x2="3" y2="12"></line>
          </svg>
          LOGIN
        </a>
        <a class="nav-link" href="register.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="8.5" cy="7" r="4"></circle>
            <path d="M20 8v7"></path>
            <path d="M23 11h-6"></path>
          </svg>
          REGISTER
        </a>
      <?php endif; ?>

      <a class="nav-link active" href="faq.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        FAQS
      </a>
    </nav>

    <div class="theme-toggle">
      <button id="themeToggle">
        <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
        <span id="themeLabel">Light Mode</span>
      </button>
    </div>

    <?php if ($is_logged_in): ?>
      <a href="logout.php" class="nav-link" style="margin-top: 1rem; color: #ef5350; border-top: 1px solid var(--border-color); padding-top: 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        LOGOUT
      </a>
    <?php endif; ?>
  </div>

  <div class="main-wrapper">
    <div class="content-inner">
      
      <div class="page-header">
        <h1>Hello, <span class="user-name"><?= htmlspecialchars($user_name) ?></span>!</h1>
        <p class="subtitle">Find answers to commonly asked questions</p>
      </div>

      <div class="faq-container">
        <h2 class="section-title">❓ Frequently Asked Questions</h2>

        <div class="faq-accordion">
          <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
              How do I take a mental health assessment?
            </button>
            <div class="faq-answer">
              <div class="faq-answer-content">
                Go to the <a href="assessment.php">Assessment</a> page and answer the questions honestly. Your results will be summarized and stored for future reference.
              </div>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
              How do I book or reschedule an appointment?
            </button>
            <div class="faq-answer">
              <div class="faq-answer-content">
                Visit the <a href="book_appointment.php">Book Appointment</a> page to schedule a session. To reschedule, go to <a href="appointments.php">My Appointments</a> and select "Reschedule".
              </div>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
              What types of specialists are available?
            </button>
            <div class="faq-answer">
              <div class="faq-answer-content">
                We have licensed **psychologists** (who provide therapy and counseling) and **psychiatrists** (who can prescribe medication if needed) available.
              </div>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
              Is my data private and secure?
            </button>
            <div class="faq-answer">
              <div class="faq-answer-content">
                Yes. Your data is stored securely and only accessible to authorized professionals. We follow strict confidentiality and data protection standards.
              </div>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
              What should I expect during my first appointment?
            </button>
            <div class="faq-answer">
              <div class="faq-answer-content">
                Your first appointment will be an introductory session where you'll discuss your concerns, goals, and background with your specialist. This helps create a personalized treatment plan.
              </div>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
              Can I change my specialist after booking?
            </button>
            <div class="faq-answer">
              <div class="faq-answer-content">
                Yes, you can cancel your current appointment and book a new one with a different specialist from the <a href="book_appointment.php">Book Appointment</a> page.
              </div>
            </div>
          </div>
        </div>

        <div class="back-section">
          <a href="<?= $is_logged_in ? 'dashboard.php' : 'index.php' ?>" class="btn-back">← Back</a>
        </div>
      </div>

    </div>
  </div>

  <script src="mobile.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // FAQ Toggle Function
    function toggleFaq(button) {
      const answer = button.nextElementSibling;
      const isActive = button.classList.contains('active');
      
      // Close all others
      document.querySelectorAll('.faq-question').forEach(q => {
        q.classList.remove('active');
        q.nextElementSibling.classList.remove('show');
      });
      
      // Open the selected one if it was not already active
      if (!isActive) {
        button.classList.add('active');
        answer.classList.add('show');
      }
    }

    // Initial load logic to ensure all are closed on start
    document.addEventListener('DOMContentLoaded', function() {
      // Ensure the first item starts closed (as per your desired minimal appearance on load)
      document.querySelectorAll('.faq-question').forEach(q => {
        q.classList.remove('active');
        q.nextElementSibling.classList.remove('show');
      });
    });

    // Dark Mode Toggle
    const toggleBtn = document.getElementById('themeToggle');
    const icon = document.getElementById('themeIcon');
    const label = document.getElementById('themeLabel');

    const prefersDark = localStorage.getItem('dark-mode') === 'true';
    
    // SVG icon strings (from dashboard.php)
    const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
    const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';

    if (prefersDark) {
      document.body.classList.add('dark-mode');
      icon.innerHTML = moonIcon;
      label.textContent = 'Dark Mode';
    } else {
      icon.innerHTML = sunIcon;
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