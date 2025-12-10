<?php
/**
 * Supprimer un produit (soft delete)
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin();

$produitId = intval($_GET['id'] ?? 0);

if (!$produitId) {
    $_SESSION['error_message'] = 'ID produit invalide.';
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Vérifier si le produit existe
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = :id");
    $stmt->execute(['id' => $produitId]);
    $produit = $stmt->fetch();
    
    if (!$produit) {
        $_SESSION['error_message'] = 'Produit introuvable.';
        header('Location: index.php');
        exit();
    }
    
    // Vérifier s'il y a des bons liés
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bons_details WHERE produit_id = :produit_id");
    $stmt->execute(['produit_id' => $produitId]);
    $hasBons = $stmt->fetchColumn() > 0;
    
    if ($hasBons) {
        $_SESSION['error_message'] = 'Impossible de supprimer ce produit car il est utilisé dans des bons. Veuillez d\'abord supprimer les bons associés.';
        header('Location: view.php?id=' . $produitId);
        exit();
    }
    
    // Soft delete (marquer comme inactif)
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE produits SET actif = 0 WHERE id = :id");
    $stmt->execute(['id' => $produitId]);
    
    // Historique
    $user = getCurrentUser();
    $stmt = $pdo->prepare("
        INSERT INTO historique (user_id, produit_id, action, type_action, details, ip_address)
        VALUES (:user_id, :produit_id, :action, :type_action, :details, :ip_address)
    ");
    $stmt->execute([
        'user_id' => $user['id'],
        'produit_id' => $produitId,
        'action' => 'Suppression d\'un produit',
        'type_action' => 'suppression',
        'details' => "Produit supprimé: " . $produit['nom_produit'],
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Produit supprimé avec succès.';
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur suppression produit: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors de la suppression du produit.';
}

header('Location: index.php');
exit();

