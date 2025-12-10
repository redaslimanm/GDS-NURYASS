<?php
/**
 * Voir les détails d'un client
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'Détails Client';

$clientId = intval($_GET['id'] ?? 0);

if (!$clientId) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Récupérer le client avec crédit
    $stmt = $pdo->prepare("
        SELECT c.*, cr.montant_actuel, cr.max_montant, cr.statut as credit_statut
        FROM clients c
        LEFT JOIN credits cr ON c.id = cr.client_id
        WHERE c.id = :id
    ");
    $stmt->execute(['id' => $clientId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        $_SESSION['error_message'] = 'Client introuvable.';
        header('Location: index.php');
        exit();
    }
    
    // Récupérer les bons du client
    $stmt = $pdo->prepare("
        SELECT * FROM bons 
        WHERE client_id = :client_id 
        ORDER BY date_bon DESC 
        LIMIT 10
    ");
    $stmt->execute(['client_id' => $clientId]);
    $bons = $stmt->fetchAll();
    
    // Récupérer les factures du client
    $stmt = $pdo->prepare("
        SELECT * FROM factures 
        WHERE client_id = :client_id 
        ORDER BY date_facture DESC 
        LIMIT 10
    ");
    $stmt->execute(['client_id' => $clientId]);
    $factures = $stmt->fetchAll();
    
    // Récupérer l'historique des transactions de crédit
    $stmt = $pdo->prepare("
        SELECT ct.*, u.username
        FROM credits_transactions ct
        LEFT JOIN users u ON ct.user_id = u.id
        WHERE ct.client_id = :client_id
        ORDER BY ct.date_transaction DESC
        LIMIT 10
    ");
    $stmt->execute(['client_id' => $clientId]);
    $transactions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur view client: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors du chargement des données.';
    header('Location: index.php');
    exit();
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">
            <?php 
            if ($client['type_client'] === 'entreprise') {
                echo htmlspecialchars($client['nom_entreprise'] ?? $client['nom']);
            } else {
                echo htmlspecialchars($client['nom'] . ' ' . ($client['prenom'] ?? ''));
            }
            ?>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Clients</a></li>
                <li class="breadcrumb-item active" aria-current="page">Détails</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="edit.php?id=<?php echo $clientId; ?>" class="btn btn-primary me-2">
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
                <h5 class="mb-0">Informations du Client</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Type:</strong>
                        <span class="badge bg-<?php echo $client['type_client'] === 'entreprise' ? 'info' : 'primary'; ?> ms-2">
                            <?php echo $client['type_client'] === 'entreprise' ? 'Entreprise' : 'Personne'; ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>ID:</strong> #<?php echo $client['id']; ?>
                    </div>
                </div>
                
                <hr>
                
                <?php if ($client['type_client'] === 'personne'): ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Nom:</strong> <?php echo htmlspecialchars($client['nom']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Prénom:</strong> <?php echo htmlspecialchars($client['prenom'] ?? '-'); ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>CIN:</strong> <?php echo htmlspecialchars($client['cin'] ?? '-'); ?>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <strong>Nom de l'entreprise:</strong> <?php echo htmlspecialchars($client['nom_entreprise'] ?? '-'); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Patente:</strong> <?php echo htmlspecialchars($client['patente'] ?? '-'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <strong>Adresse:</strong><br>
                    <?php echo nl2br(htmlspecialchars($client['adresse'])); ?>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Téléphone:</strong> 
                        <?php if ($client['telephone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>">
                                <?php echo htmlspecialchars($client['telephone']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Email:</strong> 
                        <?php if ($client['email']): ?>
                            <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>">
                                <?php echo htmlspecialchars($client['email']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Date de création:</strong> 
                    <?php echo date('d/m/Y à H:i', strtotime($client['date_creation'])); ?>
                </div>
            </div>
        </div>
        
        <!-- Bons récents -->
        <div class="card mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Derniers Bons</h5>
                <a href="<?php echo url('bons/index.php?client_id=' . $clientId); ?>" class="btn btn-sm btn-outline-primary">
                    Voir tout
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($bons)): ?>
                    <p class="text-muted text-center py-3">Aucun bon enregistré</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bons as $bon): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($bon['numero_bon']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $bon['type_bon'] === 'entree' ? 'success' : 'primary'; ?>">
                                                <?php echo $bon['type_bon'] === 'entree' ? 'Entrée' : 'Sortie'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($bon['date_bon'])); ?></td>
                                        <td><?php echo number_format($bon['total'], 2); ?> DH</td>
                                        <td>
                                            <span class="badge bg-<?php echo $bon['statut_paiement'] === 'paye' ? 'success' : 'warning'; ?>">
                                                <?php echo $bon['statut_paiement'] === 'paye' ? 'Payé' : 'Non Payé'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Crédit -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Crédit</h5>
            </div>
            <div class="card-body">
                <?php 
                $creditActuel = floatval($client['montant_actuel'] ?? 0);
                $creditMax = floatval($client['max_montant'] ?? 5000);
                $pourcentage = $creditMax > 0 ? ($creditActuel / $creditMax) * 100 : 0;
                ?>
                <div class="text-center mb-3">
                    <h2 class="text-<?php echo $pourcentage >= 80 ? 'danger' : ($pourcentage >= 50 ? 'warning' : 'success'); ?>">
                        <?php echo number_format($creditActuel, 2); ?> DH
                    </h2>
                    <small class="text-muted">sur <?php echo number_format($creditMax, 2); ?> DH</small>
                </div>
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar bg-<?php echo $pourcentage >= 80 ? 'danger' : ($pourcentage >= 50 ? 'warning' : 'success'); ?>" 
                         style="width: <?php echo min(100, $pourcentage); ?>%"></div>
                </div>
                <div class="text-center">
                    <span class="badge bg-<?php echo $client['credit_statut'] === 'actif' ? 'success' : 'secondary'; ?>">
                        <?php echo $client['credit_statut'] === 'actif' ? 'Actif' : 'Bloqué'; ?>
                    </span>
                </div>
                <div class="mt-3">
                    <a href="<?php echo url('credits/index.php?client_id=' . $clientId); ?>" class="btn btn-outline-primary w-100">
                        <i class="bi bi-credit-card me-2"></i>Gérer le crédit
                    </a>
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
                    <a href="<?php echo url('bons/create.php?client_id=' . $clientId); ?>" class="btn btn-outline-primary">
                        <i class="bi bi-receipt me-2"></i>Nouveau Bon
                    </a>
                    <a href="<?php echo url('factures/create.php?client_id=' . $clientId); ?>" class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-text me-2"></i>Nouvelle Facture
                    </a>
                    <a href="edit.php?id=<?php echo $clientId; ?>" class="btn btn-outline-info">
                        <i class="bi bi-pencil me-2"></i>Modifier
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

