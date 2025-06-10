<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a teacher
if (!isLoggedIn() || !hasRole('dowsen')) {
    header('Location: ../error.php?msg=Access denied');
    exit();
}

// Get class ID from URL
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Verify teacher has access to this class
if (!hasClassAccess($pdo, $classId, $_SESSION['user_id'], 'dowsen')) {
    header('Location: ../error.php?msg=Access denied or class not found');
    exit();
}

// Get class details
$class = getClassDetails($pdo, $classId);
if (!$class) {
    header('Location: ../error.php?msg=Class not found');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $dueDate = $_POST['due_date'];
    
    $errors = [];
    
    // Validate input
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($dueDate)) {
        $errors[] = "Due date is required";
    } elseif (strtotime($dueDate) < time()) {
        $errors[] = "Due date cannot be in the past";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO assignments (class_id, title, description, due_date) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$classId, $title, $description, $dueDate]);
            
            // Log activity
            logActivity('create_assignment', "Created assignment: $title for class: {$class['name']}", $_SESSION['user_id']);
            
            header("Location: ../class/view.php?id=$classId&tab=assignments&success=1");
            exit();
        } catch (PDOException $e) {
            $errors[] = "An error occurred while creating the assignment";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment - <?= htmlspecialchars($class['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .form-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .class-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Class Header -->
    <div class="class-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><?= htmlspecialchars($class['name']) ?></h4>
                    <p class="mb-0 opacity-75">Create New Assignment</p>
                </div>
                <a href="../class/view.php?id=<?= $classId ?>" class="btn btn-light">Back to Class</a>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card form-card">
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="title" class="form-label">Assignment Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
                                       required maxlength="200">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="6"
                                          placeholder="Enter assignment instructions, requirements, and any additional information"
                                          ><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="datetime-local" class="form-control" id="due_date" name="due_date"
                                       value="<?= isset($_POST['due_date']) ? $_POST['due_date'] : '' ?>"
                                       required>
                                <div class="form-text">
                                    Set the deadline for this assignment
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Create Assignment</button>
                                <a href="../class/view.php?id=<?= $classId ?>" class="btn btn-light">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today
        const dueDateInput = document.getElementById('due_date');
        const today = new Date();
        today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
        dueDateInput.min = today.toISOString().slice(0, 16);
    </script>
</body>
</html>
