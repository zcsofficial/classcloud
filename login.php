<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Database connection
    $conn = new mysqli('localhost', 'root', 'Adnan@66202', 'class_cloud');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Query based on role
    if ($role === 'institution') {
        $stmt = $conn->prepare("SELECT * FROM institutions WHERE email = ? AND password = ?");
    } elseif ($role === 'instructor') {
        $stmt = $conn->prepare("SELECT * FROM instructors WHERE email = ? AND password = ?");
    } elseif ($role === 'student') {
        $stmt = $conn->prepare("SELECT * FROM students WHERE email = ? AND password = ?");
    }

    $stmt->bind_param('ss', $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if it's the first time login for instructor or student
        if (($role === 'instructor' || $role === 'student') && $user['first_time_login'] == 1) {
            // Set session variables
            $_SESSION['role'] = $role;
            $_SESSION['email'] = $email;

            // Redirect to profile.php for first-time login
            echo "<script>alert('First time login! Please complete your profile.');</script>";
            echo "<script>window.location.href = 'profile.php';</script>";
            exit;
        } else {
            // Set session variables
            $_SESSION['role'] = $role;
            $_SESSION['email'] = $email;

            // Redirect based on role
            if ($role === 'institution') {
                echo "<script>alert('Login successful! Redirecting to admin panel...');</script>";
                echo "<script>window.location.href = 'admin.php';</script>";
            } else {
                echo "<script>alert('Login successful! Redirecting to dashboard...');</script>";
                echo "<script>window.location.href = 'dashboard.php';</script>";
            }
            exit;
        }
    } else {
        echo "<script>alert('Invalid credentials or role. Please try again.');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Cloud - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h3 class="mb-0">Login to Class Cloud</h3>
            </div>
            <div class="card-body">
                <!-- Display error message -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="role" class="form-label">
                            <i class="fas fa-user-tag"></i> Login as
                        </label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="institution" <?php echo (isset($role) && $role === 'institution') ? 'selected' : ''; ?>>Institution</option>
                            <option value="instructor" <?php echo (isset($role) && $role === 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                            <option value="student" <?php echo (isset($role) && $role === 'student') ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Don't have an account? <a href="register.php" class="text-primary">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
