<?php
session_start();
// Database connection
require_once '../users/includes/config.php';


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
        // Add new employee
        addEmployee($conn);
    } elseif (isset($_POST['update_employee'])) {
        // Update employee
        updateEmployee($conn);
    } elseif (isset($_POST['delete_employee'])) {
        // Delete employee
        deleteEmployee($conn);
    } elseif (isset($_POST['update_hr_profile'])) {
        // Update HR profile
        updateHrProfile($conn);
    }
}

// Function to add new employee
function addEmployee($conn) {
    // Basic employee info
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $department = $conn->real_escape_string($_POST['department']);
    $position = $conn->real_escape_string($_POST['position']);
    $date_of_joining = $conn->real_escape_string($_POST['date_of_joining']);
    $contact_no = $conn->real_escape_string($_POST['contact_no']);
    $address = $conn->real_escape_string($_POST['address']);
    $password = password_hash($conn->real_escape_string($_POST['password']), PASSWORD_DEFAULT);
    
    // Salary info
    $base_salary = $conn->real_escape_string($_POST['base_salary']);
    $allowances = $conn->real_escape_string($_POST['allowances']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $deduction = $conn->real_escape_string($_POST['deduction']);
    
    // Work experience
    $total_experience = $conn->real_escape_string($_POST['total_experience']);
    $previous_company = $conn->real_escape_string($_POST['previous_company']);
    $skills = $conn->real_escape_string($_POST['skills']);
    $certificates = $conn->real_escape_string($_POST['certificates']);
    $projects = $conn->real_escape_string($_POST['projects']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into employee table
        $employee_sql = "INSERT INTO employee (name, email, department, position, date_of_joining, contact_no, address, password) 
                         VALUES ('$name', '$email', '$department', '$position', '$date_of_joining', '$contact_no', '$address', '$password')";
        
        if (!$conn->query($employee_sql)) {
            throw new Exception("Error adding employee: " . $conn->error);
        }
        
        $employee_id = $conn->insert_id;
        
        // Insert into salary table
        $salary_sql = "INSERT INTO salary (employee_id, base_salary, allowances, payment_method, deduction) 
                       VALUES ('$employee_id', '$base_salary', '$allowances', '$payment_method', '$deduction')";
        
        if (!$conn->query($salary_sql)) {
            throw new Exception("Error adding salary: " . $conn->error);
        }
        
        // Insert into employee_work table
        $work_sql = "INSERT INTO employee_work (employee_id, total_experience, previous_company, skills, certificates, projects) 
                     VALUES ('$employee_id', '$total_experience', '$previous_company', '$skills', '$certificates', '$projects')";
        
        if (!$conn->query($work_sql)) {
            throw new Exception("Error adding work experience: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        $_SESSION['message'] = "Employee added successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: dashboard.php");
    exit();
}

// Function to update employee
function updateEmployee($conn) {
    $employee_id = $conn->real_escape_string($_POST['employee_id']);
    
    // Basic employee info
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $department = $conn->real_escape_string($_POST['department']);
    $position = $conn->real_escape_string($_POST['position']);
    $date_of_joining = $conn->real_escape_string($_POST['date_of_joining']);
    $contact_no = $conn->real_escape_string($_POST['contact_no']);
    $address = $conn->real_escape_string($_POST['address']);
    $present_days = $conn->real_escape_string($_POST['present_days']);
    $absent_days = $conn->real_escape_string($_POST['absent_days']);
    $annual_leave = $conn->real_escape_string($_POST['annual_leave']);
    $sick_leave = $conn->real_escape_string($_POST['sick_leave']);
    
    // Salary info
    $base_salary = $conn->real_escape_string($_POST['base_salary']);
    $allowances = $conn->real_escape_string($_POST['allowances']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $deduction = $conn->real_escape_string($_POST['deduction']);
    
    // Work experience
    $total_experience = $conn->real_escape_string($_POST['total_experience']);
    $previous_company = $conn->real_escape_string($_POST['previous_company']);
    $skills = $conn->real_escape_string($_POST['skills']);
    $certificates = $conn->real_escape_string($_POST['certificates']);
    $projects = $conn->real_escape_string($_POST['projects']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update employee table
        $employee_sql = "UPDATE employee SET 
                         name = '$name',
                         email = '$email',
                         department = '$department',
                         position = '$position',
                         date_of_joining = '$date_of_joining',
                         contact_no = '$contact_no',
                         address = '$address',
                         present_days = '$present_days',
                         absent_days = '$absent_days',
                         annual_leave = '$annual_leave',
                         sick_leave = '$sick_leave',
                         updated_at = NOW()
                         WHERE id = '$employee_id'";
        
        if (!$conn->query($employee_sql)) {
            throw new Exception("Error updating employee: " . $conn->error);
        }
        
        // Update salary table
        $salary_sql = "UPDATE salary SET 
                       base_salary = '$base_salary',
                       allowances = '$allowances',
                       payment_method = '$payment_method',
                       deduction = '$deduction'
                       WHERE employee_id = '$employee_id'";
        
        if (!$conn->query($salary_sql)) {
            throw new Exception("Error updating salary: " . $conn->error);
        }
        
        // Update employee_work table
        $work_sql = "UPDATE employee_work SET 
                     total_experience = '$total_experience',
                     previous_company = '$previous_company',
                     skills = '$skills',
                     certificates = '$certificates',
                     projects = '$projects'
                     WHERE employee_id = '$employee_id'";
        
        if (!$conn->query($work_sql)) {
            throw new Exception("Error updating work experience: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        $_SESSION['message'] = "Employee updated successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: dashboard.php");
    exit();
}

// Function to delete employee
function deleteEmployee($conn) {
    $employee_id = $conn->real_escape_string($_POST['employee_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete from salary table
        $salary_sql = "DELETE FROM salary WHERE employee_id = '$employee_id'";
        if (!$conn->query($salary_sql)) {
            throw new Exception("Error deleting salary: " . $conn->error);
        }
        
        // Delete from employee_work table
        $work_sql = "DELETE FROM employee_work WHERE employee_id = '$employee_id'";
        if (!$conn->query($work_sql)) {
            throw new Exception("Error deleting work experience: " . $conn->error);
        }
        
        // Delete from employee table
        $employee_sql = "DELETE FROM employee WHERE id = '$employee_id'";
        if (!$conn->query($employee_sql)) {
            throw new Exception("Error deleting employee: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        $_SESSION['message'] = "Employee deleted successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: dashboard.php");
    exit();
}

// Function to update HR profile
function updateHrProfile($conn) {
    // Get employee_id from session (set during login)
    $employee_id = $_SESSION['employee_id'] ?? null;
    
    if (!$employee_id) {
        $_SESSION['error'] = "No employee ID found in session";
        header("Location: dashboard.php");
        exit();
    }

    $name = $conn->real_escape_string($_POST['name']);
    $position = $conn->real_escape_string($_POST['position']);
    $email = $conn->real_escape_string($_POST['email']);
    $department = $conn->real_escape_string($_POST['department']);
    
    $sql = "UPDATE employee SET 
            name = '$name',
            position = '$position',
            email = '$email',
            department = '$department',
            updated_at = NOW()
            WHERE id = '$employee_id'";
    
    if ($conn->query($sql)) {
        $_SESSION['message'] = "Profile updated successfully!";
        
        // Update session variables if needed
        $_SESSION['name'] = $name;
        $_SESSION['position'] = $position;
        $_SESSION['email'] = $email;
        $_SESSION['department'] = $department;
    } else {
        $_SESSION['error'] = "Error updating profile: " . $conn->error;
    }
    
    header("Location: ../users/dashboard.php?tab=profile");
    exit();
}

// Function to search employees
function searchEmployees($conn, $search_term) {
    $search_term = "%" . $conn->real_escape_string($search_term) . "%";
    
    $sql = "SELECT e.id, e.name, e.email, e.department, e.position 
            FROM employee e
            WHERE e.name LIKE ? OR e.email LIKE ? OR e.id LIKE ?
            ORDER BY e.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = array();
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    return $employees;
}

// Function to get employee details
function getEmployeeDetails($conn, $employee_id) {
    $employee_id = $conn->real_escape_string($employee_id);
    
    // Get basic info
    $employee_sql = "SELECT * FROM employee WHERE id = '$employee_id'";
    $employee_result = $conn->query($employee_sql);
    $employee = $employee_result->fetch_assoc();
    
    if (!$employee) {
        return null;
    }
    
    // Get salary info
    $salary_sql = "SELECT * FROM salary WHERE employee_id = '$employee_id'";
    $salary_result = $conn->query($salary_sql);
    $salary = $salary_result->fetch_assoc();
    
    // Get work experience
    $work_sql = "SELECT * FROM employee_work WHERE employee_id = '$employee_id'";
    $work_result = $conn->query($work_sql);
    $work = $work_result->fetch_assoc();
    
    return array(
        'employee' => $employee,
        'salary' => $salary,
        'work' => $work
    );
}

// Get HR profile (assuming HR is logged in)
$employee_id = $_SESSION['employee_id'] ?? null;

if ($employee_id) {
    $hr_sql = "SELECT * FROM employee WHERE id = '$employee_id'";
    $hr_result = $conn->query($hr_sql);
    $hr_profile = $hr_result->fetch_assoc();
} else {
    $hr_profile = null;
}

// Handle search requests
$search_results = array();
if (isset($_GET['search'])) {
    $search_term = $_GET['search'];
    $search_results = searchEmployees($conn, $search_term);
}

// Get employee details if requested
$employee_details = null;
if (isset($_GET['employee_id'])) {
    $employee_details = getEmployeeDetails($conn, $_GET['employee_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HR Portal</title>
    <meta name="description" content="HR Portal Dashboard" />
    <link rel="stylesheet" href="../users/css/dashboard.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Add any additional styles here */
        .error { color: red; }
        .success { color: green; }
        <style>
    /* Add any additional styles here */
    .error { 
        color: red; 
        margin: 10px 0; 
        padding: 10px; 
        background: #ffeeee;
        border-radius: 5px;
    }
    .success { 
        color: green; 
        margin: 10px 0; 
        padding: 10px; 
        background: #eeffee;
        border-radius: 5px;
    }
    
    /* Employee Details Container */
    #employee-details-container {
        margin-top: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
    }
    
    /* Employee Details Card */
    .employee-details-card {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    /* Employee Header */
    .employee-details-header {
        display: flex;
        align-items: center;
        gap: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .employee-avatar {
        width: 80px;
        height: 80px;
        background: #4e73df;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: bold;
    }
    
    .employee-name-position {
        flex-grow: 1;
    }
    
    .employee-name-position h3 {
        margin: 0;
        font-size: 24px;
        color: #333;
    }
    
    .employee-name-position p {
        margin: 5px 0 0;
        color: #666;
    }
    
    /* Tabs Navigation */
    .employee-details-tabs {
        display: flex;
        border-bottom: 1px solid #ddd;
        margin-bottom: 20px;
    }
    
    .detail-tab-btn {
        padding: 10px 20px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-weight: 600;
        color: #666;
        transition: all 0.3s;
    }
    
    .detail-tab-btn:hover {
        color: #4e73df;
    }
    
    .detail-tab-btn.active {
        color: #4e73df;
        border-bottom-color: #4e73df;
    }
    
    /* Tab Content */
    .detail-tab-content {
        display: none;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 5px;
    }
    
    .detail-tab-content.active {
        display: block;
    }
    
    /* Detail Items */
    .detail-item {
        display: flex;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .detail-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .detail-label {
        font-weight: 600;
        color: #555;
        width: 200px;
        flex-shrink: 0;
    }
    
    /* Actions */
    .employee-details-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    
    .edit-btn {
        background: #4e73df;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        font-size: 14px;
    }
    
    .cancel-btn {
        background: #6c757d;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        font-size: 14px;
    }
    
    .edit-btn:hover, .cancel-btn:hover {
        opacity: 0.9;
    }
    
    /* Employee List */
    .employee-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    
    .employee-item {
        background: white;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
    }
    
    .employee-avatar.small {
        width: 50px;
        height: 50px;
        font-size: 18px;
    }
    
    .employee-info {
        flex-grow: 1;
    }
    
    .view-btn {
        background: #4e73df;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
    }
    
    /* Form Styles */
    .employee-form {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .form-section {
        margin-bottom: 30px;
    }
    
    .section-title {
        color: #4e73df;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #555;
    }
    
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group input[type="date"],
    .form-group input[type="number"],
    .form-group input[type="password"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .form-group textarea {
        min-height: 80px;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    
    .submit-btn {
        background: #4e73df;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    
    .submit-btn:hover {
        background: #3a5bbf;
    }
    
    /* Search Bar */
    .search-bar {
        display: flex;
        margin-bottom: 20px;
    }
    
    .search-bar input[type="text"] {
        flex-grow: 1;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px 0 0 4px;
        font-size: 16px;
    }
    
    .search-icon-btn {
        background: #4e73df;
        color: white;
        border: none;
        padding: 0 15px;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
    }
    
    .search-icon {
        font-size: 16px;
    }
    /* Logout Button Styles */
#logout-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    background: #e74a3b;
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    z-index: 100;
}

#logout-btn:hover {
    background: #d62c1a;
    transform: translateY(-1px);
}

#logout-btn i {
    font-size: 14px;
}

/* Adjust main content to account for the logout button */
.main-content {
    position: relative;
    padding-top: 60px; /* Add some space at the top */
}
</style>
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Display messages/errors -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
         
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>HR Portal</h2>
            </div>
            <div class="sidebar-menu">
                <button class="active" data-tab="profile">Profile</button>
                <button data-tab="search">Search Employee</button>
                <button data-tab="add">Add Employee</button>
                <button data-tab="update">Update Employee</button>
                <button data-tab="delete">Delete Employee</button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Profile Tab -->
            <a href="#" id="logout-btn" class="logout-btn" onclick="confirmLogout()">Logout</a>
            <div class="tab-content active" id="profile">
                <h2>HR Profile</h2>
                <div class="profile-card">
                    <div class="profile-image">
                        <div class="profile-placeholder">HR</div>
                    </div>
                    <div class="profile-details">
                    <div id="hr-profile-view">
                        <?php if ($hr_profile): ?>
                            <h3><?php echo htmlspecialchars($hr_profile['name'] ?? ''); ?></h3>
                            <p><?php echo htmlspecialchars($hr_profile['position'] ?? ''); ?></p>
                            <p>Email: <?php echo htmlspecialchars($hr_profile['email'] ?? ''); ?></p>
                            <p>Department: <?php echo htmlspecialchars($hr_profile['department'] ?? ''); ?></p>
                            <p>Employee ID: <?php echo htmlspecialchars($hr_profile['id'] ?? ''); ?></p>
                            <p>Joined: <?php echo isset($hr_profile['date_of_joining']) ? date('F j, Y', strtotime($hr_profile['date_of_joining'])) : ''; ?></p>
                            <button id="edit-hr-profile" class="edit-btn">Edit Profile</button>
                        <?php else: ?>
                            <p>No profile information available.</p>
                        <?php endif; ?>
                    </div>
                        <div id="hr-profile-edit" style="display: none;">
                            <?php if ($hr_profile): ?>
                                <form method="post" action="dashboard.php" class="employee-form">
                                    <input type="hidden" name="update_hr_profile" value="1">
                                    <div class="form-group">
                                        <label>Name:</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($hr_profile['name'] ?? ''); ?>" required />
                                    </div>
                                    <div class="form-group">
                                        <label>Position:</label>
                                        <input type="text" name="position" value="<?php echo htmlspecialchars($hr_profile['position'] ?? ''); ?>" required />
                                    </div>
                                    <div class="form-group">
                                        <label>Email:</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($hr_profile['email'] ?? ''); ?>" required />
                                    </div>
                                    <div class="form-group">
                                        <label>Department:</label>
                                        <input type="text" name="department" value="<?php echo htmlspecialchars($hr_profile['department'] ?? ''); ?>" required />
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="submit-btn">Save Changes</button>
                                        <button type="button" id="cancel-hr-edit" class="cancel-btn">Cancel</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Employee Tab -->
            <div class="tab-content" id="search">
                <h2>Search Employee</h2>
                <form method="get" action="dashboard.php" class="search-bar">
                    <input 
                        type="text" 
                        name="search"
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                        placeholder="Search employees by name or ID..." 
                    />
                    <button type="submit" class="search-icon-btn"><i class="fas fa-search search-icon"></i></button>
                    <input type="hidden" name="tab" value="search">
                </form>
                
                <?php if (!empty($search_results)): ?>
                    <div id="employee-list-search" class="employee-list">
                        <?php foreach ($search_results as $employee): ?>
                            <div class="employee-item">
                                <div class="employee-avatar"><?php echo strtoupper(substr($employee['name'], 0, 2)); ?></div>
                                <div class="employee-info">
                                    <h4><?php echo htmlspecialchars($employee['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($employee['position']); ?></p>
                                    <p><?php echo htmlspecialchars($employee['department']); ?></p>
                                </div>
                                <a href="dashboard.php?tab=search&employee_id=<?php echo $employee['id']; ?>" class="view-btn">View Details</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (isset($_GET['search'])): ?>
                    <p>No employees found matching your search.</p>
                <?php endif; ?>
                
                <?php if ($employee_details && isset($_GET['tab']) && $_GET['tab'] === 'search'): ?>
                    <div id="employee-details-container">
                        <h3>Employee Details</h3>
                        <div class="employee-details-card">
                            <div class="employee-details-header">
                                <div class="profile-placeholder employee-avatar">
                                    <?php echo strtoupper(substr($employee_details['employee']['name'], 0, 2)); ?>
                                </div>
                                <div class="employee-name-position">
                                    <h3 id="detail-name"><?php echo htmlspecialchars($employee_details['employee']['name']); ?></h3>
                                    <p id="detail-position"><?php echo htmlspecialchars($employee_details['employee']['position']); ?></p>
                                </div>
                            </div>
                            
                            <div class="employee-details-tabs">
                                <button class="detail-tab-btn active" data-detail-tab="basic">Basic Info</button>
                                <button class="detail-tab-btn" data-detail-tab="attendance">Attendance</button>
                                <button class="detail-tab-btn" data-detail-tab="salary">Salary & Payroll</button>
                                <button class="detail-tab-btn" data-detail-tab="leaves">Leaves</button>
                                <button class="detail-tab-btn" data-detail-tab="work">Work Experience</button>
                            </div>
                            
                            <div class="employee-details-content">
                                <!-- Basic Info Tab -->
                                <div class="detail-tab-content active" id="basic-tab">
                                    <div class="detail-item">
                                        <span class="detail-label">Employee ID:</span>
                                        <span id="detail-id"><?php echo htmlspecialchars($employee_details['employee']['id']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Email:</span>
                                        <span id="detail-email"><?php echo htmlspecialchars($employee_details['employee']['email']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Department:</span>
                                        <span id="detail-department"><?php echo htmlspecialchars($employee_details['employee']['department']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Date of Joining:</span>
                                        <span id="detail-joining"><?php echo date('F j, Y', strtotime($employee_details['employee']['date_of_joining'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Contact:</span>
                                        <span id="detail-contact"><?php echo htmlspecialchars($employee_details['employee']['contact_no']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Address:</span>
                                        <span id="detail-address"><?php echo htmlspecialchars($employee_details['employee']['address']); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Attendance Tab -->
                                <div class="detail-tab-content" id="attendance-tab">
                                    <div class="detail-item">
                                        <span class="detail-label">Present Days (Current Month):</span>
                                        <span id="detail-present"><?php echo htmlspecialchars($employee_details['employee']['present_days']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Absent Days (Current Month):</span>
                                        <span id="detail-absent"><?php echo htmlspecialchars($employee_details['employee']['absent_days']); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Salary Tab -->
                                <div class="detail-tab-content" id="salary-tab">
                                    <?php if ($employee_details['salary']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Base Salary:</span>
                                            <span id="detail-base-salary">$<?php echo number_format($employee_details['salary']['base_salary'], 2); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Allowances:</span>
                                            <span id="detail-allowances">$<?php echo number_format($employee_details['salary']['allowances'], 2); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Deductions:</span>
                                            <span id="detail-deductions">$<?php echo number_format($employee_details['salary']['deduction'], 2); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Net Salary:</span>
                                            <span id="detail-net-salary">$<?php echo number_format(
                                                $employee_details['salary']['base_salary'] + 
                                                $employee_details['salary']['allowances'] - 
                                                $employee_details['salary']['deduction'], 
                                                2
                                            ); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Payment Method:</span>
                                            <span id="detail-payment-method"><?php echo htmlspecialchars($employee_details['salary']['payment_method']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <p>No salary information available.</p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Leaves Tab -->
                                <div class="detail-tab-content" id="leaves-tab">
                                    <div class="detail-item">
                                        <span class="detail-label">Annual Leave Balance:</span>
                                        <span id="detail-annual-leave"><?php echo htmlspecialchars($employee_details['employee']['annual_leave']); ?> days</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Sick Leave Balance:</span>
                                        <span id="detail-sick-leave"><?php echo htmlspecialchars($employee_details['employee']['sick_leave']); ?> days</span>
                                    </div>
                                </div>
                                
                                <!-- Work Experience Tab -->
                                <div class="detail-tab-content" id="work-tab">
                                    <?php if ($employee_details['work']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Total Experience:</span>
                                            <span id="detail-experience"><?php echo htmlspecialchars($employee_details['work']['total_experience']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Previous Companies:</span>
                                            <span id="detail-previous-companies"><?php echo htmlspecialchars($employee_details['work']['previous_company']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Skills:</span>
                                            <span id="detail-skills"><?php echo htmlspecialchars($employee_details['work']['skills']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Certifications:</span>
                                            <span id="detail-certifications"><?php echo htmlspecialchars($employee_details['work']['certificates']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Projects:</span>
                                            <span id="detail-projects"><?php echo htmlspecialchars($employee_details['work']['projects']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <p>No work experience information available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="employee-details-actions">
                                <a href="dashboard.php?tab=update&employee_id=<?php echo $employee_details['employee']['id']; ?>" class="edit-btn">Update Employee</a>
                                <a href="dashboard.php?tab=search" class="cancel-btn">Back to Search</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add Employee Tab -->
            <div class="tab-content" id="add">
                <h2>Add New Employee</h2>
                <form method="post" action="dashboard.php" class="employee-form">
                    <input type="hidden" name="add_employee" value="1">
                    
                    <div class="form-section">
                        <h3 class="section-title">Basic Information</h3>
                        <div class="form-group">
                            <label>Name:</label>
                            <input type="text" name="name" required />
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" required />
                        </div>
                        <div class="form-group">
                            <label>Department:</label>
                            <input type="text" name="department" required />
                        </div>
                        <div class="form-group">
                            <label>Position:</label>
                            <input type="text" name="position" required />
                        </div>
                        <div class="form-group">
                            <label>Date of Joining:</label>
                            <input type="date" name="date_of_joining" required />
                        </div>
                        <div class="form-group">
                            <label>Contact:</label>
                            <input type="tel" name="contact_no" required />
                        </div>
                        <div class="form-group">
                            <label>Address:</label>
                            <textarea name="address" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="password" required />
                        </div>
                        <div class="form-group">
                            <label>Confirm Password:</label>
                            <input type="password" name="confirm_password" required />
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">Salary Information</h3>
                        <div class="form-group">
                            <label>Base Salary:</label>
                            <input type="number" step="0.01" name="base_salary" required />
                        </div>
                        <div class="form-group">
                            <label>Allowances:</label>
                            <input type="number" step="0.01" name="allowances" required />
                        </div>
                        <div class="form-group">
                            <label>Payment Method:</label>
                            <select name="payment_method" required>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Check">Check</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Deductions:</label>
                            <input type="number" step="0.01" name="deduction" required />
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">Work Experience</h3>
                        <div class="form-group">
                            <label>Total Experience (years):</label>
                            <input type="text" name="total_experience" required />
                        </div>
                        <div class="form-group">
                            <label>Previous Companies:</label>
                            <textarea name="previous_company"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Skills:</label>
                            <textarea name="skills" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Certifications:</label>
                            <textarea name="certificates"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Projects:</label>
                            <textarea name="projects"></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">Add Employee</button>
                </form>
            </div>

            <!-- Update Employee Tab -->
            <div class="tab-content" id="update">
                <h2>Update Employee</h2>
                <form method="get" action="dashboard.php" class="search-bar">
                    <input 
                        type="text" 
                        name="search"
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                        placeholder="Search employees by name or ID..." 
                    />
                    <button type="submit" class="search-icon-btn"><i class="fas fa-search search-icon"></i></button>
                    <input type="hidden" name="tab" value="update">
                </form>
                
                <?php if (!empty($search_results) && isset($_GET['tab']) && $_GET['tab'] === 'update'): ?>
                    <div id="employee-list-update" class="employee-list">
                        <?php foreach ($search_results as $employee): ?>
                            <div class="employee-item">
                                <div class="employee-avatar"><?php echo strtoupper(substr($employee['name'], 0, 2)); ?></div>
                                <div class="employee-info">
                                    <h4><?php echo htmlspecialchars($employee['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($employee['position']); ?></p>
                                    <p><?php echo htmlspecialchars($employee['department']); ?></p>
                                </div>
                                <a href="dashboard.php?tab=update&employee_id=<?php echo $employee['id']; ?>" class="view-btn">Update</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (isset($_GET['search']) && isset($_GET['tab']) && $_GET['tab'] === 'update' ): ?>
                    <p>No employees found matching your search.</p>
                <?php endif; ?>
                
                <?php if ($employee_details && isset($_GET['tab']) && $_GET['tab'] === 'update'): ?>
                    <div id="update-edit-form-container">
                        <form method="post" action="dashboard.php" class="employee-form">
                            <input type="hidden" name="update_employee" value="1">
                            <input type="hidden" name="employee_id" value="<?php echo $employee_details['employee']['id']; ?>">
                            
                            <div class="form-section">
                                <h3 class="section-title">Basic Information</h3>
                                <div class="form-group">
                                    <label>Employee ID:</label>
                                    <input type="text" value="<?php echo htmlspecialchars($employee_details['employee']['id']); ?>" disabled />
                                </div>
                                <div class="form-group">
                                    <label>Name:</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($employee_details['employee']['name']); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Email:</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($employee_details['employee']['email']); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Department:</label>
                                    <input type="text" name="department" value="<?php echo htmlspecialchars($employee_details['employee']['department']); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Position:</label>
                                    <input type="text" name="position" value="<?php echo htmlspecialchars($employee_details['employee']['position']); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Date of Joining:</label>
                                    <input type="date" name="date_of_joining" value="<?php echo htmlspecialchars($employee_details['employee']['date_of_joining']); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Contact:</label>
                                    <input type="tel" name="contact_no" value="<?php echo htmlspecialchars($employee_details['employee']['contact_no']); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Address:</label>
                                    <textarea name="address" required><?php echo htmlspecialchars($employee_details['employee']['address']); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3 class="section-title">Attendance & Leaves</h3>
                                <div class="form-group">
                                    <label>Present Days (Current Month):</label>
                                    <input type="number" name="present_days" value="<?php echo htmlspecialchars($employee_details['employee']['present_days']); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Absent Days (Current Month):</label>
                                    <input type="number" name="absent_days" value="<?php echo htmlspecialchars($employee_details['employee']['absent_days']); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Annual Leave Balance:</label>
                                    <input type="number" name="annual_leave" value="<?php echo htmlspecialchars($employee_details['employee']['annual_leave']); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Sick Leave Balance:</label>
                                    <input type="number" name="sick_leave" value="<?php echo htmlspecialchars($employee_details['employee']['sick_leave']); ?>" required />
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3 class="section-title">Salary Information</h3>
                                <?php if ($employee_details['salary']): ?>
                                    <div class="form-group">
                                        <label>Base Salary:</label>
                                        <input type="number" step="0.01" name="base_salary" value="<?php echo htmlspecialchars($employee_details['salary']['base_salary']); ?>" required />
                                    </div>
                                    <div class="form-group">
                                        <label>Allowances:</label>
                                        <input type="number" step="0.01" name="allowances" value="<?php echo htmlspecialchars($employee_details['salary']['allowances']); ?>" required />
                                    </div>
                                    <div class="form-group">
                                        <label>Deductions:</label>
                                        <input type="number" step="0.01" name="deduction" value="<?php echo htmlspecialchars($employee_details['salary']['deduction']); ?>" required />
                                    </div>
                                    <div class="form-group">
                                        <label>Payment Method:</label>
                                        <select name="payment_method" required>
                                            <option value="Bank Transfer" <?php echo $employee_details['salary']['payment_method'] === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                            <option value="Check" <?php echo $employee_details['salary']['payment_method'] === 'Check' ? 'selected' : ''; ?>>Check</option>
                                            <option value="Cash" <?php echo $employee_details['salary']['payment_method'] === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                        </select>
                                    </div>
                                <?php else: ?>
                                    <p>No salary information available for this employee.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-section">
                                <h3 class="section-title">Work Experience</h3>
                                <?php if ($employee_details['work']): ?>
                                    <div class="form-group">
                                        <label>Total Experience (years):</label>
                                        <input type="text" name="total_experience" value="<?php echo htmlspecialchars($employee_details['work']['total_experience']); ?>" required />
                                    </div>
                                    <div class="form-group">
                                        <label>Previous Companies:</label>
                                        <textarea name="previous_company"><?php echo htmlspecialchars($employee_details['work']['previous_company']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Skills:</label>
                                        <textarea name="skills" required><?php echo htmlspecialchars($employee_details['work']['skills']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Certifications:</label>
                                        <textarea name="certificates"><?php echo htmlspecialchars($employee_details['work']['certificates']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Projects:</label>
                                        <textarea name="projects"><?php echo htmlspecialchars($employee_details['work']['projects']); ?></textarea>
                                    </div>
                                <?php else: ?>
                                    <p>No work experience information available for this employee.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="submit-btn">Update Employee</button>
                                <a href="dashboard.php?tab=update" class="cancel-btn">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Delete Employee Tab -->
            <div class="tab-content" id="delete">
                <h2>Delete Employee</h2>
                <form method="get" action="dashboard.php" class="search-bar">
                    <input 
                        type="text" 
                        name="search"
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                        placeholder="Search employees by name or ID..." 
                    />
                    <button type="submit" class="search-icon-btn"><i class="fas fa-search search-icon"></i></button>
                    <input type="hidden" name="tab" value="delete">
                </form>
                
                <?php if (!empty($search_results) && isset($_GET['tab']) && $_GET['tab'] === 'delete'): ?>
                    <div id="employee-list-delete" class="employee-list">
                        <?php foreach ($search_results as $employee): ?>
                            <div class="employee-item">
                                <div class="employee-avatar"><?php echo strtoupper(substr($employee['name'], 0, 2)); ?></div>
                                <div class="employee-info">
                                    <h4><?php echo htmlspecialchars($employee['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($employee['position']); ?></p>
                                    <p><?php echo htmlspecialchars($employee['department']); ?></p>
                                </div>
                                <form method="post" action="dashboard.php" style="display: inline;">
                                    <input type="hidden" name="delete_employee" value="1">
                                    <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                    <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this employee?')">Delete</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (isset($_GET['search']) && isset($_GET['tab']) && $_GET['tab'] === 'delete'): ?>
                    <p>No employees found matching your search.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../users/js/script.js"></script>
    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Get all tab buttons and content
            const tabButtons = document.querySelectorAll('.sidebar-menu button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Handle tab button clicks
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Update URL without reloading
                    history.pushState(null, null, `?tab=${tabId}`);
                    
                    // Update active tab
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show corresponding content
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                        if (content.id === tabId) {
                            content.classList.add('active');
                        }
                    });
                });
            });
            
            // Check for tab parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            
            if (activeTab) {
                const tabButton = document.querySelector(`.sidebar-menu button[data-tab="${activeTab}"]`);
                if (tabButton) {
                    tabButton.click();
                }
            }
            
            // HR profile edit toggle
            const editHrProfileBtn = document.getElementById('edit-hr-profile');
            const cancelHrEditBtn = document.getElementById('cancel-hr-edit');
            const hrProfileView = document.getElementById('hr-profile-view');
            const hrProfileEdit = document.getElementById('hr-profile-edit');
            
            if (editHrProfileBtn && cancelHrEditBtn) {
                editHrProfileBtn.addEventListener('click', function() {
                    hrProfileView.style.display = 'none';
                    hrProfileEdit.style.display = 'block';
                });
                
                cancelHrEditBtn.addEventListener('click', function() {
                    hrProfileView.style.display = 'block';
                    hrProfileEdit.style.display = 'none';
                });
            }
            
            // Employee details tab switching
            const detailTabButtons = document.querySelectorAll('.detail-tab-btn');
            const detailTabContents = document.querySelectorAll('.detail-tab-content');
            
            detailTabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-detail-tab');
                    
                    detailTabButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    detailTabContents.forEach(content => {
                        content.classList.remove('active');
                        if (content.id === `${tabId}-tab`) {
                            content.classList.add('active');
                        }
                    });
                });
            });
        });


        //logout
      function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        // If user confirms, redirect to logout URL
        window.location.href = '../users/logout.php?logout=1';
    }
    // If user cancels, do nothing
}
    </script>
</body>
</html>
