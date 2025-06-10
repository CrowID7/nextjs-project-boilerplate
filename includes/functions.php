<?php
/**
 * Common utility functions for the Google Classroom Clone
 */

/**
 * Sanitize user input
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 * @param string $role Role to check (admin, dowsen, mahasiswa)
 * @return bool True if user has role, false otherwise
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Redirect to appropriate dashboard based on user role
 */
function redirectToDashboard() {
    if (!isLoggedIn()) {
        header('Location: /index.php');
        exit();
    }

    switch($_SESSION['role']) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'dowsen':
            header('Location: /teacher/dashboard.php');
            break;
        case 'mahasiswa':
            header('Location: /student/dashboard.php');
            break;
        default:
            header('Location: /index.php');
    }
    exit();
}

/**
 * Format date to readable format
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

/**
 * Generate random string
 * @param int $length Length of string
 * @return string Random string
 */
function generateRandomString($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Check if user has access to a specific class
 * @param PDO $pdo Database connection
 * @param int $classId Class ID
 * @param int $userId User ID
 * @param string $role User role
 * @return bool True if user has access, false otherwise
 */
function hasClassAccess($pdo, $classId, $userId, $role) {
    try {
        switch($role) {
            case 'admin':
                return true;
            case 'dowsen':
                $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
                $stmt->execute([$classId, $userId]);
                return (bool)$stmt->fetch();
            case 'mahasiswa':
                $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE class_id = ? AND student_id = ? AND status = 'active'");
                $stmt->execute([$classId, $userId]);
                return (bool)$stmt->fetch();
            default:
                return false;
        }
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get user's full name
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return string User's full name
 */
function getUserName($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? $result['name'] : 'Unknown User';
    } catch (PDOException $e) {
        return 'Unknown User';
    }
}

/**
 * Count students in a class
 * @param PDO $pdo Database connection
 * @param int $classId Class ID
 * @return int Number of students
 */
function getClassStudentCount($pdo, $classId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE class_id = ? AND status = 'active'");
        $stmt->execute([$classId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get class details
 * @param PDO $pdo Database connection
 * @param int $classId Class ID
 * @return array|false Class details or false if not found
 */
function getClassDetails($pdo, $classId) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.name as teacher_name 
            FROM classes c 
            JOIN users u ON c.teacher_id = u.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$classId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Log system activity
 * @param string $action Action performed
 * @param string $details Additional details
 * @param int $userId User ID who performed the action
 */
function logActivity($action, $details = '', $userId = null) {
    if (!$userId && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    $logMessage = date('Y-m-d H:i:s') . " | ";
    $logMessage .= "User: " . ($userId ?? 'System') . " | ";
    $logMessage .= "Action: " . $action . " | ";
    $logMessage .= "Details: " . $details . "\n";
    
    error_log($logMessage, 3, __DIR__ . '/../logs/system.log');
}

/**
 * Check if string contains only allowed characters
 * @param string $str String to check
 * @return bool True if string is valid, false otherwise
 */
function isValidInput($str) {
    return preg_match('/^[a-zA-Z0-9\s\-_.]+$/', $str);
}

/**
 * Create breadcrumb navigation
 * @param array $items Array of items (url => label)
 * @return string HTML breadcrumb navigation
 */
function createBreadcrumbs($items) {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    $last = count($items) - 1;
    $i = 0;
    
    foreach ($items as $url => $label) {
        if ($i === $last) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . sanitize($label) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . sanitize($url) . '">' . sanitize($label) . '</a></li>';
        }
        $i++;
    }
    
    $html .= '</ol></nav>';
    return $html;
}
