<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
if (!isAdmin()) { $_SESSION['error_message'] = 'Accès refusé.'; header('Location: ' . url('dashboard.php')); exit(); }
$pageTitle = 'Couleurs';
try {
    $pdo = getDBConnection();
    $couleurs = $pdo->query("SELECT c.*, COUNT(p.id) as nb_produits FROM couleurs c LEFT JOIN produits p ON c.id = p.couleur_id GROUP BY c.id ORDER BY c.nom_couleur")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
    $couleurs = [];
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Couleurs</h1></div><div><a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nouvelle Couleur</a></div></div>
<div class="card"><div class="card-body p-0"><?php if (empty($couleurs)): ?><div class="text-center py-5"><p class="text-muted">Aucune couleur</p></div><?php else: ?><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>ID</th><th>Couleur</th><th>Code</th><th>Produits</th><th>Actions</th></tr></thead><tbody><?php foreach ($couleurs as $couleur): ?><tr><td>#<?php echo $couleur['id']; ?></td><td><strong><?php echo htmlspecialchars($couleur['nom_couleur']); ?></strong></td><td><span class="badge" style="background-color: <?php echo htmlspecialchars($couleur['code_couleur'] ?? '#000'); ?>; color: white;"><?php echo htmlspecialchars($couleur['code_couleur'] ?? '-'); ?></span></td><td><span class="badge bg-info"><?php echo $couleur['nb_produits']; ?></span></td><td><a href="edit.php?id=<?php echo $couleur['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a> <a href="delete.php?id=<?php echo $couleur['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer?')"><i class="bi bi-trash"></i></a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
<?php require_once '../includes/footer.php'; ?>

