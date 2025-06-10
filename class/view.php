<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Get class ID from URL
$classId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if class exists and user has access
if (!hasClassAccess($pdo, $classId, $_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: ../error.php?msg=Access denied or class not found');
    exit();
}

// Get class details
$class = getClassDetails($pdo, $classId);
if (!$class) {
    header('Location: ../error.php?msg=Class not found');
    exit();
}

// Get class statistics
$studentCount = getClassStudentCount($pdo, $classId);

// Get assignments
$stmt = $pdo->prepare("
    SELECT a.*, 
    (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
    FROM assignments a 
    WHERE a.class_id = ? 
    ORDER BY a.due_date ASC
");
$stmt->execute([$classId]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get enrolled students if teacher or admin
$students = [];
if ($_SESSION['role'] === 'dowsen' || $_SESSION['role'] === 'admin') {
    $stmt = $pdo->prepare("
        SELECT u.*, e.enrolled_at 
        FROM users u 
        JOIN enrollments e ON u.id = e.student_id 
        WHERE e.class_id = ? AND e.status = 'active'
        ORDER BY u.name ASC
    ");
    $stmt->execute([$classId]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($class['name']) ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .class-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .nav-pills .nav-link {
            color: #495057;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .assignment-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .assignment-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Class Header -->
    <div class="class-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 mb-2"><?= htmlspecialchars($class['name']) ?></h1>
                    <p class="mb-0">Teacher: <?= htmlspecialchars($class['teacher_name']) ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <?php if ($_SESSION['role'] === 'dowsen' && $class['teacher_id'] === $_SESSION['user_id']): ?>
                        <a href="edit.php?id=<?= $classId ?>" class="btn btn-light">Edit Class</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4" id="classTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="stream-tab" data-bs-toggle="pill" href="#stream" role="tab">Stream</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="assignments-tab" data-bs-toggle="pill" href="#assignments" role="tab">Assignments</a>
            </li>
            <?php if ($_SESSION['role'] === 'dowsen' || $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" id="students-tab" data-bs-toggle="pill" href="#students" role="tab">Students</a>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="classTabContent">
            <!-- Stream Tab -->
            <div class="tab-pane fade show active" id="stream" role="tabpanel">
                <div class="row">
                    <!-- Main Stream -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">About This Class</h5>
                                <p class="card-text"><?= nl2br(htmlspecialchars($class['description'])) ?></p>
                            </div>
                        </div>

                        <!-- Recent Assignments -->
                        <h5 class="mb-3">Recent Assignments</h5>
                        <?php if (empty($assignments)): ?>
                            <p class="text-muted">No assignments yet.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($assignments, 0, 3) as $assignment): ?>
                                <div class="card assignment-card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title mb-1"><?= htmlspecialchars($assignment['title']) ?></h6>
                                        <p class="text-muted small mb-2">
                                            Due: <?= formatDate($assignment['due_date']) ?>
                                        </p>
                                        <p class="card-text"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                                        <a href="assignment.php?id=<?= $assignment['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-md-4">
                        <!-- Class Info Card -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Class Information</h5>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <strong>Class Code:</strong><br>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($class['code']) ?></span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Students:</strong><br>
                                        <?= $studentCount ?> enrolled
                                    </li>
                                    <li>
                                        <strong>Created:</strong><br>
                                        <?= formatDate($class['created_at']) ?>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <?php if ($_SESSION['role'] === 'dowsen' && $class['teacher_id'] === $_SESSION['user_id']): ?>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Quick Actions</h5>
                                <div class="d-grid gap-2">
                                    <a href="create_assignment.php?class_id=<?= $classId ?>" class="btn btn-primary">
                                        Create Assignment
                                    </a>
                                    <a href="manage_students.php?class_id=<?= $classId ?>" class="btn btn-outline-primary">
                                        Manage Students
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Assignments Tab -->
            <div class="tab-pane fade" id="assignments" role="tabpanel">
                <?php if ($_SESSION['role'] === 'dowsen' && $class['teacher_id'] === $_SESSION['user_id']): ?>
                    <div class="mb-4">
                        <a href="create_assignment.php?class_id=<?= $classId ?>" class="btn btn-primary">
                            Create New Assignment
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (empty($assignments)): ?>
                    <div class="alert alert-info">No assignments have been created yet.</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card assignment-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($assignment['title']) ?></h5>
                                        <p class="text-muted mb-3">Due: <?= formatDate($assignment['due_date']) ?></p>
                                        <p class="card-text"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-info"><?= $assignment['submission_count'] ?> submissions</span>
                                            <a href="assignment.php?id=<?= $assignment['id'] ?>" class="btn btn-primary">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Students Tab (Teacher/Admin Only) -->
            <?php if ($_SESSION['role'] === 'dowsen' || $_SESSION['role'] === 'admin'): ?>
            <div class="tab-pane fade" id="students" role="tabpanel">
                <?php if (empty($students)): ?>
                    <div class="alert alert-info">No students enrolled in this class yet.</div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($student['name']) ?></td>
                                                <td><?= htmlspecialchars($student['email']) ?></td>
                                                <td><?= formatDate($student['enrolled_at']) ?></td>
                                                <td>
                                                    <a href="student_progress.php?class_id=<?= $classId ?>&student_id=<?= $student['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">View Progress</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
