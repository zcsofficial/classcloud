<?php
session_start();
include 'db.php'; // Database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'learner') {
    header("Location: login.php");
    exit();
}

// Initialize message variables
$message = '';
$messageType = '';

// Fetch college code from session
$college_code = $_SESSION['college_code'];

// Initialize variables for dynamic content
$semesters = [];
$subjects = [];
$units = [];
$topics = [];
$notes = '';

// Prepare the base query for fetching courses
$filterQuery = "SELECT * FROM courses WHERE college_code = ?";
$filterParams = [$college_code];

// Fetch available years for filtering
$years = $conn->prepare("SELECT DISTINCT year FROM courses WHERE college_code = ?");
$years->bind_param("s", $college_code);
$years->execute();
$yearsResult = $years->get_result();

// Handle the dynamic filtering options
if (isset($_POST['apply_filters'])) {
    $year = $_POST['year'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $unit = $_POST['unit'] ?? '';
    $topic = $_POST['topic'] ?? '';

    if ($year) {
        $filterQuery .= " AND year = ?";
        $filterParams[] = $year;
    }
    if ($semester) {
        $filterQuery .= " AND semester = ?";
        $filterParams[] = $semester;
    }
    if ($subject) {
        $filterQuery .= " AND subject = ?";
        $filterParams[] = $subject;
    }
    if ($unit) {
        $filterQuery .= " AND unit = ?";
        $filterParams[] = $unit;
    }
    if ($topic) {
        $filterQuery .= " AND topic = ?";
        $filterParams[] = $topic;
    }
}

$stmt = $conn->prepare($filterQuery);
if ($filterParams) {
    $stmt->bind_param(str_repeat("s", count($filterParams)), ...$filterParams);
}
$stmt->execute();
$courses = $stmt->get_result();

// Handle filtering options dynamically
if (isset($_POST['year'])) {
    $year = $_POST['year'];
    $semesters = $conn->prepare("SELECT DISTINCT semester FROM courses WHERE college_code = ? AND year = ?");
    $semesters->bind_param("ss", $college_code, $year);
    $semesters->execute();
    $semestersResult = $semesters->get_result();
}

if (isset($_POST['semester'])) {
    $semester = $_POST['semester'];
    $subjects = $conn->prepare("SELECT DISTINCT subject FROM courses WHERE college_code = ? AND semester = ?");
    $subjects->bind_param("ss", $college_code, $semester);
    $subjects->execute();
    $subjectsResult = $subjects->get_result();
}

if (isset($_POST['subject'])) {
    $subject = $_POST['subject'];
    $units = $conn->prepare("SELECT DISTINCT unit FROM courses WHERE college_code = ? AND subject = ?");
    $units->bind_param("ss", $college_code, $subject);
    $units->execute();
    $unitsResult = $units->get_result();
}

if (isset($_POST['unit'])) {
    $unit = $_POST['unit'];
    $topics = $conn->prepare("SELECT DISTINCT topic FROM courses WHERE college_code = ? AND unit = ?");
    $topics->bind_param("ss", $college_code, $unit);
    $topics->execute();
    $topicsResult = $topics->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Dashboard | Class Cloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
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
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            justify-items: center;
        }
        .card {
            width: 100%;
            margin-bottom: 20px;
            transition: transform 0.3s ease-in-out;
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card-body {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .card-title {
            font-size: 1.2rem;
            font-weight: 500;
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
        .navbar {
            background-color: #333;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .navbar-nav .nav-link {
            color: #fff !important;
        }
        .navbar-nav .nav-link:hover {
            color: #f0a500 !important;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Class Cloud - Learner Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="learners_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Courses List (Learner) -->
    <div class="container mt-5">
        <h2 class="text-center text-white">Available Courses</h2>

        <!-- Filter Form -->
        <form method="POST" id="filter-form">
            <div class="row">
                <div class="col-md-3">
                    <select name="year" id="year" class="form-select" aria-label="Select Year">
                        <option value="">Select Year</option>
                        <?php while ($yearRow = $yearsResult->fetch_assoc()): ?>
                            <option value="<?php echo $yearRow['year']; ?>" <?php echo (isset($_POST['year']) && $_POST['year'] == $yearRow['year']) ? 'selected' : ''; ?>>
                                <?php echo $yearRow['year']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="semester" id="semester" class="form-select" aria-label="Select Semester">
                        <option value="">Select Semester</option>
                        <?php if (isset($semestersResult)): ?>
                            <?php while ($semesterRow = $semestersResult->fetch_assoc()): ?>
                                <option value="<?php echo $semesterRow['semester']; ?>" <?php echo (isset($_POST['semester']) && $_POST['semester'] == $semesterRow['semester']) ? 'selected' : ''; ?>>
                                    <?php echo $semesterRow['semester']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="subject" id="subject" class="form-select" aria-label="Select Subject">
                        <option value="">Select Subject</option>
                        <?php if (isset($subjectsResult)): ?>
                            <?php while ($subjectRow = $subjectsResult->fetch_assoc()): ?>
                                <option value="<?php echo $subjectRow['subject']; ?>" <?php echo (isset($_POST['subject']) && $_POST['subject'] == $subjectRow['subject']) ? 'selected' : ''; ?>>
                                    <?php echo $subjectRow['subject']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="unit" id="unit" class="form-select" aria-label="Select Unit">
                        <option value="">Select Unit</option>
                        <?php if (isset($unitsResult)): ?>
                            <?php while ($unitRow = $unitsResult->fetch_assoc()): ?>
                                <option value="<?php echo $unitRow['unit']; ?>" <?php echo (isset($_POST['unit']) && $_POST['unit'] == $unitRow['unit']) ? 'selected' : ''; ?>>
                                    <?php echo $unitRow['unit']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <select name="topic" id="topic" class="form-select" aria-label="Select Topic">
                        <option value="">Select Topic</option>
                        <?php if (isset($topicsResult)): ?>
                            <?php while ($topicRow = $topicsResult->fetch_assoc()): ?>
                                <option value="<?php echo $topicRow['topic']; ?>" <?php echo (isset($_POST['topic']) && $_POST['topic'] == $topicRow['topic']) ? 'selected' : ''; ?>>
                                    <?php echo $topicRow['topic']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-warning" name="apply_filters">Apply</button>
                </div>
            </div>
        </form>

        <!-- Courses List -->
        <div class="card-container mt-4">
            <?php if ($courses->num_rows > 0): ?>
                <?php while ($course = $courses->fetch_assoc()): ?>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $course['course_name']; ?></h5>
                            <p class="card-text">Year: <?php echo $course['year']; ?> | Semester: <?php echo $course['semester']; ?></p>
                            <a href="course_details.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">Select Course</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-white">No courses found for the selected filters.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
