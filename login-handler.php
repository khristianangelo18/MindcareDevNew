<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include 'supabase.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
  header("Location: login.php?error=" . urlencode("Email and password are required"));
  exit;
}

// Debug: Log the email being searched
error_log("Login attempt for email: " . $email);

// Get user by email using Supabase REST API with RLS bypass
$users = supabaseSelect('users', ['email' => $email], '*', null, null, true);

// Debug: Log the response
error_log("Supabase response count: " . count($users));

if (empty($users)) {
  error_log("No user found for email: " . $email);
  header("Location: login.php?error=" . urlencode("Email not found"));
  exit;
}

$user = $users[0];

// Verify password
if (!password_verify($password, $user['password'])) {
  error_log("Incorrect password for email: " . $email);
  header("Location: login.php?error=" . urlencode("Incorrect password"));
  exit;
}

// Set session data
$_SESSION['user'] = [
  'id' => $user['id'],
  'fullname' => $user['fullname'],
  'email' => $user['email'],
  'gender' => $user['gender'] ?? null,
  'role' => $user['role'] ?? 'Patient',
  'created_at' => $user['created_at']
];

error_log("Login successful for user ID: " . $user['id']);

// Determine redirect URL based on role
$redirectUrl = 'dashboard.php';
if ($user['role'] === 'Admin') {
  $redirectUrl = 'admin_appointments.php';
} elseif ($user['role'] === 'Specialist') {
  $redirectUrl = 'specialist_dashboard.php';
}

// ULTIMATE FIX: Use meta refresh instead of JavaScript
// This is more reliable and doesn't trigger popup blockers
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="0;url=<?php echo $redirectUrl; ?>">
  <title>Logging in...</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      background: linear-gradient(135deg, #5ad0be 0%, #1aa592 100%);
      color: white;
    }
    .loader {
      text-align: center;
    }
    .spinner {
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top: 4px solid white;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin: 0 auto 20px;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="loader">
    <div class="spinner"></div>
    <p>Logging you in...</p>
    <small>If you are not redirected, <a href="<?php echo $redirectUrl; ?>" style="color: white; text-decoration: underline;">click here</a></small>
  </div>
</body>
</html>