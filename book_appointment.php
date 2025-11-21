<?php
session_start();
include 'supabase.php';

$user_name = $_SESSION['user']['fullname'] ?? 'User';

// FIXED: Get current date/time for greeting - using same logic as dashboard
date_default_timezone_set('Asia/Manila');
$hour = (int)date('G'); // Get hour in 24-hour format

// FIXED: Same greeting logic as dashboard.php
if ($hour >= 5 && $hour < 12) {
  $greeting = 'Good Morning';
} elseif ($hour >= 12 && $hour < 18) {
  $greeting = 'Good Afternoon';
} else {
  $greeting = 'Good Evening';
}

$current_date = date('l, F j, Y, g:i A');

// --- NEW RESCHEDULE LOGIC START ---
$appointment_id = $_GET['appointment_id'] ?? null;
$is_rescheduling = false;
$original_specialist_id = null;
$user_id = $_SESSION['user']['id']; // Required for RLS checks if not bypassing

if ($appointment_id) {
    // Fetch the original appointment details to pre-select the specialist
    $originalAppointment = supabaseSelect(
        'appointments',
        ['id' => $appointment_id, 'user_id' => $user_id], // Added user_id check for security
        'specialist_id',
        null,
        1, 
        true // Use SERVICE_KEY for fetch
    );

    if (!empty($originalAppointment)) {
        $is_rescheduling = true;
        $original_specialist_id = $originalAppointment[0]['specialist_id'];
    } else {
        $appointment_id = null;
    }
}
// --- NEW RESCHEDULE LOGIC END ---


// =========================================
// DYNAMIC: Fetch specialists from Supabase
// =========================================
$specialistUsers = supabaseSelect(
  'users',
  ['role' => 'Specialist'],
  'id,fullname,email,gender,created_at',
  'fullname.asc'
);

// Transform to match existing structure with sensible defaults
$specialists = [];
foreach ($specialistUsers as $specialist) {
  $specialists[$specialist['id']] = [
    'name' => $specialist['fullname'],
    'role' => 'Psychologist',
    'description' => 'Registered Psychologist',
    'profile_pic' => 'images/Dr.Dela.jpg',
    'location' => 'Metro Manila',
    'experience' => '2 Years',
    'contact' => 'Contact via platform',
    'availability' => [
      'Monday' => ['09:00 AM - 10:00 AM', '10:00 AM - 11:00 AM', '02:00 PM - 03:00 PM'],
      'Tuesday' => ['09:00 AM - 10:00 AM', '11:00 AM - 12:00 PM'],
      'Wednesday' => ['10:00 AM - 11:00 AM', '01:00 PM - 02:00 PM'],
      'Thursday' => ['09:00 AM - 10:00 AM', '03:00 PM - 04:00 PM'],
      'Friday' => ['10:00 AM - 11:00 AM', '02:00 PM - 03:00 PM']
    ]
  ];
}

