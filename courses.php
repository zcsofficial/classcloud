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

// Fetch courses, subjects, topics, and notes for the institution
$courses = $conn->query("SELECT * FROM courses WHERE institution_id = '{$institution['institution_id']}'");

// Handle course actions (edit, delete)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];

    if ($action === 'delete_course') {
        $conn->query("DELETE FROM courses WHERE id = $id");
        header("Location: courses.php");
        exit;
    }
    if ($action === 'delete_subject') {
        $conn->query("DELETE FROM subjects WHERE id = $id");
        header("Location: courses.php");
        exit;
    }
    if ($action === 'delete_topic') {
        $conn->query("DELETE FROM topics WHERE id = $id");
        header("Location: courses.php");
        exit;
    }
    if ($action === 'delete_note') {
        $conn->query("DELETE FROM notes WHERE id = $id");
        header("Location: courses.php");
        exit;
    }
}

// Add course, subject, topic, or note
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        $course_name = $_POST['course_name'];
        $description = $_POST['description'];
        $instructor_id = $_POST['instructor_id'];
        $year = $_POST['year'];
        $semester = $_POST['semester'];
        $conn->query("INSERT INTO courses (institution_id, instructor_id, course_name, description, semester) 
                      VALUES ('{$institution['institution_id']}', '$instructor_id', '$course_name', '$description', '$semester')");
        header("Location: courses.php");
        exit;
    }

    if (isset($_POST['add_subject'])) {
        $course_id = $_POST['course_id'];
        $subject_name = $_POST['subject_name'];
        $description = $_POST['description'];
        $year = $_POST['year'];
        $semester = $_POST['semester'];
        $conn->query("INSERT INTO subjects (course_id, subject_name, description) 
                      VALUES ('$course_id', '$subject_name', '$description')");
        header("Location: courses.php");
        exit;
    }

    if (isset($_POST['add_topic'])) {
        $subject_id = $_POST['subject_id'];
        $topic_name = $_POST['topic_name'];
        $description = $_POST['description'];
        $year = $_POST['year'];
        $semester = $_POST['semester'];
        $conn->query("INSERT INTO topics (subject_id, topic_name, description) 
                      VALUES ('$subject_id', '$topic_name', '$description')");
        header("Location: courses.php");
        exit;
    }

    if (isset($_POST['add_note'])) {
        $topic_id = $_POST['topic_id'];
        $note_title = $_POST['note_title'];
        $note_content = $_POST['note_content'];
        $file_path = $_POST['file_path'];  // Optional file path
        $conn->query("INSERT INTO notes (topic_id, note_title, note_content, file_path) 
                      VALUES ('$topic_id', '$note_title', '$note_content', '$file_path')");
        header("Location: courses.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses Management - Class Cloud</title>
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
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Courses Section -->
    <div class="container mt-5">
        <h1 class="text-center">Manage Courses</h1>

        <!-- Add Course Form -->
        <h2>Add New Course</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="course_name" class="form-label">Course Name</label>
                <input type="text" class="form-control" id="course_name" name="course_name" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" required></textarea>
            </div>
            <div class="mb-3">
                <label for="instructor_id" class="form-label">Instructor ID</label>
                <input type="text" class="form-control" id="instructor_id" name="instructor_id" required>
            </div>
            <div class="mb-3">
                <label for="semester" class="form-label">Semester</label>
                <input type="number" class="form-control" id="semester" name="semester" min="1" max="2" required>
            </div>
            <div class="mb-3">
                <label for="year" class="form-label">Year</label>
                <input type="number" class="form-control" id="year" name="year" min="1" max="4" required>
            </div>
            <button type="submit" name="add_course" class="btn btn-success">Add Course</button>
        </form>

        <!-- List of Courses -->
        <h2 class="mt-5">Courses</h2>
        <div class="list-group">
            <?php while ($course = $courses->fetch_assoc()): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?php echo $course['course_name']; ?></span>
                    <div>
                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="?action=delete_course&id=<?php echo $course['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                    </div>
                </div>

                <!-- Subjects for the course -->
                <?php
                $subjects = $conn->query("SELECT * FROM subjects WHERE course_id = {$course['id']}");
                while ($subject = $subjects->fetch_assoc()):
                ?>
                    <div class="ms-4">
                        <span><?php echo $subject['subject_name']; ?></span>
                        <a href="?action=delete_subject&id=<?php echo $subject['id']; ?>" class="btn btn-danger btn-sm ms-2">Delete</a>
                    </div>

                    <!-- Topics for the subject -->
                    <?php
                    $topics = $conn->query("SELECT * FROM topics WHERE subject_id = {$subject['id']}");
                    while ($topic = $topics->fetch_assoc()):
                    ?>
                        <div class="ms-5">
                            <span><?php echo $topic['topic_name']; ?></span>
                            <a href="?action=delete_topic&id=<?php echo $topic['id']; ?>" class="btn btn-danger btn-sm ms-2">Delete</a>
                        </div>

                        <!-- Notes for the topic -->
                        <?php
                        $notes = $conn->query("SELECT * FROM notes WHERE topic_id = {$topic['id']}");
                        while ($note = $notes->fetch_assoc()):
                        ?>
                            <div class="ms-6">
                                <span><?php echo $note['note_title']; ?></span>
                                <a href="?action=delete_note&id=<?php echo $note['id']; ?>" class="btn btn-danger btn-sm ms-2">Delete</a>
                            </div>
                        <?php endwhile; ?>
                    <?php endwhile; ?>
                <?php endwhile; ?>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
