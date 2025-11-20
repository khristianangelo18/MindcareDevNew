<?php
session_start();

// Handle reset request - clears session and redirects to show form
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
  unset($_SESSION['pre_assessment_result']);
  header("Location: pre_assessment.php");
  exit;
}

// Handle form submission - calculate score and set session for immediate display
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $q1 = (int)$_POST['q1'];
  $q2 = (int)$_POST['q2'];
  $q3 = (int)$_POST['q3'];
  $score = $q1 + $q2 + $q3;
  
  // Determine summary based on score
  if ($score <= 2) {
    $summary = "Minimal symptoms";
  } elseif ($score <= 5) {
    $summary = "Mild symptoms";
  } elseif ($score <= 7) {
    $summary = "Moderate symptoms";
  } else {
    $summary = "Severe symptoms";
  }
  
  // Store in session for display (temporary - goes away on refresh)
  $_SESSION['pre_assessment_result'] = [
    'score' => $score,
    'summary' => $summary,
    'timestamp' => time()
  ];
  
  // Redirect to same page to show results
  header("Location: pre_assessment.php");
  exit;
}

// Get result from session if exists
$assessment_result = $_SESSION['pre_assessment_result'] ?? null;

// Function to get recommendations based on score and summary
function getAnonymousRecommendations($score, $summary) {
  // Determine severity and recommendations based on score
  if ($score <= 2 || stripos($summary, 'minimal') !== false) {
    return [
      'message' => 'Your responses suggest minimal distress. Keep up the good work with self-care!',
      'recommendations' => [
        'Continue your current self-care routine',
        'Practice mindfulness exercises daily',
        'Maintain regular sleep schedule',
        'Stay physically active with light exercise',
        'Keep a gratitude journal',
        'Connect with friends and family regularly',
        'Take breaks during stressful moments',
        'Listen to calming music or nature sounds'
      ],
      'severity' => 'minimal',
      'color' => '#28a745'
    ];
  } elseif ($score <= 5 || stripos($summary, 'mild') !== false) {
    return [
      'message' => 'You may be experiencing mild symptoms. Consider these strategies to help manage your wellbeing.',
      'recommendations' => [
        'Start a daily mindfulness or meditation practice',
        'Keep a mood journal to track patterns',
        'Practice deep breathing exercises when stressed',
        'Limit caffeine and maintain healthy eating habits',
        'Establish a consistent sleep routine',
        'Engage in regular physical activity',
        'Talk to trusted friends or family about your feelings',
        'Consider exploring MindCare\'s professional support services',
        'Take short walks outdoors for mental clarity',
        'Practice positive self-talk and affirmations'
      ],
      'severity' => 'mild',
      'color' => '#17a2b8'
    ];
  } elseif ($score <= 7 || stripos($summary, 'moderate') !== false) {
    return [
      'message' => 'Your assessment shows moderate symptoms. We recommend reaching out to a mental health professional.',
      'recommendations' => [
        'Schedule a consultation with a mental health specialist',
        'Keep a mood journal to track patterns',
        'Practice deep breathing exercises when stressed',
        'Limit caffeine and maintain healthy eating habits',
        'Establish a consistent sleep routine',
        'Engage in regular physical activity',
        'Talk to trusted friends or family about your feelings',
        'Consider joining a support group',
        'Take short walks outdoors for mental clarity',
        'Practice positive self-talk and affirmations'
      ],
      'severity' => 'moderate',
      'color' => '#ffc107'
    ];
  } else {
    return [
      'message' => 'Your score indicates significant distress. We strongly recommend creating an account at MindCare and booking a consultation with our professionals.',
      'recommendations' => [
        'Create a MindCare account and book an appointment immediately',
        'Reach out to a trusted friend or family member for support',
        'Practice grounding techniques during difficult moments',
        'Avoid isolation and maintain social connections',
        'Follow a structured daily routine',
        'Limit alcohol and avoid substance use',
        'Get adequate sleep and nutrition',
        'Consider joining a support group',
        'Use crisis helplines if you feel overwhelmed',
        'Be patient with yourself during the healing process'
      ],
      'severity' => 'severe',
      'color' => '#dc3545'
    ];
  }
}

