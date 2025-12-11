<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Détails Bon';
$bonId = intval($_GET['id'] ?? 0);
if (!$bonId) { header('Location: index.php'); exit(); }
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT b.*, c.nom, c.prenom, c.nom_entreprise, c.type_client, c.telephone, c.adresse, u.username FROM bons b LEFT JOIN clients c ON b.client_id = c.id LEFT JOIN users u ON b.user_id = u.id WHERE b.id = :id");
    $stmt->execute(['id' => $bonId]);
    $bon = $stmt->fetch();
    if (!$bon) { $_SESSION['error_message'] = 'Bon introuvable.'; header('Location: index.php'); exit(); }
    $stmt = $pdo->prepare("SELECT bd.*, p.nom_produit, tp.nom_type, c.nom_couleur FROM bons_details bd LEFT JOIN produits p ON bd.produit_id = p.id LEFT JOIN types_produits tp ON p.type_id = tp.id LEFT JOIN couleurs c ON p.couleur_id = c.id WHERE bd.bon_id = :bon_id");
    $stmt->execute(['bon_id' => $bonId]);
    $details = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur view bon: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors du chargement.';
    header('Location: index.php'); exit();
}
require_once '../includes/header.php';
?>
<div class="top-bar">
    <div><h1 class="page-title">Bon #<?php echo htmlspecialchars($bon['numero_bon']); ?></h1></div>
    <div>
        <button onclick="window.print()" class="btn btn-primary me-2">
            <i class="bi bi-printer me-2"></i>Imprimer
        </button>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>
