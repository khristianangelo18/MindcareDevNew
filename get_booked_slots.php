<?php
session_start();
include 'supabase.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

// Get specialist ID from query parameter
$specialist_id = $_GET['specialist_id'] ?? null;

if (!$specialist_id) {
  http_response_code(400);
  echo json_encode(['error' => 'Specialist ID is required']);
  exit;
}

try {
  // Fetch all appointments for this specialist that are not cancelled
  // Only fetch future or today's appointments
  $appointments = supabaseSelect(
    'appointments',
    [
      'specialist_id' => $specialist_id,
      'status' => ['neq' => 'Cancelled'] // Exclude cancelled appointments
    ],
    'appointment_date,appointment_time',
    'appointment_date.asc,appointment_time.asc'
  );

  // Organize booked slots by date
  $bookedSlots = [];
  
  foreach ($appointments as $appointment) {
    $date = $appointment['appointment_date'];
    $time = $appointment['appointment_time'];
    
    // Initialize array for this date if it doesn't exist
    if (!isset($bookedSlots[$date])) {
      $bookedSlots[$date] = [];
    }
    
    // Store the start time in 24-hour format (HH:MM:SS)
    // This matches what's stored in your database
    $bookedSlots[$date][] = $time;
  }

  // Return JSON response
  header('Content-Type: application/json');
  echo json_encode($bookedSlots);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to fetch booked slots', 'details' => $e->getMessage()]);
}
exit;