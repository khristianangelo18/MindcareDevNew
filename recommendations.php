<?php
session_start();
include 'supabase.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['fullname'];

// Get the latest assessment (with RLS bypass)
$assessments = supabaseSelect(
  'assessments',
  ['user_id' => $user_id],
  '*',
  'created_at.desc',
  1,
  true  // Bypass RLS to access assessments
);

$assessment = !empty($assessments) ? $assessments[0] : null;

// Function to get recommendations based on score and summary
function getRecommendations($assessment) {
  if (!$assessment) {
    return [
      'message' => 'No assessment found. Please complete an assessment first.',
      'recommendations' => [],
      'severity' => 'none'
    ];
  }
  
  $score = $assessment['score'];
  $summary = $assessment['summary'];
  
  // Determine severity and recommendations based on score
  if ($score <= 2 || stripos($summary, 'mild') !== false || stripos($summary, 'minimal') !== false) {
    return [
      'message' => 'Your assessment indicates minimal distress. Keep up the good work with self-care!',
      'recommendations' => [
        'Try guided breathing exercises',
        'Start a daily journal',
        'Talk to a specialist if symptoms persist',
        'Listen to calming music or nature sounds',
        'Take short mindful walks outdoors',
        'Read something uplifting or inspiring',
        'Maintain a consistent sleep schedule',
        'Limit caffeine and sugar intake',
        'Connect with a trusted friend or support group',
        'Practice positive self-talk and affirmations'
      ],
      'severity' => 'minimal'
    ];
  } elseif ($score <= 4 || stripos($summary, 'moderate') !== false) {
    return [
      'message' => 'Your assessment shows moderate symptoms. Consider these strategies to help manage your wellbeing.',
      'recommendations' => [
        'Start a daily mindfulness or meditation practice',
        'Keep a mood journal to track patterns',
        'Practice deep breathing exercises when stressed',
        'Limit caffeine and maintain healthy eating habits',
        'Establish a consistent sleep routine',
        'Engage in regular physical activity',
        'Talk to trusted friends or family about your feelings',
        'Consider scheduling a consultation with a specialist',
        'Take short walks outdoors for mental clarity',
        'Practice positive self-talk and affirmations'
      ],
      'severity' => 'moderate'
    ];
  } else {
    return [
      'message' => 'Your assessment indicates significant distress. We strongly recommend speaking with a mental health professional.',
      'recommendations' => [
        'Schedule an appointment with a mental health specialist immediately',
        'Reach out to a trusted friend or family member for support',
        'Practice grounding techniques during difficult moments',
        'Avoid isolation and maintain social connections',
        'Follow a structured daily routine',
        'Get adequate sleep and nutrition',
        'Consider joining a support group',
        'Use crisis helplines if you feel overwhelmed',
        'Be patient with yourself during the healing process'
      ],
      'severity' => 'severe'
    ];
  }
}

$recommendationData = getRecommendations($assessment);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recommendations - MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="mobile.css" />
  <style>
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
      background-color: var(--bg-light);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-dark);
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

    .main-content {
      margin-left: 250px;
      padding: 2rem;
      min-height: 100vh;
    }

    .dashboard-header {
      margin-bottom: 2rem;
    }

    .dashboard-header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
      transition: color 0.3s ease;
    }

    .dashboard-header h1 .user-name {
      color: var(--primary-teal);
    }

    .dashboard-header .date-time {
      color: var(--text-muted);
      font-size: 0.95rem;
      transition: color 0.3s ease;
    }

    /* Cards */
    .card {
      background-color: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      transition: all 0.3s ease;
    }

    .card-body {
      padding: 1.5rem;
    }

    .card h5, .card h6 {
      color: var(--text-dark);
      transition: color 0.3s ease;
    }

    /* Assessment Summary Card */
    .assessment-summary {
      background: linear-gradient(135deg, var(--primary-teal) 0%, var(--primary-teal-dark) 100%);
      color: white;
      border: none;
      margin-bottom: 1.5rem;
    }

    .assessment-summary h5 {
      color: white;
      font-weight: 600;
    }

    .assessment-summary .score {
      font-size: 2.5rem;
      font-weight: 700;
      margin: 0.5rem 0;
    }

    .assessment-summary .summary-text {
      font-size: 1.1rem;
      opacity: 0.95;
    }

    /* Severity Badge */
    .severity-badge {
      display: inline-block;
      padding: 0.35rem 1rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      margin-top: 0.5rem;
    }

    .severity-minimal {
      background-color: rgba(255, 255, 255, 0.2);
      color: white;
    }

    .severity-moderate {
      background-color: rgba(255, 215, 0, 0.3);
      color: white;
    }

    .severity-severe {
      background-color: rgba(255, 82, 82, 0.3);
      color: white;
    }

    /* Recommendations List */
    .recommendation-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .recommendation-list li {
      padding: 1rem;
      background-color: var(--bg-light);
      border-radius: 8px;
      margin-bottom: 0.75rem;
      color: var(--text-dark);
      font-size: 0.95rem;
      transition: all 0.3s ease;
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
    }

    .recommendation-list li:hover {
      background-color: rgba(90, 208, 190, 0.05);
      transform: translateX(5px);
    }

    .recommendation-list li svg {
      flex-shrink: 0;
      margin-top: 0.125rem;
    }

    /* Alert Messages */
    .alert-info-custom {
      color: var(--text-dark);
      padding: 1rem 1.5rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
    }

    body.dark-mode .alert-info-custom {
      background-color: rgba(90, 208, 190, 0.15);
    }

    /* Buttons */
    .btn-primary {
      background-color: var(--primary-teal);
      border-color: var(--primary-teal);
    }

    .btn-primary:hover {
      background-color: var(--primary-teal-dark);
      border-color: var(--primary-teal-dark);
    }

    /* No Assessment Card */
    .no-assessment-card {
      text-align: center;
      padding: 3rem 2rem;
    }

    .no-assessment-card svg {
      width: 80px;
      height: 80px;
      margin-bottom: 1.5rem;
      opacity: 0.5;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.show {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
      }

      .assessment-summary .score {
        font-size: 2rem;
      }
    }

    /* Mobile Menu Toggle */
    .mobile-menu-toggle {
      display: none;
      position: fixed;
      top: 1rem;
      left: 1rem;
      z-index: 1001;
      background: var(--primary-teal);
      border: none;
      border-radius: 8px;
      padding: 0.5rem;
      color: white;
      cursor: pointer;
    }

    @media (max-width: 768px) {
      .mobile-menu-toggle {
        display: block;
      }
    }
  </style>
