<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hr_mng');

// File upload configuration
define('UPLOAD_DIR', 'uploads/');
if (!file_exists(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0777, true)) {
        die("Failed to create upload directory");
    }
}

// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Check if HR profile exists, if not insert default
    $checkHR = $conn->query("SELECT id FROM employees WHERE is_hr = TRUE LIMIT 1");
    if ($checkHR === false) {
        throw new Exception("Error checking HR profile: " . $conn->error);
    }
    
    if ($checkHR->num_rows == 0) {
        $insertSql = "INSERT INTO employees (name, position, department, email, phone, address, is_hr) 
                      VALUES ('John Doe', 'HR Manager', 'Human Resources', 'john.doe@company.com', '+1 (555) 123-4567', '123 HR Street, New York, NY 10001', TRUE)";
        if (!$conn->query($insertSql)) {
            throw new Exception("Default HR profile insertion failed: " . $conn->error);
        }
    }

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Handle file upload
function handleFileUpload($file, $prefix = 'profile') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return null;
    }

    // Validate file size (max 2MB)
    if ($file['size'] > 2097152) {
        return null;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . uniqid() . '.' . $extension;
    $destination = UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $destination;
    }

    return null;
}

// Initialize messages
$hr_success = $hr_error = $employee_success = $employee_error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle HR profile update
    if (isset($_POST['update_hr_profile'])) {
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $position = $conn->real_escape_string($_POST['position'] ?? '');
        $department = $conn->real_escape_string($_POST['department'] ?? 'Human Resources');
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        $address = $conn->real_escape_string($_POST['address'] ?? '');
        $email = $conn->real_escape_string($_POST['email'] ?? '');
        
        // Get current HR profile to check for existing image
        $result = $conn->query("SELECT img_url FROM employees WHERE is_hr = TRUE LIMIT 1");
        $hr_profile = $result->fetch_assoc();
        $img_url = $hr_profile['img_url'] ?? null;
        
        // Handle file upload
        if (!empty($_FILES['profile_img']['name'])) {
            $new_img_url = handleFileUpload($_FILES['profile_img'], 'hr');
            if ($new_img_url) {
                // Delete old image if it exists
                if ($img_url && file_exists($img_url)) {
                    unlink($img_url);
                }
                $img_url = $new_img_url;
            }
        }
        
        $img_url_value = $img_url ? "'" . $conn->real_escape_string($img_url) . "'" : "NULL";
        
        $updateSql = "UPDATE employees SET 
                      name = '$name', 
                      position = '$position', 
                      department = '$department',
                      phone = '$phone', 
                      address = '$address', 
                      email = '$email', 
                      img_url = $img_url_value 
                      WHERE is_hr = TRUE";
        
        if ($conn->query($updateSql)) {
            $hr_success = "HR Profile updated successfully!";
        } else {
            $hr_error = "Error updating profile: " . $conn->error;
        }
    }
    
    // Handle employee actions
    if (isset($_POST['employee_action'])) {
        $action = $_POST['employee_action'];
        $id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
        
        if ($action === 'add' || $action === 'update') {
            $name = $conn->real_escape_string($_POST['name'] ?? '');
            $position = $conn->real_escape_string($_POST['position'] ?? '');
            $department = $conn->real_escape_string($_POST['department'] ?? '');
            $phone = $conn->real_escape_string($_POST['phone'] ?? '');
            $address = $conn->real_escape_string($_POST['address'] ?? '');
            $email = $conn->real_escape_string($_POST['email'] ?? '');
            $bio = $conn->real_escape_string($_POST['bio'] ?? '');
        
            // Handle file upload
            $img_url = null;
            if (!empty($_FILES['employee_img']['name'])) {
                $img_url = handleFileUpload($_FILES['employee_img'], 'emp');
            } elseif ($action === 'update' && !empty($_POST['existing_img_url'])) {
                $img_url = $_POST['existing_img_url'];
            }
            
            $img_url_value = $img_url ? "'" . $conn->real_escape_string($img_url) . "'" : "NULL";
            
            if ($action === 'add') {
                $insertSql = "INSERT INTO employees (name, position, department, phone, address, email, img_url, bio, is_hr) 
                             VALUES ('$name', '$position', '$department', '$phone', '$address', '$email', $img_url_value, '$bio', FALSE)";
                
                if ($conn->query($insertSql)) {
                    $employee_success = "Employee added successfully!";
                } else {
                    $employee_error = "Error adding employee: " . $conn->error;
                }
            } else {
                // First get current image path to delete if needed
                $current_img = null;
                if ($id) {
                    $result = $conn->query("SELECT img_url FROM employees WHERE id = $id");
                    if ($result && $result->num_rows > 0) {
                        $current_img = $result->fetch_assoc()['img_url'];
                    }
                }
                
                $updateSql = "UPDATE employees SET 
                             name = '$name', 
                             position = '$position', 
                             department = '$department',
                             phone = '$phone', 
                             address = '$address', 
                             email = '$email', 
                             img_url = $img_url_value, 
                             bio = '$bio' 
                             WHERE id = $id";
                
                if ($conn->query($updateSql)) {
                    // Delete old image if it was replaced
                    if ($img_url && $current_img && $current_img != $img_url && file_exists($current_img)) {
                        unlink($current_img);
                    }
                    $employee_success = "Employee updated successfully!";
                } else {
                    // Delete the new image if update failed
                    if ($img_url && file_exists($img_url)) {
                        unlink($img_url);
                    }
                    $employee_error = "Error updating employee: " . $conn->error;
                }
            }
        } elseif ($action === 'delete') {
            // First get image path to delete
            $img_url = null;
            $result = $conn->query("SELECT img_url FROM employees WHERE id = $id");
            if ($result && $result->num_rows > 0) {
                $img_url = $result->fetch_assoc()['img_url'];
            }
            
            $deleteSql = "DELETE FROM employees WHERE id = $id AND is_hr = FALSE";
            if ($conn->query($deleteSql)) {
                // Delete the image file
                if ($img_url && file_exists($img_url)) {
                    unlink($img_url);
                }
                $employee_success = "Employee deleted successfully!";
            } else {
                $employee_error = "Error deleting employee: " . $conn->error;
            }
        }
    }
}

