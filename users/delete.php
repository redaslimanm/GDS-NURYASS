<?php
/**
 * Supprimer un utilisateur
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');

if (!isAdmin()) {
    $_SESSION['error_message'] = 'Accès refusé.';
    header('Location: ' . url('dashboard.php'));
    exit();
}

$userId = intval($_GET['id'] ?? 0);

if (!$userId) {
    $_SESSION['error_message'] = 'ID utilisateur invalide.';
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error_message'] = 'Utilisateur introuvable.';
        header('Location: index.php');
        exit();
    }
    
    // Empêcher la suppression de son propre compte
    $currentUser = getCurrentUser();
    if ($userId == $currentUser['id']) {
        $_SESSION['error_message'] = 'Vous ne pouvez pas supprimer votre propre compte.';
        header('Location: index.php');
        exit();
    }
    
    // Supprimer définitivement l'utilisateur
    $pdo->beginTransaction();
    
    // Mettre à jour l'historique pour remplacer les références à cet utilisateur par NULL
    // (garder l'historique mais sans référence à l'utilisateur supprimé)
    $stmt = $pdo->prepare("UPDATE historique SET user_id = NULL WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    
    // Mettre à jour les bons pour remplacer les références
    $stmt = $pdo->prepare("UPDATE bons SET user_id = NULL WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    
    // Mettre à jour les factures pour remplacer les références
    $stmt = $pdo->prepare("UPDATE factures SET user_id = NULL WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    
    // Mettre à jour les transactions de crédit pour remplacer les références
    $stmt = $pdo->prepare("UPDATE credits_transactions SET user_id = NULL WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    
    // Supprimer l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    
    // Historique
    $stmt = $pdo->prepare("
        INSERT INTO historique (user_id, action, type_action, details, ip_address)
        VALUES (:user_id, :action, :type_action, :details, :ip_address)
    ");
    $stmt->execute([
        'user_id' => $currentUser['id'],
        'action' => 'Suppression définitive d\'un utilisateur',
        'type_action' => 'suppression',
        'details' => "Utilisateur supprimé définitivement: " . $user['username'] . " (ID: $userId)",
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Utilisateur supprimé définitivement avec succès.';
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur suppression user: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors de la suppression de l\'utilisateur.';
}

header('Location: index.php');
exit();

