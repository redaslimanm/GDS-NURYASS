<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
if (!isAdmin()) { $_SESSION['error_message'] = 'Accès refusé.'; header('Location: ' . url('dashboard.php')); exit(); }
$pageTitle = 'Nouvelle Couleur';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomCouleur = trim($_POST['nom_couleur'] ?? '');
    $codeCouleur = trim($_POST['code_couleur'] ?? '');
    if (empty($nomCouleur)) {
        $errors[] = 'Le nom est requis.';
    } else {
        try {
            $pdo = getDBConnection();
            $pdo->prepare("INSERT INTO couleurs (nom_couleur, code_couleur) VALUES (:nom, :code)")->execute(['nom' => $nomCouleur, 'code' => $codeCouleur ?: null]);
            $_SESSION['success_message'] = 'Couleur créée!';
            header('Location: index.php'); exit();
        } catch (PDOException $e) {
            error_log("Erreur: " . $e->getMessage());
            $errors[] = 'Erreur.';
        }
    }
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Nouvelle Couleur</h1></div><div><a href="index.php" class="btn btn-outline-secondary">Retour</a></div></div>
<div class="row"><div class="col-md-6 mx-auto"><div class="card"><div class="card-body"><?php if (!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="POST"><div class="mb-3"><label class="form-label">Nom <span class="text-danger">*</span></label><input type="text" class="form-control" name="nom_couleur" required></div><div class="mb-3"><label class="form-label">Code Couleur (Hex)</label><input type="color" class="form-control form-control-color" name="code_couleur" value="#000000"></div><div class="d-grid"><a href="index.php" class="btn btn-outline-secondary">Annuler</a><button type="submit" class="btn btn-primary mt-2">Créer</button></div></form></div></div></div></div>
<?php require_once '../includes/footer.php'; ?>

