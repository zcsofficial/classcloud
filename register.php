<?php
include 'db.php'; // Database connection

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $collegeName = trim($_POST['collegeName'] ?? '');
    $collegeCode = trim($_POST['collegeCode'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required.";
        $messageType = "danger";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "danger";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        if ($role === 'college') {
            if (empty($collegeName) || empty($collegeCode)) {
                $message = "College name and code are required for College Admin.";
                $messageType = "danger";
            } else {
                $stmt = $conn->prepare("INSERT INTO colleges (name, code) VALUES (?, ?)");
                $stmt->bind_param("ss", $collegeName, $collegeCode);

                if ($stmt->execute()) {
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, college_code) VALUES (?, ?, ?, 'admin', ?)");
                    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $collegeCode);
                    if ($stmt->execute()) {
                        $message = "College and Admin account registered successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error registering admin: " . $stmt->error;
                        $messageType = "danger";
                    }
                } else {
                    $message = "Error registering college: " . $stmt->error;
                    $messageType = "danger";
                }
            }
        } else {
            if (empty($collegeCode)) {
                $message = "College code is required.";
                $messageType = "danger";
            } else {
                $stmt = $conn->prepare("SELECT code FROM colleges WHERE code = ?");
                $stmt->bind_param("s", $collegeCode);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, college_code) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $collegeCode);
                    if ($stmt->execute()) {
                        $message = ucfirst($role) . " registered successfully! Waiting for admin approval.";
                        $messageType = "success";
                    } else {
                        $message = "Error registering user: " . $stmt->error;
                        $messageType = "danger";
                    }
                } else {
                    $message = "Invalid college code. Please check and try again.";
                    $messageType = "danger";
                }
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
    <title>Register | Class Cloud</title>
    <!-- External Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
        }
        .form-container h1 {
            font-weight: 700;
            color: #6e8efb;
            text-align: center;
            margin-bottom: 1rem;
        }
        .form-container .form-label {
            font-weight: 500;
        }
        .form-container .btn-primary {
            background: #6e8efb;
            border: none;
        }
        .form-container .btn-primary:hover {
            background: #5a76d6;
        }
        .form-container .form-control {
            border-radius: 10px;
        }
        .form-container .input-group-text {
            background: #6e8efb;
            color: white;
            border: none;
            border-radius: 10px 0 0 10px;
        }
        .form-container a {
            color: #6e8efb;
            text-decoration: none;
        }
        .form-container a:hover {
            text-decoration: underline;
        }
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1>Register</h1>

            <!-- Message Display -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="learner">Learner</option>
                        <option value="instructor">Instructor</option>
                        <option value="college">College Admin</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="mb-3">
                    <label for="collegeName" class="form-label">College Name</label>
                    <input type="text" class="form-control" id="collegeName" name="collegeName" disabled>
                </div>

                <div class="mb-3">
                    <label for="collegeCode" class="form-label">College Code</label>
                    <input type="text" class="form-control" id="collegeCode" name="collegeCode" >
                </div>

                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></p>
            </div>
        </div>
    </div>

    <script>
        const roleSelect = document.getElementById('role');
        const collegeNameInput = document.getElementById('collegeName');
        const collegeCodeInput = document.getElementById('collegeCode');

        roleSelect.addEventListener('change', () => {
            const role = roleSelect.value;
            if (role === 'college') {
                collegeNameInput.disabled = false;
                collegeCodeInput.disabled = false;
            } else {
                collegeNameInput.disabled = true;
                collegeCodeInput.disabled = false;
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
