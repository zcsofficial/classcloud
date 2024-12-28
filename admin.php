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
    $userId = $_GET['user_id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        // Display role selection form when approving
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND role = 'approval'");
        if (isset($_POST['role'])) {
            $role = $_POST['role'];
            $stmt->bind_param("si", $role, $userId);
            if ($stmt->execute()) {
                $message = "User approved and role updated to $role!";
                $messageType = 'success';
            } else {
                $message = "Error approving user: " . $stmt->error;
                $messageType = 'danger';
            }
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'approval'");
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $message = "User rejected and removed.";
            $messageType = 'warning';
        } else {
            $message = "Error rejecting user: " . $stmt->error;
            $messageType = 'danger';
        }
    }
}

// Handle user role update (edit user)
if (isset($_POST['edit_user'])) {
    $userId = $_POST['user_id'];
    $newRole = $_POST['new_role'];
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $newRole, $userId);
    if ($stmt->execute()) {
        $message = "User role updated successfully!";
        $messageType = 'success';
    } else {
        $message = "Error updating user role: " . $stmt->error;
        $messageType = 'danger';
    }
}

// Fetch all users with approval status
$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'approval' AND college_code = ?");
$stmt->bind_param("s", $_SESSION['college_code']);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all users for role editing
$stmtAllUsers = $conn->prepare("SELECT * FROM users WHERE role != 'admin' AND college_code = ?");
$stmtAllUsers->bind_param("s", $_SESSION['college_code']);
$stmtAllUsers->execute();
$allUsers = $stmtAllUsers->get_result();

// Fetch counts for students and instructors
$collegeCode = $_SESSION['college_code']; // Assuming college_code is stored in session
$stmtStudentCount = $conn->prepare("SELECT COUNT(*) AS student_count FROM users WHERE role = 'learner' AND college_code = ?");
$stmtStudentCount->bind_param("s", $collegeCode);
$stmtStudentCount->execute();
$studentCountResult = $stmtStudentCount->get_result();
$studentCount = $studentCountResult->fetch_assoc()['student_count'];

$stmtInstructorCount = $conn->prepare("SELECT COUNT(*) AS instructor_count FROM users WHERE role = 'instructor' AND college_code = ?");
$stmtInstructorCount->bind_param("s", $collegeCode);
$stmtInstructorCount->execute();
$instructorCountResult = $stmtInstructorCount->get_result();
$instructorCount = $instructorCountResult->fetch_assoc()['instructor_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Class Cloud</title>
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
            margin-top: 20px;
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
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Class Cloud - Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="courses.php">Courses</a>
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

    <!-- User Approval -->
    <div class="container mt-5 table-container">
        <h2 class="text-center">User Approval</h2>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>College Code</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['college_code']); ?></td>
                        <td>
                            <a href="admin.php?action=approve&user_id=<?php echo $user['id']; ?>" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $user['id']; ?>">Approve</a>
                            <a href="admin.php?action=reject&user_id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm">Reject</a>
                        </td>
                    </tr>

                    <!-- Modal for Approve Role Selection -->
                    <div class="modal fade" id="approveModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="approveModalLabel<?php echo $user['id']; ?>">Assign Role to <?php echo htmlspecialchars($user['name']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="admin.php?action=approve&user_id=<?php echo $user['id']; ?>">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Select Role</label>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="learner">Learner</option>
                                                <option value="instructor">Instructor</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Approve</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- User Management (Edit or Delete Users) -->
    <div class="container mt-5">
        <h2 class="text-center">Manage Users</h2>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $allUsers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <form method="POST" action="admin.php">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select class="form-select" name="new_role" required>
                                    <option value="learner" <?php echo ($user['role'] === 'learner') ? 'selected' : ''; ?>>Learner</option>
                                    <option value="instructor" <?php echo ($user['role'] === 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                                </select>
                                <button type="submit" name="edit_user" class="btn btn-warning btn-sm">Edit Role</button>
                            </form>
                            <a href="admin.php?action=reject&user_id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS and External Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery.counterup2/jquery.counterup.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.counter').counterUp({
                delay: 10,
                time: 1000
            });
        });
    </script>
</body>
</html>
