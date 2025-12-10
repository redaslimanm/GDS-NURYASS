<?php
/**
 * Supprimer un client (soft delete)
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin();

$clientId = intval($_GET['id'] ?? 0);

if (!$clientId) {
    $_SESSION['error_message'] = 'ID client invalide.';
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Vérifier si le client existe
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
    $stmt->execute(['id' => $clientId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        $_SESSION['error_message'] = 'Client introuvable.';
        header('Location: index.php');
        exit();
    }
    
    // Vérifier s'il y a des crédits actifs
    $stmt = $pdo->prepare("SELECT montant_actuel FROM credits WHERE client_id = :client_id AND montant_actuel > 0");
    $stmt->execute(['client_id' => $clientId]);
    $hasCredit = $stmt->fetch();
    
    if ($hasCredit) {
        $_SESSION['error_message'] = 'Impossible de supprimer ce client car il a un crédit actif. Veuillez d\'abord régler le crédit.';
        header('Location: view.php?id=' . $clientId);
        exit();
    }
    
    // Soft delete (marquer comme inactif)
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE clients SET actif = 0 WHERE id = :id");
    $stmt->execute(['id' => $clientId]);
    
    // Historique
    $user = getCurrentUser();
    $stmt = $pdo->prepare("
        INSERT INTO historique (user_id, client_id, action, type_action, details, ip_address)
        VALUES (:user_id, :client_id, :action, :type_action, :details, :ip_address)
    ");
    $stmt->execute([
        'user_id' => $user['id'],
        'client_id' => $clientId,
        'action' => 'Suppression d\'un client',
        'type_action' => 'suppression',
        'details' => "Client supprimé: " . ($client['nom_entreprise'] ?? $client['nom']),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Client supprimé avec succès.';
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur suppression client: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors de la suppression du client.';
}

header('Location: index.php');
exit();

