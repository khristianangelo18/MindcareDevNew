<?php
session_start();
include 'supabase.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    
    $appointment_id = $_POST['appointment_id'];
    $user_id = $_SESSION['user']['id'];
    $cancellation_reason = trim($_POST['cancellation_reason'] ?? 'Client cancelled without reason.');
    
    // Sanitize the reason content before use
    $cancellation_reason = filter_var($cancellation_reason, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // 1. Fetch current appointment details and status
    $current_apt = supabaseSelect(
        'appointments',
        ['id' => $appointment_id, 'user_id' => $user_id],
        'status', 
        null,
        1,
        true
    );

    if (empty($current_apt)) {
        $_SESSION['message'] = "Appointment #{$appointment_id} not found or you lack permission.";
        $_SESSION['message_type'] = "danger";
        header("Location: appointments.php");
        exit;
    }

    $current_status = $current_apt[0]['status'];

    // 2. FIXED CHECK: Allow cancellation if the status is Pending OR Confirmed
    if ($current_status === 'Pending' || $current_status === 'Confirmed') {
        
        $where_clause = [
            'id' => $appointment_id,
            'user_id' => $user_id
        ];
        
        // --- STEP 1: Update NOTES first ---
        $notes_update_data = ['notes' => $cancellation_reason];
        
        // CORRECTED CALL ORDER: ($table, $filters, $data, true)
        $notes_result = supabaseUpdate(
            'appointments',
            $where_clause,      // Filters (WHERE)
            $notes_update_data, // Data (SET)
            true 
        );

        if (isset($notes_result['error']) && $notes_result['error'] === true) {
            $_SESSION['message'] = "Cancellation failed: Failed to log cancellation reason (Notes update error).";
            $_SESSION['message_type'] = "danger";
            header("Location: appointments.php");
            exit;
        }

        // --- STEP 2: Update STATUS only (The ENUM field) ---
        $status_update_data = ['status' => 'Cancelled'];
        
        // CORRECTED CALL ORDER: ($table, $filters, $data, true)
        $status_result = supabaseUpdate(
            'appointments',
            $where_clause,       // Filters (WHERE)
            $status_update_data, // Data (SET)
            true 
        );

        // 3. Final Success Check
        if (!empty($status_result) && (isset($status_result['id']) || isset($status_result['success']))) {
            $_SESSION['message'] = "Appointment #{$appointment_id} has been successfully cancelled and reason logged.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Cancellation failed: Database error occurred while setting status to 'Cancelled'.";
            $_SESSION['message_type'] = "danger";
        }

    } else {
        // This handles Completed or already Cancelled appointments (final statuses)
        $_SESSION['message'] = "Cancellation failed: Appointment #{$appointment_id} has a final status ({$current_status}) and cannot be cancelled.";
        $_SESSION['message_type'] = "warning";
    }

} else {
    $_SESSION['message'] = "Invalid request to cancel appointment.";
    $_SESSION['message_type'] = "warning";
}

header("Location: appointments.php");
exit;
?>