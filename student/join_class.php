<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student (mahasiswa)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    
    try {
        // Check if class exists
        $stmt = $pdo->prepare("SELECT id, name FROM classes WHERE code = ?");
        $stmt->execute([$code]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($class) {
            // Check if already enrolled
            $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE class_id = ? AND student_id = ?");
            $stmt->execute([$class['id'], $_SESSION['user_id']]);
            
            if (!$stmt->fetch()) {
                // Enroll in class
                $stmt = $pdo->prepare("INSERT INTO enrollments (class_id, student_id) VALUES (?, ?)");
                $stmt->execute([$class['id'], $_SESSION['user_id']]);
                header('Location: dashboard.php?success=joined');
                exit();
            } else {
                $error = "You are already enrolled in this class.";
            }
        } else {
            $error = "Invalid class code. Please check and try again.";
        }
    } catch (PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Class - Google Classroom Clone</title>
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
        .code-input {
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 1.2rem;
            text-align: center;
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
                    <a href="dashboard.php" class="nav-link text-dark">
                        My Classes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="join_class.php" class="nav-link active">
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
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card form-card">
                    <div class="card-header bg-white">
                        <h4 class="card-title mb-0">Join a Class</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="text-center mb-4">
                                <p class="text-muted">Ask your teacher for the class code, then enter it here.</p>
                            </div>

                            <div class="mb-4">
                                <label for="code" class="form-label">Class Code</label>
                                <input type="text" class="form-control code-input" id="code" name="code" 
                                    maxlength="6" placeholder="XXXXXX" required
                                    pattern="[A-Za-z0-9]{6}" 
                                    title="Class code should be 6 characters long">
                                <div class="form-text">
                                    Class code is 6 characters long and contains only letters and numbers
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Join Class</button>
                                <a href="dashboard.php" class="btn btn-light">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-capitalize input
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>
