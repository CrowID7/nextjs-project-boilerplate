<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a teacher (dowsen)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dowsen') {
    header('Location: ../index.php');
    exit();
}

// Fetch teacher's classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get student count for each class
$classStats = [];
foreach ($classes as $class) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE class_id = ? AND status = 'active'");
    $stmt->execute([$class['id']]);
    $classStats[$class['id']] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Google Classroom Clone</title>
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
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar col-md-3 col-lg-2">
        <div class="d-flex flex-column p-3">
            <h5 class="mb-4 px-3">Teacher Panel</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        My Classes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="create_class.php" class="nav-link text-dark">
                        Create Class
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
            <h2>My Classes</h2>
            <a href="create_class.php" class="btn btn-primary">Create New Class</a>
        </div>

        <?php if (empty($classes)): ?>
        <div class="alert alert-info">
            You haven't created any classes yet. Click "Create New Class" to get started.
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
                            <?= htmlspecialchars($class['description']) ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary"><?= $classStats[$class['id']] ?> Students</span>
                            <a href="class.php?id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-primary">View Class</a>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">Class Code: <?= htmlspecialchars($class['code']) ?></small>
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
