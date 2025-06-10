<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a teacher (dowsen)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dowsen') {
    header('Location: ../index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    // Generate a unique class code
    function generateClassCode() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $code;
    }

    // Keep generating until we get a unique code
    do {
        $code = generateClassCode();
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetch());

    try {
        $stmt = $pdo->prepare("INSERT INTO classes (name, description, code, teacher_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $code, $_SESSION['user_id']]);
        header('Location: dashboard.php?success=1');
        exit();
    } catch (PDOException $e) {
        $error = "An error occurred while creating the class. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Class - Google Classroom Clone</title>
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
        .form-card {
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
            <h5 class="mb-4 px-3">Teacher Panel</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link text-dark">
                        My Classes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="create_class.php" class="nav-link active">
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card form-card">
                    <div class="card-header bg-white">
                        <h4 class="card-title mb-0">Create New Class</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Class Name</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                    placeholder="Enter class name (e.g., Mathematics 101)">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"
                                    placeholder="Enter class description, syllabus, or any important information"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Create Class</button>
                                <a href="dashboard.php" class="btn btn-light">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
