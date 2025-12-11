<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Détails Facture';
$factureId = intval($_GET['id'] ?? 0);
if (!$factureId) { header('Location: index.php'); exit(); }
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT f.*, c.*, u.username FROM factures f LEFT JOIN clients c ON f.client_id = c.id LEFT JOIN users u ON f.user_id = u.id WHERE f.id = :id");
    $stmt->execute(['id' => $factureId]);
    $facture = $stmt->fetch();
    if (!$facture) { $_SESSION['error_message'] = 'Facture introuvable.'; header('Location: index.php'); exit(); }
    $stmt = $pdo->prepare("SELECT fd.*, p.nom_produit FROM factures_details fd LEFT JOIN produits p ON fd.produit_id = p.id WHERE fd.facture_id = :facture_id");
    $stmt->execute(['facture_id' => $factureId]);
    $details = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur.';
    header('Location: index.php'); exit();
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Facture #<?php echo htmlspecialchars($facture['numero_facture']); ?></h1></div><div><a href="index.php" class="btn btn-outline-secondary">Retour</a> <a href="pdf.php?id=<?php echo $factureId; ?>" class="btn btn-danger" target="_blank"><i class="bi bi-file-pdf me-2"></i>PDF</a></div></div>
<div class="row"><div class="col-md-8"><div class="card"><div class="card-body"><h5>Client</h5><p><?php echo htmlspecialchars($facture['type_client'] === 'entreprise' ? ($facture['nom_entreprise'] ?? $facture['nom']) : ($facture['nom'] . ' ' . ($facture['prenom'] ?? ''))); ?></p><h5>Produits</h5><table class="table"><thead><tr><th>Produit</th><th>Qté</th><th>Prix</th><th>Total</th></tr></thead><tbody><?php foreach ($details as $detail): ?><tr><td><?php echo htmlspecialchars($detail['nom_produit']); ?></td><td><?php echo $detail['quantite']; ?></td><td><?php echo number_format($detail['prix_unitaire'], 2); ?> DH</td><td><?php echo number_format($detail['sous_total'], 2); ?> DH</td></tr><?php endforeach; ?></tbody><tfoot><tr><th colspan="3">Total</th><th><?php echo number_format($facture['total'], 2); ?> DH</th></tr></tfoot></table></div></div></div></div>
<?php require_once '../includes/footer.php'; ?>





