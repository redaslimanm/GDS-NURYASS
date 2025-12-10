<?php
/**
 * Créer un nouveau client
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'Nouveau Client';

$errors = [];
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeClient = $_POST['type_client'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $cin = trim($_POST['cin'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $nomEntreprise = trim($_POST['nom_entreprise'] ?? '');
    $patente = trim($_POST['patente'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $creditMax = floatval($_POST['credit_max'] ?? 5000);
    
    // Validation
    if (empty($typeClient) || !in_array($typeClient, ['personne', 'entreprise'])) {
        $errors[] = 'Type de client invalide.';
    }
    
    if (empty($nom)) {
        $errors[] = 'Le nom est requis.';
    }
    
    if (empty($adresse)) {
        $errors[] = 'L\'adresse est requise.';
    }
    
    if ($typeClient === 'entreprise') {
        if (empty($nomEntreprise)) {
            $errors[] = 'Le nom de l\'entreprise est requis pour une entreprise.';
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $pdo->beginTransaction();
            
            // Insérer le client
            $stmt = $pdo->prepare("
                INSERT INTO clients (
                    type_client, nom, prenom, cin, adresse, 
                    nom_entreprise, patente, telephone, email, credit_max
                ) VALUES (
                    :type_client, :nom, :prenom, :cin, :adresse,
                    :nom_entreprise, :patente, :telephone, :email, :credit_max
                )
            ");
            
            $stmt->execute([
                'type_client' => $typeClient,
                'nom' => $nom,
                'prenom' => $typeClient === 'personne' ? ($prenom ?: null) : null,
                'cin' => $typeClient === 'personne' ? ($cin ?: null) : null,
                'adresse' => $adresse,
                'nom_entreprise' => $typeClient === 'entreprise' ? ($nomEntreprise ?: null) : null,
                'patente' => $typeClient === 'entreprise' ? ($patente ?: null) : null,
                'telephone' => $telephone ?: null,
                'email' => $email ?: null,
                'credit_max' => $creditMax
            ]);
            
            $clientId = $pdo->lastInsertId();
            
            // Créer l'enregistrement de crédit pour ce client
            $stmt = $pdo->prepare("
                INSERT INTO credits (client_id, montant_actuel, max_montant, statut)
                VALUES (:client_id, 0, :max_montant, 'actif')
            ");
            $stmt->execute([
                'client_id' => $clientId,
                'max_montant' => $creditMax
            ]);
            
            // Enregistrer dans l'historique
            $user = getCurrentUser();
            $stmt = $pdo->prepare("
                INSERT INTO historique (user_id, client_id, action, type_action, details, ip_address)
                VALUES (:user_id, :client_id, :action, :type_action, :details, :ip_address)
            ");
            $stmt->execute([
                'user_id' => $user['id'],
                'client_id' => $clientId,
                'action' => 'Création d\'un nouveau client',
                'type_action' => 'creation',
                'details' => "Client créé: $nom" . ($typeClient === 'entreprise' ? " ($nomEntreprise)" : ""),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Client créé avec succès!';
            header('Location: index.php');
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur création client: " . $e->getMessage());
            $errors[] = 'Une erreur est survenue lors de la création du client.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Nouveau Client</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Clients</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nouveau</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informations du Client</h5>
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
                
                <form method="POST" id="clientForm">
                    <!-- Type de client -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Type de Client <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-check-card">
                                    <input class="form-check-input" type="radio" name="type_client" id="type_personne" value="personne" checked onchange="toggleClientType()">
                                    <label class="form-check-label w-100 p-3 border rounded" for="type_personne">
                                        <i class="bi bi-person fs-3 d-block mb-2"></i>
                                        <strong>Personne</strong>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-check-card">
                                    <input class="form-check-input" type="radio" name="type_client" id="type_entreprise" value="entreprise" onchange="toggleClientType()">
                                    <label class="form-check-label w-100 p-3 border rounded" for="type_entreprise">
                                        <i class="bi bi-building fs-3 d-block mb-2"></i>
                                        <strong>Entreprise</strong>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Champs pour Personne -->
                    <div id="personne_fields">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cin" class="form-label">CIN</label>
                            <input type="text" class="form-control" id="cin" name="cin" maxlength="20">
                        </div>
                    </div>
                    
                    <!-- Champs pour Entreprise -->
                    <div id="entreprise_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="nom_entreprise" class="form-label">Nom de l'Entreprise <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom_entreprise" name="nom_entreprise">
                        </div>
                        
                        <div class="mb-3">
                            <label for="patente" class="form-label">Numéro de Patente</label>
                            <input type="text" class="form-control" id="patente" name="patente" maxlength="50">
                        </div>
                    </div>
                    
                    <!-- Champs communs -->
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="2" required></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="credit_max" class="form-label">Crédit Maximum Autorisé (DH)</label>
                        <input type="number" class="form-control" id="credit_max" name="credit_max" value="5000" min="0" step="0.01">
                        <small class="form-text text-muted">Montant maximum de crédit autorisé pour ce client</small>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Créer le Client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleClientType() {
    const typePersonne = document.getElementById('type_personne').checked;
    const personneFields = document.getElementById('personne_fields');
    const entrepriseFields = document.getElementById('entreprise_fields');
    const nomEntreprise = document.getElementById('nom_entreprise');
    
    if (typePersonne) {
        personneFields.style.display = 'block';
        entrepriseFields.style.display = 'none';
        nomEntreprise.removeAttribute('required');
    } else {
        personneFields.style.display = 'none';
        entrepriseFields.style.display = 'block';
        nomEntreprise.setAttribute('required', 'required');
    }
}

// Initialiser au chargement
document.addEventListener('DOMContentLoaded', toggleClientType);
</script>

<style>
.form-check-card {
    position: relative;
}

.form-check-card .form-check-input {
    position: absolute;
    top: 10px;
    right: 10px;
}

.form-check-card .form-check-label {
    cursor: pointer;
    transition: all 0.3s;
}

.form-check-card .form-check-input:checked + .form-check-label {
    background: rgba(102, 126, 234, 0.1);
    border-color: #667eea !important;
}
</style>

<?php require_once '../includes/footer.php'; ?>

