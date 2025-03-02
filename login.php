<?php
include 'db.php'; // Database connection

// Initialize message variables
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // No need to sanitize password here since it’s hashed

    // Prepare SQL query to check if the user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if the password matches
        if (password_verify($password, $user['password'])) {
            // Check the role
            if ($user['role'] === 'approval') {
                $message = "Your account is pending approval. Please contact the admin.";
                $messageType = 'warning';
            } else {
                // Successful login
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['college_code'] = $user['college_code'];

                // Redirect based on the role
                if ($user['role'] === 'admin') {
                    header("Location: admin.php");
                } elseif ($user['role'] === 'instructor') {
                    header("Location: dashboard.php");
                } elseif ($user['role'] === 'learner') {
                    header("Location: learners_dashboard.php");
                }
                exit();
            }
        } else {
            $message = "Incorrect password.";
            $messageType = 'danger';
        }
    } else {
        $message = "No account found with that email.";
        $messageType = 'danger';
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
    <title>Login | ClassCloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#FF7F50',
                        secondary: '#FFA07A'
                    },
                    borderRadius: {
                        'button': '8px'
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-[#FFFAF0] min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg max-w-md w-full">
        <h2 class="text-3xl font-bold text-primary text-center mb-6">Login to ClassCloud</h2>

        <!-- Message Display -->
        <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-lg text-white <?php echo $messageType === 'danger' ? 'bg-red-500' : 'bg-yellow-500'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="login.php" method="POST">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <div class="mt-1 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-primary">
                        <i class="ri-mail-line text-xl"></i>
                    </span>
                    <input type="email" id="email" name="email" class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" placeholder="Enter your email" required>
                </div>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <div class="mt-1 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-primary">
                        <i class="ri-lock-line text-xl"></i>
                    </span>
                    <input type="password" id="password" name="password" class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button hover:bg-primary/90 transition-all font-semibold">Login</button>
        </form>
        <p class="mt-4 text-center text-gray-600">
            Don’t have an account? <a href="register.php" class="text-primary hover:underline">Register here</a>.
        </p>
    </div>
</body>
</html>