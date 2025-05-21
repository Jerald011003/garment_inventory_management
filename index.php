<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include database connection
require_once 'includes/config/database.php';

// Basic authentication with database
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && $password === $user['password']) { // In production, use password_verify() with hashed passwords
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Log login activity
            $stmt = $conn->prepare("INSERT INTO activity_log (user_id, description, action) VALUES (?, ?, ?)");
            $stmt->execute([
                $user['id'],
                "User logged in successfully",
                "login"
            ]);
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: pages/admin/dashboard.php");
                    break;
                case 'sales':
                    header("Location: pages/sales/dashboard.php");
                    break;
                case 'staff':
                    header("Location: pages/staff/processorders.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            $error_message = "Invalid username or password!";
            
            // Log failed login attempt
            if ($user) {
                $stmt = $conn->prepare("INSERT INTO activity_log (user_id, description, action) VALUES (?, ?, ?)");
                $stmt->execute([
                    $user['id'],
                    "Failed login attempt",
                    "login_failed"
                ]);
            }
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triple S Garments - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            min-height: 100vh;
            background: #0F172A;
            color: #e2e8f0;
            line-height: 1.6;
            overflow: hidden;
        }

        .background {
            position: fixed;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .background::before {
            content: '';
            position: absolute;
            top: 0;
            left: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 50%);
            transform: rotate(-45deg);
        }

        .background::after {
            content: '';
            position: absolute;
            bottom: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 50%);
            transform: rotate(45deg);
        }

        .login-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            padding: 20px;
        }

        .login-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 2.5rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -50%;
            width: 200%;
            height: 100%;
            background: linear-gradient(
                to right,
                transparent,
                rgba(255, 255, 255, 0.05),
                transparent
            );
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }

        .login-header p {
            color: #94a3b8;
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #94a3b8;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.5);
            color: #e2e8f0;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
            margin-top: 15px; /* Half of the label's height + margin-bottom */
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        @media (max-width: 640px) {
            .login-card {
                padding: 2rem;
            }

            .login-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Triple S Garments</h1>
                <p>Sign in to your account</p>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html> 