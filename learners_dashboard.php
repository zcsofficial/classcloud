<?php
session_start();
include 'db.php'; // Database connection

// Check if the user is logged in and is a learner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'learner') {
    header("Location: login.php");
    exit();
}

// Initialize message variables
$message = '';
$messageType = '';

// Fetch college code from session
$college_code = $_SESSION['college_code'];

// Fetch distinct filter options
$years = $conn->prepare("SELECT DISTINCT year FROM courses WHERE college_code = ? ORDER BY year");
$years->bind_param("s", $college_code);
$years->execute();
$yearsResult = $years->get_result();
$years->close();

$semesters = $conn->prepare("SELECT DISTINCT semester FROM courses WHERE college_code = ? ORDER BY semester");
$semesters->bind_param("s", $college_code);
$semesters->execute();
$semestersResult = $semesters->get_result();
$semesters->close();

$subjects = $conn->prepare("SELECT DISTINCT subject FROM courses WHERE college_code = ? ORDER BY subject");
$subjects->bind_param("s", $college_code);
$subjects->execute();
$subjectsResult = $subjects->get_result();
$subjects->close();

$units = $conn->prepare("SELECT DISTINCT unit FROM courses WHERE college_code = ? ORDER BY unit");
$units->bind_param("s", $college_code);
$units->execute();
$unitsResult = $units->get_result();
$units->close();

$topics = $conn->prepare("SELECT DISTINCT topic FROM courses WHERE college_code = ? ORDER BY topic");
$topics->bind_param("s", $college_code);
$topics->execute();
$topicsResult = $topics->get_result();
$topics->close();

// Prepare the base query for fetching courses
$filterQuery = "SELECT * FROM courses WHERE college_code = ?";
$filterParams = [$college_code];
$paramTypes = "s";

// Handle dynamic filtering
if (isset($_POST['apply_filters'])) {
    $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_STRING) ?? '';
    $semester = filter_input(INPUT_POST, 'semester', FILTER_SANITIZE_STRING) ?? '';
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING) ?? '';
    $unit = filter_input(INPUT_POST, 'unit', FILTER_SANITIZE_STRING) ?? '';
    $topic = filter_input(INPUT_POST, 'topic', FILTER_SANITIZE_STRING) ?? '';

    if ($year) {
        $filterQuery .= " AND year = ?";
        $filterParams[] = $year;
        $paramTypes .= "s";
    }
    if ($semester) {
        $filterQuery .= " AND semester = ?";
        $filterParams[] = $semester;
        $paramTypes .= "s";
    }
    if ($subject) {
        $filterQuery .= " AND subject = ?";
        $filterParams[] = $subject;
        $paramTypes .= "s";
    }
    if ($unit) {
        $filterQuery .= " AND unit = ?";
        $filterParams[] = $unit;
        $paramTypes .= "s";
    }
    if ($topic) {
        $filterQuery .= " AND topic = ?";
        $filterParams[] = $topic;
        $paramTypes .= "s";
    }
}

$stmt = $conn->prepare($filterQuery);
if (count($filterParams) > 1) {
    $stmt->bind_param($paramTypes, ...$filterParams);
} else {
    $stmt->bind_param($paramTypes, $college_code);
}
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();

