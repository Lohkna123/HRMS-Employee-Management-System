<?php
session_start();
require_once '../users/includes/config.php'; // Your database connection file

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        // Check if connection is established
        if ($conn->connect_error) {
            $error_message = "Database connection failed: " . $conn->connect_error;
        } else {
            // Prepare SQL statement to prevent SQL injection
            $sql = "SELECT id, email, password, is_admin FROM user WHERE email = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                $error_message = "Failed to prepare statement: " . $conn->error;
            } else {
                $stmt->bind_param("s", $email);
                if (!$stmt->execute()) {
                    $error_message = "Execute failed: " . $stmt->error;
                } else {
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows == 1) {
                        $user = $result->fetch_assoc();
                        
                        // Verify password
                        if (password_verify($password, $user['password'])) {
                            // Regenerate session ID for security
                            session_regenerate_id(true);
                            
                            // Set session variables
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['is_admin'] = $user['is_admin'];
                            
                            // Redirect based on admin status
                            if ($user['is_admin'] == 1) {
                                header("Location: ../admin/admin.php");
                            } else {
                                header("Location: ../users/dashboard.php");
                            }
                            exit();
                        } else {
                            $error_message = "Invalid email or password.";
                        }
                    } else {
                        $error_message = "Invalid email or password.";
                    }
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }
      
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
      
        .form-container {
            background-color: #ffffff;
            padding: 2rem 2.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
      
        .form-container h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
      
        form label {
            display: block;
            margin-top: 1rem;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: #34495e;
        }
      
        form input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            background-color: #f9f9f9;
            transition: border-color 0.3s ease;
        }
      
        form input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
      
        form button {
            width: 100%;
            margin-top: 1.5rem;
            padding: 0.8rem;
            font-size: 1rem;
            background-color: #3498db;
            border: none;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
      
        form button:hover {
            background-color: #2980b9;
        }
      
        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #7f8c8d;
        }
      
        .form-footer a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
      
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background-color: #fadbd8;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container" id="login-form">
            <h2>HR Management System</h2>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
                
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
                
                <button type="submit" name="login">Login</button>
                
                <div class="form-footer">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                    <p><a href="forgot_password.php">Forgot your password?</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
