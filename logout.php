<?php
session_start();

// If logout parameter is set
if (isset($_GET['logout'])) {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: ../users/index.php");
    exit();
}
?>
