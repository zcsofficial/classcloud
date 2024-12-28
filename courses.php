<?php
session_start();
include 'db.php'; // Database connection

// Check if the user is logged in and has admin or instructor privileges
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'instructor'])) {
    header("Location: login.php");
    exit();
}

// Initialize message variables
$message = '';
$messageType = '';

// Handle adding a new course
if (isset($_POST['add_course'])) {
    $courseName = $_POST['course_name'];
    $year = $_POST['year'];
    $semester = $_POST['semester'];
    $subject = $_POST['subject'];
    $unit = $_POST['unit'];
    $topic = $_POST['topic'];
    $notes = '';

    // Handle file upload
    if ($_FILES['notes_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['notes_file']['tmp_name'];
        $fileName = $_FILES['notes_file']['name'];
        $fileSize = $_FILES['notes_file']['size'];
        $fileType = $_FILES['notes_file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        // Validate file type
        $allowedExtensions = ['pdf', 'ppt', 'pptx'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = 'uploads/';
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $notes = $destPath; // Store the file path in the database
            } else {
                $message = "There was an error uploading the file.";
                $messageType = 'danger';
            }
        } else {
            $message = "Only PDF, PPT, and PPTX files are allowed.";
            $messageType = 'danger';
        }
    } elseif (!empty($_POST['notes_link'])) {
        // If a link is provided, use it as the notes
        $notes = $_POST['notes_link'];
    }

    if ($notes) {
        // Insert course data into the database
        $stmt = $conn->prepare("INSERT INTO courses (course_name, year, semester, subject, unit, topic, notes, college_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $courseName, $year, $semester, $subject, $unit, $topic, $notes, $_SESSION['college_code']);
        if ($stmt->execute()) {
            $message = "Course added successfully!";
            $messageType = 'success';
        } else {
            $message = "Error adding course: " . $stmt->error;
            $messageType = 'danger';
        }
    }
}

// Handle course deletion
if (isset($_GET['action']) && isset($_GET['course_id']) && $_GET['action'] === 'delete') {
    $courseId = $_GET['course_id'];
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    if ($stmt->execute()) {
        $message = "Course deleted successfully!";
        $messageType = 'warning';
    } else {
        $message = "Error deleting course: " . $stmt->error;
        $messageType = 'danger';
    }
}

// Fetch all courses
$stmt = $conn->prepare("SELECT * FROM courses WHERE college_code = ?");
$stmt->bind_param("s", $_SESSION['college_code']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin/Instructor Dashboard | Class Cloud</title>
    <!-- External Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.7/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            font-family: 'Roboto', sans-serif;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card-container {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
        }
        .card {
            width: 250px;
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            border-radius: 10px;
        }
        .card i {
            font-size: 50px;
        }
        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }
        .alert {
            font-weight: 600;
        }
        .btn-primary {
            background: #6e8efb;
            border: none;
        }
        .btn-primary:hover {
            background: #5a76d6;
        }
        .btn-danger {
            background: #f44336;
            border: none;
        }
        .btn-danger:hover {
            background: #e53935;
        }
        .btn-warning {
            background: #ff9800;
            border: none;
        }
        .btn-warning:hover {
            background: #fb8c00;
        }
        .step-container {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }
        .step-container.active {
            display: block;
        }
        .next-btn {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Class Cloud - Admin/Instructor Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin.php">Dashboard</a>
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

    <!-- Message Display -->
    <?php if ($message): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Course Add/Edit Form -->
    <div class="container mt-5">
        <h2 class="text-center">Add New Course</h2>
        <div class="step-container active" id="step1">
            <h4>Course Name</h4>
            <input type="text" class="form-control" name="course_name" id="course_name" required placeholder="Enter course name">
            <button type="button" class="btn btn-primary next-btn" onclick="nextStep(2)">Next</button>
        </div>

        <div class="step-container" id="step2">
            <h4>Year</h4>
            <input type="text" class="form-control" name="year" id="year" required placeholder="Enter year">
            <button type="button" class="btn btn-primary next-btn" onclick="nextStep(3)">Next</button>
        </div>

        <div class="step-container" id="step3">
            <h4>Semester</h4>
            <input type="text" class="form-control" name="semester" id="semester" required placeholder="Enter semester">
            <button type="button" class="btn btn-primary next-btn" onclick="nextStep(4)">Next</button>
        </div>

        <div class="step-container" id="step4">
            <h4>Subject</h4>
            <input type="text" class="form-control" name="subject" id="subject" required placeholder="Enter subject">
            <button type="button" class="btn btn-primary next-btn" onclick="nextStep(5)">Next</button>
        </div>

        <div class="step-container" id="step5">
            <h4>Unit</h4>
            <input type="text" class="form-control" name="unit" id="unit" required placeholder="Enter unit">
            <button type="button" class="btn btn-primary next-btn" onclick="nextStep(6)">Next</button>
        </div>

        <div class="step-container" id="step6">
            <h4>Topic</h4>
            <input type="text" class="form-control" name="topic" id="topic" required placeholder="Enter topic">
            <button type="button" class="btn btn-primary next-btn" onclick="nextStep(7)">Next</button>
        </div>

        <div class="step-container" id="step7">
            <h4>Notes (Upload PDF/PPT or Paste Link)</h4>
            <input type="file" class="form-control" name="notes_file" id="notes_file" accept=".pdf,.ppt,.pptx">
            <p>OR</p>
            <input type="text" class="form-control" name="notes_link" id="notes_link" placeholder="Paste the link to the notes (PDF/PPT)">
            <button type="button" class="btn btn-success next-btn" onclick="submitCourse()">Submit</button>
        </div>
    </div>

    <!-- Courses List -->
    <div class="container mt-5 table-container">
        <h2 class="text-center">Manage Courses</h2>
        <table id="coursesTable" class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Year</th>
                    <th>Semester</th>
                    <th>Subject</th>
                    <th>Unit</th>
                    <th>Topic</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($course = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($course['year']); ?></td>
                        <td><?php echo htmlspecialchars($course['semester']); ?></td>
                        <td><?php echo htmlspecialchars($course['subject']); ?></td>
                        <td><?php echo htmlspecialchars($course['unit']); ?></td>
                        <td><?php echo htmlspecialchars($course['topic']); ?></td>
                        <td>
                            <?php
                                if (filter_var($course['notes'], FILTER_VALIDATE_URL)) {
                                    echo '<a href="' . $course['notes'] . '" target="_blank">View Notes</a>';
                                } else {
                                    echo '<a href="' . $course['notes'] . '" target="_blank">Download Notes</a>';
                                }
                            ?>
                        </td>
                        <td>
                            <a href="courses.php?action=delete&course_id=<?php echo $course['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS and External Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.7/dist/sweetalert2.all.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#coursesTable').DataTable();
        });

        function nextStep(step) {
            // Hide all steps
            $('.step-container').removeClass('active');
            // Show the next step
            $('#step' + step).addClass('active');
        }

        function submitCourse() {
            // Collect form data
            var courseData = new FormData();
            courseData.append('course_name', $('#course_name').val());
            courseData.append('year', $('#year').val());
            courseData.append('semester', $('#semester').val());
            courseData.append('subject', $('#subject').val());
            courseData.append('unit', $('#unit').val());
            courseData.append('topic', $('#topic').val());
            courseData.append('notes_file', $('#notes_file')[0].files[0]);
            courseData.append('notes_link', $('#notes_link').val());
            courseData.append('add_course', true);

            $.ajax({
                url: 'courses.php',
                type: 'POST',
                data: courseData,
                contentType: false,
                processData: false,
                success: function(response) {
                    // Display success message
                    Swal.fire('Success', 'Course added successfully!', 'success');
                },
                error: function() {
                    Swal.fire('Error', 'There was an issue adding the course.', 'error');
                }
            });
        }
    </script>
</body>
</html>
