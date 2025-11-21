<?php
session_start();
include 'supabase.php';

// Restrict access to Admin or Specialist
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Admin', 'Specialist'])) {
  echo "<script>alert('Access denied.'); window.location.href='login.php';</script>";
  exit;
}

$appointment_id = $_POST['appointment_id'];
$status = $_POST['status'];

// FIX: Added 'Pending' to valid statuses
$valid_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
if (!in_array($status, $valid_statuses)) {
  echo "<script>alert('Invalid status.'); window.history.back();</script>";
  exit;
}

// Added 'true' to ensure the Service Key is used for RLS bypass
$result = supabaseUpdate('appointments', ['id' => $appointment_id], [
  'status' => $status
], true);

if (isset($result['error'])) {
  echo "<script>alert('Failed to update status.'); window.history.back();</script>";
  exit;
}

// Determine redirect based on user role
$redirect_url = 'admin_appointments.php';
if ($_SESSION['user']['role'] === 'Specialist') {
  $redirect_url = 'specialist_dashboard.php';
}

echo "<script>alert('Status updated successfully.'); window.location.href='$redirect_url';</script>";
exit;