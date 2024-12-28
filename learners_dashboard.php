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

// Fetch available years, semesters, subjects, units, and topics for filtering
$years = $conn->query("SELECT DISTINCT year FROM courses WHERE college_code = '$college_code'");
$semesters = $conn->query("SELECT DISTINCT semester FROM courses WHERE college_code = '$college_code'");
$subjects = $conn->query("SELECT DISTINCT subject FROM courses WHERE college_code = '$college_code'");
$units = $conn->query("SELECT DISTINCT unit FROM courses WHERE college_code = '$college_code'");
$topics = $conn->query("SELECT DISTINCT topic FROM courses WHERE college_code = '$college_code'");

// Fetch courses based on selected filters
$filterQuery = "SELECT * FROM courses WHERE college_code = ?";
$filterParams = [$college_code];

// Apply filters if any are selected
if (isset($_GET['year'])) {
    $filterQuery .= " AND year = ?";
    $filterParams[] = $_GET['year'];
}
if (isset($_GET['semester'])) {
    $filterQuery .= " AND semester = ?";
    $filterParams[] = $_GET['semester'];
}
if (isset($_GET['subject'])) {
    $filterQuery .= " AND subject = ?";
    $filterParams[] = $_GET['subject'];
}
if (isset($_GET['unit'])) {
    $filterQuery .= " AND unit = ?";
    $filterParams[] = $_GET['unit'];
}
if (isset($_GET['topic'])) {
    $filterQuery .= " AND topic = ?";
    $filterParams[] = $_GET['topic'];
}

$stmt = $conn->prepare($filterQuery);
$stmt->bind_param(str_repeat("s", count($filterParams)), ...$filterParams);
$stmt->execute();
$courses = $stmt->get_result();

// Fetch course details based on the course ID
if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $courseDetails = $stmt->get_result()->fetch_assoc();

    // Fetch year, semester, subject, unit, topic, and notes
    $year = $courseDetails['year'];
    $semester = $courseDetails['semester'];
    $subject = $courseDetails['subject'];
    $unit = $courseDetails['unit'];
    $topic = $courseDetails['topic'];
    $notes = $courseDetails['notes'];  // File path or link to download
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Dashboard | Class Cloud</title>
    <!-- External Libraries -->
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

    <!-- Filters Section -->
    <div class="container mt-5">
        <h2 class="text-center text-white">Filter Courses</h2>
        <form action="learners_dashboard.php" method="get">
            <div class="row">
                <div class="col-md-3">
                    <select name="year" class="form-select" aria-label="Select Year">
                        <option value="">Select Year</option>
                        <?php while ($yearRow = $years->fetch_assoc()): ?>
                            <option value="<?php echo $yearRow['year']; ?>" <?php echo isset($_GET['year']) && $_GET['year'] == $yearRow['year'] ? 'selected' : ''; ?>>
                                <?php echo $yearRow['year']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="semester" class="form-select" aria-label="Select Semester">
                        <option value="">Select Semester</option>
                        <?php while ($semesterRow = $semesters->fetch_assoc()): ?>
                            <option value="<?php echo $semesterRow['semester']; ?>" <?php echo isset($_GET['semester']) && $_GET['semester'] == $semesterRow['semester'] ? 'selected' : ''; ?>>
                                <?php echo $semesterRow['semester']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="subject" class="form-select" aria-label="Select Subject">
                        <option value="">Select Subject</option>
                        <?php while ($subjectRow = $subjects->fetch_assoc()): ?>
                            <option value="<?php echo $subjectRow['subject']; ?>" <?php echo isset($_GET['subject']) && $_GET['subject'] == $subjectRow['subject'] ? 'selected' : ''; ?>>
                                <?php echo $subjectRow['subject']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="unit" class="form-select" aria-label="Select Unit">
                        <option value="">Select Unit</option>
                        <?php while ($unitRow = $units->fetch_assoc()): ?>
                            <option value="<?php echo $unitRow['unit']; ?>" <?php echo isset($_GET['unit']) && $_GET['unit'] == $unitRow['unit'] ? 'selected' : ''; ?>>
                                <?php echo $unitRow['unit']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <select name="topic" class="form-select" aria-label="Select Topic">
                        <option value="">Select Topic</option>
                        <?php while ($topicRow = $topics->fetch_assoc()): ?>
                            <option value="<?php echo $topicRow['topic']; ?>" <?php echo isset($_GET['topic']) && $_GET['topic'] == $topicRow['topic'] ? 'selected' : ''; ?>>
                                <?php echo $topicRow['topic']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Courses List (Learner) -->
    <div class="container mt-5">
        <h2 class="text-center text-white">Available Courses</h2>
        <div class="card-container" id="courses-container">
            <?php while ($course = $courses->fetch_assoc()): ?>
                <div class="card" id="course-<?php echo $course['id']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                        <a href="learners_dashboard.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Course Details (Learner) -->
    <?php if (isset($courseDetails)): ?>
        <div class="container mt-5">
            <h2 class="text-center text-white">Course Details</h2>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($courseDetails['course_name']); ?></h5>
                    <p class="card-text"><strong>Year:</strong> <?php echo htmlspecialchars($year); ?></p>
                    <p class="card-text"><strong>Semester:</strong> <?php echo htmlspecialchars($semester); ?></p>
                    <p class="card-text"><strong>Subject:</strong> <?php echo htmlspecialchars($subject); ?></p>
                    <p class="card-text"><strong>Unit:</strong> <?php echo htmlspecialchars($unit); ?></p>
                    <p class="card-text"><strong>Topic:</strong> <?php echo htmlspecialchars($topic); ?></p>
                    <p class="card-text"><strong>Notes:</strong> 
                        <a href="<?php echo htmlspecialchars($notes); ?>" download class="btn btn-warning">
                            <i class="fas fa-download"></i> Download Notes
                        </a>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- External JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