</div>
<!-- Zone imprimable -->
<div class="printable-area" id="printable-area">
    <div class="print-header">
        <div class="print-logo">
            <?php 
            $logoPath = url('images/logo.png');
            $logoExists = file_exists(__DIR__ . '/../images/logo.png');
            if ($logoExists): 
            ?>
                <img src="<?php echo $logoPath; ?>" alt="NURYASS Logo" class="logo-print">
            <?php else: ?>
                <div class="logo-placeholder">NURYASS</div>
            <?php endif; ?>
        </div>
        <div class="print-title">
            <h2>NURYASS</h2>
            <p class="print-subtitle">Bon <?php echo $bon['type_bon'] === 'entree' ? 'd\'Entrée' : 'de Sortie'; ?></p>
        </div>
    </div>
    
    <div class="print-info">
        <div class="print-info-row">
            <div class="print-info-col">
                <div class="info-box">
                    <h6>Informations du Bon</h6>
                    <p><strong>Numéro:</strong> <?php echo htmlspecialchars($bon['numero_bon']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($bon['date_bon'])); ?></p>
                    <p><strong>Type:</strong> <?php echo $bon['type_bon'] === 'entree' ? 'Entrée (Réception)' : 'Sortie (Vente/Usage)'; ?></p>
                    <p><strong>Statut:</strong> <?php echo $bon['statut_paiement'] === 'paye' ? 'Payé' : 'Non Payé'; ?></p>
                </div>
            </div>
            <div class="print-info-col">
                <div class="info-box">
                    <h6>Informations du Client</h6>
                    <p><strong>Nom:</strong> <?php echo htmlspecialchars($bon['type_client'] === 'entreprise' ? ($bon['nom_entreprise'] ?? $bon['nom']) : ($bon['nom'] . ' ' . ($bon['prenom'] ?? ''))); ?></p>
                    <?php if ($bon['telephone']): ?>
                        <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($bon['telephone']); ?></p>
                    <?php endif; ?>
                    <?php if ($bon['adresse']): ?>
                        <p><strong>Adresse:</strong> <?php echo htmlspecialchars($bon['adresse']); ?></p>
                    <?php endif; ?>
                    <?php if ($bon['details']): ?>
                        <p><strong>Détails:</strong> <?php echo nl2br(htmlspecialchars($bon['details'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="print-products">
        <h5>Détails des Produits</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Prix Unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $detail): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($detail['nom_produit'] . ' (' . $detail['nom_type'] . ' - ' . $detail['nom_couleur'] . ')'); ?></td>
                        <td class="text-center"><?php echo $detail['quantite']; ?></td>
                        <td class="text-end"><?php echo number_format($detail['prix_unitaire'], 2); ?> DH</td>
                        <td class="text-end"><?php echo number_format($detail['sous_total'], 2); ?> DH</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">TOTAL</th>
                    <th class="text-end"><?php echo number_format($bon['total'], 2); ?> DH</th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="print-signatures">
        <div class="signature-row">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p class="signature-label">Signature Client</p>
            </div>
            <div class="signature-box">
                <div class="cachet-box">
                    <div class="cachet-placeholder">
                        <span>Cachet</span>
                    </div>
                </div>
                <p class="signature-label">Cachet & Signature</p>
            </div>
        </div>
    </div>
    
    <div class="print-footer">
        <p class="text-center text-muted">
            <small>Bon généré le <?php echo date('d/m/Y à H:i'); ?> par <?php echo htmlspecialchars($bon['username'] ?? 'Système'); ?></small>
        </p>
    </div>
</div>

<!-- Affichage normal (non imprimable) -->
<div class="row no-print">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white"><h5>Informations</h5></div>
            <div class="card-body">
                <p><strong>Client:</strong> <?php echo htmlspecialchars($bon['type_client'] === 'entreprise' ? ($bon['nom_entreprise'] ?? $bon['nom']) : ($bon['nom'] . ' ' . ($bon['prenom'] ?? ''))); ?></p>
                <p><strong>Type:</strong> <span class="badge bg-<?php echo $bon['type_bon'] === 'entree' ? 'success' : 'primary'; ?>"><?php echo $bon['type_bon'] === 'entree' ? 'Entrée' : 'Sortie'; ?></span></p>
                <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($bon['date_bon'])); ?></p>
                <p><strong>Statut:</strong> <span class="badge bg-<?php echo $bon['statut_paiement'] === 'paye' ? 'success' : 'warning'; ?>"><?php echo $bon['statut_paiement'] === 'paye' ? 'Payé' : 'Non Payé'; ?></span></p>
                <?php if ($bon['details']): ?><p><strong>Détails:</strong> <?php echo nl2br(htmlspecialchars($bon['details'])); ?></p><?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white"><h5>Produits</h5></div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Produit</th><th>Quantité</th><th>Prix</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($details as $detail): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['nom_produit'] . ' (' . $detail['nom_type'] . ' - ' . $detail['nom_couleur'] . ')'); ?></td>
                                <td><?php echo $detail['quantite']; ?></td>
                                <td><?php echo number_format($detail['prix_unitaire'], 2); ?> DH</td>
                                <td><?php echo number_format($detail['sous_total'], 2); ?> DH</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><th colspan="3">Total</th><th><?php echo number_format($bon['total'], 2); ?> DH</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour l'impression */
@media print {
    body {
        background: white;
        font-family: 'Arial', 'Helvetica', sans-serif;
    }
    
    .sidebar,
    .top-bar,
    .no-print,
    .btn,
    nav,
    .breadcrumb {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    
    .printable-area {
        display: block !important;
        width: 100%;
        padding: 15mm;
        max-width: 210mm;
        margin: 0 auto;
    }
    
    .print-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 3px solid #667eea;
    }
    
    .print-logo {
        flex-shrink: 0;
    }
    
    .logo-print {
        max-width: 120px;
        max-height: 80px;
        width: auto;
        height: auto;
        object-fit: contain;
    }
    
    .logo-placeholder {
        font-size: 28px;
        font-weight: bold;
        color: #667eea;
    }
    
    .print-title {
        flex-grow: 1;
        text-align: center;
    }
    
    .print-title h2 {
        margin: 0;
        font-size: 32px;
        font-weight: bold;
        color: #667eea;
        letter-spacing: 2px;
    }
    
    .print-subtitle {
        margin: 8px 0 0 0;
        font-size: 18px;
        color: #333;
        font-weight: 600;
    }
    
    .print-info {
        margin-bottom: 25px;
    }
    
    .print-info-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .print-info-col {
        flex: 1;
    }
    
    .info-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }
    
    .info-box h6 {
        margin: 0 0 12px 0;
        font-size: 16px;
        font-weight: bold;
        color: #667eea;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .info-box p {
        margin-bottom: 8px;
        font-size: 13px;
        line-height: 1.6;
    }
    
    .info-box strong {
        color: #333;
        min-width: 100px;
        display: inline-block;
    }
    
    .print-products {
        margin-bottom: 30px;
    }
    
    .print-products h5 {
        margin-bottom: 15px;
        font-size: 18px;
        font-weight: bold;
        color: #333;
        padding-bottom: 8px;
        border-bottom: 2px solid #667eea;
    }
    
    .print-products table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    
    .print-products th,
    .print-products td {
        padding: 12px 8px;
        border: 1px solid #ddd;
        text-align: left;
        font-size: 13px;
    }
    
    .print-products th {
        background-color: #667eea;
        color: white;
        font-weight: bold;
        text-align: center;
    }
    
    .print-products td {
        background-color: #fff;
    }
    
    .print-products tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    
    .print-products tfoot th {
        background-color: #e9ecef;
        font-size: 16px;
        font-weight: bold;
        padding: 15px 8px;
    }
    
    .print-signatures {
        margin-top: 50px;
        margin-bottom: 30px;
    }
    
    .signature-row {
        display: flex;
        justify-content: space-between;
        gap: 40px;
        margin-top: 40px;
    }
    
    .signature-box {
        flex: 1;
        text-align: center;
    }
    
    .signature-line {
        width: 100%;
        height: 60px;
        border-bottom: 2px solid #333;
        margin-bottom: 8px;
    }
    
    .signature-label {
        margin: 0;
        font-size: 12px;
        font-weight: bold;
        color: #666;
        text-transform: uppercase;
    }
    
    .cachet-box {
        width: 100%;
        height: 80px;
        border: 2px dashed #999;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
        background-color: #fafafa;
    }
    
    .cachet-placeholder {
        color: #999;
        font-size: 14px;
        font-style: italic;
    }
    
    .print-footer {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
        text-align: center;
    }
    
    .print-footer p {
        margin: 0;
        font-size: 11px;
        color: #666;
    }
    
    @page {
        margin: 15mm;
        size: A4;
    }
}

/* Styles pour l'affichage à l'écran */
.printable-area {
    display: none;
}

.no-print {
    display: block;
}
</style>
<?php require_once '../includes/footer.php'; ?>


