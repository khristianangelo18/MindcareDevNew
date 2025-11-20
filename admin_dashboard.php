<?php
session_start();
include 'supabase.php';

// Restrict access to admins only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
  echo "<script>alert('Access denied.'); window.location.href='login.php';</script>";
  exit;
}

// Fetch all appointments with patient and specialist info
$stmt = $conn->prepare("
  SELECT 
    a.id AS appointment_id,
    u1.fullname AS patient_name,
    u2.fullname AS specialist_name,
    a.appointment_date,
    a.appointment_time,
    a.status
  FROM appointments a
  JOIN users u1 ON a.user_id = u1.id
  JOIN users u2 ON a.specialist_id = u2.id
  ORDER BY a.appointment_date, a.appointment_time
");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin – Appointment Summary</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3>📋 Appointment Summary</h3>
    <a href="logout.php" class="btn btn-outline-danger">Logout</a>
  </div>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Patient</th>
        <th>Specialist</th>
        <th>Date</th>
        <th>Time</th>
        <th>Status</th>
        <th>Update</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['appointment_id'] ?></td>
          <td><?= htmlspecialchars($row['patient_name']) ?></td>
          <td><?= htmlspecialchars($row['specialist_name']) ?></td>
          <td><?= date('F j, Y', strtotime($row['appointment_date'])) ?></td>
          <td><?= date('g:i A', strtotime($row['appointment_time'])) ?></td>
          <td><?= $row['status'] ?></td>
          <td>
            <form method="POST" action="update_status.php" class="d-flex">
              <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
              <select name="status" class="form-select me-2">
                <option value="Confirmed" <?= $row['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Cancelled" <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
              </select>
              <button type="submit" class="btn btn-sm btn-success">Update</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>