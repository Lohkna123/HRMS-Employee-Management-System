<?php
// Start session
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Replace with your database username
define('DB_PASS', ''); // Replace with your database password
define('DB_NAME', 'hr_mng'); // Your database name

// Create database connection 
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Set page title
$page_title = "Talent Bridge | Human Resource Management";

// Process contact form if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    try {
        // Validate and sanitize inputs
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
        
        if ($name && $email && $message) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
            
            $_SESSION['contact_message'] = [
                'type' => 'success',
                'text' => 'Thank you for your message! We will get back to you soon.'
            ];
        } else {
            $_SESSION['contact_message'] = [
                'type' => 'error',
                'text' => 'Please fill in all required fields correctly.'
            ];
        }
    } catch (Exception $e) {
        $_SESSION['contact_message'] = [
            'type' => 'error',
            'text' => 'Error processing your request. Please try again later.'
        ];
        error_log("Contact form error: " . $e->getMessage());
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '#contact');
    exit();
}

// Process newsletter subscription if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_submit'])) {
    try {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        
        if ($email) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                // Check if error is due to duplicate email
                if ($conn->errno == 1062) {
                    $_SESSION['newsletter_message'] = [
                        'type' => 'info',
                        'text' => 'This email is already subscribed to our newsletter.'
                    ];
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
            } else {
                $_SESSION['newsletter_message'] = [
                    'type' => 'success',
                    'text' => 'Thank you for subscribing to our newsletter!'
                ];
            }
            $stmt->close();
        } else {
            $_SESSION['newsletter_message'] = [
                'type' => 'error',
                'text' => 'Please enter a valid email address.'
            ];
        }
    } catch (Exception $e) {
        $_SESSION['newsletter_message'] = [
            'type' => 'error',
            'text' => 'Error processing your subscription. Please try again later.'
        ];
        error_log("Newsletter subscription error: " . $e->getMessage());
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '#footer');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../users/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<style>
    /* Global Styles */
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --accent-color: #e74c3c;
    --light-color: #ecf0f1;
    --dark-color: #2c3e50;
    --text-color: #333;
    --text-light: #7f8c8d;
    --white: #fff;
    --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    background-color: var(--white);
}

.container {
    width: 90%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

h1, h2, h3, h4 {
    margin-bottom: 1rem;
    line-height: 1.2;
}

p {
    margin-bottom: 1rem;
}

a {
    text-decoration: none;
    color: var(--secondary-color);
    transition: var(--transition);
}

a:hover {
    color: var(--accent-color);
}

ul {
    list-style: none;
}

.btn {
    display: inline-block;
    background: var(--secondary-color);
    color: var(--white);
    padding: 0.8rem 1.8rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: var(--transition);
    font-weight: 600;
    text-align: center;
}

.btn:hover {
    background: var(--accent-color);
    transform: translateY(-3px);
    box-shadow: var(--shadow);
}

.section-padding {
    padding: 5rem 0;
}

.text-center {
    text-align: center;
}

/* Header Styles */
header {
    background-color: var(--white);
    box-shadow: var(--shadow);
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    transition: var(--transition);
}

header.scrolled {
    background-color: rgba(255, 255, 255, 0.95);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
}

.logo h1 {
    color: var(--primary-color);
    font-size: 1.8rem;
}

.logo p {
    color: var(--text-light);
    font-size: 0.8rem;
    margin: 0;
}

nav ul {
    display: flex;
}

nav ul li {
    margin-left: 2rem;
}

nav ul li a {
    color: var(--dark-color);
    font-weight: 600;
    position: relative;
}

nav ul li a::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    background: var(--secondary-color);
    bottom: -5px;
    left: 0;
    transition: var(--transition);
}

nav ul li a:hover::after {
    width: 100%;
}