// Organize courses into a tree structure by year and semester
$courseTree = [];
while ($course = $courses->fetch_assoc()) {
    $year = $course['year'];
    $semester = $course['semester'];
    if (!isset($courseTree[$year])) {
        $courseTree[$year] = [];
    }
    if (!isset($courseTree[$year][$semester])) {
        $courseTree[$year][$semester] = [];
    }
    $courseTree[$year][$semester][] = $course;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Dashboard | ClassCloud</title>
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
    <style>
        .tree-node {
            cursor: pointer;
        }
        .tree-node:hover {
            background-color: #f5f5f5;
        }
        .tree-child {
            display: none;
        }
        .tree-child.active {
            display: block;
        }
    </style>
</head>
<body class="bg-[#FFFAF0] font-['Roboto'] min-h-screen">
    <!-- Navbar -->
    <nav class="bg-gray-900 text-white p-4 shadow-md">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="#" class="text-2xl font-bold">ClassCloud - Learner</a>
            <div class="hidden md:flex space-x-6">
                <a href="learners_dashboard.php" class="hover:text-primary">Dashboard</a>
                <a href="logout.php" class="hover:text-primary">Logout</a>
            </div>
            <button id="mobile-menu-btn" class="md:hidden text-2xl">
                <i class="ri-menu-line"></i>
            </button>
        </div>
        <div id="mobile-menu" class="hidden md:hidden mt-4 space-y-2">
            <a href="learners_dashboard.php" class="block text-white hover:text-primary py-2 px-4">Dashboard</a>
            <a href="logout.php" class="block text-white hover:text-primary py-2 px-4">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto mt-8 px-4 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 text-center">Available Courses</h2>

        <!-- Filter Form -->
        <form method="POST" class="bg-white p-6 rounded-lg shadow-lg mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                    <select id="year" name="year" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary">
                        <option value="">All Years</option>
                        <?php while ($yearRow = $yearsResult->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($yearRow['year']); ?>" <?php echo (isset($_POST['year']) && $_POST['year'] === $yearRow['year']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($yearRow['year']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label for="semester" class="block text-sm font-medium text-gray-700">Semester</label>
                    <select id="semester" name="semester" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary">
                        <option value="">All Semesters</option>
                        <?php while ($semesterRow = $semestersResult->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($semesterRow['semester']); ?>" <?php echo (isset($_POST['semester']) && $_POST['semester'] === $semesterRow['semester']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($semesterRow['semester']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
                    <select id="subject" name="subject" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary">
                        <option value="">All Subjects</option>
                        <?php while ($subjectRow = $subjectsResult->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($subjectRow['subject']); ?>" <?php echo (isset($_POST['subject']) && $_POST['subject'] === $subjectRow['subject']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subjectRow['subject']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700">Unit</label>
                    <select id="unit" name="unit" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary">
                        <option value="">All Units</option>
                        <?php while ($unitRow = $unitsResult->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($unitRow['unit']); ?>" <?php echo (isset($_POST['unit']) && $_POST['unit'] === $unitRow['unit']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($unitRow['unit']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label for="topic" class="block text-sm font-medium text-gray-700">Topic</label>
                    <select id="topic" name="topic" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary">
                        <option value="">All Topics</option>
                        <?php while ($topicRow = $topicsResult->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($topicRow['topic']); ?>" <?php echo (isset($_POST['topic']) && $_POST['topic'] === $topicRow['topic']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($topicRow['topic']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <button type="submit" name="apply_filters" class="mt-4 w-full bg-primary text-white px-6 py-3 rounded-button hover:bg-primary/90 transition-all font-semibold">Apply Filters</button>
        </form>

        <!-- Tree-like Course List -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <?php if (!empty($courseTree)): ?>
                <?php foreach ($courseTree as $year => $semesters): ?>
                    <div class="tree-node p-2">
                        <div class="flex items-center">
                            <i class="ri-arrow-right-s-line text-primary text-xl"></i>
                            <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($year); ?></span>
                        </div>
                        <div class="tree-child pl-6">
                            <?php foreach ($semesters as $semester => $courses): ?>
                                <div class="tree-node p-2">
                                    <div class="flex items-center">
                                        <i class="ri-arrow-right-s-line text-primary text-xl"></i>
                                        <span class="font-medium text-gray-700"><?php echo htmlspecialchars($semester); ?></span>
                                    </div>
                                    <div class="tree-child pl-6">
                                        <?php foreach ($courses as $course): ?>
                                            <div class="p-4 border-t border-gray-200">
                                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                                <p class="text-gray-600">Subject: <?php echo htmlspecialchars($course['subject']); ?></p>
                                                <p class="text-gray-600">Unit: <?php echo htmlspecialchars($course['unit']); ?></p>
                                                <p class="text-gray-600">Topic: <?php echo htmlspecialchars($course['topic']); ?></p>
                                                <div class="mt-2 flex space-x-2">
                                                    <a href="<?php echo filter_var($course['notes'], FILTER_VALIDATE_URL) ? htmlspecialchars($course['notes']) : htmlspecialchars($course['notes']); ?>" target="_blank" class="text-primary hover:underline">View Notes</a>
                                                    <a href="course_details.php?course_id=<?php echo $course['id']; ?>" class="bg-primary text-white px-4 py-1 rounded-button hover:bg-primary/90 transition-all">Select</a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-700 py-4">No courses found for the selected filters.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Tree node toggle
        document.querySelectorAll('.tree-node').forEach(node => {
            node.addEventListener('click', (e) => {
                const child = node.querySelector('.tree-child');
                if (child && e.target.tagName !== 'A') { // Prevent toggling when clicking links
                    child.classList.toggle('active');
                    const arrow = node.querySelector('.ri-arrow-right-s-line');
                    arrow.classList.toggle('ri-arrow-down-s-line');
                    arrow.classList.toggle('ri-arrow-right-s-line');
                }
            });
        });
    </script>
</body>
</html>