<?php
session_start();
require_once '../users/includes/config.php'; // Your database connection file

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Validate session and admin status
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle logout
if (isset($_GET['logout'])) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    session_destroy();
    header("Location: ../users/index.php");
    exit();
}

// Function to validate and sanitize input
function validateInput($data, $type = 'string') {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    
    switch ($type) {
        case 'email':
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            break;
        case 'int':
            if (!filter_var($data, FILTER_VALIDATE_INT)) {
                return false;
            }
            break;
        case 'float':
            if (!filter_var($data, FILTER_VALIDATE_FLOAT)) {
                return false;
            }
            break;
        case 'date':
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                return false;
            }
            break;
        case 'phone':
            if (!preg_match('/^[0-9]{10}$/', $data)) {
                return false;
            }
            break;
    }
    
    return $data;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid CSRF token";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['add_employee'])) {
        addEmployee($conn);
    } elseif (isset($_POST['update_employee'])) {
        updateEmployee($conn);
    } elseif (isset($_POST['delete_employee']) && isset($_POST['id'], $_POST['type'])) {
        deleteEmployee($conn);
    }
}

function addEmployee($conn) {
    // Validate required fields
    $required = ['type', 'name', 'email', 'phone', 'department', 'position', 'joiningDate', 'address',
                'base_salary', 'allowances', 'payment_method', 'experience', 'skills'];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['error_message'] = "All required fields must be filled";
            return;
        }
    }

    // Validate and sanitize inputs
    $type = validateInput($_POST['type']);
    if (!in_array($type, ['employee', 'hr'])) {
        $_SESSION['error_message'] = "Invalid employee type";
        return;
    }

    $name = validateInput($_POST['name']);
    if (strlen($name) > 100 || empty($name)) {
        $_SESSION['error_message'] = "Invalid name";
        return;
    }

    $email = validateInput($_POST['email'], 'email');
    if (!$email || strlen($email) > 100) {
        $_SESSION['error_message'] = "Invalid email";
        return;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM employee WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "Email already exists";
        return;
    }

    $phone = validateInput($_POST['phone'], 'phone');
    if (!$phone) {
        $_SESSION['error_message'] = "Invalid phone number";
        return;
    }

    $department = validateInput($_POST['department']);
    $validDepartments = ['IT', 'Finance', 'Marketing', 'HR', 'Operations'];
    if (!in_array($department, $validDepartments)) {
        $_SESSION['error_message'] = "Invalid department";
        return;
    }

    $position = validateInput($_POST['position']);
    if (strlen($position) > 100 || empty($position)) {
        $_SESSION['error_message'] = "Invalid position";
        return;
    }

    $joiningDate = validateInput($_POST['joiningDate'], 'date');
    if (!$joiningDate || $joiningDate > date('Y-m-d')) {
        $_SESSION['error_message'] = "Invalid joining date";
        return;
    }

    $address = validateInput($_POST['address']);
    if (strlen($address) > 255 || empty($address)) {
        $_SESSION['error_message'] = "Invalid address";
        return;
    }

    $baseSalary = validateInput($_POST['base_salary'], 'float');
    if (!$baseSalary || $baseSalary <= 0) {
        $_SESSION['error_message'] = "Invalid base salary";
        return;
    }

    $allowances = validateInput($_POST['allowances'], 'float');
    if ($allowances === false || $allowances < 0) {
        $_SESSION['error_message'] = "Invalid allowances";
        return;
    }

    $paymentMethod = validateInput($_POST['payment_method']);
    $validMethods = ['Bank Transfer', 'Check', 'Cash'];
    if (!in_array($paymentMethod, $validMethods)) {
        $_SESSION['error_message'] = "Invalid payment method";
        return;
    }

    $experience = validateInput($_POST['experience'], 'float');
    if ($experience === false || $experience < 0) {
        $_SESSION['error_message'] = "Invalid experience";
        return;
    }

    $previousCompanies = validateInput($_POST['previous_companies'] ?? '');
    $skills = validateInput($_POST['skills']);
    if (empty($skills)) {
        $_SESSION['error_message'] = "Skills are required";
        return;
    }

    $certifications = validateInput($_POST['certifications'] ?? '');

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into employee table
        $stmt = $conn->prepare("INSERT INTO employee (type, name, email, department, position, date_of_joining, contact_no, address, created_by, updated_by) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $createdBy = $_SESSION['email'];
        $stmt->bind_param("ssssssssss", $type, $name, $email, $department, $position, $joiningDate, $phone, $address, $createdBy, $createdBy);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $employeeId = $conn->insert_id;
        
        // Insert into employee_work table
        $stmt = $conn->prepare("INSERT INTO employee_work (employee_id, total_experience, previous_company, skills, certificates) 
                               VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("issss", $employeeId, $experience, $previousCompanies, $skills, $certifications);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        // Insert into salary table
        $netSalary = $baseSalary + $allowances;
        $stmt = $conn->prepare("INSERT INTO salary (employee_id, base_salary, allowances, payment_method, net_salary) 
                               VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("iidsd", $employeeId, $baseSalary, $allowances, $paymentMethod, $netSalary);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        // If HR staff, create user account with employee_id reference
        if ($type == 'hr') {
            $password = password_hash('default_password', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user (email, password, created_at, is_admin, id) VALUES (?, ?, NOW(), 0, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ssi", $email, $password, $employeeId);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        }
        
        $conn->commit();
        $_SESSION['success_message'] = ($type == 'employee') ? 'Employee added successfully!' : 'HR staff added successfully!';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error adding employee: " . $e->getMessage();
    }
}


function updateEmployee($conn) {
    // Validate required fields
    $required = ['id', 'type', 'name', 'email', 'phone', 'department', 'position', 'joiningDate', 'address',
                'base_salary', 'allowances', 'payment_method', 'experience', 'skills'];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['error_message'] = "All required fields must be filled";
            return;
        }
    }

    // Validate ID
    $id = validateInput($_POST['id'], 'int');
    if (!$id || $id <= 0) {
        $_SESSION['error_message'] = "Invalid employee ID";
        return;
    }

    // Check if employee exists
    $stmt = $conn->prepare("SELECT id FROM employee WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $_SESSION['error_message'] = "Employee not found";
        return;
    }

    // Validate and sanitize other inputs
    $type = validateInput($_POST['type']);
    if (!in_array($type, ['employee', 'hr'])) {
        $_SESSION['error_message'] = "Invalid employee type";
        return;
    }

    $name = validateInput($_POST['name']);
    if (strlen($name) > 100 || empty($name)) {
        $_SESSION['error_message'] = "Invalid name";
        return;
    }

    $email = validateInput($_POST['email'], 'email');
    if (!$email || strlen($email) > 100) {
        $_SESSION['error_message'] = "Invalid email";
        return;
    }

    // Check if email belongs to another employee
    $stmt = $conn->prepare("SELECT id FROM employee WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "Email already exists for another employee";
        return;
    }

    // Validate other fields
    $phone = validateInput($_POST['phone'], 'phone');
    if (!$phone) {
        $_SESSION['error_message'] = "Invalid phone number";
        return;
    }

    $department = validateInput($_POST['department']);
    $validDepartments = ['IT', 'Finance', 'Marketing', 'HR', 'Operations'];
    if (!in_array($department, $validDepartments)) {
        $_SESSION['error_message'] = "Invalid department";
        return;
    }

    $position = validateInput($_POST['position']);
    if (strlen($position) > 100 || empty($position)) {
        $_SESSION['error_message'] = "Invalid position";
        return;
    }

    $joiningDate = validateInput($_POST['joiningDate'], 'date');
    if (!$joiningDate || $joiningDate > date('Y-m-d')) {
        $_SESSION['error_message'] = "Invalid joining date";
        return;
    }

    $address = validateInput($_POST['address']);
    if (strlen($address) > 255 || empty($address)) {
        $_SESSION['error_message'] = "Invalid address";
        return;
    }

    $baseSalary = validateInput($_POST['base_salary'], 'float');
    if (!$baseSalary || $baseSalary <= 0) {
        $_SESSION['error_message'] = "Invalid base salary";
        return;
    }

    $allowances = validateInput($_POST['allowances'], 'float');
    if ($allowances === false || $allowances < 0) {
        $_SESSION['error_message'] = "Invalid allowances";
        return;
    }

    $paymentMethod = validateInput($_POST['payment_method']);
    $validMethods = ['Bank Transfer', 'Check', 'Cash'];
    if (!in_array($paymentMethod, $validMethods)) {
        $_SESSION['error_message'] = "Invalid payment method";
        return;
    }

    $experience = validateInput($_POST['experience'], 'float');
    if ($experience === false || $experience < 0) {
        $_SESSION['error_message'] = "Invalid experience";
        return;
    }

    $previousCompanies = validateInput($_POST['previous_companies'] ?? '');
    $skills = validateInput($_POST['skills']);
    if (empty($skills)) {
        $_SESSION['error_message'] = "Skills are required";
        return;
    }

    $certifications = validateInput($_POST['certifications'] ?? '');

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get current employee type before updating
        $stmt = $conn->prepare("SELECT type, email FROM employee WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentData = $result->fetch_assoc();
        $currentType = $currentData['type'];
        $currentEmail = $currentData['email'];
        
        // Update employee table 
        $stmt = $conn->prepare("UPDATE employee SET type=?, name=?, email=?, department=?, position=?, date_of_joining=?, contact_no=?, address=?, updated_by=?, updated_at=NOW() WHERE id=?");
        $updatedBy = $_SESSION['email'];
        $stmt->bind_param("sssssssssi", $type, $name, $email, $department, $position, $joiningDate, $phone, $address, $updatedBy, $id);
        $stmt->execute();
        
        // Update employee_work table
        $stmt = $conn->prepare("UPDATE employee_work SET total_experience=?, previous_company=?, skills=?, certificates=?, updated_at=NOW() WHERE employee_id=?");
        $stmt->bind_param("isssi", $experience, $previousCompanies, $skills, $certifications, $id);
        $stmt->execute();
        
        // Update salary table
        $netSalary = $baseSalary + $allowances;
        $stmt = $conn->prepare("UPDATE salary SET base_salary=?, allowances=?, payment_method=?, net_salary=? WHERE employee_id=?");
        $stmt->bind_param("ddsdi", $baseSalary, $allowances, $paymentMethod, $netSalary, $id);
        $stmt->execute();
        
        // Handle user account changes
        if ($type == 'hr') {
            // Check if user account exists
            $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
            $stmt->bind_param("s", $currentEmail);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows == 0) {
                // Create new user account for HR
                $password = password_hash('default_password', PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO user (email, password, created_at, is_admin, employee_id) VALUES (?, ?, NOW(), 1, ?)");
                $stmt->bind_param("ssi", $email, $password, $id);
                $stmt->execute();
            } else {
                // Update existing user account to ensure it's admin and linked
                $stmt = $conn->prepare("UPDATE user SET email=?, is_admin=0, id=? WHERE email=?");
                $stmt->bind_param("sis", $email, $id, $currentEmail);
                $stmt->execute();
            }
        } elseif ($currentType == 'hr' && $type == 'employee') {
            // Changed from HR to employee - remove admin privileges but keep account
            $stmt = $conn->prepare("UPDATE user SET is_admin=0, employee_id=NULL WHERE email=?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
        }
        
        $conn->commit();
        $_SESSION['success_message'] = ($type == 'employee') ? 'Employee updated successfully!' : 'HR staff updated successfully!';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error updating employee: " . $e->getMessage();
    }
}

function deleteEmployee($conn) {
    // Validate inputs
    if (!isset($_POST['id'], $_POST['type'])) {
        $_SESSION['error_message'] = "Missing required fields";
        return;
    }

    $id = validateInput($_POST['id'], 'int');
    if (!$id || $id <= 0) {
        $_SESSION['error_message'] = "Invalid employee ID";
        return;
    }

    $type = validateInput($_POST['type']);
    if (!in_array($type, ['employee', 'hr'])) {
        $_SESSION['error_message'] = "Invalid employee type";
        return;
    }

    // Check if employee exists
    $stmt = $conn->prepare("SELECT id FROM employee WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $_SESSION['error_message'] = "Employee not found";
        return;
    }

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get email before deleting (for HR staff)
        $email = '';
        if ($type == 'hr') {
            $stmt = $conn->prepare("SELECT email FROM employee WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $email = $row['email'];
            }
        }
        
        // Delete from salary table
        $stmt = $conn->prepare("DELETE FROM salary WHERE employee_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Delete from employee_work table
        $stmt = $conn->prepare("DELETE FROM employee_work WHERE employee_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Delete from employee table
        $stmt = $conn->prepare("DELETE FROM employee WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // If HR staff, delete from users table
        if ($type == 'hr' && !empty($email)) {
            // Don't allow deleting own admin account
            if ($email == $_SESSION['email']) {
                throw new Exception("You cannot delete your own admin account");
            }
            
            $stmt = $conn->prepare("DELETE FROM user WHERE email=?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
        }
        
        $conn->commit();
        $_SESSION['success_message'] = ($type == 'employee') ? 'Employee deleted successfully!' : 'HR staff deleted successfully!';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting employee: " . $e->getMessage();
    }
}

// Get data for display with prepared statements
$employee = [];
$hrStaff = [];
$recentemployee = [];

// Get all HR staff
$query = "SELECT e.*, s.base_salary, s.allowances, s.payment_method, 
          ew.total_experience, ew.skills
          FROM employee e
          LEFT JOIN salary s ON e.id = s.employee_id
          LEFT JOIN employee_work ew ON e.id = ew.employee_id
          WHERE e.type = 'hr'
          ORDER BY e.created_at DESC";

if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $hrStaff[] = $row;
    }
    $stmt->close();
}

// Get all regular employees
$query = "SELECT e.*, s.base_salary, s.allowances, s.payment_method, 
          ew.total_experience, ew.skills
          FROM employee e
          LEFT JOIN salary s ON e.id = s.employee_id
          LEFT JOIN employee_work ew ON e.id = ew.employee_id
          WHERE e.type = 'employee'
          ORDER BY e.created_at DESC";

if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $employee[] = $row;
    }
    $stmt->close();
}

// Get recent employee (last 20)
$query = "SELECT e.* FROM employee e WHERE e.type = 'employee' ORDER BY e.created_at DESC LIMIT 20";
if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentemployee[] = $row;
    }
    $stmt->close();
}

