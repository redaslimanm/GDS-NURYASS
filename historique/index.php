<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Historique';
$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;
try {
    $pdo = getDBConnection();
    $where = ["1=1"];
    $params = [];
    if (!empty($search)) {
        $where[] = "h.action LIKE :search OR h.details LIKE :search";
        $params['search'] = "%$search%";
    }
    if (!empty($typeFilter)) {
        $where[] = "h.type_action = :type_action";
        $params['type_action'] = $typeFilter;
    }
    if (!empty($dateFrom)) {
        $where[] = "DATE(h.date_action) >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    if (!empty($dateTo)) {
        $where[] = "DATE(h.date_action) <= :date_to";
        $params['date_to'] = $dateTo;
    }
    $whereClause = implode(' AND ', $where);
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM historique h WHERE $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $perPage);
    $stmt = $pdo->prepare("SELECT h.*, u.username FROM historique h LEFT JOIN users u ON h.user_id = u.id WHERE $whereClause ORDER BY h.date_action DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $value) $stmt->bindValue(":$key", $value);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $historique = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur historique: " . $e->getMessage());
    $historique = [];
    $total = 0;
    $totalPages = 0;
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Historique des Opérations</h1></div></div>
<div class="card mb-4"><div class="card-body"><form method="GET" class="row g-3"><div class="col-md-4"><input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>"></div><div class="col-md-2"><select class="form-select" name="type"><option value="">Tous</option><option value="creation" <?php echo $typeFilter === 'creation' ? 'selected' : ''; ?>>Création</option><option value="modification" <?php echo $typeFilter === 'modification' ? 'selected' : ''; ?>>Modification</option><option value="suppression" <?php echo $typeFilter === 'suppression' ? 'selected' : ''; ?>>Suppression</option><option value="paiement" <?php echo $typeFilter === 'paiement' ? 'selected' : ''; ?>>Paiement</option></select></div><div class="col-md-2"><input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="Du"></div><div class="col-md-2"><input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="Au"></div><div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Filtrer</button></div></form></div></div>
<div class="card"><div class="card-body p-0"><?php if (empty($historique)): ?><div class="text-center py-5"><p class="text-muted">Aucun historique</p></div><?php else: ?><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Type</th><th>Détails</th></tr></thead><tbody><?php foreach ($historique as $h): ?><tr><td><?php echo date('d/m/Y H:i', strtotime($h['date_action'])); ?></td><td><?php echo htmlspecialchars($h['username'] ?? '-'); ?></td><td><?php echo htmlspecialchars($h['action']); ?></td><td><span class="badge bg-<?php echo $h['type_action'] === 'creation' ? 'success' : ($h['type_action'] === 'modification' ? 'warning' : ($h['type_action'] === 'suppression' ? 'danger' : 'info')); ?>"><?php echo htmlspecialchars($h['type_action']); ?></span></td><td><small><?php echo htmlspecialchars($h['details'] ?? '-'); ?></small></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
<?php require_once '../includes/footer.php'; ?>





