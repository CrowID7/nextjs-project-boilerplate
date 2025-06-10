<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // Redirect based on role
            switch($user['role']) {
                case 'admin':
                    header('Location: ../admin/dashboard.php');
                    break;
                case 'dowsen':
                    header('Location: ../teacher/dashboard.php');
                    break;
                case 'mahasiswa':
                    header('Location: ../student/dashboard.php');
                    break;
            }
            exit();
        } else {
            header('Location: ../index.php?error=invalid_credentials');
            exit();
        }
    } catch(PDOException $e) {
        header('Location: ../index.php?error=server_error');
        exit();
    }
}
