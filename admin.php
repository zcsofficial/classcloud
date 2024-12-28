<?php
session_start();

// Check if the user is logged in and has the 'institution' role
if ($_SESSION['role'] !== 'institution') {
    header('Location: login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', 'Adnan@66202', 'class_cloud');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch institution data
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM institutions WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$institution = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch instructors linked to the institution
$instructors = $conn->query("SELECT * FROM instructors WHERE institution_id = '{$institution['institution_id']}'");

// Fetch students linked to the institution through their instructors
$students = $conn->query("SELECT * FROM students WHERE instructor_id IN (SELECT instructor_id FROM instructors WHERE institution_id = '{$institution['institution_id']}')");

// Fetch courses linked to the institution
$courses = $conn->query("SELECT * FROM courses WHERE institution_id = '{$institution['institution_id']}'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Class Cloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Roboto:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        h1, h2 {
            font-family: 'Poppins', sans-serif;
            color: #343A40;
        }
        .bg-primary {
            background-color: #007BFF !important;
        }
        .btn-success {
            background-color: #28A745;
        }
        .btn-warning {
            background-color: #FFC107;
        }
        .bg-light-gray {
            background-color: #F8F9FA;
        }
        .search-bar {
            margin-bottom: 20px;
        }
        .card-container {
            animation: fadeIn 1s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="bg-light-gray">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin.php">Class Cloud</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="courses.php">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin Dashboard -->
    <div class="container mt-5">
        <h1 class="text-center">Welcome, <?php echo $institution['institution_name']; ?>!</h1>

        <!-- Search Bar for Courses -->
        <div class="search-bar">
            <input type="text" class="form-control" id="courseSearch" placeholder="Search for courses...">
        </div>

        <!-- Courses Section -->
        <h2>Manage Courses</h2>
        <div class="list-group">
            <?php while ($course = $courses->fetch_assoc()): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?php echo $course['course_name']; ?></span>
                    <div>
                        <button class="btn btn-warning btn-sm" onclick="editCourse(<?php echo $course['id']; ?>)">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteCourse(<?php echo $course['id']; ?>)">Delete</button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Students Section -->
        <h2 class="mt-4">Students</h2>
        <div class="card-container">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Students</h5>
                    <p class="card-text"><?php echo $students->num_rows; ?> Students</p>
                </div>
            </div>
        </div>
        <div class="list-group">
            <?php while ($student = $students->fetch_assoc()): ?>
                <div class="list-group-item">
                    <?php echo $student['name']; ?> - <?php echo $student['email']; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Instructors Section -->
        <h2 class="mt-4">Instructors</h2>
        <div class="card-container">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Instructors</h5>
                    <p class="card-text"><?php echo $instructors->num_rows; ?> Instructors</p>
                </div>
            </div>
        </div>
        <div class="list-group">
            <?php while ($instructor = $instructors->fetch_assoc()): ?>
                <div class="list-group-item">
                    <?php echo $instructor['name']; ?> - <?php echo $instructor['email']; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Switch Semester -->
        <h2 class="mt-4">Switch Semester</h2>
        <form method="POST" action="switch_semester.php">
            <div class="mb-3">
                <label for="semester" class="form-label">Select Semester</label>
                <select class="form-select" id="semester" name="semester">
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                    <option value="3">Semester 3</option>
                    <option value="4">Semester 4</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Switch Semester</button>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search for courses
        document.getElementById('courseSearch').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const courseItems = document.querySelectorAll('.list-group-item');
            courseItems.forEach(function(item) {
                const courseName = item.querySelector('span').textContent.toLowerCase();
                if (courseName.includes(query)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        function editCourse(courseId) {
            // Redirect to course edit page
            window.location.href = 'edit_course.php?id=' + courseId;
        }

        function deleteCourse(courseId) {
            // Confirm deletion
            if (confirm('Are you sure you want to delete this course?')) {
                window.location.href = 'delete_course.php?id=' + courseId;
            }
        }
    </script>
</body>
</html>