if (empty($specialists)) {
  $specialists = [
    0 => [
      'name' => 'No Specialists Available',
      'role' => 'N/A',
      'description' => 'Please check back later',
      'profile_pic' => 'images/Dr.Dela.jpg',
      'location' => 'N/A',
      'experience' => 'N/A',
      'contact' => 'N/A',
      'availability' => []
    ]
  ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $is_rescheduling ? 'Reschedule Appointment' : 'Book Appointment' ?> - MindCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="mobile.css" />
  <style>
    /* CSS STYLES OMITTED FOR BREVITY (Assumed to be correct) */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary-teal: #5ad0be;
      --primary-teal-dark: #17a08d;
      --text-dark: #2b2f38;
      --text-muted: #6b7280;
      --bg-light: #f8f9fa;
      --sidebar-bg: #f5f6f7;
      --card-bg: #f9fafb;
      --border-color: #e5e7eb;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
      background-color: var(--bg-light);
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
      transition: all 0.2s ease;
    }

    .sidebar .nav-link:hover {
      background-color: rgba(90, 208, 190, 0.1);
      color: var(--primary-teal);
    }

    .sidebar .nav-link.active {
      background-color: var(--primary-teal);
      color: white;
    }

    /* Theme Toggle */
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

    /* Main Wrapper */
    .main-wrapper {
      margin-left: 250px;
      padding: 2rem 3rem;
      max-width: 1400px;
    }

    /* Page Header */
    .page-header {
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 1.75rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 0.25rem;
      transition: color 0.3s ease;
    }

    .page-header .user-name {
      color: var(--primary-teal);
    }

    .date-time {
      font-size: 0.875rem;
      color: var(--text-muted);
      transition: color 0.3s ease;
    }

    /* Section Heading */
    .section-heading {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
      transition: color 0.3s ease;
    }

    .section-heading .highlight {
      color: var(--primary-teal);
    }

    .section-subtext {
      font-size: 0.9375rem;
      color: var(--text-muted);
      margin-bottom: 2rem;
      transition: color 0.3s ease;
    }

    /* Specialist Selection View */
    .specialist-selection-view {
      display: block;
      animation: fadeIn 0.5s ease-in;
    }

    .specialist-selection-view.hidden {
      display: none;
    }

    /* Booking View (Calendar + Timeslots) */
    .booking-view {
      display: none;
      animation: fadeIn 0.5s ease-in;
    }

    .booking-view.active {
      display: block;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Specialist Cards - Full width stacked layout */
    .specialist-grid {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .specialist-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 0;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      display: flex;
      flex-direction: row;
      overflow: hidden;
      min-height: 200px;
    }
    
    .specialist-card.reschedule-highlight {
        border-color: var(--primary-teal-dark);
        border-width: 2px;
        background: rgba(90, 208, 190, 0.05);
    }

    body.dark-mode .specialist-card {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .specialist-card:hover {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    body.dark-mode .specialist-card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .specialist-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0;
      flex: 1;
      padding: 1.5rem 2rem 0 2rem;
    }

    .specialist-info-wrapper {
      display: flex;
      gap: 0;
      align-items: flex-start;
      width: 100%;
    }

    .specialist-profile-pic {
      width: 200px;
      height: 100%;
      border-radius: 0;
      object-fit: contain;
      object-position: center;
      background-color: var(--card-bg);
      border: none;
      border-right: 3px solid var(--border-color);
      flex-shrink: 0;
    }

    .specialist-info {
      flex: 1;
      padding-left: 2rem;
    }

    .specialist-badge {
      display: inline-block;
      padding: 0;
      font-size: 0.875rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: var(--primary-teal);
    }

    .badge-psychologist {
      color: var(--primary-teal);
    }

    .badge-psychiatrist {
      color: #ff6da2;
    }

    .specialist-name {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 0.25rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .specialist-name .verified-icon {
      color: #10b981;
      font-size: 1rem;
    }

    .specialist-role {
      color: var(--text-muted);
      font-size: 0.875rem;
      margin-bottom: 0;
    }

    .specialist-details-grid {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 0.5rem 2rem;
      margin-top: 0.5rem;
    }

    .specialist-details-left {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .specialist-details-right {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      text-align: right;
    }

    .specialist-details-row {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid var(--border-color);
    }

    .specialist-detail-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.8125rem;
      color: var(--text-muted);
    }

    .specialist-detail-item i {
      color: var(--primary-teal);
      font-size: 0.875rem;
      width: 16px;
      text-align: center;
    }


    .specialist-content {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: 0 2rem 1.5rem 4rem;
    }

    .specialist-next-info {
      display: flex;
      flex-direction: column;
      gap: 0.125rem;
      align-self: flex-start;
    }

    .specialist-next-label {
      font-size: 0.875rem;
      color: var(--text-muted);
      font-weight: 400;
    }

    .specialist-next-time {
      font-size: 0.875rem;
      color: var(--text-dark);
      font-weight: 400;
    }

    .specialist-status {
      position: absolute;
      top: 1.5rem;
      right: 2rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.8125rem;
      font-weight: 500;
    }

    .status-available {
      color: var(--primary-teal);
    }

    .status-available::before {
      content: '●';
      font-size: 0.75rem;
    }

    .status-unavailable {
      color: #ef4444;
    }

    .status-unavailable::before {
      content: '●';
      font-size: 0.75rem;
    }

    .btn-book-specialist {
      background: var(--primary-teal-dark);
      color: white;
      border: none;
      padding: 0.625rem 1.25rem;
      border-radius: 6px;
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .specialist-card.reschedule-highlight .btn-book-specialist {
        background: #0d9488;
    }


    .btn-book-specialist:hover {
      background: #148e7f;
      transform: translateY(-1px);
    }

    .back-button {
      background: var(--card-bg);
      color: var(--text-dark);
      border: 1px solid var(--border-color);
      padding: 0.625rem 1.125rem;
      border-radius: 8px;
      font-size: 0.875rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 1.5rem;
    }

    .back-button:hover {
      background: var(--primary-teal);
      color: white;
      border-color: var(--primary-teal);
    }

    .selected-specialist-bar {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 1rem 1.5rem;
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .selected-specialist-info {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .selected-specialist-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
    }

    #selectedSpecialistName {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-dark);
      margin: 0;
    }

    #selectedSpecialistRole {
      font-size: 0.8125rem;
      color: var(--text-muted);
      margin: 0;
    }

    .booking-section {
      display: grid;
      grid-template-columns: 400px 1fr;
      gap: 2rem;
      margin-bottom: 2rem;
    }

    .calendar-wrapper {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .calendar-month {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-dark);
    }

    .calendar-nav {
      display: flex;
      gap: 0.5rem;
    }

    .calendar-nav button {
      background: transparent;
      border: 1px solid var(--border-color);
      width: 32px;
      height: 32px;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      color: var(--text-dark);
    }

    .calendar-nav button:hover {
      background: var(--bg-light);
      border-color: var(--primary-teal);
    }

    body.dark-mode .calendar-nav button {
      background: transparent;
      border-color: var(--border-color);
      color: var(--text-dark);
    }

    body.dark-mode .calendar-nav button:hover {
      background: rgba(90, 208, 190, 0.1);
      border-color: var(--primary-teal);
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 0.5rem;
    }

    .calendar-day-header {
      text-align: center;
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--text-muted);
      padding: 0.5rem;
    }

    .calendar-day {
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.2s ease;
      color: var(--text-dark);
    }

    .calendar-day.empty {
      color: var(--text-muted);
      opacity: 0.3;
      cursor: default;
    }

    .calendar-day.disabled {
      color: var(--text-muted);
      opacity: 0.4;
      cursor: not-allowed;
      background: rgba(0, 0, 0, 0.02);
    }

    .calendar-day.disabled.today {
      opacity: 1;
      border: 2px solid var(--primary-teal);
      background: rgba(90, 208, 190, 0.2);
      font-weight: 700;
      color: var(--primary-teal);
    }

    .calendar-day:not(.empty):not(.disabled):hover {
      background: rgba(90, 208, 190, 0.1);
    }

    .calendar-day.selected {
      background: var(--primary-teal);
      color: white;
      font-weight: 600;
    }

    .calendar-day.today {
      border: 2px solid var(--primary-teal);
      background: rgba(90, 208, 190, 0.15);
      font-weight: 600;
    }

    .calendar-day.today.selected {
      background: var(--primary-teal);
      color: white;
    }

    .timeslots-wrapper {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 1.5rem;
      max-height: 600px;
      overflow-y: auto;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .day-timeslots {
      margin-bottom: 1.5rem;
    }

    .day-timeslots:last-child {
      margin-bottom: 0;
    }

    .day-label {
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 0.75rem;
    }

    .timeslot-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 0.75rem;
    }

    .timeslot-btn {
      background: rgba(90, 208, 190, 0.2);
      color: var(--primary-teal);
      border: 1px solid var(--primary-teal);
      padding: 0.75rem;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .timeslot-btn:hover:not(:disabled) {
      background: rgba(90, 208, 190, 0.3);
      color: white;
    }

    .timeslot-btn.selected {
      background: var(--primary-teal);
      color: white;
      border-color: var(--primary-teal);
    }

    .timeslot-btn:disabled {
      background: rgba(128, 128, 128, 0.2);
      color: var(--text-muted);
      border: 1px solid var(--border-color);
      cursor: not-allowed;
      opacity: 0.5;
    }

    body.dark-mode .timeslot-btn {
      background: rgba(90, 208, 190, 0.2);
      color: var(--primary-teal);
      border: 1px solid var(--primary-teal);
    }

    body.dark-mode .timeslot-btn:hover:not(:disabled) {
      background: rgba(90, 208, 190, 0.3);
      color: white;
    }

    body.dark-mode .timeslot-btn.selected {
      background: var(--primary-teal);
      color: white;
      border-color: var(--primary-teal);
    }

    body.dark-mode .timeslot-btn:disabled {
      background: rgba(128, 128, 128, 0.15);
      color: var(--text-muted);
      border: 1px solid var(--border-color);
    }
    
    .submit-wrapper {
      text-align: center;
      margin-top: 2rem;
    }

    .btn-book {
      background: #0d9488;
      color: white;
      border: none;
      padding: 1rem 4rem;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
    }
    
    .btn-reschedule {
        background: #f59e0b;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .btn-book:hover {
      background: #0f766e;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(13, 148, 136, 0.4);
    }
    
    .btn-reschedule:hover {
        background: #d97706; 
        box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
    }

    .btn-book:disabled {
      background: var(--text-muted);
      cursor: not-allowed;
      box-shadow: none;
      transform: none;
    }

    @media (max-width: 1024px) {
      .booking-section {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .main-wrapper {
        margin-left: 0;
        padding: 1.5rem;
      }

      .specialist-card {
        flex-direction: column;
      }

      .specialist-profile-pic {
        width: 100%;
        height: 200px;
        border-right: none;
        border-bottom: 3px solid var(--border-color);
      }

      .specialist-info {
        padding-left: 0;
        padding: 1.5rem;
      }

      .specialist-header {
        padding: 1.5rem 1.5rem 0 1.5rem;
      }

      .specialist-content {
        padding: 0 1.5rem 1.5rem 1.5rem;
      }

      .specialist-details-grid {
        grid-template-columns: 1fr;
      }

      .specialist-details-right {
        text-align: left;
      }
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="logo-wrapper">
      <img src="images/MindCare.png" alt="MindCare Logo" class="logo-img" />
    </div>

    <nav>
      <a class="nav-link" href="dashboard.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
        DASHBOARD
      </a>
      <a class="nav-link" href="resources.php">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
          viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>
      </svg>        RESOURCES
      </a>
      <a class="nav-link" href="assessment.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        ASSESSMENT
      </a>
      <a class="nav-link active" href="book_appointment.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        BOOK APPOINTMENT
      </a>
      <a class="nav-link" href="appointments.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
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

  <div class="main-wrapper">
    
    <div class="page-header">
      <h1><?= $greeting ?>, <span class="user-name"><?= htmlspecialchars($user_name) ?></span>!</h1>
      <p class="date-time">Today is <?= $current_date ?></p>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="specialist-selection-view" id="specialistView">
      <?php if ($is_rescheduling): ?>
        <h2 class="section-heading">Reschedule <span class="highlight">Appointment #<?= $appointment_id ?></span></h2>
        <p class="section-subtext">Select a specialist and a new date/time below. You may choose your original specialist or another one.</p>
      <?php else: ?>
        <h2 class="section-heading">Book an <span class="highlight">Appointment</span></h2>
        <p class="section-subtext">Please choose your preferred specialist below:</p>
      <?php endif; ?>

      <div class="specialist-grid">
        <?php foreach ($specialists as $id => $specialist): ?>
          <?php
            $days = array_keys($specialist['availability']);
            $nextDay = $days[0] ?? 'N/A';
            $nextTime = $specialist['availability'][$nextDay][0] ?? 'N/A';
            $isAvailable = !empty($specialist['availability']);
            $profilePic = $specialist['profile_pic'] ?? 'images/Dr.Dela.jpg';
            $highlightClass = ($is_rescheduling && (int)$id === (int)$original_specialist_id) ? ' reschedule-highlight' : '';
          ?>
          <div class="specialist-card<?= $highlightClass ?>" onclick="selectSpecialist(<?= $id ?>)">
            <img src="<?= htmlspecialchars($profilePic) ?>" alt="<?= htmlspecialchars($specialist['name']) ?>" class="specialist-profile-pic" onerror="this.src='https://via.placeholder.com/200x200/5ad0be/ffffff?text=<?= substr($specialist['name'], 0, 1) ?>'">
            
            <div style="flex: 1; display: flex; flex-direction: column;">
              <div class="specialist-status <?= $isAvailable ? 'status-available' : 'status-unavailable' ?>">
                <?= $isAvailable ? 'Available' : 'Unavailable' ?>
              </div>
              
              <div class="specialist-header">
                <div class="specialist-info-wrapper">
                  <div class="specialist-info">
                    <div class="specialist-badge <?= strtolower($specialist['role']) === 'psychologist' ? 'badge-psychologist' : 'badge-psychiatrist' ?>">
                      <?= $specialist['role'] ?>
                    </div>
                    
                    <div class="specialist-name">
                      <?= htmlspecialchars($specialist['name']) ?>
                      <span class="verified-icon">●</span>
                    </div>
                    
                    <div class="specialist-details-grid">
                      <div class="specialist-details-left">
                        <div class="specialist-role"><?= htmlspecialchars($specialist['description']) ?></div>
                        <div class="specialist-detail-item">
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                          <span><?= htmlspecialchars($specialist['location']) ?></span>
                        </div>
                      </div>
                      
                      <div class="specialist-details-right">
                        <div class="specialist-detail-item">
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                          <span><?= htmlspecialchars($specialist['contact']) ?></span>
                        </div>
                        <div class="specialist-detail-item">
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                          <span><?= htmlspecialchars($specialist['experience']) ?> of Experience</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            
              <div class="specialist-content">
                <div class="specialist-next-info">
                  <span class="specialist-next-label">Next available at</span>
                  <span class="specialist-next-time"><?= $nextTime ?> - <?= date('d', strtotime('next ' . $nextDay)) ?> <?= date('M', strtotime('next ' . $nextDay)) ?>, <?= substr($nextDay, 0, 3) ?></span>
                </div>
                
                <button class="btn-book-specialist" onclick="event.stopPropagation(); selectSpecialist(<?= $id ?>)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                  <?= $is_rescheduling ? 'Select Specialist' : 'Book Appointment' ?>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="booking-view" id="bookingView">
      <button class="back-button" onclick="goBackToSpecialists()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to Specialists
      </button>

      <div class="selected-specialist-bar">
        <div class="selected-specialist-info">
          <img src="" alt="Specialist" id="selectedSpecialistAvatar" class="selected-specialist-avatar">
          <div>
            <h5 id="selectedSpecialistName">Dr. Name</h5>
            <p id="selectedSpecialistRole">Psychologist</p>
          </div>
        </div>
      </div>

      <div class="booking-section">
        <div class="calendar-wrapper">
          <div class="calendar-header">
            <div class="calendar-month" id="calendarMonth">October 2025</div>
            <div class="calendar-nav">
              <button onclick="previousMonth()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
              </button>
              <button onclick="nextMonth()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </button>
            </div>
          </div>
          <div class="calendar-grid">
            <div class="calendar-day-header">Sun</div>
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>
          </div>
          <div class="calendar-grid" id="calendarDays"></div>
        </div>

        <div class="timeslots-wrapper" id="timeslotsWrapper">
          <p style="color: var(--text-muted); text-align: center;">Select a date to view available times</p>
        </div>
      </div>

      <div class="submit-wrapper">
        <button 
            class="btn-book <?= $is_rescheduling ? 'btn-reschedule' : '' ?>" 
            id="bookBtn" 
            onclick="bookAppointment()" 
            disabled>
            <?= $is_rescheduling ? 'Reschedule Appointment' : 'Book an Appointment' ?>
        </button>
      </div>
    </div>
  </div>

  <script src="mobile.js"></script>

  <script>
    // ============================================
    // DARK MODE TOGGLE (Standard Logic)
    // ============================================
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

    // ============================================
    // GLOBAL STATE & DATA
    // ============================================
    const specialistsData = <?= json_encode($specialists) ?>;
    const isRescheduling = <?= $is_rescheduling ? 'true' : 'false' ?>;
    const rescheduleAppointmentId = '<?= $appointment_id ?>';
    const originalSpecialistId = '<?= $original_specialist_id ?>';

    let selectedSpecialistId = null;
    let selectedDate = null;
    let selectedTime = null;
    // bookedAppointments now stores an array of objects { time, id }
    let bookedAppointments = {}; 

    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();

    // ============================================
    // FETCH BOOKED APPOINTMENTS FOR SPECIALIST (Updated)
    // ============================================
    async function fetchBookedAppointments(specialistId) {
      try {
        // Calls the new PHP file to get occupied slots
        const response = await fetch('get_booked_slots.php?specialist_id=' + specialistId);
        const data = await response.json();
        // data format: { "2025-11-25": [{time: "09:00:00", id: 10}, {time: "10:00:00", id: 18}], ... }
        bookedAppointments = data; 
      } catch (error) {
        console.error('Error fetching booked appointments:', error);
        bookedAppointments = {};
      }
    }

    // ============================================
    // VIEW SWITCHING (selectSpecialist)
    // ============================================
    async function selectSpecialist(specialistId) {
      selectedSpecialistId = specialistId;
      const specialist = specialistsData[specialistId];

      await fetchBookedAppointments(specialistId);

      document.getElementById('selectedSpecialistName').textContent = specialist.name;
      document.getElementById('selectedSpecialistRole').textContent = specialist.description;
      document.getElementById('selectedSpecialistAvatar').src = specialist.profile_pic;

      document.getElementById('specialistView').classList.add('hidden');
      document.getElementById('bookingView').classList.add('active');

      renderCalendar();
    }

    function goBackToSpecialists() {
      document.getElementById('bookingView').classList.remove('active');
      document.getElementById('specialistView').classList.remove('hidden');
      
      selectedSpecialistId = null;
      selectedDate = null;
      selectedTime = null;
      bookedAppointments = {};
      document.getElementById('bookBtn').disabled = true;
    }
    
    // Auto-select specialist if rescheduling
    if (isRescheduling && originalSpecialistId && specialistsData[originalSpecialistId]) {
        setTimeout(() => {
            selectSpecialist(originalSpecialistId);
        }, 100);
    }

    // ============================================
    // CALENDAR RENDERING (selectDate)
    // ============================================
    function renderCalendar() {
      const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
      
      document.getElementById('calendarMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;
      
      const firstDay = new Date(currentYear, currentMonth, 1).getDay();
      const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
      
      let calendarHTML = '';
      
      for (let i = 0; i < firstDay; i++) {
        calendarHTML += '<div class="calendar-day empty"></div>';
      }
      
      for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(currentYear, currentMonth, day);
        const isWeekday = date.getDay() >= 1 && date.getDay() <= 5;
        
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const currentDate = new Date(currentYear, currentMonth, day);
        
        const isToday = (currentDate.getFullYear() === today.getFullYear() && 
                        currentDate.getMonth() === today.getMonth() && 
                        currentDate.getDate() === today.getDate());
        const isPast = currentDate < today;
        
        if (isToday) {
          if (isWeekday && !isPast) {
            calendarHTML += `<div class="calendar-day today" onclick="selectDate(${day})">${day}</div>`;
          } else {
            calendarHTML += `<div class="calendar-day disabled today">${day}</div>`; 
          }
        } else if (isWeekday && !isPast) {
          calendarHTML += `<div class="calendar-day" onclick="selectDate(${day})">${day}</div>`;
        } else {
          calendarHTML += `<div class="calendar-day disabled">${day}</div>`;
        }
      }
      
      document.getElementById('calendarDays').innerHTML = calendarHTML;
      document.getElementById('timeslotsWrapper').innerHTML = '<p style="color: var(--text-muted); text-align: center;">Select a date to view available times</p>';
    }

    function selectDate(day) {
      const date = new Date(currentYear, currentMonth, day);
      
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const tomorrow = new Date(today);
      tomorrow.setDate(tomorrow.getDate() + 1);
      date.setHours(0, 0, 0, 0);
      
      if (date.getTime() < tomorrow.getTime() && date.getTime() !== today.getTime()) {
         return; 
      }
      
      const dayName = date.toLocaleDateString('en-US', { weekday: 'long' });
      
      selectedDate = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      
      document.querySelectorAll('.calendar-day').forEach(el => {
        el.classList.remove('selected');
      });
      
      if(event && event.target && !event.target.classList.contains('disabled')) {
        event.target.classList.add('selected');
      }

      renderTimeSlots(dayName);
      document.getElementById('bookBtn').disabled = true;
      selectedTime = null;
    }

    // ============================================
    // TIME SLOT RENDERING (Updated to check for booked slots)
    // ============================================
    function renderTimeSlots(dayName) {
      const wrapper = document.getElementById('timeslotsWrapper');
      const specialist = specialistsData[selectedSpecialistId];
      const availableSlots = specialist.availability[dayName] || [];

      if (availableSlots.length === 0) {
        wrapper.innerHTML = '<p style="color: var(--text-muted); text-align: center;">No available times for this day</p>';
        return;
      }

      // Get booked slots for the selected date (an array of {time, id} objects)
      const bookedSlots = bookedAppointments[selectedDate] || [];
      
      const now = new Date();
      const todayDateStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
      const isSelectedDateToday = selectedDate === todayDateStr;

      let html = '<div class="day-timeslots">';
      html += `<div class="day-label">${dayName}</div>`;
      html += '<div class="timeslot-grid">';

      availableSlots.forEach(timeRange => {
        const time12h = timeRange.split(' - ')[0];
        const startTime = convertTo24Hour(time12h);
        
        // Check if this time slot is booked by ANYONE
        const bookedSlotInfo = bookedSlots.find(slot => slot.time === startTime);
        const isBooked = !!bookedSlotInfo;
        
        // If rescheduling, check if this is the appointment currently being rescheduled
        const isOwnAppointment = isRescheduling && bookedSlotInfo && bookedSlotInfo.id == rescheduleAppointmentId;

        // Check if the time slot is in the past (only relevant for today's date)
        let isPast = false;
        if (isSelectedDateToday) {
            const [hours, minutes] = startTime.split(':');
            const slotTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), parseInt(hours), parseInt(minutes));
            
            isPast = now.getTime() > slotTime.getTime() + (60 * 1000); 
        }

        // Slot is disabled if it is booked by someone else OR if it is in the past
        // Crucially: A user CAN select their own slot if rescheduling.
        const isDisabled = (isBooked && !isOwnAppointment) || isPast;

        if (isDisabled) {
          const statusLabel = isPast ? '(Past)' : '(Booked)';
          html += `<button class="timeslot-btn" disabled>${timeRange} ${statusLabel}</button>`;
        } else {
          html += `<button class="timeslot-btn" onclick="selectTime('${timeRange}', '${startTime}')">${timeRange}</button>`;
        }
      });

      html += '</div></div>';
      wrapper.innerHTML = html;
    }

    // Helper function to convert 12-hour time to 24-hour format
    function convertTo24Hour(time12h) {
      const [time, modifier] = time12h.split(' ');
      let [hours, minutes] = time.split(':');
      
      if (modifier === 'AM' && hours === '12') {
        hours = '00';
      } else if (modifier === 'PM' && hours !== '12') {
        hours = parseInt(hours, 10) + 12;
      }
      
      return `${String(hours).padStart(2, '0')}:${minutes}:00`;
    }

    function selectTime(timeRange, time24h) {
      selectedTime = time24h;
      
      document.querySelectorAll('.timeslot-btn').forEach(btn => {
        btn.classList.remove('selected');
        if (btn.textContent.trim().startsWith(timeRange)) {
          btn.classList.add('selected');
        }
      });

      document.getElementById('bookBtn').disabled = false;
    }

    function previousMonth() {
        const tempDate = new Date(currentYear, currentMonth, 1);
        const today = new Date();
        if (tempDate.getMonth() <= today.getMonth() && tempDate.getFullYear() <= today.getFullYear()) {
            return;
        }
        
        if (currentMonth === 0) {
            currentMonth = 11;
            currentYear--;
        } else {
            currentMonth--;
        }
        renderCalendar();
    }

    function nextMonth() {
      if (currentMonth === 11) {
        currentMonth = 0;
        currentYear++;
      } else {
        currentMonth++;
      }
      renderCalendar();
    }

    // ============================================
    // BOOK/RESCHEDULE APPOINTMENT
    // ============================================
    function bookAppointment() {
      if (!selectedSpecialistId || !selectedDate || !selectedTime) {
        alert('Please select a date and time');
        return;
      }

      const form = document.createElement('form');
      form.method = 'POST';
      
      if (isRescheduling) {
          form.action = 'reschedule_confirm.php'; 
      } else {
          form.action = 'confirm_appointment.php';
      }

      const inputs = {
        specialist_id: selectedSpecialistId,
        appointment_date: selectedDate,
        appointment_time: selectedTime
      };
      
      if (isRescheduling) {
          inputs.appointment_id = rescheduleAppointmentId;
      }

      for (const [name, value] of Object.entries(inputs)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
      }

      document.body.appendChild(form);
      form.submit();
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>