<?php
include 'db.php'; // Database connection

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING) ?? '';
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '');
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $password = trim($_POST['password'] ?? ''); // Password is hashed, no need to sanitize
    $collegeName = trim(filter_input(INPUT_POST, 'collegeName', FILTER_SANITIZE_STRING) ?? '');
    $collegeCode = trim(filter_input(INPUT_POST, 'collegeCode', FILTER_SANITIZE_STRING) ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($role) || $role === 'none') {
        $message = "All required fields must be filled.";
        $messageType = "danger";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "danger";
    } else {
        // Check for existing email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $message = "This email is already registered.";
            $messageType = "danger";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            if ($role === 'college') {
                if (empty($collegeName) || empty($collegeCode)) {
                    $message = "College name and code are required for College Admin.";
                    $messageType = "danger";
                } else {
                    // Check if college code already exists
                    $stmt = $conn->prepare("SELECT code FROM colleges WHERE code = ?");
                    $stmt->bind_param("s", $collegeCode);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $message = "This college code is already in use.";
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
                        $actualRole = ($role === 'learner' || $role === 'instructor') ? $role : 'approval';
                        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, college_code) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssss", $name, $email, $hashedPassword, $actualRole, $collegeCode);
                        if ($stmt->execute()) {
                            $message = ucfirst($actualRole) . " registered successfully! " . ($actualRole === 'approval' ? "Waiting for admin approval." : "");
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
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | ClassCloud</title>
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
        <h1 class="text-3xl font-bold text-primary text-center mb-6">Register for ClassCloud</h1>

        <!-- Message Display -->
        <?php if (!empty($message)): ?>
            <div class="mb-4 p-4 rounded-lg text-white <?php echo $messageType === 'danger' ? 'bg-red-500' : 'bg-green-500'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                <div class="mt-1 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-primary">
                        <i class="ri-user-3-line text-xl"></i>
                    </span>
                    <select id="role" name="role" class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" required>
                        <option value="none" selected disabled>Select your role</option>
                        <option value="learner">Learner</option>
                        <option value="instructor">Instructor</option>
                        <option value="college">College Admin</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <div class="mt-1 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-primary">
                        <i class="ri-user-line text-xl"></i>
                    </span>
                    <input type="text" id="name" name="name" class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" placeholder="Enter your name" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <div class="mt-1 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-primary">
                        <i class="ri-mail-line text-xl"></i>
                    </span>
                    <input type="email" id="email" name="email" class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" placeholder="Enter your email" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <div class="mt-1 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-primary">
                        <i class="ri-lock-line text-xl"></i>
                    </span>
                    <input type="password" id="password" name="password" class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" placeholder="Enter your password" required>
                </div>
            </div>

            <div class="mb-4" id="collegeNameGroup">
                <label for="collegeName" class="block text-sm font-medium text-gray-700">College Name</label>
                <div class="mt-1 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-primary">
                        <i class="ri-building-line text-xl"></i>
                    </span>
                    <input type="text" id="collegeName" name="collegeName" class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary bg-gray-100 cursor-not-allowed" placeholder="Enter college name" disabled>
                </div>
            </div>

            <div class="mb-6">
                <label for="collegeCode" class="block text-sm font-medium text-gray-700">College Code</label>
                <div class="mt-1 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-primary">
                        <i class="ri-key-2-line text-xl"></i>
                    </span>
                    <input type="text" id="collegeCode" name="collegeCode" class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" placeholder="Enter college code" required>
                </div>
            </div>

            <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button hover:bg-primary/90 transition-all font-semibold">Register</button>
        </form>

        <p class="mt-4 text-center text-gray-600">
            Already have an account? <a href="login.php" class="text-primary hover:underline">Login here</a>.
        </p>
    </div>

    <script>
        const roleSelect = document.getElementById('role');
        const collegeNameInput = document.getElementById('collegeName');

        roleSelect.addEventListener('change', () => {
            collegeNameInput.disabled = roleSelect.value !== 'college';
            collegeNameInput.classList.toggle('bg-gray-100', collegeNameInput.disabled);
            collegeNameInput.classList.toggle('cursor-not-allowed', collegeNameInput.disabled);
        });
    </script>
</body>
</html>