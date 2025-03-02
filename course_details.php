<?php
session_start();
include 'db.php'; // Database connection

// Check if the user is logged in and is a learner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'learner') {
    header("Location: login.php");
    exit();
}

// Check if course_id is provided
if (!isset($_GET['course_id'])) {
    header("Location: learners_dashboard.php");
    exit();
}

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_SANITIZE_NUMBER_INT);
$college_code = $_SESSION['college_code'];

// Fetch course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND college_code = ?");
$stmt->bind_param("is", $course_id, $college_code);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if course exists
if (!$course) {
    header("Location: learners_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details | ClassCloud</title>
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

    <!-- Course Details -->
    <div class="max-w-7xl mx-auto mt-8 px-4 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($course['course_name']); ?></h2>
                <a href="learners_dashboard.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-button hover:bg-gray-400 transition-all flex items-center">
                    <i class="ri-arrow-left-line mr-2"></i> Back to Dashboard
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-gray-600"><span class="font-semibold">Year:</span> <?php echo htmlspecialchars($course['year']); ?></p>
                    <p class="text-gray-600"><span class="font-semibold">Semester:</span> <?php echo htmlspecialchars($course['semester']); ?></p>
                    <p class="text-gray-600"><span class="font-semibold">Subject:</span> <?php echo htmlspecialchars($course['subject']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600"><span class="font-semibold">Unit:</span> <?php echo htmlspecialchars($course['unit']); ?></p>
                    <p class="text-gray-600"><span class="font-semibold">Topic:</span> <?php echo htmlspecialchars($course['topic']); ?></p>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Notes</h3>
                <?php if (filter_var($course['notes'], FILTER_VALIDATE_URL)): ?>
                    <a href="<?php echo htmlspecialchars($course['notes']); ?>" target="_blank" class="bg-primary text-white px-6 py-2 rounded-button hover:bg-primary/90 transition-all inline-flex items-center">
                        <i class="ri-eye-line mr-2"></i> View Notes
                    </a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($course['notes']); ?>" target="_blank" class="bg-primary text-white px-6 py-2 rounded-button hover:bg-primary/90 transition-all inline-flex items-center">
                        <i class="ri-download-line mr-2"></i> Download Notes
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>