// Get HR profile data
$hr_profile = [];
$result = $conn->query("SELECT * FROM employees WHERE is_hr = TRUE LIMIT 1");
if ($result && $result->num_rows > 0) {
    $hr_profile = $result->fetch_assoc();
} else {
    $hr_profile = [
        'name' => '',
        'position' => '',
        'department' => 'Human Resources',
        'email' => '',
        'phone' => '',
        'address' => '',
        'img_url' => null
    ];
}

// Get all employees (excluding HR)
$employees = [];
$result = $conn->query("SELECT * FROM employees WHERE is_hr = FALSE ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Handle employee search
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $search_sql = "SELECT * FROM employees 
                   WHERE (name LIKE '%$search_term%' 
                   OR position LIKE '%$search_term%'
                   OR department LIKE '%$search_term%')
                   AND is_hr = FALSE
                   ORDER BY name";
    $search_result = $conn->query($search_sql);
    if ($search_result) {
        while ($row = $search_result->fetch_assoc()) {
            $search_results[] = $row;
        }
    }
}

// Get employee count (excluding HR)
$count_result = $conn->query("SELECT COUNT(*) as total FROM employees WHERE is_hr = FALSE");
$employee_count = $count_result ? $count_result->fetch_assoc()['total'] : 0;

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Profile Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            height: 100vh;
            overflow: hidden;
        }

        .container {
            max-width: 100%;
            height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        h1, h2, h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #3498db;
            padding: 20px 0;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .main-content {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .profile-section {
            flex: 1;
            background-color: white;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .search-section {
            flex: 2;
            background-color: white;
            padding: 20px;
            overflow-y: auto;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .profile-img-container {
            position: relative;
            width: 100px;
            height: 100px;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
        }

        .edit-img-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .edit-img-btn:hover {
            background-color: #2980b9;
        }

        .profile-title h2 {
            margin-bottom: 5px;
        }

        .profile-title p {
            color: #7f8c8d;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .info-display p {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .info-display strong {
            display: inline-block;
            width: 100px;
            color: #555;
        }

        .edit-btn, .save-btn, .cancel-btn, #search-btn, .action-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background-color: #3498db;
            color: white;
            align-self: flex-start;
        }

        .edit-btn:hover {
            background-color: #2980b9;
        }

        .edit-form {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .save-btn {
            background-color: #2ecc71;
            color: white;
        }

        .save-btn:hover {
            background-color: #27ae60;
        }

        .cancel-btn {
            background-color: #e74c3c;
            color: white;
        }

        .cancel-btn:hover {
            background-color: #c0392b;
        }

        /* Search Section Styles */
        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        #search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        #search-btn {
            background-color: #9b59b6;
            color: white;
        }

        #search-btn:hover {
            background-color: #8e44ad;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .section-title h2 {
            margin-bottom: 0;
        }

        .employee-count {
            background-color: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .add-btn {
            background-color: #2ecc71;
            color: white;
        }

        .add-btn:hover {
            background-color: #27ae60;
        }

        .update-btn {
            background-color: #f39c12;
            color: white;
        }

        .update-btn:hover {
            background-color: #e67e22;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        /* Table Styles */
        .employee-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .employee-table th, .employee-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .employee-table th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
        }

        .employee-table tr:hover {
            background-color: #f5f5f5;
        }

        .employee-table tr.selected {
            background-color: #e8f4fc;
        }

        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #3498db;
        }

        .no-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
            font-size: 10px;
        }

        /* Modal Styles */
        #employee-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-container {
            background-color: white;
            margin: 20px auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 800px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .file-upload {
            text-align: center;
            margin-bottom: 20px;
        }

        .file-upload-label {
            display: inline-block;
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .file-upload-label:hover {
            background-color: #2980b9;
        }

        .file-upload-input {
            display: none;
        }

        .employee-img-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
            margin: 0 auto 10px;
            display: block;
        }

        .success {
            color: #2ecc71;
            padding: 10px;
            margin: 10px 0;
            background-color: #e8f8f0;
            border-radius: 4px;
        }

        .error {
            color: #e74c3c;
            padding: 10px;
            margin: 10px 0;
            background-color: #fae8e6;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            
            .profile-section, .search-section {
                flex: none;
                height: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons button {
                width: 100%;
            }
            
            .modal-container {
                margin: 10px;
                width: calc(100% - 20px);
            }
            
            .employee-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>HR Profile Management</h1>
        
        <div class="main-content">
            <!-- First Part: Profile Information -->
            <div class="profile-section">
                <div class="profile-header">
                    <div class="profile-img-container">
                        <?php if (!empty($hr_profile['img_url'])): ?>
                            <img src="<?php echo htmlspecialchars($hr_profile['img_url']); ?>" alt="Profile Image" class="profile-img" id="profile-img">
                        <?php else: ?>
                            <div class="profile-img" style="background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #777;">No Image</span>
                            </div>
                        <?php endif; ?>
                        <button class="edit-img-btn" id="edit-img-btn">✏️</button>
                    </div>
                    <div class="profile-title">
                        <h2 id="display-name"><?php echo htmlspecialchars($hr_profile['name']); ?></h2>
                        <p id="display-position"><?php echo htmlspecialchars($hr_profile['position']); ?></p>
                    </div>
                </div>
                
                <div class="profile-info">
                    <div class="info-display">
                        <p><strong>Phone:</strong> <span id="display-phone"><?php echo htmlspecialchars($hr_profile['phone']); ?></span></p>
                        <p><strong>Address:</strong> <span id="display-address"><?php echo htmlspecialchars($hr_profile['address']); ?></span></p>
                        <p><strong>Email:</strong> <span id="display-email"><?php echo htmlspecialchars($hr_profile['email']); ?></span></p>
                    </div>
                    
                    <button id="edit-profile-btn" class="edit-btn">Edit Profile</button>
                    
                    <div class="edit-form" id="edit-form" style="display: none;">
                        <h3>Edit Profile</h3>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="update_hr_profile" value="1">
                            <div class="file-upload">
                                <?php if (!empty($hr_profile['img_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($hr_profile['img_url']); ?>" class="profile-img-preview" id="profile-img-preview">
                                <?php else: ?>
                                    <div class="profile-img-preview" id="profile-img-preview" style="background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                        <span style="color: #777;">No Image</span>
                                    </div>
                                <?php endif; ?>
                                <label for="profile-upload" class="file-upload-label">Choose Profile Image</label>
                                <input type="file" id="profile-upload" name="profile_img" class="file-upload-input" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label for="edit-name">Name:</label>
                                <input type="text" id="edit-name" name="name" value="<?php echo htmlspecialchars($hr_profile['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-position">Position:</label>
                                <input type="text" id="edit-position" name="position" value="<?php echo htmlspecialchars($hr_profile['position']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-phone">Phone:</label>
                                <input type="tel" id="edit-phone" name="phone" value="<?php echo htmlspecialchars($hr_profile['phone']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-address">Address:</label>
                                <input type="text" id="edit-address" name="address" value="<?php echo htmlspecialchars($hr_profile['address']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-email">Email:</label>
                                <input type="email" id="edit-email" name="email" value="<?php echo htmlspecialchars($hr_profile['email']); ?>" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="save-btn">Save Changes</button>
                                <button type="button" id="cancel-edit" class="cancel-btn">Cancel</button>
                            </div>
                        </form>
                        <?php if (!empty($hr_success)): ?>
                            <div class="success"><?php echo $hr_success; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($hr_error)): ?>
                            <div class="error"><?php echo $hr_error; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Second Part: Employee Directory -->
            <div class="search-section">
                <div class="section-title">
                    <h2>Employee Directory</h2>
                    <span class="employee-count" id="employee-count"><?php echo $employee_count; ?> employees</span>
                </div>
                
                <div class="action-buttons">
                    <button id="add-employee-btn" class="add-btn action-btn">Add Employee</button>
                    <button id="update-employee-btn" class="update-btn action-btn" disabled>Update Employee</button>
                    <button id="delete-employee-btn" class="delete-btn action-btn" disabled>Delete Employee</button>
                </div>
                
                <div class="search-container">
                    <form method="GET" action="">
                        <input type="text" id="search-input" name="search" placeholder="Search employees by name or position..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" id="search-btn">Search</button>
                    </form>
                </div>
                
                <div class="employee-directory">
                    <?php 
                    $display_employees = !empty($search_results) ? $search_results : $employees;
                    
                    if (empty($display_employees)): ?>
                        <div class="no-results">No employees found</div>
                    <?php else: ?>
                        <table class="employee-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($display_employees as $employee): ?>
                                    <tr data-id="<?php echo $employee['id']; ?>">
                                        <td>
                                            <?php if (!empty($employee['img_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($employee['img_url']); ?>" class="employee-avatar">
                                            <?php else: ?>
                                                <div class="no-avatar">No Image</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Employee Modal -->
    <div id="employee-modal">
        <div class="modal-container">
            <h3 id="modal-title">Add New Employee</h3>
            <form method="POST" action="" id="employee-form" enctype="multipart/form-data">
                <input type="hidden" name="employee_action" id="employee_action" value="add">
                <input type="hidden" name="employee_id" id="employee_id" value="">
                <input type="hidden" name="existing_img_url" id="existing_img_url" value="">
                
                <div class="file-upload">
                    <img src="" class="employee-img-preview" id="employee-img-preview" style="display: none;">
                    <div id="no-employee-img" style="width: 100px; height: 100px; background-color: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <span style="color: #777;">No Image</span>
                    </div>
                    <label for="employee-upload" class="file-upload-label">Choose Profile Image</label>
                    <input type="file" id="employee-upload" name="employee_img" class="file-upload-input" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="modal-name">Name:</label>   
                    <input type="text" id="modal-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="modal-position">Position:</label>
                    <input type="text" id="modal-position" name="position" required>
                </div>
                <div class="form-group">
                    <label for="modal-department">Department:</label>
                    <input type="text" id="modal-department" name="department" required>
                </div>
                <div class="form-group">
                    <label for="modal-phone">Phone:</label>
                    <input type="tel" id="modal-phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="modal-address">Address:</label>
                    <input type="text" id="modal-address" name="address">
                </div>
                <div class="form-group">
                    <label for="modal-email">Email:</label>
                    <input type="email" id="modal-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="modal-bio">Bio:</label>
                    <textarea id="modal-bio" name="bio" rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" id="cancel-modal" class="cancel-btn">Cancel</button>
                </div>
            </form>
            <?php if (!empty($employee_success)): ?>
                <div class="success"><?php echo $employee_success; ?></div>
            <?php endif; ?>
            <?php if (!empty($employee_error)): ?>
                <div class="error"><?php echo $employee_error; ?></div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Profile Edit Functionality
            const editProfileBtn = document.getElementById('edit-profile-btn');
            const editForm = document.getElementById('edit-form');
            const cancelEditBtn = document.getElementById('cancel-edit');
            const editImgBtn = document.getElementById('edit-img-btn');
            
            // Profile image preview
            const profileUpload = document.getElementById('profile-upload');
            let profileImgPreview = document.getElementById('profile-img-preview');
            
            profileUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (profileImgPreview.tagName === 'IMG') {
                            profileImgPreview.src = e.target.result;
                        } else {
                            // Replace the div with an img element
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'profile-img-preview';
                            img.id = 'profile-img-preview';
                            profileImgPreview.replaceWith(img);
                            profileImgPreview = img;
                        }
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Toggle edit form
            editProfileBtn.addEventListener('click', function() {
                editForm.style.display = 'block';
                editProfileBtn.style.display = 'none';
            });
            
            // Edit image button
            editImgBtn.addEventListener('click', function() {
                editProfileBtn.click();
                profileUpload.click();
            });
            
            // Cancel edit
            cancelEditBtn.addEventListener('click', function() {
                editForm.style.display = 'none';
                editProfileBtn.style.display = 'block';
            });
            
            // Employee Directory Functionality
            const addEmployeeBtn = document.getElementById('add-employee-btn');
            const updateEmployeeBtn = document.getElementById('update-employee-btn');
            const deleteEmployeeBtn = document.getElementById('delete-employee-btn');
            const employeeModal = document.getElementById('employee-modal');
            const cancelModalBtn = document.getElementById('cancel-modal');
            const employeeForm = document.getElementById('employee-form');
            const modalTitle = document.getElementById('modal-title');
            const employeeAction = document.getElementById('employee_action');
            const employeeId = document.getElementById('employee_id');
            const existingImgUrl = document.getElementById('existing_img_url');
            
            // Employee image upload preview
            const employeeUpload = document.getElementById('employee-upload');
            const employeeImgPreview = document.getElementById('employee-img-preview');
            const noEmployeeImg = document.getElementById('no-employee-img');
            
            employeeUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        employeeImgPreview.src = e.target.result;
                        employeeImgPreview.style.display = 'block';
                        noEmployeeImg.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Modal fields
            const modalName = document.getElementById('modal-name');
            const modalPosition = document.getElementById('modal-position');
            const modalDepartment = document.getElementById('modal-department');
            const modalPhone = document.getElementById('modal-phone');
            const modalAddress = document.getElementById('modal-address');
            const modalEmail = document.getElementById('modal-email');
            const modalBio = document.getElementById('modal-bio');
            
            let selectedEmployeeId = null;
            let employees = <?php echo json_encode($employees); ?>;
            let searchResultsData = <?php echo !empty($search_results) ? json_encode($search_results) : '[]'; ?>;
            
            // Select employee row
            document.querySelectorAll('.employee-table tbody tr').forEach(row => {
                row.addEventListener('click', function(e) {
                    // Don't select if clicking on action buttons
                    if (e.target.tagName === 'BUTTON') return;
                    
                    // Remove selected class from all rows
                    document.querySelectorAll('.employee-table tbody tr').forEach(r => {
                        r.classList.remove('selected');
                    });
                    
                    // Add selected class to clicked row
                    this.classList.add('selected');
                    
                    // Set selected employee ID
                    selectedEmployeeId = parseInt(this.getAttribute('data-id'));
                    
                    // Enable update and delete buttons
                    updateEmployeeBtn.disabled = false;
                    deleteEmployeeBtn.disabled = false;
                });
            });
            
            // Open edit modal with employee data
            function openEditModal(id) {
                const employee = [...employees, ...searchResultsData].find(e => e.id === id);
                if (!employee) return;
                
                employeeAction.value = 'update';
                employeeId.value = id;
                existingImgUrl.value = employee.img_url || '';
                modalTitle.textContent = 'Update Employee';
                modalName.value = employee.name;
                modalPosition.value = employee.position;
                modalDepartment.value = employee.department || '';
                modalPhone.value = employee.phone;
                modalAddress.value = employee.address || '';
                modalEmail.value = employee.email;
                modalBio.value = employee.bio || '';
                
                // Set image preview
                if (employee.img_url) {
                    employeeImgPreview.src = employee.img_url;
                    employeeImgPreview.style.display = 'block';
                    noEmployeeImg.style.display = 'none';
                } else {
                    employeeImgPreview.style.display = 'none';
                    noEmployeeImg.style.display = 'flex';
                }
                employeeUpload.value = '';
                
                employeeModal.style.display = 'block';
            }
            
            // Open modal for adding new employee
            addEmployeeBtn.addEventListener('click', function() {
                selectedEmployeeId = null;
                employeeAction.value = 'add';
                employeeId.value = '';
                existingImgUrl.value = '';
                modalTitle.textContent = 'Add New Employee';
                employeeForm.reset();
                
                // Reset image preview
                employeeImgPreview.style.display = 'none';
                employeeImgPreview.src = '';
                noEmployeeImg.style.display = 'flex';
                employeeUpload.value = '';
                
                employeeModal.style.display = 'block';
            });
            
            // Open modal for updating employee
            updateEmployeeBtn.addEventListener('click', function() {
                if (!selectedEmployeeId) return;
                openEditModal(selectedEmployeeId);
            });
            
            // Delete employee
            deleteEmployeeBtn.addEventListener('click', function() {
                if (!selectedEmployeeId) return;
                
                if (confirm('Are you sure you want to delete this employee?')) {
                    // Create a hidden form to submit the delete action
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'employee_action';
                    actionInput.value = 'delete';
                    form.appendChild(actionInput);
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'employee_id';
                    idInput.value = selectedEmployeeId;
                    form.appendChild(idInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
            
            // Cancel modal
            cancelModalBtn.addEventListener('click', function() {
                employeeModal.style.display = 'none';
            });
            
            // Close modal when clicking outside
            employeeModal.addEventListener('click', function(e) {
                if (e.target === employeeModal) {
                    employeeModal.style.display = 'none';
                }
            });
            
            // If there's a success message from PHP, show it for 5 seconds
            const successMessages = document.querySelectorAll('.success');
            successMessages.forEach(msg => {
                setTimeout(() => {
                    msg.style.display = 'none';
                }, 5000);
            });
        });
    </script>
</body>
</html>