$recommendation_data = null;
if ($assessment_result) {
  $recommendation_data = getAnonymousRecommendations(
    $assessment_result['score'],
    $assessment_result['summary']
  );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quick Mental Health Check - MindCare</title>
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
      min-height: 100vh;
      overflow-x: hidden;
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    /* Global Content Shift for Sidebar */
    .main-wrapper {
      margin-left: 250px; 
      padding-top: 2rem;
      padding-bottom: 2rem;
      min-height: 100vh;
      transition: margin-left 0.3s ease;
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

    /* --- SIDEBAR STYLING (COPIED FROM DASHBOARD) --- */
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
    /* --- END SIDEBAR STYLING --- */

    .content-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    /* Page Header */
    .page-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .page-header .subtitle {
      color: var(--text-muted);
      font-size: 1rem;
    }

    /* Assessment Form Card */
    .assessment-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 3rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    body.dark-mode .assessment-card {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .question-item {
      margin-bottom: 2rem;
    }

    .question-item label {
      display: block;
      font-size: 1rem;
      font-weight: 500;
      color: var(--text-dark);
      margin-bottom: 0.75rem;
      transition: color 0.3s ease;
    }

    .question-item label .question-number {
      color: var(--primary-teal);
      font-weight: 600;
      margin-right: 0.5rem;
    }

    .question-item select {
      width: 100%;
      padding: 0.875rem 1rem;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 1rem;
      background: var(--bg-light);
      color: var(--text-dark);
      transition: all 0.3s ease;
    }

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
      padding: 1rem 3rem;
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

    /* Results Section */
    .results-section {
      margin-bottom: 2rem;
    }

    .result-header {
      background: linear-gradient(135deg, var(--primary-teal) 0%, var(--primary-teal-dark) 100%);
      color: white;
      padding: 2rem;
      border-radius: 12px;
      text-align: center;
      margin-bottom: 2rem;
    }

    .result-header h2 {
      font-size: 1.5rem;
      margin-bottom: 1rem;
      color: white;
    }

    .result-score {
      font-size: 3rem;
      font-weight: 700;
      margin: 1rem 0;
    }

    .result-summary {
      font-size: 1.25rem;
      opacity: 0.95;
      margin-bottom: 0.5rem;
    }

    .severity-badge {
      display: inline-block;
      padding: 0.5rem 1.5rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      background-color: rgba(255, 255, 255, 0.2);
      color: white;
      margin-top: 1rem;
    }

    .alert-message {
      color: var(--text-dark);
      padding: 1.25rem 1.5rem;
      border-radius: 8px;
      margin-bottom: 2rem;
      display: flex;
      align-items: flex-start;
      gap: 1rem;
    }

    body.dark-mode .alert-message {
      background-color: rgba(90, 208, 190, 0.15);
    }

    .recommendations-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .recommendations-card h3 {
      color: var(--text-dark);
      font-size: 1.25rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

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

    .action-buttons {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      justify-content: center;
      margin-top: 2rem;
    }

    .btn-primary-custom {
      background: var(--primary-teal);
      color: white;
      padding: 0.875rem 2rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-primary-custom:hover {
      background: var(--primary-teal-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(90, 208, 190, 0.3);
      color: white;
    }

    .btn-secondary-custom {
      background: transparent;
      color: var(--text-dark);
      padding: 0.875rem 2rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      border: 1px solid var(--border-color);
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-secondary-custom:hover {
      background: rgba(90, 208, 190, 0.1);
      border-color: var(--primary-teal);
      color: var(--primary-teal);
    }

    

    .info-note {
      border: 1px solid rgba(90, 208, 190, 0.3);
      border-radius: 8px;
      padding: 1rem 1.5rem;
      margin-top: 2rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      font-size: 0.9rem;
      color: var(--text-muted);
    }

    /* Responsive */
    @media (max-width: 768px) {
      /* Reset sidebar shift */
      .main-wrapper {
        margin-left: 0;
        padding-top: 1.5rem; /* Adjust padding for mobile view without sidebar */
      }
      
      .sidebar {
        display: none; /* Hide sidebar completely on mobile */
      }

      .content-container {
        padding: 0 1rem;
      }

      .assessment-card {
        padding: 1.5rem;
      }

      .page-header h1 {
        font-size: 1.5rem;
      }

      .result-score {
        font-size: 2.5rem;
      }

      .action-buttons {
        flex-direction: column;
      }

      .btn-primary-custom,
      .btn-secondary-custom {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>
    
  <div class="sidebar">
    <div class="logo-wrapper">
      <img src="images/Mindcare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <nav class="nav flex-column" style="flex: 1;">
      
      <a class="nav-link active" href="pre_assessment.php">
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
      
      <a class="nav-link" href="faq.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        FAQ
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
  </div>
  <div class="main-wrapper">
    <div class="content-container">
      
      <?php if ($assessment_result): ?>
        <div class="results-section">
          <div class="result-header">
            <h2>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 0.5rem;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
              Your Assessment Results
            </h2>
            <div class="result-score"><?= htmlspecialchars($assessment_result['score']) ?></div>
            <div class="result-summary"><?= htmlspecialchars($assessment_result['summary']) ?></div>
            <span class="severity-badge">
              <?= ucfirst($recommendation_data['severity']) ?> Level
            </span>
          </div>

          <div class="alert-message">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 0.125rem;">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
              <strong><?= htmlspecialchars($recommendation_data['message']) ?></strong>
            </div>
          </div>

          <div class="recommendations-card">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20h9"></path>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
              </svg>
              Recommended Actions
            </h3>
            <ul class="recommendation-list">
              <?php foreach ($recommendation_data['recommendations'] as $recommendation): ?>
                <li>
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 0.125rem; color: var(--primary-teal);">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                  <span><?= htmlspecialchars($recommendation) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <div class="action-buttons">
            <?php if ($recommendation_data['severity'] === 'moderate' || $recommendation_data['severity'] === 'severe'): ?>
              <a href="register.php" class="btn-primary-custom">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <line x1="19" y1="8" x2="19" y2="14"></line>
                  <line x1="22" y1="11" x2="16" y2="11"></line>
                </svg>
                Create Account & Book Consultation
              </a>
            <?php endif; ?>
            
            <a href="pre_assessment.php?reset=1" class="btn-secondary-custom">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"></polyline>
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
              </svg>
              Take Assessment Again
            </a>
            
            <a href="index.php" class="btn-secondary-custom">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
              </svg>
              Back to Home
            </a>
          </div>

          <div class="info-note">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
              <strong>Note:</strong> These results are temporary and anonymous. They will disappear when you refresh this page. 
              <?php if (!isset($_SESSION['user'])): ?>
                Create a MindCare account to track your progress over time and access professional support.
              <?php endif; ?>
            </div>
          </div>
        </div>

      <?php else: ?>
        <div class="page-header">
          <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 0.5rem; color: var(--primary-teal);">
              <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
              <path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z"></path>
            </svg>
            Quick Mental Health Check
          </h1>
          <p class="subtitle">Anonymous & Confidential - Get instant insights into your wellbeing</p>
        </div>

        <div class="assessment-card">
          <form method="POST" action="">
            <div class="question-item">
              <label>
                <span class="question-number">1.</span>
                Over the past two weeks, how often have you felt down, depressed, or hopeless?
              </label>
              <select name="q1" required>
                <option value="">-- Select an answer --</option>
                <option value="0">Not at all</option>
                <option value="1">Several days</option>
                <option value="2">More than half the days</option>
                <option value="3">Nearly every day</option>
              </select>
            </div>

            <div class="question-item">
              <label>
                <span class="question-number">2.</span>
                How often have you had trouble falling asleep, staying asleep, or sleeping too much?
              </label>
              <select name="q2" required>
                <option value="">-- Select an answer --</option>
                <option value="0">Not at all</option>
                <option value="1">Several days</option>
                <option value="2">More than half the days</option>
                <option value="3">Nearly every day</option>
              </select>
            </div>

            <div class="question-item">
              <label>
                <span class="question-number">3.</span>
                How often have you felt nervous, anxious, or on edge?
              </label>
              <select name="q3" required>
                <option value="">-- Select an answer --</option>
                <option value="0">Not at all</option>
                <option value="1">Several days</option>
                <option value="2">More than half the days</option>
                <option value="3">Nearly every day</option>
              </select>
            </div>

            <div class="submit-area">
              <button type="submit" class="btn-submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: middle;">
                  <polyline points="9 11 12 14 22 4"></polyline>
                  <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                Submit Assessment
              </button>
            </div>
          </form>

          <div class="info-note">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
            <div>
              <strong>Your privacy matters:</strong> This assessment is completely anonymous. Your responses are not stored and will only be shown to you temporarily.
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <script>
    // Dark Mode Icons
    const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
    const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';

    // Dark Mode Toggle Functionality
    const toggleBtn = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeLabel = document.getElementById('themeLabel');

    function updateThemeVisuals(isDark) {
        themeLabel.textContent = isDark ? 'Dark Mode' : 'Light Mode';
        themeIcon.innerHTML = isDark ? moonIcon : sunIcon;
    }

    function loadTheme() {
        const prefersDark = localStorage.getItem('dark-mode') === 'true';
        if (prefersDark) {
            document.body.classList.add('dark-mode');
        }
        updateThemeVisuals(prefersDark);
    }
    
    toggleBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('dark-mode', isDark);
        
        themeIcon.style.transform = 'rotate(360deg)';
        setTimeout(() => themeIcon.style.transform = 'rotate(0deg)', 500);
        
        updateThemeVisuals(isDark);
    });

    // Initial load for non-DOM content
    document.addEventListener('DOMContentLoaded', loadTheme);
    themeIcon.style.transition = 'transform 0.5s ease';
  </script>
</body>
</html>