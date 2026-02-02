<?php
require_once "../auth/auth_check.php";
require_once "../config/db.php";

// Only admin
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$result = $conn->query("
    SELECT b.id, u.full_name, u.email, e.title, b.tickets, b.total_amount, b.booking_status, b.booking_date
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN events e ON b.event_id = e.id
    ORDER BY b.booking_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #007bff, #ffffff); min-height: 100vh; color: #333; }
        .card { background-color: rgba(255, 255, 255, 0.9); border: 2px solid #007bff; }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; }
        .text-amber { color: #FFBF00; }
        .table th { background-color: #FFBF00; color: #333; }
        .badge-confirmed { background-color: #28a745; }
        .badge-cancelled { background-color: #dc3545; }
        .badge-pending { background-color: #ffc107; color: #000; }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center text-amber mb-4">All Bookings</h2>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-warning">
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Event</th>
                                        <th>Tickets</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Booking Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['id']) ?></td>
                                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= htmlspecialchars($row['title']) ?></td>
                                            <td><?= htmlspecialchars($row['tickets']) ?></td>
                                            <td>$<?= htmlspecialchars($row['total_amount']) ?></td>
                                            <td>
                                                <?php
                                                $status = htmlspecialchars($row['booking_status']);
                                                $badgeClass = 'badge-secondary';
                                                if ($status === 'confirmed') {
                                                    $badgeClass = 'badge-success';
                                                } elseif ($status === 'cancelled') {
                                                    $badgeClass = 'badge-danger';
                                                } elseif ($status === 'pending') {
                                                    $badgeClass = 'badge-warning text-dark';
                                                }
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($row['booking_date']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-center mt-3">
                            <a href="../../frontend/admin-dashboard.html" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>