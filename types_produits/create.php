<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
if (!isAdmin()) { $_SESSION['error_message'] = 'Accès refusé.'; header('Location: ' . url('dashboard.php')); exit(); }
$pageTitle = 'Nouveau Type';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomType = trim($_POST['nom_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if (empty($nomType)) {
        $errors[] = 'Le nom est requis.';
    } else {
        try {
            $pdo = getDBConnection();
            $pdo->prepare("INSERT INTO types_produits (nom_type, description) VALUES (:nom, :desc)")->execute(['nom' => $nomType, 'desc' => $description ?: null]);
            $_SESSION['success_message'] = 'Type créé!';
            header('Location: index.php'); exit();
        } catch (PDOException $e) {
            error_log("Erreur: " . $e->getMessage());
            $errors[] = 'Erreur.';
        }
    }
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Nouveau Type</h1></div><div><a href="index.php" class="btn btn-outline-secondary">Retour</a></div></div>
<div class="row"><div class="col-md-6 mx-auto"><div class="card"><div class="card-body"><?php if (!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="POST"><div class="mb-3"><label class="form-label">Nom <span class="text-danger">*</span></label><input type="text" class="form-control" name="nom_type" required></div><div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea></div><div class="d-grid"><a href="index.php" class="btn btn-outline-secondary">Annuler</a><button type="submit" class="btn btn-primary mt-2">Créer</button></div></form></div></div></div></div>
<?php require_once '../includes/footer.php'; ?>