.mobile-menu {
    display: none;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Hero Section */
.hero {
    height: 100vh;
    background: linear-gradient(rgba(44, 62, 80, 0.8), rgba(44, 62, 80, 0.8)), url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center/cover;
    color: var(--white);
    display: flex;
    align-items: center;
    text-align: center;
    padding-top: 80px;
}

.hero-content h1 {
    font-size: 3.5rem;
    margin-bottom: 1.5rem;
}

.hero-content p {
    font-size: 1.2rem;
    max-width: 700px;
    margin: 0 auto 2rem;
}

/* Features Section */
.features {
    padding: 5rem 0;
    background-color: var(--light-color);
}

.features h2 {
    text-align: center;
    margin-bottom: 3rem;
    font-size: 2.5rem;
    color: var(--primary-color);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.feature-card {
    background: var(--white);
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.feature-card:hover {
    transform: translateY(-10px);
}

.feature-card i {
    font-size: 3rem;
    color: var(--secondary-color);
    margin-bottom: 1.5rem;
}

.feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

/* Section Styles */
.section-payroll, .section-attendance, 
.section-performance, .section-hire {
    padding: 5rem 0;
}

.section-payroll {
    background-color: var(--white);
}

.section-attendance {
    background-color: var(--light-color);
}

.section-performance {
    background-color: var(--white);
}

.section-hire {
    background-color: var(--light-color);
}

.section-content {
    display: flex;
    align-items: center;
    gap: 3rem;
}

.section-text, .section-image {
    flex: 1;
}

.section-image img {
    width: 100%;
    border-radius: 10px;
    box-shadow: var(--shadow);
}

.section-text h2 {
    font-size: 2.2rem;
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}

.section-text ul {
    margin: 1.5rem 0;
}

.section-text ul li {
    margin-bottom: 0.8rem;
    position: relative;
    padding-left: 1.5rem;
}

.section-text ul li::before {
    content: '✓';
    color: var(--secondary-color);
    position: absolute;
    left: 0;
    font-weight: bold;
}

/* Testimonials */
.testimonials {
    padding: 5rem 0;
    background-color: var(--white);
}

.testimonials h2 {
    text-align: center;
    margin-bottom: 3rem;
    font-size: 2.5rem;
    color: var(--primary-color);
}

.testimonial-slider {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
    overflow: hidden;
}

.testimonial {
    display: none;
    text-align: center;
    padding: 2rem;
    background: var(--light-color);
    border-radius: 10px;
    box-shadow: var(--shadow);
}

.testimonial.active {
    display: block;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.testimonial p {
    font-size: 1.2rem;
    font-style: italic;
    margin-bottom: 1.5rem;
}

.author strong {
    display: block;
    color: var(--primary-color);
}

.author span {
    color: var(--text-light);
    font-size: 0.9rem;
}

.slider-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 2rem;
}

.slider-prev, .slider-next {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--secondary-color);
    cursor: pointer;
    margin: 0 1rem;
    transition: var(--transition);
}

.slider-prev:hover, .slider-next:hover {
    color: var(--accent-color);
}

.slider-dots {
    display: flex;
}

.dot {
    width: 12px;
    height: 12px;
    background: #ccc;
    border-radius: 50%;
    margin: 0 5px;
    cursor: pointer;
    transition: var(--transition);
}

.dot.active {
    background: var(--secondary-color);
}

/* Contact Section */
.contact {
    padding: 5rem 0;
    background-color: var(--primary-color);
    color: var(--white);
}

.contact h2 {
    text-align: center;
    margin-bottom: 3rem;
    font-size: 2.5rem;
}

.contact-content {
    display: flex;
    gap: 3rem;
}

.contact-info, .contact-form {
    flex: 1;
}

.contact-info h3, .contact-form h3 {
    font-size: 1.8rem;
    margin-bottom: 1.5rem;
}

.contact-info p {
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
}

.contact-info i {
    margin-right: 1rem;
    color: var(--secondary-color);
}

.social-links {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.social-links a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: var(--white);
    transition: var(--transition);
}

.social-links a:hover {
    background: var(--secondary-color);
    transform: translateY(-3px);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.8rem;
    border: none;
    border-radius: 5px;
    background: rgba(255, 255, 255, 0.9);
    font-family: inherit;
}

.form-group textarea {
    resize: vertical;
    min-height: 150px;
}

/* Footer */
footer {
    background-color: var(--dark-color);
    color: var(--white);
    padding: 3rem 0 0;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.footer-section h3 {
    font-size: 1.3rem;
    margin-bottom: 1.5rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.footer-section h3::after {
    content: '';
    position: absolute;
    width: 50px;
    height: 2px;
    background: var(--secondary-color);
    bottom: 0;
    left: 0;
}

.footer-section ul li {
    margin-bottom: 0.8rem;
}

.footer-section ul li a {
    color: var(--text-light);
    transition: var(--transition);
}

.footer-section ul li a:hover {
    color: var(--secondary-color);
    padding-left: 5px;
}

#newsletterForm {
    display: flex;
    margin-top: 1rem;
}

#newsletterForm input {
    flex: 1;
    padding: 0.8rem;
    border: none;
    border-radius: 5px 0 0 5px;
}

#newsletterForm button {
    background: var(--secondary-color);
    color: var(--white);
    border: none;
    border-radius: 0 5px 5px 0;
    padding: 0 1rem;
    cursor: pointer;
    transition: var(--transition);
}

#newsletterForm button:hover {
    background: var(--accent-color);
}

