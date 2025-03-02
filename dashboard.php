<?php
session_start();
include 'db.php'; // Database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize message variables
$message = '';
$messageType = '';

// Handle adding a new course (Instructor only)
if ($_SESSION['user_role'] === 'instructor' && isset($_POST['add_course'])) {
    $courseName = filter_input(INPUT_POST, 'course_name', FILTER_SANITIZE_STRING);
    $semester = filter_input(INPUT_POST, 'semester', FILTER_SANITIZE_STRING);
    $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_STRING);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $unit = filter_input(INPUT_POST, 'unit', FILTER_SANITIZE_STRING);
    $topic = filter_input(INPUT_POST, 'topic', FILTER_SANITIZE_STRING);
    $notesLink = filter_input(INPUT_POST, 'notes_link', FILTER_SANITIZE_URL);
    $notes = '';

    // Validate required fields
    if (empty($courseName) || empty($semester) || empty($year) || empty($subject) || empty($unit) || empty($topic)) {
        $message = "All fields are required.";
        $messageType = 'danger';
    } else {
        // Handle file upload
        if ($_FILES['notes_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['notes_file']['tmp_name'];
            $fileName = $_FILES['notes_file']['name'];
            $fileSize = $_FILES['notes_file']['size'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'ppt', 'pptx'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $notes = $destPath;
                } else {
                    $message = "Error uploading the file.";
                    $messageType = 'danger';
                }
            } else {
                $message = "Only PDF, PPT, and PPTX files are allowed.";
                $messageType = 'danger';
            }
        } elseif (!empty($notesLink)) {
            $notes = $notesLink;
        } else {
            $message = "Please provide either a file or a link for notes.";
            $messageType = 'danger';
        }

        if ($notes && !$message) {
            $stmt = $conn->prepare("INSERT INTO courses (course_name, semester, year, subject, unit, topic, notes, college_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $courseName, $semester, $year, $subject, $unit, $topic, $notes, $_SESSION['college_code']);
            if ($stmt->execute()) {
                $message = "Course added successfully!";
                $messageType = 'success';
            } else {
                $message = "Error adding course: " . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }
}

// Handle course deletion (Instructor only)
if ($_SESSION['user_role'] === 'instructor' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['course_id'])) {
    $courseId = filter_input(INPUT_GET, 'course_id', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ? AND college_code = ?");
    $stmt->bind_param("is", $courseId, $_SESSION['college_code']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Course deleted successfully!";
        $messageType = 'warning';
    } else {
        $message = "Error deleting course or course not found.";
        $messageType = 'danger';
    }
    $stmt->close();
}

// Fetch all courses
$stmt = $conn->prepare("SELECT * FROM courses WHERE college_code = ?");
$stmt->bind_param("s", $_SESSION['college_code']);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();

// Fetch distinct values for dropdowns
$semesters = $conn->query("SELECT DISTINCT semester FROM courses WHERE college_code = '{$_SESSION['college_code']}'")->fetch_all(MYSQLI_ASSOC);
$years = $conn->query("SELECT DISTINCT year FROM courses WHERE college_code = '{$_SESSION['college_code']}'")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT DISTINCT subject FROM courses WHERE college_code = '{$_SESSION['college_code']}'")->fetch_all(MYSQLI_ASSOC);
$units = $conn->query("SELECT DISTINCT unit FROM courses WHERE college_code = '{$_SESSION['college_code']}'")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ClassCloud</title>
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
            <a href="#" class="text-2xl font-bold">ClassCloud - Dashboard</a>
            <div class="hidden md:flex space-x-6">
                <a href="dashboard.php" class="hover:text-primary">Dashboard</a>
                <a href="courses.php" class="hover:text-primary">Courses</a>
                <a href="logout.php" class="hover:text-primary">Logout</a>
            </div>
            <button id="mobile-menu-btn" class="md:hidden text-2xl">
                <i class="ri-menu-line"></i>
            </button>
        </div>
        <div id="mobile-menu" class="hidden md:hidden mt-4 space-y-2">
            <a href="dashboard.php" class="block text-white hover:text-primary py-2 px-4">Dashboard</a>
            <a href="courses.php" class="block text-white hover:text-primary py-2 px-4">Courses</a>
            <a href="logout.php" class="block text-white hover:text-primary py-2 px-4">Logout</a>
        </div>
    </nav>

    <!-- Message Display -->
    <?php if ($message): ?>
        <div class="max-w-7xl mx-auto mt-4 px-4">
            <div class="p-4 rounded-lg text-white <?php echo $messageType === 'success' ? 'bg-green-500' : ($messageType === 'warning' ? 'bg-yellow-500' : 'bg-red-500'); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add Course Form (Instructor only) -->
    <?php if ($_SESSION['user_role'] === 'instructor'): ?>
        <div class="max-w-7xl mx-auto mt-8 px-4">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Add New Course</h2>
            <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="course_name" class="block text-sm font-medium text-gray-700">Course Name</label>
                        <input type="text" id="course_name" name="course_name" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" placeholder="Enter course name" required>
                    </div>
                    <div class="mb-4">
                        <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                        <select id="year" name="year" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" required>
                            <option value="">Select Year</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo htmlspecialchars($year['year']); ?>"><?php echo htmlspecialchars($year['year']); ?></option>
                            <?php endforeach; ?>
                            <option value="other">Other</option>
                        </select>
                        <input type="text" id="year_other" name="year" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary hidden" placeholder="Enter new year">
                    </div>
                    <div class="mb-4">
                        <label for="semester" class="block text-sm font-medium text-gray-700">Semester</label>
                        <select id="semester" name="semester" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" required>
                            <option value="">Select Semester</option>
                            <?php foreach ($semesters as $semester): ?>
                                <option value="<?php echo htmlspecialchars($semester['semester']); ?>"><?php echo htmlspecialchars($semester['semester']); ?></option>
                            <?php endforeach; ?>
                            <option value="other">Other</option>
                        </select>
                        <input type="text" id="semester_other" name="semester" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary hidden" placeholder="Enter new semester">
                    </div>
                    <div class="mb-4">
                        <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
                        <select id="subject" name="subject" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['subject']); ?>"><?php echo htmlspecialchars($subject['subject']); ?></option>
                            <?php endforeach; ?>
                            <option value="other">Other</option>
                        </select>
                        <input type="text" id="subject_other" name="subject" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary hidden" placeholder="Enter new subject">
                    </div>
                    <div class="mb-4">
                        <label for="unit" class="block text-sm font-medium text-gray-700">Unit</label>
                        <select id="unit" name="unit" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" required>
                            <option value="">Select Unit</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?php echo htmlspecialchars($unit['unit']); ?>"><?php echo htmlspecialchars($unit['unit']); ?></option>
                            <?php endforeach; ?>
                            <option value="other">Other</option>
                        </select>
                        <input type="text" id="unit_other" name="unit" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary hidden" placeholder="Enter new unit">
                    </div>
                    <div class="mb-4">
                        <label for="topic" class="block text-sm font-medium text-gray-700">Topic</label>
                        <input type="text" id="topic" name="topic" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" placeholder="Enter topic" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="notes_file" class="block text-sm font-medium text-gray-700">Notes (Upload PDF/PPT)</label>
                    <input type="file" id="notes_file" name="notes_file" accept=".pdf,.ppt,.pptx" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary">
                    <p class="text-sm text-gray-500 mt-1">OR</p>
                    <input type="text" id="notes_link" name="notes_link" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" placeholder="Paste link to notes (PDF/PPT)">
                </div>
                <button type="submit" name="add_course" class="w-full bg-primary text-white px-6 py-3 rounded-button hover:bg-primary/90 transition-all font-semibold">Add Course</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Courses List -->
    <div class="max-w-7xl mx-auto mt-8 px-4 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Courses</h2>
        <div class="overflow-x-auto">
            <table class="w-full bg-white shadow-lg rounded-lg">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Course Name</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Semester</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Year</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Subject</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Unit</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Topic</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Notes</th>
                        <?php if ($_SESSION['user_role'] === 'instructor'): ?>
                            <th class="p-4 text-left text-sm font-semibold text-gray-700">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <tr class="border-t">
                            <td class="p-4"><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($course['semester']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($course['year']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($course['subject']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($course['unit']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($course['topic']); ?></td>
                            <td class="p-4">
                                <?php
                                if (filter_var($course['notes'], FILTER_VALIDATE_URL)) {
                                    echo '<a href="' . htmlspecialchars($course['notes']) . '" target="_blank" class="text-primary hover:underline">View Notes</a>';
                                } else {
                                    echo '<a href="' . htmlspecialchars($course['notes']) . '" target="_blank" class="text-primary hover:underline">Download Notes</a>';
                                }
                                ?>
                            </td>
                            <?php if ($_SESSION['user_role'] === 'instructor'): ?>
                                <td class="p-4">
                                    <a href="dashboard.php?action=delete&course_id=<?php echo $course['id']; ?>" class="bg-red-500 text-white px-4 py-2 rounded-button hover:bg-red-600">Delete</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Dynamic dropdown handling
        const dropdowns = ['year', 'semester', 'subject', 'unit'];
        dropdowns.forEach(field => {
            const select = document.getElementById(field);
            const otherInput = document.getElementById(`${field}_other`);
            select.addEventListener('change', () => {
                if (select.value === 'other') {
                    otherInput.classList.remove('hidden');
                    otherInput.required = true;
                    select.required = false;
                } else {
                    otherInput.classList.add('hidden');
                    otherInput.required = false;
                    select.required = true;
                }
            });
        });
    </script>
</body>
</html>