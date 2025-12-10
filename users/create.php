<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
if (!isAdmin()) { $_SESSION['error_message'] = 'Accès refusé.'; header('Location: ' . url('dashboard.php')); exit(); }
$pageTitle = 'Nouvel Utilisateur';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'caissier';
    if (empty($username) || empty($password)) {
        $errors[] = 'Tous les champs sont requis.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            if ($stmt->fetch()) {
                $errors[] = 'Ce nom d\'utilisateur existe déjà.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)")->execute(['username' => $username, 'password' => $hashedPassword, 'role' => $role]);
                $_SESSION['success_message'] = 'Utilisateur créé!';
                header('Location: index.php'); exit();
            }
        } catch (PDOException $e) {
            error_log("Erreur: " . $e->getMessage());
            $errors[] = 'Erreur lors de la création.';
        }
    }
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Nouvel Utilisateur</h1></div><div><a href="index.php" class="btn btn-outline-secondary">Retour</a></div></div>
<div class="row"><div class="col-md-6 mx-auto"><div class="card"><div class="card-body"><?php if (!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="POST"><div class="mb-3"><label class="form-label">Username <span class="text-danger">*</span></label><input type="text" class="form-control" name="username" required></div><div class="mb-3"><label class="form-label">Password <span class="text-danger">*</span></label><input type="password" class="form-control" name="password" required></div><div class="mb-3"><label class="form-label">Rôle</label><select class="form-select" name="role"><option value="caissier">Caissier</option><option value="admin">Admin</option></select></div><div class="d-grid"><a href="index.php" class="btn btn-outline-secondary">Annuler</a><button type="submit" class="btn btn-primary mt-2">Créer</button></div></form></div></div></div></div>
<?php require_once '../includes/footer.php'; ?>

