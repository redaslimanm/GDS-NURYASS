<?php
/**
 * Déconnexion
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

// Enregistrer la déconnexion dans l'historique si connecté
if (isLoggedIn()) {
    try {
        $pdo = getDBConnection();
        $user = getCurrentUser();
        
        $stmt = $pdo->prepare("
            INSERT INTO historique (user_id, action, type_action, details, ip_address)
            VALUES (:user_id, :action, :type_action, :details, :ip_address)
        ");
        
        $stmt->execute([
            'user_id' => $user['id'],
            'action' => 'Déconnexion du système',
            'type_action' => 'creation',
            'details' => 'Déconnexion - Utilisateur: ' . $user['username'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement de la déconnexion: " . $e->getMessage());
    }
}

// Détruire la session
destroySession();

// Rediriger vers la page de login
header('Location: ' . url('login.php') . '?logout=success');
exit();