</head>
<body>
  <!-- Mobile Menu Toggle -->
  <button class="mobile-menu-toggle" onclick="toggleSidebar()">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="3" y1="12" x2="21" y2="12"></line>
      <line x1="3" y1="6" x2="21" y2="6"></line>
      <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
  </button>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="logo-wrapper">
      <img src="images/Mindcare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <nav class="nav flex-column" style="flex: 1;">
      <a class="nav-link" href="dashboard.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        DASHBOARD
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
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        MY APPOINTMENTS
      </a>
      <a class="nav-link" href="profile.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        PROFILE
      </a>
      <a class="nav-link" href="faq.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        FAQ
      </a>
    </nav>

    <div class="theme-toggle">
      <button id="themeToggle" onclick="toggleTheme()">
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
        <span id="themeLabel">LIGHT MODE</span>
      </button>
    </div>
    <a href="logout.php" class="nav-link" style="margin-top: 1rem; color: #ef5350; border-top: 1px solid var(--border-color); padding-top: 1rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      LOGOUT
    </a>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Header -->
    <div class="dashboard-header">
      <h1>Your Personalized <span class="user-name">Recommendations</span></h1>
      <p class="date-time">Based on your latest mental health assessment</p>
    </div>

    <?php if ($assessment): ?>
      <!-- Assessment Summary Card -->
      <div class="card assessment-summary">
        <div class="card-body">
          <h5>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: middle;">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Latest Assessment Results
          </h5>
          <div class="score"><?= htmlspecialchars($assessment['score']) ?></div>
          <div class="summary-text"><?= htmlspecialchars($assessment['summary']) ?></div>
          <span class="severity-badge severity-<?= $recommendationData['severity'] ?>">
            <?= ucfirst($recommendationData['severity']) ?> Level
          </span>
          <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.9;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem; vertical-align: middle;">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Assessed on <?= date('F j, Y', strtotime($assessment['created_at'])) ?>
          </p>
        </div>
      </div>

      <!-- Recommendation Message -->
      <div class="alert-info-custom">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: middle;">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <strong><?= htmlspecialchars($recommendationData['message']) ?></strong>
      </div>

      <!-- Recommendations Card -->
      <div class="card">
        <div class="card-body">
          <h5 style="margin-bottom: 1.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: middle;">
              <path d="M12 20h9"></path>
              <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
            </svg>
            Recommended Actions
          </h5>
          <ul class="recommendation-list">
            <?php foreach ($recommendationData['recommendations'] as $recommendation): ?>
              <li>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span><?= htmlspecialchars($recommendation) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- Action Buttons -->
      <?php if ($recommendationData['severity'] !== 'minimal'): ?>
        <div class="card" style="margin-top: 1.5rem;">
          <div class="card-body text-center">
            <h6 style="margin-bottom: 1rem;">Need Professional Support?</h6>
            <a href="book_appointment.php" class="btn btn-primary">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: middle;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
              Book an Appointment
            </a>
          </div>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- No Assessment Found -->
      <div class="card no-assessment-card">
        <div class="card-body">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
          </svg>
          <h5 style="margin-bottom: 1rem;">No Assessment Found</h5>
          <p class="text-muted" style="margin-bottom: 1.5rem;">
            To receive personalized recommendations, please complete a mental health assessment first.
          </p>
          <a href="assessment.php" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: middle;">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Take Assessment
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Dark Mode Toggle
    function toggleTheme() {
      const body = document.body;
      const themeIcon = document.getElementById('themeIcon');
      const themeLabel = document.getElementById('themeLabel');
      
      body.classList.toggle('dark-mode');
      
      if (body.classList.contains('dark-mode')) {
        localStorage.setItem('theme', 'dark');
        themeLabel.textContent = 'DARK MODE';
        themeIcon.innerHTML = `
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        `;
      } else {
        localStorage.setItem('theme', 'light');
        themeLabel.textContent = 'LIGHT MODE';
        themeIcon.innerHTML = `
          <circle cx="12" cy="12" r="5"></circle>
          <line x1="12" y1="1" x2="12" y2="3"></line>
          <line x1="12" y1="21" x2="12" y2="23"></line>
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
          <line x1="1" y1="12" x2="3" y2="12"></line>
          <line x1="21" y1="12" x2="23" y2="12"></line>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
          <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        `;
      }
    }

    // Load saved theme
    if (localStorage.getItem('theme') === 'dark') {
      document.body.classList.add('dark-mode');
      document.getElementById('themeLabel').textContent = 'DARK MODE';
      document.getElementById('themeIcon').innerHTML = `
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
      `;
    }

    // Mobile Sidebar Toggle
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('show');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
      const sidebar = document.getElementById('sidebar');
      const toggle = document.querySelector('.mobile-menu-toggle');
      
      if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
          sidebar.classList.remove('show');
        }
      }
    });
  </script>
</body>
</html>