// Get counts for dashboard with error handling
$totalemployee = 0;
$totalHr = 0;
$totalDepartments = 0;

$query = "SELECT COUNT(*) as count FROM employee WHERE type = 'employee'";
if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $totalemployee = $result->fetch_assoc()['count'];
    $stmt->close();
}

$query = "SELECT COUNT(*) as count FROM employee WHERE type = 'hr'";
if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $totalHr = $result->fetch_assoc()['count'];
    $stmt->close();
}

$query = "SELECT COUNT(DISTINCT department) as count FROM employee";
if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $totalDepartments = $result->fetch_assoc()['count'];
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HR Management System</title>
    <meta name="description" content="HR Management System Admin Portal" />
    <meta name="author" content="HR Admin" />

    <meta property="og:title" content="HR Management System" />
    <meta property="og:description" content="Admin Portal for HR Management" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="https://lovable.dev/opengraph-image-p98pqg.png" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:site" content="@lovable_dev" />
    <meta name="twitter:image" content="https://lovable.dev/opengraph-image-p98pqg.png" />
    <link rel="stylesheet" href="../users/css/Admin.css">
  </head>

  <body>
    <div id="root">
      <div class="hr-admin-container">
        <!-- Header -->
        <header class="header">
          <div class="logo">
            <h1>HR Admin Portal</h1>
          </div>
          <div class="user-info">
          <a href="#" id="logout-btn" class="header-btn logout-btn" onclick="confirmLogout()">Logout</a>
            <span><?php echo htmlspecialchars($_SESSION['email']); ?></span>
            <div class="avatar">A</div>
          </div>
        </header>

        <!-- Main Content -->
        <div class="main-content">
          <!-- Sidebar -->
          <div class="sidebar">
            <ul>
              <li class="active" id="dashboard-tab">Dashboard</li>
              <li id="add-employee-tab">Add Employee/HR</li>
              <li id="view-employee-tab">View employee</li>
              <li id="view-hr-tab">View HR Staff</li>
            </ul>
          </div>

          <!-- Content Area -->
          <div class="content-area">
            <!-- Success/Error Message -->
            <?php if (isset($_SESSION['success_message'])): ?>
              <div class="success-message" id="success-message"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
              <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
              <div class="error-message" id="error-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
              <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Dashboard -->
            <div class="dashboard" id="dashboard-content">
              <h2>Dashboard</h2>
              <div class="dashboard-cards">
                <div class="card">
                  <h3>Total employee</h3>
                  <p id="total-employee"><?php echo $totalemployee; ?></p>
                </div>
                <div class="card">
                  <h3>HR Staff</h3>
                  <p id="total-hr"><?php echo $totalHr; ?></p>
                </div>
                <div class="card">
                  <h3>Departments</h3>
                  <p id="total-departments"><?php echo $totalDepartments; ?></p>
                </div>
              </div>

              <div class="recent-employee">
                <h3>Recently Added employee</h3>
                <table>
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Department</th>
                      <th>Position</th>
                      <th>Created By</th>
                    </tr>
                  </thead>
                  <tbody id="recent-employee-body">
                    <?php foreach ($recentemployee as $emp): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($emp['name']); ?></td>
                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                        <td><?php echo htmlspecialchars($emp['department']); ?></td>
                        <td><?php echo htmlspecialchars($emp['position']); ?></td>
                        <td><?php echo htmlspecialchars($emp['created_by']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Add Employee/HR Form -->
            <div class="add-employee" id="add-employee-content" style="display: none;">
                <h2 id="form-title">Add Employee/HR</h2>
                <form id="employee-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" id="edit-id" value="">
                    <input type="hidden" name="add_employee" id="add-employee-flag" value="1">
                
                <div class="form-group">
                  <label>Type</label>
                  <select name="type" id="type" required>
                    <option value="employee">Employee</option>
                    <option value="hr">HR Staff</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label>Full Name</label>
                  <input type="text" name="name" id="name" required />
                </div>
                
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" name="email" id="email" required />
                </div>
                
                <div class="form-group">
                  <label>Phone</label>
                  <input type="tel" name="phone" id="phone" required />
                </div>
                
                <div class="form-group">
                  <label>Department</label>
                  <select name="department" id="department" required>
                    <option value="">Select Department</option>
                    <option value="IT">IT</option>
                    <option value="Finance">Finance</option>
                    <option value="Marketing">Marketing</option>
                    <option value="HR">Human Resources</option>
                    <option value="Operations">Operations</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label>Position</label>
                  <input type="text" name="position" id="position" required />
                </div>
                
                <div class="form-group">
                  <label>Joining Date</label>
                  <input type="date" name="joiningDate" id="joiningDate" required />
                </div>
                
                <div class="form-group">
                  <label>Address</label>
                  <textarea name="address" id="address" required></textarea>
                </div>
                
                <div class="form-section">
                  <h3 class="section-title">Salary Information</h3>
                  <div class="form-group">
                    <label>Base Salary:</label>
                    <input type="number" name="base_salary" id="add-base-salary" required />
                  </div>
                  <div class="form-group">
                    <label>Allowances:</label>
                    <input type="number" name="allowances" id="add-allowances" required />
                  </div>
                  <div class="form-group">
                    <label>Payment Method:</label>
                    <select name="payment_method" id="add-payment-method" required>
                      <option value="Bank Transfer">Bank Transfer</option>
                      <option value="Check">Check</option>
                      <option value="Cash">Cash</option>
                    </select>
                  </div>
                </div>
                
                <div class="form-section">
                  <h3 class="section-title">Work Experience</h3>
                  <div class="form-group">
                    <label>Total Experience (years):</label>
                    <input type="number" name="experience" id="add-experience" required />
                  </div>
                  <div class="form-group">
                    <label>Previous Companies:</label>
                    <textarea name="previous_companies" id="add-previous-companies"></textarea>
                  </div>
                  <div class="form-group">
                    <label>Skills:</label>
                    <textarea name="skills" id="add-skills" required></textarea>
                  </div>
                  <div class="form-group">
                    <label>Certifications:</label>
                    <textarea name="certifications" id="add-certifications"></textarea>
                  </div>
                </div>
                
                <button type="submit" class="submit-btn" id="submit-btn">Add Employee</button>
              </form>
            </div>

            <!-- View employee -->
            <div class="view-employee" id="view-employee-content" style="display: none;">
              <h2>Employee List</h2>
              <div class="search-container">
                <input 
                  type="text" 
                  id="employee-search" 
                  placeholder="Search by name, email, or department..." 
                />
              </div>
              
              <table>
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Created By</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="employee-table-body">
                  <?php foreach ($employee as $emp): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($emp['name']); ?></td>
                      <td><?php echo htmlspecialchars($emp['email']); ?></td>
                      <td><?php echo htmlspecialchars($emp['department']); ?></td>
                      <td><?php echo htmlspecialchars($emp['position']); ?></td>
                      <td><?php echo htmlspecialchars($emp['created_by']); ?></td>
                      <td>
                        <button class="edit-btn" data-id="<?php echo $emp['id']; ?>" data-type="employee">Edit</button>
                        <form method="post" style="display: inline;">
                          <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
                          <input type="hidden" name="type" value="employee">
                          <input type="hidden" name="delete_employee" value="1">
                          <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this employee?')">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <p class="no-data" id="no-employee" style="<?php echo empty($employee) ? '' : 'display: none;' ?>">No employee have been added yet.</p>
            </div>

            <!-- View HR Staff -->
            <div class="view-hr" id="view-hr-content" style="display: none;">
              <h2>HR Staff List</h2>
              
              <table>
                <thead>
                    <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Phone</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Created By</th>
                    <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="hr-table-body">
                    <?php foreach ($hrStaff as $hr): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($hr['name']); ?></td>
                        <td><?php echo htmlspecialchars($hr['email']); ?></td>
                        <td><?php echo htmlspecialchars($hr['department']); ?></td>
                        <td><?php echo htmlspecialchars($hr['position']); ?></td>
                        <td><?php echo htmlspecialchars($hr['contact_no']); ?></td>
                        <td><?php echo htmlspecialchars($hr['email']); ?></td>
                        <td><?php echo htmlspecialchars('default_password'); ?></td>
                        <td><?php echo htmlspecialchars($hr['created_by']); ?></td>
                        <td>
                        <button class="edit-btn" data-id="<?php echo $hr['id']; ?>" data-type="hr">Edit</button>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo $hr['id']; ?>">
                            <input type="hidden" name="type" value="hr">
                            <input type="hidden" name="delete_employee" value="1">
                            <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this HR staff?')">Delete</button>
                        </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
              <p class="no-data" id="no-hr" style="<?php echo empty($hrStaff) ? '' : 'display: none;' ?>">No HR staff have been added yet.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <script>
      // Tab switching
      document.getElementById("dashboard-tab").addEventListener("click", () => {
        showTab("dashboard");
      });
      
      document.getElementById("add-employee-tab").addEventListener("click", () => {
        resetForm();
        showTab("add-employee");
      });
      
      document.getElementById("view-employee-tab").addEventListener("click", () => {
        showTab("view-employee");
      });
      
      document.getElementById("view-hr-tab").addEventListener("click", () => {
        showTab("view-hr");
      });
      
      // Edit button handlers
      document.querySelectorAll('.edit-btn[data-type="employee"]').forEach(btn => {
        btn.addEventListener('click', function() {
          const id = this.getAttribute('data-id');
          editEmployee(id);
        });
      });
      
      document.querySelectorAll('.edit-btn[data-type="hr"]').forEach(btn => {
        btn.addEventListener('click', function() {
          const id = this.getAttribute('data-id');
          editHr(id);
        });
      });
      
      // Client-side form validations
document.getElementById('employee-form').addEventListener('submit', function(e) {
    // Clear previous error messages
    clearErrorMessages();
    
    let isValid = true;
    
    // Validate phone number (exactly 10 digits)
    const phone = document.getElementById('phone').value;
    if (!/^\d{10}$/.test(phone)) {
        showError('phone', 'Phone number must be exactly 10 digits');
        isValid = false;
    }
    
    // Validate email format
    const email = document.getElementById('email').value;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('email', 'Please enter a valid email address');
        isValid = false;
    }
    
    // Validate joining date (not in future)
    const joiningDate = new Date(document.getElementById('joiningDate').value);
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time part for accurate comparison
    if (joiningDate > today) {
        showError('joiningDate', 'Joining date cannot be in the future');
        isValid = false;
    }
    
    // Validate numeric fields (positive numbers)
    const numericFields = [
        {id: 'add-base-salary', name: 'Base Salary'},
        {id: 'add-allowances', name: 'Allowances'},
        {id: 'add-experience', name: 'Experience'}
    ];
    
    numericFields.forEach(field => {
        const value = parseFloat(document.getElementById(field.id).value);
        if (isNaN(value) || value < 0) {
            showError(field.id, `${field.name} must be a positive number`);
            isValid = false;
        }
    });
    
    // Validate required fields
    const requiredFields = [
        'name', 'email', 'phone', 'department', 'position', 
        'joiningDate', 'address', 'add-skills'
    ];
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            showError(fieldId, 'This field is required');
            isValid = false;
        }
    });
    
    if (!isValid) {
        e.preventDefault(); // Prevent form submission if validation fails
        // Scroll to the first error
        const firstError = document.querySelector('.error-message');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

// Add input validation for phone field to prevent non-digit input
document.getElementById('phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, ''); // Remove non-digit characters
    if (this.value.length > 10) {
        this.value = this.value.slice(0, 10); // Limit to 10 digits
    }
});

