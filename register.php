<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $name = $_POST['name'] ?? '';
    $institution_id = $_POST['institution_id'] ?? '';
    $instructor_id = $_POST['instructor_id'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $institution_name = $_POST['institution_name'] ?? '';

    // Database connection
    $conn = new mysqli('localhost', 'root', 'Adnan@66202', 'class_cloud');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Insert data based on role
    if ($role === 'institution') {
        $stmt = $conn->prepare("INSERT INTO institutions (institution_name, institution_id, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $institution_name, $institution_id, $email, $password);
        if ($stmt->execute()) {
            echo "<script>alert('Institution registration successful!');</script>";
        } else {
            echo "<script>alert('Institution registration failed. Please try again.');</script>";
        }
    } elseif ($role === 'instructor') {
        // First, check if the institution exists
        $stmt = $conn->prepare("SELECT institution_id FROM institutions WHERE institution_id = ?");
        $stmt->bind_param('s', $institution_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Institution exists, insert instructor
            $stmt = $conn->prepare("INSERT INTO instructors (name, instructor_id, institution_id, email, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $name, $instructor_id, $institution_id, $email, $password);
            if ($stmt->execute()) {
                echo "<script>alert('Instructor registration successful!');</script>";
            } else {
                echo "<script>alert('Instructor registration failed. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Institution ID does not exist. Please check the institution details.');</script>";
        }
    } elseif ($role === 'student') {
        $stmt = $conn->prepare("INSERT INTO students (name, instructor_id, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $instructor_id, $email, $password);
        if ($stmt->execute()) {
            echo "<script>alert('Student registration successful!');</script>";
        } else {
            echo "<script>alert('Student registration failed. Please try again.');</script>";
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
    <title>Class Cloud - Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h3 class="mb-0">Register on Class Cloud</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="role" class="form-label">
                            <i class="fas fa-user-tag"></i> Register as
                        </label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="institution">Institution</option>
                            <option value="instructor">Instructor</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div id="dynamic-fields"></div>
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Already have an account? <a href="login.php" class="text-primary">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#role').change(function () {
                const role = $(this).val();
                let fields = '';

                if (role === 'institution') {
                    fields = ` 
                        <div class="mb-3">
                            <label for="institution_name" class="form-label">
                                <i class="fas fa-building"></i> Institution Name
                            </label>
                            <input type="text" name="institution_name" id="institution_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="institution_id" class="form-label">
                                <i class="fas fa-id-badge"></i> Institution ID
                            </label>
                            <input type="text" name="institution_id" id="institution_id" class="form-control" required>
                        </div>
                    `;
                } else if (role === 'instructor') {
                    fields = `
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-chalkboard-teacher"></i> Instructor Name
                            </label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="instructor_id" class="form-label">
                                <i class="fas fa-id-card"></i> Instructor ID
                            </label>
                            <input type="text" name="instructor_id" id="instructor_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="institution_id" class="form-label">
                                <i class="fas fa-school"></i> Institution ID
                            </label>
                            <input type="text" name="institution_id" id="institution_id" class="form-control" required>
                        </div>
                    `;
                } else if (role === 'student') {
                    fields = `
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-user"></i> Student Name
                            </label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="instructor_id" class="form-label">
                                <i class="fas fa-user-graduate"></i> Instructor ID
                            </label>
                            <input type="text" name="instructor_id" id="instructor_id" class="form-control" required>
                        </div>
                    `;
                }

                $('#dynamic-fields').html(fields);
            });
        });
    </script>
</body>
</html>
