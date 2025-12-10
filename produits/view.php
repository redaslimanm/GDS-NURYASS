<?php
/**
 * Voir les détails d'un produit
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'Détails Produit';

$produitId = intval($_GET['id'] ?? 0);

if (!$produitId) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Récupérer le produit avec type et couleur
    $stmt = $pdo->prepare("
        SELECT p.*, 
               tp.nom_type,
               c.nom_couleur,
               c.code_couleur
        FROM produits p
        LEFT JOIN types_produits tp ON p.type_id = tp.id
        LEFT JOIN couleurs c ON p.couleur_id = c.id
        WHERE p.id = :id
    ");
    $stmt->execute(['id' => $produitId]);
    $produit = $stmt->fetch();
    
    if (!$produit) {
        $_SESSION['error_message'] = 'Produit introuvable.';
        header('Location: index.php');
        exit();
    }
    
    // Statistiques du produit
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bons_details bd JOIN bons b ON bd.bon_id = b.id WHERE bd.produit_id = :id AND b.type_bon = 'entree'");
    $stmt->execute(['id' => $produitId]);
    $bonsEntree = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bons_details bd JOIN bons b ON bd.bon_id = b.id WHERE bd.produit_id = :id AND b.type_bon = 'sortie'");
    $stmt->execute(['id' => $produitId]);
    $bonsSortie = $stmt->fetchColumn();
    
    $stats = [
        'valeur_stock' => $produit['prix'] * $produit['stock'],
        'bons_entree' => $bonsEntree,
        'bons_sortie' => $bonsSortie
    ];
    
} catch (PDOException $e) {
    error_log("Erreur view produit: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors du chargement des données.';
    header('Location: index.php');
    exit();
}

$stockPourcentage = $produit['stock_minimum'] > 0 ? ($produit['stock'] / $produit['stock_minimum']) * 100 : 100;
$stockClass = $produit['stock'] == 0 ? 'danger' : ($produit['stock'] <= $produit['stock_minimum'] ? 'warning' : 'success');

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title"><?php echo htmlspecialchars($produit['nom_produit']); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Produits</a></li>
                <li class="breadcrumb-item active" aria-current="page">Détails</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="edit.php?id=<?php echo $produitId; ?>" class="btn btn-primary me-2">
            <i class="bi bi-pencil me-2"></i>Modifier
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Informations principales -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informations du Produit</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>ID:</strong> #<?php echo $produit['id']; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Statut:</strong>
                        <span class="badge bg-<?php echo $produit['actif'] ? 'success' : 'secondary'; ?>">
                            <?php echo $produit['actif'] ? 'Actif' : 'Inactif'; ?>
                        </span>
                    </div>
                </div>
                
                <hr>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Type:</strong>
                        <span class="badge bg-info ms-2">
                            <?php echo htmlspecialchars($produit['nom_type'] ?? '-'); ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Couleur:</strong>
                        <span class="badge ms-2" style="background-color: <?php echo htmlspecialchars($produit['code_couleur'] ?? '#6c757d'); ?>; color: white;">
                            <?php echo htmlspecialchars($produit['nom_couleur'] ?? '-'); ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Nom:</strong> <?php echo htmlspecialchars($produit['nom_produit']); ?>
                </div>
                
                <?php if ($produit['description']): ?>
                    <div class="mb-3">
                        <strong>Description:</strong><br>
                        <?php echo nl2br(htmlspecialchars($produit['description'])); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Prix:</strong>
                        <h4 class="text-primary mb-0"><?php echo number_format($produit['prix'], 2); ?> DH</h4>
                    </div>
                    <div class="col-md-4">
                        <strong>Stock:</strong>
                        <h4 class="text-<?php echo $stockClass; ?> mb-0">
                            <?php echo number_format($produit['stock']); ?>
                        </h4>
                        <?php if ($produit['stock_minimum'] > 0): ?>
                            <small class="text-muted">Minimum: <?php echo $produit['stock_minimum']; ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Valeur Stock:</strong>
                        <h4 class="text-success mb-0">
                            <?php echo number_format($stats['valeur_stock'], 2); ?> DH
                        </h4>
                    </div>
                </div>
                
                <?php if ($produit['stock'] <= $produit['stock_minimum']): ?>
                    <div class="alert alert-<?php echo $produit['stock'] == 0 ? 'danger' : 'warning'; ?>">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php if ($produit['stock'] == 0): ?>
                            <strong>Rupture de stock!</strong> Le stock est à zéro.
                        <?php else: ?>
                            <strong>Stock faible!</strong> Le stock est en dessous du minimum (<?php echo $produit['stock_minimum']; ?>).
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($produit['stock_minimum'] > 0): ?>
                    <div class="mb-3">
                        <strong>Niveau de stock:</strong>
                        <div class="progress mt-2" style="height: 20px;">
                            <div class="progress-bar bg-<?php echo $stockClass; ?>" 
                                 style="width: <?php echo min(100, $stockPourcentage); ?>%">
                                <?php echo number_format($stockPourcentage, 1); ?>%
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <strong>Date de création:</strong> 
                    <?php echo date('d/m/Y à H:i', strtotime($produit['date_creation'])); ?>
                </div>
                
                <?php if ($produit['date_modification'] && $produit['date_modification'] != $produit['date_creation']): ?>
                    <div class="mb-3">
                        <strong>Dernière modification:</strong> 
                        <?php echo date('d/m/Y à H:i', strtotime($produit['date_modification'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Statistiques -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Statistiques</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Valeur du stock:</strong>
                    <h4 class="text-success"><?php echo number_format($stats['valeur_stock'], 2); ?> DH</h4>
                </div>
                <hr>
                <div class="mb-2">
                    <small class="text-muted">Bons d'entrée:</small>
                    <div class="fw-bold"><?php echo $stats['bons_entree']; ?></div>
                </div>
                <div>
                    <small class="text-muted">Bons de sortie:</small>
                    <div class="fw-bold"><?php echo $stats['bons_sortie']; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Actions rapides -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="edit.php?id=<?php echo $produitId; ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-2"></i>Modifier
                    </a>
                    <a href="<?php echo url('bons/create.php?produit_id=' . $produitId); ?>" class="btn btn-outline-info">
                        <i class="bi bi-receipt me-2"></i>Créer un Bon
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

