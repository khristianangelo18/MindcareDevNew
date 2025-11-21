<?php
session_start();
include 'supabase.php';

// Ensure the request includes a specialist ID
if (!isset($_GET['specialist_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Specialist ID is required.']);
    exit;
}

$specialist_id = (int)$_GET['specialist_id'];

// 1. Fetch appointments for the selected specialist that are NOT finalized
// We need Pending and Confirmed appointments to block those slots.
$appointments = supabaseSelect(
    'appointments',
    [
        'specialist_id' => $specialist_id,
        // Only fetch active appointments
        'status' => ['operator' => 'in', 'value' => "(Pending,Confirmed)"] 
    ],
    'appointment_date,appointment_time,id', // We need the ID to skip the current appointment during reschedule
    'appointment_date.asc',
    null,
    true
);

$bookedSlots = [];

if (is_array($appointments)) {
    foreach ($appointments as $apt) {
        $date = $apt['appointment_date'];
        $time = date('H:i:s', strtotime($apt['appointment_time'])); // Ensure 24-hour format (e.g., 09:00:00)

        // Initialize the date key if it doesn't exist
        if (!isset($bookedSlots[$date])) {
            $bookedSlots[$date] = [];
        }

        // Store the time slot and the appointment ID
        $bookedSlots[$date][] = [
            'time' => $time,
            'id' => $apt['id']
        ];
    }
}

// 2. Return the array as JSON
header('Content-Type: application/json');
echo json_encode($bookedSlots);
?>