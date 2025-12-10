<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
if (!isAdmin()) { $_SESSION['error_message'] = 'Accès refusé.'; header('Location: ' . url('dashboard.php')); exit(); }
$pageTitle = 'Gestion des Utilisateurs';
try {
    $pdo = getDBConnection();
    $users = $pdo->query("SELECT * FROM users ORDER BY date_creation DESC")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur users: " . $e->getMessage());
    $users = [];
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Gestion des Utilisateurs</h1></div><div><a href="create.php" class="btn btn-primary"><i class="bi bi-person-plus me-2"></i>Nouvel Utilisateur</a></div></div>
<div class="card"><div class="card-body p-0"><?php if (empty($users)): ?><div class="text-center py-5"><p class="text-muted">Aucun utilisateur</p></div><?php else: ?><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>ID</th><th>Username</th><th>Rôle</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td>#<?php echo $user['id']; ?></td><td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td><td><span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>"><?php echo htmlspecialchars($user['role']); ?></span></td><td><span class="badge bg-<?php echo $user['actif'] ? 'success' : 'secondary'; ?>"><?php echo $user['actif'] ? 'Actif' : 'Inactif'; ?></span></td><td><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></td><td><a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a> <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer?')"><i class="bi bi-trash"></i></a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
<?php require_once '../includes/footer.php'; ?>

