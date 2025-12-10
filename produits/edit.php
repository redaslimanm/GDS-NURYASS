<?php
/**
 * Modifier un produit
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'Modifier Produit';

$errors = [];
$produit = null;
$produitId = intval($_GET['id'] ?? 0);

if (!$produitId) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = :id");
    $stmt->execute(['id' => $produitId]);
    $produit = $stmt->fetch();
    
    if (!$produit) {
        $_SESSION['error_message'] = 'Produit introuvable.';
        header('Location: index.php');
        exit();
    }
    
    // Récupérer les types et couleurs
    $types = $pdo->query("SELECT * FROM types_produits ORDER BY nom_type")->fetchAll();
    $couleurs = $pdo->query("SELECT * FROM couleurs ORDER BY nom_couleur")->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur edit produit: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors du chargement du produit.';
    header('Location: index.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeId = intval($_POST['type_id'] ?? 0);
    $couleurId = intval($_POST['couleur_id'] ?? 0);
    $nomProduit = trim($_POST['nom_produit'] ?? '');
    $prix = floatval($_POST['prix'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $stockMinimum = intval($_POST['stock_minimum'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if ($typeId <= 0) {
        $errors[] = 'Le type de produit est requis.';
    }
    
    if ($couleurId <= 0) {
        $errors[] = 'La couleur est requise.';
    }
    
    if (empty($nomProduit)) {
        $errors[] = 'Le nom du produit est requis.';
    }
    
    if ($prix <= 0) {
        $errors[] = 'Le prix doit être supérieur à 0.';
    }
    
    if ($stock < 0) {
        $errors[] = 'Le stock ne peut pas être négatif.';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Mettre à jour le produit
            $stmt = $pdo->prepare("
                UPDATE produits SET
                    type_id = :type_id,
                    couleur_id = :couleur_id,
                    nom_produit = :nom_produit,
                    prix = :prix,
                    stock = :stock,
                    stock_minimum = :stock_minimum,
                    description = :description
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $produitId,
                'type_id' => $typeId,
                'couleur_id' => $couleurId,
                'nom_produit' => $nomProduit,
                'prix' => $prix,
                'stock' => $stock,
                'stock_minimum' => $stockMinimum,
                'description' => $description ?: null
            ]);
            
            // Historique
            $user = getCurrentUser();
            $stmt = $pdo->prepare("
                INSERT INTO historique (user_id, produit_id, action, type_action, details, ip_address)
                VALUES (:user_id, :produit_id, :action, :type_action, :details, :ip_address)
            ");
            $stmt->execute([
                'user_id' => $user['id'],
                'produit_id' => $produitId,
                'action' => 'Modification d\'un produit',
                'type_action' => 'modification',
                'details' => "Produit modifié: $nomProduit (Stock: $stock)",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Produit modifié avec succès!';
            header('Location: view.php?id=' . $produitId);
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur modification produit: " . $e->getMessage());
            $errors[] = 'Une erreur est survenue lors de la modification.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Modifier Produit</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Produits</a></li>
                <li class="breadcrumb-item active" aria-current="page">Modifier</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="view.php?id=<?php echo $produitId; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informations du Produit</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="produitForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="type_id" class="form-label">Type de Produit <span class="text-danger">*</span></label>
                            <select class="form-select" id="type_id" name="type_id" required>
                                <option value="">Sélectionner un type</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo $produit['type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['nom_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="couleur_id" class="form-label">Couleur <span class="text-danger">*</span></label>
                            <select class="form-select" id="couleur_id" name="couleur_id" required>
                                <option value="">Sélectionner une couleur</option>
                                <?php foreach ($couleurs as $couleur): ?>
                                    <option value="<?php echo $couleur['id']; ?>" 
                                            <?php echo $produit['couleur_id'] == $couleur['id'] ? 'selected' : ''; ?>
                                            data-color="<?php echo htmlspecialchars($couleur['code_couleur'] ?? '#000'); ?>">
                                        <?php echo htmlspecialchars($couleur['nom_couleur']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nom_produit" class="form-label">Nom du Produit <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom_produit" name="nom_produit" value="<?php echo htmlspecialchars($produit['nom_produit']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($produit['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="prix" class="form-label">Prix (DH) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="prix" name="prix" step="0.01" min="0" value="<?php echo htmlspecialchars($produit['prix']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($produit['stock']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="stock_minimum" class="form-label">Stock Minimum</label>
                            <input type="number" class="form-control" id="stock_minimum" name="stock_minimum" min="0" value="<?php echo htmlspecialchars($produit['stock_minimum']); ?>">
                            <small class="form-text text-muted">Seuil d'alerte</small>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="view.php?id=<?php echo $produitId; ?>" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

