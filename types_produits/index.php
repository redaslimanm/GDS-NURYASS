<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
if (!isAdmin()) { $_SESSION['error_message'] = 'Accès refusé.'; header('Location: ' . url('dashboard.php')); exit(); }
$pageTitle = 'Types de Produits';
try {
    $pdo = getDBConnection();
    $types = $pdo->query("SELECT tp.*, COUNT(p.id) as nb_produits FROM types_produits tp LEFT JOIN produits p ON tp.id = p.type_id GROUP BY tp.id ORDER BY tp.nom_type")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
    $types = [];
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Types de Produits</h1></div><div><a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nouveau Type</a></div></div>
<div class="card"><div class="card-body p-0"><?php if (empty($types)): ?><div class="text-center py-5"><p class="text-muted">Aucun type</p></div><?php else: ?><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>ID</th><th>Nom</th><th>Description</th><th>Produits</th><th>Actions</th></tr></thead><tbody><?php foreach ($types as $type): ?><tr><td>#<?php echo $type['id']; ?></td><td><strong><?php echo htmlspecialchars($type['nom_type']); ?></strong></td><td><?php echo htmlspecialchars($type['description'] ?? '-'); ?></td><td><span class="badge bg-info"><?php echo $type['nb_produits']; ?></span></td><td><a href="edit.php?id=<?php echo $type['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a> <a href="delete.php?id=<?php echo $type['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer?')"><i class="bi bi-trash"></i></a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
<?php require_once '../includes/footer.php'; ?>

