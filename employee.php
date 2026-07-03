<?php
session_start();
require_once '../users/includes/config.php'; 

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Employee ID not provided']);
    exit;
}

$id = intval($_GET['id']);

// Get employee data
$stmt = $conn->prepare("SELECT * FROM employee WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

// Get salary data
$stmt = $conn->prepare("SELECT * FROM salary WHERE employee_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$salary = $stmt->get_result()->fetch_assoc();

// Get work experience data
$stmt = $conn->prepare("SELECT * FROM employee_work WHERE employee_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$work = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'employee' => $employee,
    'salary' => $salary,
    'work' => $work
]);
?>
