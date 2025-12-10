<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Détails Bon';
$bonId = intval($_GET['id'] ?? 0);
if (!$bonId) { header('Location: index.php'); exit(); }
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT b.*, c.nom, c.prenom, c.nom_entreprise, c.type_client, u.username FROM bons b LEFT JOIN clients c ON b.client_id = c.id LEFT JOIN users u ON b.user_id = u.id WHERE b.id = :id");
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
    <div><a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour</a></div>
</div>
<div class="row">
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
<?php require_once '../includes/footer.php'; ?>

