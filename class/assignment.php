<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Get assignment ID from URL
$assignmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get assignment details
$stmt = $pdo->prepare("
    SELECT a.*, c.id as class_id, c.name as class_name, c.teacher_id,
    u.name as teacher_name
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN users u ON c.teacher_id = u.id
    WHERE a.id = ?
");
$stmt->execute([$assignmentId]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    header('Location: ../error.php?msg=Assignment not found');
    exit();
}

// Check if user has access to this assignment's class
if (!hasClassAccess($pdo, $assignment['class_id'], $_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: ../error.php?msg=Access denied');
    exit();
}

// Get submissions if teacher
$submissions = [];
if ($_SESSION['role'] === 'dowsen' && $assignment['teacher_id'] === $_SESSION['user_id']) {
    $stmt = $pdo->prepare("
        SELECT s.*, u.name as student_name
        FROM submissions s
        JOIN users u ON s.student_id = u.id
        WHERE s.assignment_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$assignmentId]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get student's submission if student
$studentSubmission = null;
if ($_SESSION['role'] === 'mahasiswa') {
    $stmt = $pdo->prepare("
        SELECT * FROM submissions 
        WHERE assignment_id = ? AND student_id = ?
    ");
    $stmt->execute([$assignmentId, $_SESSION['user_id']]);
    $studentSubmission = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle submission upload for students
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'mahasiswa') {
    if (isset($_POST['content'])) {
        try {
            $content = trim($_POST['content']);
            
            if ($studentSubmission) {
                // Update existing submission
                $stmt = $pdo->prepare("
                    UPDATE submissions 
                    SET content = ?, submitted_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$content, $studentSubmission['id']]);
            } else {
                // Create new submission
                $stmt = $pdo->prepare("
                    INSERT INTO submissions (assignment_id, student_id, content) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$assignmentId, $_SESSION['user_id'], $content]);
            }
            
            header("Location: assignment.php?id=$assignmentId&success=1");
            exit();
        } catch (PDOException $e) {
            $error = "An error occurred while submitting your work";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($assignment['title']) ?> - Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .assignment-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .submission-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Assignment Header -->
    <div class="assignment-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1"><?= htmlspecialchars($assignment['title']) ?></h4>
                    <p class="mb-0">
                        <?= htmlspecialchars($assignment['class_name']) ?> • 
                        Due <?= formatDate($assignment['due_date']) ?>
                    </p>
                </div>
                <a href="view.php?id=<?= $assignment['class_id'] ?>" class="btn btn-light">Back to Class</a>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-md-8">
                <!-- Assignment Details -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Assignment Instructions</h5>
                        <p class="card-text">
                            <?= nl2br(htmlspecialchars($assignment['description'])) ?>
                        </p>
                    </div>
                </div>

                <?php if ($_SESSION['role'] === 'mahasiswa'): ?>
                    <!-- Student Submission Form -->
                    <div class="card submission-card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Your Submission</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($_GET['success'])): ?>
                                <div class="alert alert-success">
                                    Your work has been submitted successfully!
                                </div>
                            <?php endif; ?>

                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="content" class="form-label">Your Work</label>
                                    <textarea class="form-control" id="content" name="content" rows="8" required><?= $studentSubmission ? htmlspecialchars($studentSubmission['content']) : '' ?></textarea>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <?php if ($studentSubmission): ?>
                                        <small class="text-muted">
                                            Last submitted: <?= formatDate($studentSubmission['submitted_at']) ?>
                                        </small>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-primary">
                                        <?= $studentSubmission ? 'Update Submission' : 'Submit Assignment' ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'dowsen' && $assignment['teacher_id'] === $_SESSION['user_id']): ?>
                    <!-- Teacher View of Submissions -->
                    <div class="card submission-card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Student Submissions</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($submissions)): ?>
                                <p class="text-muted mb-0">No submissions yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($submissions as $submission): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($submission['student_name']) ?></td>
                                                    <td><?= formatDate($submission['submitted_at']) ?></td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#submissionModal<?= $submission['id'] ?>">
                                                            View Submission
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Submission Modals -->
                                <?php foreach ($submissions as $submission): ?>
                                    <div class="modal fade" id="submissionModal<?= $submission['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        Submission by <?= htmlspecialchars($submission['student_name']) ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-muted">
                                                        Submitted: <?= formatDate($submission['submitted_at']) ?>
                                                    </p>
                                                    <div class="border rounded p-3 bg-light">
                                                        <?= nl2br(htmlspecialchars($submission['content'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Assignment Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Assignment Details</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <strong>Due Date:</strong><br>
                                <?= formatDate($assignment['due_date']) ?>
                            </li>
                            <li class="mb-2">
                                <strong>Teacher:</strong><br>
                                <?= htmlspecialchars($assignment['teacher_name']) ?>
                            </li>
                            <li>
                                <strong>Class:</strong><br>
                                <?= htmlspecialchars($assignment['class_name']) ?>
                            </li>
                        </ul>
                    </div>
                </div>

                <?php if ($_SESSION['role'] === 'dowsen' && $assignment['teacher_id'] === $_SESSION['user_id']): ?>
                    <!-- Submission Stats -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Submission Statistics</h5>
                            <?php
                            $totalStudents = getClassStudentCount($pdo, $assignment['class_id']);
                            $submissionCount = count($submissions);
                            $submissionRate = $totalStudents > 0 ? ($submissionCount / $totalStudents) * 100 : 0;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Submission Rate</span>
                                    <span><?= number_format($submissionRate, 1) ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $submissionRate ?>%"></div>
                                </div>
                            </div>
                            <ul class="list-unstyled mb-0">
                                <li>Total Students: <?= $totalStudents ?></li>
                                <li>Submissions: <?= $submissionCount ?></li>
                                <li>Pending: <?= $totalStudents - $submissionCount ?></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
