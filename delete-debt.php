<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $debt = get_debt_by_id($pdo, $id);
        
        if ($debt) {
            $stmt = $pdo->prepare("DELETE FROM payments WHERE debt_id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            
            $stmt = $pdo->prepare("DELETE FROM debts WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
        }
        
        header('Location: index.php?success=deleted');
    } catch (PDOException $e) {
        header('Location: index.php?error=delete_failed');
    }
} else {
    header('Location: index.php');
}
exit;
?>