<?php
session_start();

// Check if the user is logged in and if it's the first time login
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', 'Adnan@66202', 'class_cloud');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch user details
$email = $_SESSION['email'];
$role = $_SESSION['role'];

if ($role === 'student') {
    $stmt = $conn->prepare("SELECT * FROM students WHERE email = ?");
} elseif ($role === 'instructor') {
    $stmt = $conn->prepare("SELECT * FROM instructors WHERE email = ?");
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user['first_time_login'] == 0) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course = $_POST['course'];
    $year = $_POST['year'];
    $semester = $_POST['semester'];

    if ($role === 'student') {
        $stmt = $conn->prepare("UPDATE students SET courses = ?, year = ?, semester = ?, first_time_login = 0 WHERE email = ?");
    } elseif ($role === 'instructor') {
        $stmt = $conn->prepare("UPDATE instructors SET courses = ?, year = ?, semester = ?, first_time_login = 0 WHERE email = ?");
    }

    $stmt->bind_param('siis', $course, $year, $semester, $email);
    $stmt->execute();

    header("Location: dashboard.php");
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Cloud - Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h3 class="mb-0">Complete Your Profile</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <!-- Step 1: Course Selection -->
                    <div class="step" id="step1">
                        <div class="mb-3">
                            <label for="course" class="form-label"><i class="fas fa-book"></i> Select Course</label>
                            <select name="course" id="course" class="form-select" required>
                                <option value="">Select Course</option>
                                <option value="CS">Computer Science (CS)</option>
                                <option value="BCA">Bachelor of Computer Applications (BCA)</option>
                                <option value="Multimedia">Multimedia</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary w-100 next-btn" data-next="step2">Next</button>
                    </div>

                    <!-- Step 2: Year Selection -->
                    <div class="step" id="step2" style="display:none;">
                        <div class="mb-3">
                            <label for="year" class="form-label"><i class="fas fa-calendar"></i> Select Year</label>
                            <select name="year" id="year" class="form-select" required>
                                <option value="">Select Year</option>
                                <option value="1">Year 1</option>
                                <option value="2">Year 2</option>
                                <option value="3">Year 3</option>
                                <option value="4">Year 4</option>
                                <option value="5">Year 5</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary w-100 next-btn" data-next="step3">Next</button>
                    </div>

                    <!-- Step 3: Semester Selection -->
                    <div class="step" id="step3" style="display:none;">
                        <div class="mb-3">
                            <label for="semester" class="form-label"><i class="fas fa-calendar-check"></i> Select Semester</label>
                            <select name="semester" id="semester" class="form-select" required>
                                <option value="">Select Semester</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                                <option value="7">Semester 7</option>
                                <option value="8">Semester 8</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // Handle Next button click
            $('.next-btn').click(function () {
                var nextStep = $(this).data('next');
                $(this).closest('.step').fadeOut(300, function () {
                    $('#' + nextStep).fadeIn(300);
                });
            });
        });
    </script>
</body>
</html>
