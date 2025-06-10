<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student (mahasiswa)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../index.php');
    exit();
}

// Fetch student's enrolled classes
$stmt = $pdo->prepare("
    SELECT c.*, u.name as teacher_name, 
    (SELECT COUNT(*) FROM assignments WHERE class_id = c.id) as assignment_count
    FROM classes c
    JOIN enrollments e ON c.id = e.class_id
    JOIN users u ON c.teacher_id = u.id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent assignments
$stmt = $pdo->prepare("
    SELECT a.*, c.name as class_name, 
    (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id AND student_id = ?) as submitted
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN enrollments e ON c.id = e.class_id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY a.due_date ASC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Google Classroom Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 48px 0;
            background: #fff;
            border-right: 1px solid #dee2e6;
        }
        .main-content {
            margin-left: 240px;
            padding: 48px;
        }
        .class-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .class-card:hover {
            transform: translateY(-5px);
        }
        .class-header {
            height: 100px;
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border-radius: 10px 10px 0 0;
        }
        .assignment-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar col-md-3 col-lg-2">
        <div class="d-flex flex-column p-3">
            <h5 class="mb-4 px-3">Student Panel</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        My Classes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="join_class.php" class="nav-link text-dark">
                        Join Class
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <a href="../auth/logout.php" class="nav-link text-danger">
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>
            <a href="join_class.php" class="btn btn-primary">Join New Class</a>
        </div>

        <!-- Upcoming Assignments -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card assignment-card">
                    <div class="card-header">
                        <h5 class="mb-0">Upcoming Assignments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignments)): ?>
                            <p class="text-muted mb-0">No upcoming assignments.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Assignment</th>
                                            <th>Class</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($assignment['title']) ?></td>
                                            <td><?= htmlspecialchars($assignment['class_name']) ?></td>
                                            <td><?= date('M d, Y', strtotime($assignment['due_date'])) ?></td>
                                            <td>
                                                <?php if ($assignment['submitted']): ?>
                                                    <span class="badge bg-success">Submitted</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view_assignment.php?id=<?= $assignment['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrolled Classes -->
        <h4 class="mb-3">My Classes</h4>
        <?php if (empty($classes)): ?>
        <div class="alert alert-info">
            You haven't joined any classes yet. Use a class code to join a class.
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($classes as $class): ?>
            <div class="col-md-4 mb-4">
                <div class="card class-card">
                    <div class="class-header"></div>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($class['name']) ?></h5>
                        <p class="card-text text-muted">
                            Teacher: <?= htmlspecialchars($class['teacher_name']) ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-info"><?= $class['assignment_count'] ?> Assignments</span>
                            <a href="class.php?id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-primary">View Class</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