.footer-bottom {
    text-align: center;
    padding: 1.5rem 0;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    overflow: auto;
}

.modal-content {
    background-color: var(--white);
    margin: 10% auto;
    padding: 2rem;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    position: relative;
    animation: modalOpen 0.5s;
}

@keyframes modalOpen {
    from { opacity: 0; transform: translateY(-50px); }
    to { opacity: 1; transform: translateY(0); }
}

.close-modal {
    position: absolute;
    top: 1rem;
    right: 1.5rem;
    font-size: 1.5rem;
    color: var(--text-light);
    cursor: pointer;
    transition: var(--transition);
}

.close-modal:hover {
    color: var(--accent-color);
}

.modal h2 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    text-align: center;
}

.calculator {
    background: var(--light-color);
    padding: 2rem;
    border-radius: 8px;
}

.calculator .form-group {
    margin-bottom: 1.2rem;
}

.calculator label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.calculator input {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.results {
    margin-top: 2rem;
    padding: 1.5rem;
    background: var(--white);
    border-radius: 8px;
    box-shadow: var(--shadow);
}

.results h3 {
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.results p {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

/* Responsive Styles */
@media (max-width: 992px) {
    .section-content {
        flex-direction: column;
    }
    
    .section-attendance .section-content {
        flex-direction: column-reverse;
    }
    
    .section-text, .section-image {
        width: 100%;
    }
    
    .contact-content {
        flex-direction: column;
    }
}

@media (max-width: 768px) {
    nav ul {
        display: none;
        position: absolute;
        top: 80px;
        left: 0;
        width: 100%;
        background: var(--white);
        flex-direction: column;
        align-items: center;
        padding: 1rem 0;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
    }
    
    nav ul.show {
        display: flex;
    }
    
    nav ul li {
        margin: 0.5rem 0;
    }
    
    .mobile-menu {
        display: block;
    }
    
    .hero-content h1 {
        font-size: 2.5rem;
    }
    
    .hero-content p {
        font-size: 1rem;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .testimonial {
        padding: 1.5rem;
    }
}

@media (max-width: 576px) {
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .btn {
        padding: 0.6rem 1.2rem;
    }
    
    .section-padding {
        padding: 3rem 0;
    }
    
    .modal-content {
        margin: 20% auto;
        padding: 1.5rem;
    }
}
</style>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1>Talent Bridge</h1>
                <p>Human Resource Management</p>
            </div>
            <nav>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#payroll">Payroll</a></li>
                    <li><a href="#attendance">Time & Attendance</a></li>
                    <li><a href="#performance">Performance</a></li>
                    <li><a href="#hire">Hire</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Streamline Your HR Processes</h1>
                <p>Comprehensive human resource management solution for modern businesses</p>
                <a href="#contact" class="btn">Get Started</a>
            </div>
        </div>
    </section>

    <section id="features" class="features">
        <div class="container">
            <h2>Key Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>Payroll Management</h3>
                    <p>Automated payroll processing with tax calculations and direct deposit.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-clock"></i>
                    <h3>Time & Attendance</h3>
                    <p>Track employee hours, leaves, and overtime with our intuitive system.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Performance Management</h3>
                    <p>Set goals, conduct reviews, and track employee performance metrics.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user-plus"></i>
                    <h3>Recruitment & Hiring</h3>
                    <p>Streamline your hiring process from posting jobs to onboarding.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="payroll" class="section-payroll">
        <div class="container">
            <div class="section-content">
                <div class="section-text">
                    <h2>Payroll Management</h2>
                    <p>Our payroll system automates the entire payroll process, ensuring accuracy and compliance with tax regulations. Features include:</p>
                    <ul>
                        <li>Automated tax calculations and filings</li>
                        <li>Direct deposit and check printing</li>
                        <li>Customizable pay schedules</li>
                        <li>Comprehensive reporting</li>
                        <li>Employee self-service portal</li>
                    </ul>
                    <button class="btn payroll-calculator-btn">Try Payroll Calculator</button>
                </div>
                <div class="section-image">
                    <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Payroll Management">
                </div>
            </div>
        </div>
    </section>

    <section id="attendance" class="section-attendance">
        <div class="container">
            <div class="section-content">
                <div class="section-image">
                    <img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Time and Attendance">
                </div>
                <div class="section-text">
                    <h2>Time & Attendance</h2>
                    <p>Efficiently track employee hours, manage schedules, and reduce time theft with our comprehensive time and attendance solution.</p>
                    <ul>
                        <li>Biometric and mobile clock-in/out</li>
                        <li>Real-time attendance tracking</li>
                        <li>Leave and vacation management</li>
                        <li>Overtime calculations</li>
                        <li>Integration with payroll</li>
                    </ul>
                    <button class="btn attendance-demo-btn">View Attendance Dashboard</button>
                </div>
            </div>
        </div>
    </section>

    <section id="performance" class="section-performance">
        <div class="container">
            <div class="section-content">
                <div class="section-text">
                    <h2>Performance Management</h2>
                    <p>Align employee performance with organizational goals through continuous feedback and development.</p>
                    <ul>
                        <li>360-degree feedback system</li>
                        <li>Goal setting and tracking</li>
                        <li>Performance review cycles</li>
                        <li>Competency assessments</li>
                        <li>Employee development plans</li>
                    </ul>
                    <button class="btn performance-review-btn">Start Performance Review</button>
                </div>
                <div class="section-image">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Performance Management">
                </div>
            </div>
        </div>
    </section>

    <section id="hire" class="section-hire">
        <div class="container">
            <div class="section-content">
                <div class="section-image">
                    <img src="https://images.unsplash.com/photo-1573497491208-6da53e885f7d?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Hiring Process">
                </div>
                <div class="section-text">
                    <h2>Recruitment & Hiring</h2>
                    <p>Attract top talent and streamline your hiring process from application to onboarding.</p>
                    <ul>
                        <li>Job posting to multiple boards</li>
                        <li>Applicant tracking system</li>
                        <li>Interview scheduling</li>
                        <li>Background checks</li>
                        <li>Digital onboarding</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="testimonials" class="testimonials">
        <div class="container">
            <h2>What Our Clients Say</h2>
            <div class="testimonial-slider">
                <?php
                // Fetch testimonials from database
                $sql = "SELECT * FROM testimonials WHERE status = 'approved' ORDER BY created_at DESC LIMIT 3";
                $result = $conn->query($sql);
                
                // Check if query was successful
                if ($result === false) {
                    // Display error message (remove this in production)
                    echo '<div class="testimonial active">';
                    echo '<p>Error loading testimonials: ' . htmlspecialchars($conn->error) . '</p>';
                    echo '</div>';
                    
                    // Alternatively, use default testimonials if query fails
                    /*
                    echo '<div class="testimonial active">
                        <p>"Talent Bridge has transformed how we manage our workforce. The payroll system alone has saved us countless hours each month."</p>
                        <div class="author">
                            <strong>Sarah Johnson</strong>
                            <span>CEO, TechSolutions Inc.</span>
                        </div>
                    </div>';
                    */
                } 
                elseif ($result->num_rows > 0) {
                    $active = true;
                    while($row = $result->fetch_assoc()) {
                        echo '<div class="testimonial' . ($active ? ' active' : '') . '">';
                        echo '<p>"' . htmlspecialchars($row['content']) . '"</p>';
                        echo '<div class="author">';
                        echo '<strong>' . htmlspecialchars($row['author_name']) . '</strong>';
                        echo '<span>' . htmlspecialchars($row['author_position']) . ', ' . htmlspecialchars($row['author_company']) . '</span>';
                        echo '</div>';
                        echo '</div>';
                        $active = false;
                    }
                } else {
                    // Default testimonials if none in database
                    echo '<div class="testimonial active">
                        <p>"Talent Bridge has transformed how we manage our workforce. The payroll system alone has saved us countless hours each month."</p>
                        <div class="author">
                            <strong>Sarah Johnson</strong>
                            <span>CEO, TechSolutions Inc.</span>
                        </div>
                    </div>';
                }
                ?>
            </div>
            <div class="slider-controls">
                <button class="slider-prev"><i class="fas fa-chevron-left"></i></button>
                <div class="slider-dots">
                    <?php
                    if (isset($result) && $result !== false && $result->num_rows > 0) {
                        $count = $result->num_rows;
                        for ($i = 0; $i < $count; $i++) {
                            echo '<span class="dot' . ($i === 0 ? ' active' : '') . '"></span>';
                        }
                    } else {
                        echo '<span class="dot active"></span>';
                    }
                    ?>
                </div>
                <button class="slider-next"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>

    <section id="contact" class="contact">
        <div class="container">
            <h2>Contact Us</h2>
            <div class="contact-content">
                <div class="contact-info">
                    <h3>Get in Touch</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 HR Street, Business District, NY 10001</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@hrpro.com</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="contact-form">
                    <h3>Send Us a Message</h3>
                    <form id="contactForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="form-group">
                            <input type="text" id="name" name="name" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" id="email" name="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <input type="text" id="subject" name="subject" placeholder="Subject">
                        </div>
                        <div class="form-group">
                            <textarea id="message" name="message" rows="5" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" name="contact_submit" class="btn">Send Message</button>
                    </form>
                    <?php
                    if (isset($_SESSION['contact_message'])) {
                        echo '<div class="alert ' . $_SESSION['contact_message']['type'] . '">' . $_SESSION['contact_message']['text'] . '</div>';
                        unset($_SESSION['contact_message']);
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>

    <footer id="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>HR Pro</h3>
                    <p>Comprehensive human resource management solutions for businesses of all sizes.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#payroll">Payroll</a></li>
                        <li><a href="#attendance">Time & Attendance</a></li>
                        <li><a href="#performance">Performance</a></li>
                        <li><a href="#hire">Hire</a></li>
                        <li><a href="login.php">Login</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                        <li><a href="gdpr.php">GDPR Compliance</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Subscribe</h3>
                    <p>Stay updated with our newsletter</p>
                    <form id="newsletterForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="email" name="email" placeholder="Your Email" required>
                        <button type="submit" name="newsletter_submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                    <?php
                    if (isset($_SESSION['newsletter_message'])) {
                        echo '<div class="alert ' . $_SESSION['newsletter_message']['type'] . '">' . $_SESSION['newsletter_message']['text'] . '</div>';
                        unset($_SESSION['newsletter_message']);
                    }
                    ?>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> HR Pro. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Payroll Calculator Modal -->
    <div id="payrollModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Payroll Calculator</h2>
            <div class="calculator">
                <form id="payrollCalculatorForm">
                    <div class="form-group">
                        <label for="hoursWorked">Hours Worked:</label>
                        <input type="number" id="hoursWorked" min="0" step="0.5" value="40">
                    </div>
                    <div class="form-group">
                        <label for="hourlyRate">Hourly Rate ($):</label>
                        <input type="number" id="hourlyRate" min="0" step="0.01" value="25.00">
                    </div>
                    <div class="form-group">
                        <label for="taxRate">Tax Rate (%):</label>
                        <input type="number" id="taxRate" min="0" max="100" value="20">
                    </div>
                    <button type="button" id="calculatePayroll" class="btn">Calculate</button>
                </form>
                <div class="results">
                    <h3>Results</h3>
                    <p>Gross Pay: $<span id="grossPay">0.00</span></p>
                    <p>Tax Amount: $<span id="taxAmount">0.00</span></p>
                    <p>Net Pay: $<span id="netPay">0.00</span></p>
                </div>
            </div>
        </div>
    </div>

    <script src="../users/js/script.js"></script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>

    <section id="testimonials" class="testimonials">
        <div class="container">
            <h2>What Our Clients Say</h2>
            <div class="testimonial-slider">
                <?php
                // Fetch testimonials from database
                $sql = "SELECT * FROM testimonials WHERE status = 'approved' ORDER BY created_at DESC LIMIT 3";
                $result = $conn->query($sql);
                
                // Check if query was successful
                if ($result === false) {
                    // Display error message (remove this in production)
                    echo '<div class="testimonial active">';
                    echo '<p>Error loading testimonials: ' . htmlspecialchars($conn->error) . '</p>';
                    echo '</div>';
                    
                    // Alternatively, use default testimonials if query fails
                    /*
                    echo '<div class="testimonial active">
                        <p>"Talent Bridge has transformed how we manage our workforce. The payroll system alone has saved us countless hours each month."</p>
                        <div class="author">
                            <strong>Sarah Johnson</strong>
                            <span>CEO, TechSolutions Inc.</span>
                        </div>
                    </div>';
                    */
                } 
                elseif ($result->num_rows > 0) {
                    $active = true;
                    while($row = $result->fetch_assoc()) {
                        echo '<div class="testimonial' . ($active ? ' active' : '') . '">';
                        echo '<p>"' . htmlspecialchars($row['content']) . '"</p>';
                        echo '<div class="author">';
                        echo '<strong>' . htmlspecialchars($row['author_name']) . '</strong>';
                        echo '<span>' . htmlspecialchars($row['author_position']) . ', ' . htmlspecialchars($row['author_company']) . '</span>';
                        echo '</div>';
                        echo '</div>';
                        $active = false;
                    }
                } else {
                    // Default testimonials if none in database
                    echo '<div class="testimonial active">
                        <p>"Talent Bridge has transformed how we manage our workforce. The payroll system alone has saved us countless hours each month."</p>
                        <div class="author">
                            <strong>Sarah Johnson</strong>
                            <span>CEO, TechSolutions Inc.</span>
                        </div>
                    </div>';
                }
                ?>
            </div>
            <div class="slider-controls">
                <button class="slider-prev"><i class="fas fa-chevron-left"></i></button>
                <div class="slider-dots">
                    <?php
                    if (isset($result) && $result !== false && $result->num_rows > 0) {
                        $count = $result->num_rows;
                        for ($i = 0; $i < $count; $i++) {
                            echo '<span class="dot' . ($i === 0 ? ' active' : '') . '"></span>';
                        }
                    } else {
                        echo '<span class="dot active"></span>';
                    }
                    ?>
                </div>
                <button class="slider-next"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>

    <section id="contact" class="contact">
        <div class="container">
            <h2>Contact Us</h2>
            <div class="contact-content">
                <div class="contact-info">
                    <h3>Get in Touch</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 HR Street, Business District, NY 10001</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@hrpro.com</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="contact-form">
                    <h3>Send Us a Message</h3>
                    <form id="contactForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="form-group">
                            <input type="text" id="name" name="name" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" id="email" name="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <input type="text" id="subject" name="subject" placeholder="Subject">
                        </div>
                        <div class="form-group">
                            <textarea id="message" name="message" rows="5" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" name="contact_submit" class="btn">Send Message</button>
                    </form>
                    <?php
                    if (isset($_SESSION['contact_message'])) {
                        echo '<div class="alert ' . $_SESSION['contact_message']['type'] . '">' . $_SESSION['contact_message']['text'] . '</div>';
                        unset($_SESSION['contact_message']);
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>

    <footer id="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>HR Pro</h3>
                    <p>Comprehensive human resource management solutions for businesses of all sizes.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#payroll">Payroll</a></li>
                        <li><a href="#attendance">Time & Attendance</a></li>
                        <li><a href="#performance">Performance</a></li>
                        <li><a href="#hire">Hire</a></li>
                        <li><a href="login.php">Login</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                        <li><a href="gdpr.php">GDPR Compliance</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Subscribe</h3>
                    <p>Stay updated with our newsletter</p>
                    <form id="newsletterForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="email" name="email" placeholder="Your Email" required>
                        <button type="submit" name="newsletter_submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                    <?php
                    if (isset($_SESSION['newsletter_message'])) {
                        echo '<div class="alert ' . $_SESSION['newsletter_message']['type'] . '">' . $_SESSION['newsletter_message']['text'] . '</div>';
                        unset($_SESSION['newsletter_message']);
                    }
                    ?>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> HR Pro. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Payroll Calculator Modal -->
    <div id="payrollModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Payroll Calculator</h2>
            <div class="calculator">
                <form id="payrollCalculatorForm">
                    <div class="form-group">
                        <label for="hoursWorked">Hours Worked:</label>
                        <input type="number" id="hoursWorked" min="0" step="0.5" value="40">
                    </div>
                    <div class="form-group">
                        <label for="hourlyRate">Hourly Rate ($):</label>
                        <input type="number" id="hourlyRate" min="0" step="0.01" value="25.00">
                    </div>
                    <div class="form-group">
                        <label for="taxRate">Tax Rate (%):</label>
                        <input type="number" id="taxRate" min="0" max="100" value="20">
                    </div>
                    <button type="button" id="calculatePayroll" class="btn">Calculate</button>
                </form>
                <div class="results">
                    <h3>Results</h3>
                    <p>Gross Pay: $<span id="grossPay">0.00</span></p>
                    <p>Tax Amount: $<span id="taxAmount">0.00</span></p>
                    <p>Net Pay: $<span id="netPay">0.00</span></p>
                </div>
            </div>
        </div>
    </div>

    <script src="../users/js/script.js"></script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>
