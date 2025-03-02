<?php
session_start();
include 'db.php'; // Database connection

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Initialize message variables
$message = '';
$messageType = '';

// Handle account approval or rejection
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $userId = filter_input(INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

    if ($action === 'approve' && isset($_POST['role'])) {
        $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND role = 'approval'");
        $stmt->bind_param("si", $role, $userId);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "User approved and role updated to $role!";
            $messageType = 'success';
        } else {
            $message = "Error approving user or no changes made.";
            $messageType = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND college_code = ?");
        $stmt->bind_param("is", $userId, $_SESSION['college_code']);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "User rejected and removed.";
            $messageType = 'warning';
        } else {
            $message = "Error rejecting user or user not found.";
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Handle user role update (edit user)
if (isset($_POST['edit_user'])) {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $newRole = filter_input(INPUT_POST, 'new_role', FILTER_SANITIZE_STRING);
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND college_code = ? AND role != 'admin'");
    $stmt->bind_param("sis", $newRole, $userId, $_SESSION['college_code']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "User role updated successfully!";
        $messageType = 'success';
    } else {
        $message = "Error updating user role or no changes made.";
        $messageType = 'danger';
    }
    $stmt->close();
}

// Fetch all users with approval status
$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'approval' AND college_code = ?");
$stmt->bind_param("s", $_SESSION['college_code']);
$stmt->execute();
$pendingUsers = $stmt->get_result();
$stmt->close();

// Fetch all users for role editing
$stmtAllUsers = $conn->prepare("SELECT * FROM users WHERE role != 'admin' AND college_code = ?");
$stmtAllUsers->bind_param("s", $_SESSION['college_code']);
$stmtAllUsers->execute();
$allUsers = $stmtAllUsers->get_result();
$stmtAllUsers->close();

// Fetch counts for students and instructors
$collegeCode = $_SESSION['college_code'];
$stmtStudentCount = $conn->prepare("SELECT COUNT(*) AS student_count FROM users WHERE role = 'learner' AND college_code = ?");
$stmtStudentCount->bind_param("s", $collegeCode);
$stmtStudentCount->execute();
$studentCount = $stmtStudentCount->get_result()->fetch_assoc()['student_count'];
$stmtStudentCount->close();

$stmtInstructorCount = $conn->prepare("SELECT COUNT(*) AS instructor_count FROM users WHERE role = 'instructor' AND college_code = ?");
$stmtInstructorCount->bind_param("s", $collegeCode);
$stmtInstructorCount->execute();
$instructorCount = $stmtInstructorCount->get_result()->fetch_assoc()['instructor_count'];
$stmtInstructorCount->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ClassCloud</title>
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
            <a href="#" class="text-2xl font-bold">ClassCloud - Admin</a>
            <div class="hidden md:flex space-x-6">
                <a href="admin.php" class="hover:text-primary">Dashboard</a>
                <a href="courses.php" class="hover:text-primary">Courses</a>
                <a href="logout.php" class="hover:text-primary">Logout</a>
            </div>
            <button id="mobile-menu-btn" class="md:hidden text-2xl">
                <i class="ri-menu-line"></i>
            </button>
        </div>
        <div id="mobile-menu" class="hidden md:hidden mt-4 space-y-2">
            <a href="admin.php" class="block text-white hover:text-primary py-2 px-4">Dashboard</a>
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

    <!-- Dashboard Stats -->
    <div class="max-w-7xl mx-auto mt-8 px-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                <i class="ri-user-3-line text-primary text-4xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900">Students</h3>
                <p class="text-3xl font-bold text-primary"><?php echo $studentCount; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                <i class="ri-user-settings-line text-primary text-4xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900">Instructors</h3>
                <p class="text-3xl font-bold text-primary"><?php echo $instructorCount; ?></p>
            </div>
        </div>
    </div>

    <!-- User Approval -->
    <div class="max-w-7xl mx-auto mt-8 px-4">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">User Approval</h2>
        <div class="overflow-x-auto">
            <table class="w-full bg-white shadow-lg rounded-lg">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Name</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Email</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">College Code</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $pendingUsers->fetch_assoc()): ?>
                        <tr class="border-t">
                            <td class="p-4"><?php echo htmlspecialchars($user['name']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($user['college_code']); ?></td>
                            <td class="p-4 flex space-x-2">
                                <button class="bg-green-500 text-white px-4 py-2 rounded-button hover:bg-green-600" data-modal-target="approveModal<?php echo $user['id']; ?>">Approve</button>
                                <a href="admin.php?action=reject&user_id=<?php echo $user['id']; ?>" class="bg-red-500 text-white px-4 py-2 rounded-button hover:bg-red-600">Reject</a>
                            </td>
                        </tr>
                        <!-- Modal for Approve Role Selection -->
                        <div id="approveModal<?php echo $user['id']; ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
                            <div class="bg-white p-6 rounded-lg shadow-lg max-w-sm w-full">
                                <h3 class="text-xl font-bold text-gray-900 mb-4">Assign Role to <?php echo htmlspecialchars($user['name']); ?></h3>
                                <form method="POST" action="admin.php?action=approve&user_id=<?php echo $user['id']; ?>">
                                    <div class="mb-4">
                                        <label for="role<?php echo $user['id']; ?>" class="block text-sm font-medium text-gray-700">Select Role</label>
                                        <select id="role<?php echo $user['id']; ?>" name="role" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-button focus:ring-primary focus:border-primary" required>
                                            <option value="learner">Learner</option>
                                            <option value="instructor">Instructor</option>
                                        </select>
                                    </div>
                                    <div class="flex justify-end space-x-2">
                                        <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-button hover:bg-gray-400" data-modal-close>Cancel</button>
                                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-button hover:bg-primary/90">Approve</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Management -->
    <div class="max-w-7xl mx-auto mt-8 px-4 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Manage Users</h2>
        <div class="overflow-x-auto">
            <table class="w-full bg-white shadow-lg rounded-lg">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Name</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Email</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Role</th>
                        <th class="p-4 text-left text-sm font-semibold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $allUsers->fetch_assoc()): ?>
                        <tr class="border-t">
                            <td class="p-4"><?php echo htmlspecialchars($user['name']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="p-4 flex space-x-2">
                                <form method="POST" action="admin.php" class="flex space-x-2">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="new_role" class="px-2 py-1 border border-gray-300 rounded-button focus:ring-primary focus:border-primary">
                                        <option value="learner" <?php echo $user['role'] === 'learner' ? 'selected' : ''; ?>>Learner</option>
                                        <option value="instructor" <?php echo $user['role'] === 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                                    </select>
                                    <button type="submit" name="edit_user" class="bg-yellow-500 text-white px-4 py-2 rounded-button hover:bg-yellow-600">Edit</button>
                                </form>
                                <a href="admin.php?action=reject&user_id=<?php echo $user['id']; ?>" class="bg-red-500 text-white px-4 py-2 rounded-button hover:bg-red-600">Delete</a>
                            </td>
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

        // Modal handling
        document.querySelectorAll('[data-modal-target]').forEach(button => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-modal-target');
                const modal = document.getElementById(modalId);
                modal.classList.remove('hidden');
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach(button => {
            button.addEventListener('click', () => {
                const modal = button.closest('.fixed');
                modal.classList.add('hidden');
            });
        });
    </script>
</body>
</html>