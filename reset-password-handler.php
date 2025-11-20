<?php
session_start();
include 'supabase.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot-password.php");
    exit;
}

// Get form data
$email = trim($_POST['email'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate input
if (empty($email) || empty($new_password) || empty($confirm_password)) {
    header("Location: forgot-password.php?error=All fields are required");
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: forgot-password.php?error=Invalid email format");
    exit;
}

// Check if passwords match
if ($new_password !== $confirm_password) {
    header("Location: forgot-password.php?error=Passwords do not match");
    exit;
}

// Check password length
if (strlen($new_password) < 6) {
    header("Location: forgot-password.php?error=Password must be at least 6 characters long");
    exit;
}

// Find user by email using Supabase with RLS bypass
$users = supabaseSelect('users', ['email' => $email], '*', null, null, true);

if (empty($users)) {
    header("Location: forgot-password.php?error=Email not found in our system");
    exit;
}

$user = $users[0];

// Hash new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password in database
$result = supabaseUpdate(
    'users',
    ['id' => $user['id']],
    ['password' => $hashed_password],
    true  // Bypass RLS
);

if (isset($result['error'])) {
    error_log("Password reset error: " . json_encode($result));
    header("Location: forgot-password.php?error=Failed to update password. Please try again");
    exit;
}

$_SESSION['password_reset_success'] = [
    'email' => $email,
    'name' => $user['fullname'],
    'timestamp' => date('Y-m-d H:i:s')
];

// Success - redirect to forgot-password page to show modal
header("Location: forgot-password.php?reset_success=1");
exit;