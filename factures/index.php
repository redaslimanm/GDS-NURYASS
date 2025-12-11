<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Gestion des Factures';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
try {
    $pdo = getDBConnection();
    $where = ["1=1"];
    $params = [];
    if (!empty($search)) {
        $where[] = "(f.numero_facture LIKE :search OR c.nom LIKE :search OR c.nom_entreprise LIKE :search)";
        $params['search'] = "%$search%";
    }
    $whereClause = implode(' AND ', $where);
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM factures f LEFT JOIN clients c ON f.client_id = c.id WHERE $whereClause");
    $countStmt->execute($params);
    $totalFactures = $countStmt->fetchColumn();
    $totalPages = ceil($totalFactures / $perPage);
    $stmt = $pdo->prepare("SELECT f.*, c.nom, c.prenom, c.nom_entreprise, c.type_client, u.username FROM factures f LEFT JOIN clients c ON f.client_id = c.id LEFT JOIN users u ON f.user_id = u.id WHERE $whereClause ORDER BY f.date_facture DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $value) $stmt->bindValue(":$key", $value);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $factures = $stmt->fetchAll();
    $totalVentes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM factures")->fetchColumn();
} catch (PDOException $e) {
    error_log("Erreur factures: " . $e->getMessage());
    $factures = [];
    $totalFactures = 0;
    $totalPages = 0;
    $totalVentes = 0;
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Gestion des Factures</h1></div><div><a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nouvelle Facture</a></div></div>
<div class="row g-3 mb-4"><div class="col-md-6"><div class="card bg-success text-white"><div class="card-body"><h6>Total Factures</h6><h3><?php echo number_format($totalFactures); ?></h3></div></div></div><div class="col-md-6"><div class="card bg-primary text-white"><div class="card-body"><h6>Total Ventes</h6><h3><?php echo number_format($totalVentes, 2); ?> DH</h3></div></div></div></div>
<div class="card mb-4"><div class="card-body"><form method="GET" class="row g-3"><div class="col-md-10"><input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>"></div><div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Rechercher</button></div></form></div></div>
<div class="card"><div class="card-body p-0"><?php if (empty($factures)): ?><div class="text-center py-5"><p class="text-muted">Aucune facture</p><a href="create.php" class="btn btn-primary">Créer la première</a></div><?php else: ?><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>N°</th><th>Client</th><th>Date</th><th>Total</th><th>Créée par</th><th>Actions</th></tr></thead><tbody><?php foreach ($factures as $facture): ?><tr><td><strong>#<?php echo htmlspecialchars($facture['numero_facture']); ?></strong></td><td><?php echo htmlspecialchars($facture['type_client'] === 'entreprise' ? ($facture['nom_entreprise'] ?? $facture['nom']) : ($facture['nom'] . ' ' . ($facture['prenom'] ?? ''))); ?></td><td><?php echo date('d/m/Y', strtotime($facture['date_facture'])); ?></td><td><strong><?php echo number_format($facture['total'], 2); ?> DH</strong></td><td><small><?php echo htmlspecialchars($facture['username'] ?? '-'); ?></small></td><td><a href="view.php?id=<?php echo $facture['id']; ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a> <a href="pdf.php?id=<?php echo $facture['id']; ?>" class="btn btn-sm btn-outline-danger" target="_blank"><i class="bi bi-file-pdf"></i></a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
<?php require_once '../includes/footer.php'; ?>





