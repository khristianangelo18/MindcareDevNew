<?php
session_start();
// 1. Check if user is logged in
if (!isset($_SESSION['user'])) {
    // Redirect to login if not authenticated
    header("Location: login.php");
    exit;
}

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit;
}

// Include Supabase access file
include 'supabase.php';

// Get user info from session
$user_id = $_SESSION['user']['id'];
$user_email = $_SESSION['user']['email'];

// Get form data
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// --- BEGIN SERVER-SIDE VALIDATION ---

// Check if all fields are present
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header("Location: change-password.php?message=" . urlencode("All password fields must be filled out.") . "&type=danger");
    exit;
}

// Check if passwords match
if ($new_password !== $confirm_password) {
    header("Location: change-password.php?message=" . urlencode("New password and confirmation password do not match.") . "&type=danger");
    exit;
}

// Check password length
if (strlen($new_password) < 6) {
    header("Location: change-password.php?message=" . urlencode("New password must be at least 6 characters long.") . "&type=danger");
    exit;
}

// Check if new password is the same as current
if ($new_password === $current_password) {
    header("Location: change-password.php?message=" . urlencode("New password must be different from the current password.") . "&type=danger");
    exit;
}

// --- VERIFICATION AND UPDATE ---

// 2. A) Verify Current Password (Crucial step using logic from reset handler)
// Fetch the user's current hashed password from the database
$users = supabaseSelect('users', ['id' => $user_id], 'id,password', null, 1, true);

if (empty($users)) {
    // Critical error: user data not found, redirect to safe logout
    session_destroy();
    header("Location: login.php?error_message=" . urlencode("Authentication failed. Please log in again."));
    exit;
}

$user = $users[0];
$current_hashed_password = $user['password'];

// Verify the current password provided by the user against the stored hash
if (!password_verify($current_password, $current_hashed_password)) {
    // Current password is wrong
    header("Location: change-password.php?message=" . urlencode("The current password you entered is incorrect.") . "&type=danger");
    exit;
}

// 2. B) Hash New Password
$hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

// 2. C) Update password in database
$result = supabaseUpdate(
    'users',
    ['id' => $user_id],
    ['password' => $hashed_new_password],
    true // Bypass RLS
);

if (isset($result['error'])) {
    error_log("Password change error: " . json_encode($result));
    header("Location: change-password.php?message=" . urlencode("Failed to update password due to a system error. Please try again.") . "&type=danger");
    exit;
}

// --- SUCCESS ---

// Redirect to profile page with success message (user stays logged in)
header("Location: profile.php?success=" . urlencode("Your password has been successfully updated."));
exit;