// Helper function to show error messages
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.color = 'red';
    errorDiv.style.fontSize = '0.8em';
    errorDiv.style.marginTop = '5px';
    
    // Insert after the field
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
    
    // Highlight the field
    field.style.borderColor = 'red';
}

// Helper function to clear previous error messages
function clearErrorMessages() {
    // Remove all error messages
    document.querySelectorAll('.error-message').forEach(el => el.remove());
    
    // Reset field borders
    document.querySelectorAll('input, select, textarea').forEach(el => {
        el.style.borderColor = '';
    });
}

// Add real-time email validation
document.getElementById('email').addEventListener('blur', function() {
    const email = this.value;
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('email', 'Please enter a valid email address');
    }
});

// Add real-time phone validation
document.getElementById('phone').addEventListener('blur', function() {
    const phone = this.value;
    if (phone && !/^\d{10}$/.test(phone)) {
        showError('phone', 'Phone number must be exactly 10 digits');
    }
});

      // Search functionality
      document.getElementById('employee-search').addEventListener('input', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#employee-table-body tr');
        
        rows.forEach(row => {
          const name = row.cells[0].textContent.toLowerCase();
          const email = row.cells[1].textContent.toLowerCase();
          const department = row.cells[2].textContent.toLowerCase();
          
          if (name.includes(searchValue) || email.includes(searchValue) || department.includes(searchValue)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      });
      
      // Hide success/error message after 3 seconds
      setTimeout(() => {
        const successMsg = document.getElementById('success-message');
        const errorMsg = document.getElementById('error-message');
        
        if (successMsg) successMsg.style.display = 'none';
        if (errorMsg) errorMsg.style.display = 'none';
      }, 3000);
      
      // Show tab
      function showTab(tabName) {
        // Hide all tabs
        document.getElementById('dashboard-content').style.display = 'none';
        document.getElementById('add-employee-content').style.display = 'none';
        document.getElementById('view-employee-content').style.display = 'none';
        document.getElementById('view-hr-content').style.display = 'none';
        
        // Remove active class from all tabs
        document.getElementById('dashboard-tab').classList.remove('active');
        document.getElementById('add-employee-tab').classList.remove('active');
        document.getElementById('view-employee-tab').classList.remove('active');
        document.getElementById('view-hr-tab').classList.remove('active');
        
        // Show selected tab
        if (tabName === 'dashboard') {
          document.getElementById('dashboard-content').style.display = 'block';
          document.getElementById('dashboard-tab').classList.add('active');
        } else if (tabName === 'add-employee') {
          document.getElementById('add-employee-content').style.display = 'block';
          document.getElementById('add-employee-tab').classList.add('active');
        } else if (tabName === 'view-employee') {
          document.getElementById('view-employee-content').style.display = 'block';
          document.getElementById('view-employee-tab').classList.add('active');
        } else if (tabName === 'view-hr') {
          document.getElementById('view-hr-content').style.display = 'block';
          document.getElementById('view-hr-tab').classList.add('active');
        }
      }
      
      // Edit employee
      function editEmployee(id) {
        fetch('get_employee.php?id=' + id)
          .then(response => response.json())
          .then(data => {
            if (data.error) {
              alert(data.error);
              return;
            }
            
            document.getElementById('edit-id').value = data.employee.id;
            document.getElementById('add-employee-flag').name = 'update_employee';
            document.getElementById('type').value = 'employee';
            document.getElementById('name').value = data.employee.name;
            document.getElementById('email').value = data.employee.email;
            document.getElementById('phone').value = data.employee.contact_no;
            document.getElementById('department').value = data.employee.department;
            document.getElementById('position').value = data.employee.position;
            document.getElementById('joiningDate').value = data.employee.date_of_joining;
            document.getElementById('address').value = data.employee.address;
            document.getElementById('add-base-salary').value = data.salary.base_salary;
            document.getElementById('add-allowances').value = data.salary.allowances;
            document.getElementById('add-payment-method').value = data.salary.payment_method;
            document.getElementById('add-experience').value = data.work.total_experience;
            document.getElementById('add-previous-companies').value = data.work.previous_company;
            document.getElementById('add-skills').value = data.work.skills;
            document.getElementById('add-certifications').value = data.work.certificates;
            
            document.getElementById('form-title').textContent = 'Edit Employee';
            document.getElementById('submit-btn').textContent = 'Update Employee';
            
            showTab('add-employee');
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Error fetching employee data');
          });
      }
      
      // Edit HR staff
      function editHr(id) {
        fetch('get_employee.php?id=' + id)
          .then(response => response.json())
          .then(data => {
            if (data.error) {
              alert(data.error);
              return;
            }
            
            document.getElementById('edit-id').value = data.employee.id;
            document.getElementById('add-employee-flag').name = 'update_employee';
            document.getElementById('type').value = 'hr';
            document.getElementById('name').value = data.employee.name;
            document.getElementById('email').value = data.employee.email;
            document.getElementById('phone').value = data.employee.contact_no;
            document.getElementById('department').value = data.employee.department;
            document.getElementById('position').value = data.employee.position;
            document.getElementById('joiningDate').value = data.employee.date_of_joining;
            document.getElementById('address').value = data.employee.address;
            document.getElementById('add-base-salary').value = data.salary.base_salary;
            document.getElementById('add-allowances').value = data.salary.allowances;
            document.getElementById('add-payment-method').value = data.salary.payment_method;
            document.getElementById('add-experience').value = data.work.total_experience;
            document.getElementById('add-previous-companies').value = data.work.previous_company;
            document.getElementById('add-skills').value = data.work.skills;
            document.getElementById('add-certifications').value = data.work.certificates;
            
            document.getElementById('form-title').textContent = 'Edit HR Staff';
            document.getElementById('submit-btn').textContent = 'Update HR Staff';
            
            showTab('add-employee');
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Error fetching HR staff data');
          });
      }
      
      // Reset form
      function resetForm() {
        document.getElementById('employee-form').reset();
        document.getElementById('edit-id').value = '';
        document.getElementById('add-employee-flag').name = 'add_employee';
        document.getElementById('type').disabled = false;
        document.getElementById('form-title').textContent = 'Add Employee/HR';
        document.getElementById('submit-btn').textContent = 'Add Employee';
        updateSubmitButtonText();
      }
      
      // Update submit button text based on selected type
      document.getElementById('type').addEventListener('change', updateSubmitButtonText);
      
      function updateSubmitButtonText() {
        if (!document.getElementById('edit-id').value) {
          const type = document.getElementById('type').value;
          document.getElementById('submit-btn').textContent = type === 'employee' 
            ? 'Add Employee' 
            : 'Add HR Staff';
        }
      }

      //logout
      function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        // If user confirms, redirect to logout URL
        window.location.href = '?logout=1';
    }
    // If user cancels, do nothing
}
    </script>
  </body>
</html>
