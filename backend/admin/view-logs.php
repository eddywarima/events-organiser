<?php
require_once "../auth/auth_check.php";
require_once "../utils/logger.php";

// Only admin
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$log_type = $_GET['type'] ?? 'application';
$lines = intval($_GET['lines'] ?? 100);

$logs = Logger::getRecentLogs($log_type, $lines);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #007bff, #ffffff); min-height: 100vh; color: #333; }
        .card { background-color: rgba(255, 255, 255, 0.9); border: 2px solid #007bff; }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .text-amber { color: #FFBF00; }
        .log-entry { margin-bottom: 15px; padding: 10px; border-left: 4px solid #007bff; background: #f8f9fa; }
        .log-debug { border-left-color: #6c757d; }
        .log-info { border-left-color: #17a2b8; }
        .log-warning { border-left-color: #ffc107; }
        .log-error { border-left-color: #dc3545; }
        .log-critical { border-left-color: #721c24; }
        .log-security { border-left-color: #fd7e14; }
        .log-access { border-left-color: #28a745; }
        .timestamp { font-family: monospace; font-size: 0.9em; color: #6c757d; }
        .context { background: #e9ecef; padding: 5px; border-radius: 3px; font-family: monospace; font-size: 0.8em; margin-top: 5px; }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center text-amber mb-4">System Logs</h2>

                        <!-- Log Type Selector -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <select name="type" class="form-select me-2">
                                        <option value="application" <?php echo $log_type === 'application' ? 'selected' : ''; ?>>Application</option>
                                        <option value="error" <?php echo $log_type === 'error' ? 'selected' : ''; ?>>Errors</option>
                                        <option value="security" <?php echo $log_type === 'security' ? 'selected' : ''; ?>>Security</option>
                                        <option value="access" <?php echo $log_type === 'access' ? 'selected' : ''; ?>>Access</option>
                                    </select>
                                    <select name="lines" class="form-select me-2">
                                        <option value="50" <?php echo $lines === 50 ? 'selected' : ''; ?>>50 lines</option>
                                        <option value="100" <?php echo $lines === 100 ? 'selected' : ''; ?>>100 lines</option>
                                        <option value="200" <?php echo $lines === 200 ? 'selected' : ''; ?>>200 lines</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </form>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="../../frontend/admin-dashboard.html" class="btn btn-secondary">Back to Dashboard</a>
                            </div>
                        </div>

                        <!-- Log Entries -->
                        <?php if (empty($logs)): ?>
                            <div class="alert alert-info text-center">
                                No log entries found for the selected criteria.
                            </div>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <div class="log-entry log-<?php echo strtolower($log['level']); ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <span class="badge bg-<?php echo getLogLevelClass($log['level']); ?> me-2">
                                                <?php echo htmlspecialchars($log['level']); ?>
                                            </span>
                                            <strong><?php echo htmlspecialchars($log['message']); ?></strong>
                                            
                                            <div class="timestamp mt-1">
                                                <?php echo htmlspecialchars($log['timestamp']); ?> | 
                                                IP: <?php echo htmlspecialchars($log['ip']); ?> | 
                                                User ID: <?php echo htmlspecialchars($log['user_id']); ?> | 
                                                URI: <?php echo htmlspecialchars($log['request_uri']); ?>
                                            </div>
                                            
                                            <?php if (!empty($log['context'])): ?>
                                                <div class="context">
                                                    <strong>Context:</strong>
                                                    <pre><?php echo htmlspecialchars(json_encode($log['context'], JSON_PRETTY_PRINT)); ?></pre>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function getLogLevelClass($level) {
    switch ($level) {
        case 'DEBUG': return 'secondary';
        case 'INFO': return 'info';
        case 'WARNING': return 'warning';
        case 'ERROR': return 'danger';
        case 'CRITICAL': return 'dark';
        case 'SECURITY': return 'warning';
        case 'ACCESS': return 'success';
        default: return 'secondary';
    }
}
?>
