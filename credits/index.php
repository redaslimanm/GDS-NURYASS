<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Gestion des Crédits';
$search = $_GET['search'] ?? '';
$clientFilter = intval($_GET['client_id'] ?? 0);
try {
    $pdo = getDBConnection();
    $where = ["cr.statut = 'actif'"];
    $params = [];
    if (!empty($search)) {
        $where[] = "(c.nom LIKE :search OR c.nom_entreprise LIKE :search)";
        $params['search'] = "%$search%";
    }
    if ($clientFilter > 0) {
        $where[] = "cr.client_id = :client_id";
        $params['client_id'] = $clientFilter;
    }
    $whereClause = implode(' AND ', $where);
    $stmt = $pdo->prepare("SELECT cr.*, c.nom, c.prenom, c.nom_entreprise, c.type_client FROM credits cr LEFT JOIN clients c ON cr.client_id = c.id WHERE $whereClause ORDER BY cr.montant_actuel DESC");
    foreach ($params as $key => $value) $stmt->bindValue(":$key", $value);
    $stmt->execute();
    $credits = $stmt->fetchAll();
    $clients = $pdo->query("SELECT id, nom, prenom, nom_entreprise, type_client FROM clients WHERE actif = 1 ORDER BY nom")->fetchAll();
    $totalCredits = $pdo->query("SELECT COALESCE(SUM(montant_actuel), 0) FROM credits WHERE statut = 'actif'")->fetchColumn();
} catch (PDOException $e) {
    error_log("Erreur credits: " . $e->getMessage());
    $credits = [];
    $clients = [];
    $totalCredits = 0;
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Gestion des Crédits</h1></div></div>
<div class="row g-3 mb-4"><div class="col-md-4"><div class="card bg-danger text-white"><div class="card-body"><h6>Total Crédits</h6><h3><?php echo number_format($totalCredits, 2); ?> DH</h3></div></div></div><div class="col-md-4"><div class="card bg-warning text-white"><div class="card-body"><h6>Clients avec Crédit</h6><h3><?php echo count($credits); ?></h3></div></div></div></div>
<div class="card mb-4"><div class="card-body"><form method="GET" class="row g-3"><div class="col-md-8"><input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>"></div><div class="col-md-4"><button type="submit" class="btn btn-primary w-100">Rechercher</button></div></form></div></div>
<div class="card"><div class="card-body p-0"><?php if (empty($credits)): ?><div class="text-center py-5"><p class="text-muted">Aucun crédit actif</p></div><?php else: ?><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Client</th><th>Crédit Actuel</th><th>Maximum</th><th>%</th><th>Actions</th></tr></thead><tbody><?php foreach ($credits as $credit): $pourcentage = $credit['max_montant'] > 0 ? ($credit['montant_actuel'] / $credit['max_montant']) * 100 : 0; ?><tr><td><?php echo htmlspecialchars($credit['type_client'] === 'entreprise' ? ($credit['nom_entreprise'] ?? $credit['nom']) : ($credit['nom'] . ' ' . ($credit['prenom'] ?? ''))); ?></td><td><strong><?php echo number_format($credit['montant_actuel'], 2); ?> DH</strong></td><td><?php echo number_format($credit['max_montant'], 2); ?> DH</td><td><div class="progress" style="height: 20px;"><div class="progress-bar bg-<?php echo $pourcentage >= 80 ? 'danger' : ($pourcentage >= 50 ? 'warning' : 'success'); ?>" style="width: <?php echo min(100, $pourcentage); ?>%"><?php echo number_format($pourcentage, 1); ?>%</div></div></td><td><a href="view.php?id=<?php echo $credit['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a> <a href="paiement.php?id=<?php echo $credit['id']; ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-cash"></i></a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
<?php require_once '../includes/footer.php'; ?>

