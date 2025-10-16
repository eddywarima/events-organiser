<?php
require 'db.php'; // Include the database connection


// Debugging: Log the request method and content type
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content Type: " . $_SERVER['CONTENT_TYPE']);

// Handle form submission for event planning and report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the request is JSON (for report submission)
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        // Handle report submission
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Debugging: Log the received JSON data
        error_log("Received JSON Data: " . print_r($data, true));

        // Validate the input
        if (empty($data['user_id']) || empty($data['report'])) {
            echo json_encode("Error: user_id and report fields are required.");
            exit;
        }

        // Extract and sanitize data
        $user_id = intval($data['user_id']);
        $report_text = htmlspecialchars(trim($data['report']));
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Debugging: Log the extracted data
        error_log("User ID: " . $user_id);
        error_log("Report: " . $report_text);
        error_log("IP Address: " . $ip_address);
        error_log("User Agent: " . $user_agent);

        // Insert report into the database
        try {
            $stmt = $conn->prepare("INSERT INTO reports (user_id, report_text, ip_address, user_agent) 
                                   VALUES (:user_id, :report_text, :ip_address, :user_agent)");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':report_text', $report_text);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->execute();

            echo json_encode("success: Report submitted successfully!");
        } catch (PDOException $e) {
            echo json_encode("Error: " . $e->getMessage());
        }
    } else {
        // Handle event submission (existing logic)
        // Debugging: Log the received form data
        error_log("Received Form Data: " . print_r($_POST, true));

        // Extract form data
        $user_id = $_POST['user_id']; // Get user_id from the form
        $event_type = $_POST['event_type'];
        $location = $_POST['location'];
        $event_date = $_POST['date']; // Use 'event_date' instead of 'date'
        $guests = $_POST['guests'];
        $notes = $_POST['notes'];
        $status = 'pending'; // Default status for new events

        // Debugging: Log the extracted form data
        error_log("User ID: " . $user_id);
        error_log("Event Type: " . $event_type);
        error_log("Location: " . $location);
        error_log("Event Date: " . $event_date); // Updated to 'event_date'
        error_log("Guests: " . $guests);
        error_log("Notes: " . $notes);
        error_log("Status: " . $status); // Log the default status

        // Insert event into the database
        try {
            $stmt = $conn->prepare("INSERT INTO events (user_id, event_type, location, event_date, guests, notes, status) VALUES (:user_id, :event_type, :location, :event_date, :guests, :notes, :status)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':event_type', $event_type);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':event_date', $event_date); // Updated to 'event_date'
            $stmt->bindParam(':guests', $guests);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':status', $status); // Bind the status parameter
            $stmt->execute();

            echo "Event submitted successfully!";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>