<?php
// Set a custom session save path
$sessionPath = __DIR__ . '/sessions'; // Use a directory named 'sessions' in the backend folder
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true); // Create the directory if it doesn't exist
}
session_save_path($sessionPath);
session_start();

// Database credentials
$host = 'localhost'; // Database host
$dbname = 'warima_events'; // Your database name
$user = 'root'; // Your database username
$pass = '9417Wekm.'; // Your database password

// Create a PDO instance (database connection)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email']); // Trim to remove extra spaces
    $password = trim($_POST['password']); // Trim to remove extra spaces

    // Fetch user from the database
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role']; // Store role in session

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../frontend/admin_dashboard.html");
            } else {
                header("Location: ../frontend/dashboard.html");
            }
            exit();
        } else {
            echo "Invalid email or password.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>