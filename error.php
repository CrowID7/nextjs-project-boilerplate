<?php
session_start();
$error_message = isset($_GET['msg']) ? $_GET['msg'] : 'An unexpected error occurred.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Google Classroom Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            max-width: 500px;
            width: 90%;
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card error-card mx-auto">
            <div class="card-body text-center p-5">
                <div class="error-icon mb-4">⚠️</div>
                <h3 class="mb-4">Oops! Something went wrong</h3>
                <p class="text-muted mb-4"><?= htmlspecialchars($error_message) ?></p>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $dashboard_url = '';
                    switch($_SESSION['role']) {
                        case 'admin':
                            $dashboard_url = 'admin/dashboard.php';
                            break;
                        case 'dowsen':
                            $dashboard_url = 'teacher/dashboard.php';
                            break;
                        case 'mahasiswa':
                            $dashboard_url = 'student/dashboard.php';
                            break;
                        default:
                            $dashboard_url = 'index.php';
                    }
                    ?>
                    <a href="<?= $dashboard_url ?>" class="btn btn-primary">Return to Dashboard</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-primary">Return to Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
