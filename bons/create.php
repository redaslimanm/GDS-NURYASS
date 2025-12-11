<?php
/**
 * Créer un nouveau bon
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');

$pageTitle = 'Nouveau Bon';

$errors = [];
$clientId = intval($_GET['client_id'] ?? 0);

try {
    $pdo = getDBConnection();
    $clients = $pdo->query("SELECT id, nom, prenom, nom_entreprise, type_client FROM clients WHERE actif = 1 ORDER BY nom")->fetchAll();
    $produits = $pdo->query("SELECT p.*, tp.nom_type, c.nom_couleur FROM produits p LEFT JOIN types_produits tp ON p.type_id = tp.id LEFT JOIN couleurs c ON p.couleur_id = c.id WHERE p.actif = 1 ORDER BY p.nom_produit")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur chargement données: " . $e->getMessage());
    $clients = [];
    $produits = [];
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientType = $_POST['client_type'] ?? 'existant'; // 'existant' ou 'nouveau'
    $clientId = intval($_POST['client_id'] ?? 0);
    $typeBon = $_POST['type_bon'] ?? '';
    $statutPaiement = $_POST['statut_paiement'] ?? 'non_paye';
    $details = trim($_POST['details'] ?? '');
    $produitsData = $_POST['produits'] ?? [];
    
    // Si nouveau client, créer le client d'abord
    if ($clientType === 'nouveau') {
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
        
        // Validation nouveau client
        if (empty($typeClient) || !in_array($typeClient, ['personne', 'entreprise'])) {
            $errors[] = 'Type de client invalide.';
        }
        
        if (empty($adresse)) {
            $errors[] = 'L\'adresse est requise.';
        }
        
        if ($typeClient === 'personne') {
            if (empty($nom)) {
                $errors[] = 'Le nom est requis pour une personne.';
            }
        }
        
        if ($typeClient === 'entreprise') {
            if (empty($nomEntreprise)) {
                $errors[] = 'Le nom de l\'entreprise est requis.';
            }
            if (empty($nom)) {
                $nom = $nomEntreprise;
            }
        }
        
        // Créer le nouveau client si pas d'erreurs
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
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
                
                $pdo->commit();
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Erreur création client: " . $e->getMessage());
                $errors[] = 'Erreur lors de la création du client.';
            }
        }
    }
    
    // Validation
    if ($clientType === 'existant' && $clientId <= 0) {
        $errors[] = 'Veuillez sélectionner un client existant.';
    }
    
    if ($clientType === 'nouveau' && $clientId <= 0) {
        $errors[] = 'Erreur lors de la création du client. Veuillez réessayer.';
    }
    
    if (empty($typeBon) || !in_array($typeBon, ['entree', 'sortie'])) {
        $errors[] = 'Le type de bon est requis.';
    }
    
    if (empty($produitsData)) {
        $errors[] = 'Au moins un produit est requis.';
    }
    
    // Valider les produits
    $hasValidProduct = false;
    foreach ($produitsData as $prod) {
        $produitId = intval($prod['produit_id'] ?? 0);
        $quantite = intval($prod['quantite'] ?? 0);
        $prix = floatval($prod['prix'] ?? 0);
        if ($produitId > 0 && $quantite > 0 && $prix > 0) {
            $hasValidProduct = true;
            break;
        }
    }
    
    if (!$hasValidProduct) {
        $errors[] = 'Au moins un produit avec quantité et prix valides est requis.';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $user = getCurrentUser();
            
            // Générer le numéro de bon
            $stmt = $pdo->query("SELECT COUNT(*) FROM bons WHERE DATE(date_bon) = CURDATE()");
            $countToday = $stmt->fetchColumn();
            $numeroBon = 'BON-' . date('Ymd') . '-' . str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);
            
            // Calculer le total
            $total = 0;
            foreach ($produitsData as $prod) {
                $quantite = intval($prod['quantite'] ?? 0);
                $prix = floatval($prod['prix'] ?? 0);
                $total += $quantite * $prix;
            }
            
            // Insérer le bon
            $stmt = $pdo->prepare("
                INSERT INTO bons (numero_bon, client_id, user_id, type_bon, statut_paiement, total, details)
                VALUES (:numero_bon, :client_id, :user_id, :type_bon, :statut_paiement, :total, :details)
            ");
            $stmt->execute([
                'numero_bon' => $numeroBon,
                'client_id' => $clientId,
                'user_id' => $user['id'],
                'type_bon' => $typeBon,
                'statut_paiement' => $statutPaiement,
                'total' => $total,
                'details' => $details ?: null
            ]);
            
            $bonId = $pdo->lastInsertId();
            
            // Insérer les détails du bon
            foreach ($produitsData as $prod) {
                $produitId = intval($prod['produit_id'] ?? 0);
                $quantite = intval($prod['quantite'] ?? 0);
                $prixUnitaire = floatval($prod['prix'] ?? 0);
                
                if ($produitId > 0 && $quantite > 0) {
                    $stmt = $pdo->prepare("
                        INSERT INTO bons_details (bon_id, produit_id, quantite, prix_unitaire, sous_total)
                        VALUES (:bon_id, :produit_id, :quantite, :prix_unitaire, :sous_total)
                    ");
                    $stmt->execute([
                        'bon_id' => $bonId,
                        'produit_id' => $produitId,
                        'quantite' => $quantite,
                        'prix_unitaire' => $prixUnitaire,
                        'sous_total' => $quantite * $prixUnitaire
                    ]);
                    
                    // Mettre à jour le stock
                    if ($typeBon === 'entree') {
                        $stmt = $pdo->prepare("UPDATE produits SET stock = stock + :quantite WHERE id = :id");
                    } else {
                        $stmt = $pdo->prepare("UPDATE produits SET stock = GREATEST(0, stock - :quantite) WHERE id = :id");
                    }
                    $stmt->execute(['quantite' => $quantite, 'id' => $produitId]);
                }
            }
            
            // Si non payé, créer/mettre à jour le crédit
            if ($statutPaiement === 'non_paye' && $typeBon === 'sortie') {
                // Vérifier si crédit existe
                $stmt = $pdo->prepare("SELECT id FROM credits WHERE client_id = :client_id");
                $stmt->execute(['client_id' => $clientId]);
                $credit = $stmt->fetch();
                
                if ($credit) {
                    $stmt = $pdo->prepare("UPDATE credits SET montant_actuel = montant_actuel + :montant WHERE client_id = :client_id");
                    $stmt->execute(['montant' => $total, 'client_id' => $clientId]);
                    $creditId = $credit['id'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO credits (client_id, montant_actuel, max_montant) VALUES (:client_id, :montant, 5000)");
                    $stmt->execute(['client_id' => $clientId, 'montant' => $total]);
                    $creditId = $pdo->lastInsertId();
                }
                
                // Enregistrer la transaction
                $stmt = $pdo->prepare("SELECT montant_actuel FROM credits WHERE id = :id");
                $stmt->execute(['id' => $creditId]);
                $montantApres = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("
                    INSERT INTO credits_transactions (credit_id, client_id, bon_id, type_transaction, montant, montant_avant, montant_apres, user_id, details)
                    VALUES (:credit_id, :client_id, :bon_id, 'ajout', :montant, :montant_avant, :montant_apres, :user_id, :details)
                ");
                $stmt->execute([
                    'credit_id' => $creditId,
                    'client_id' => $clientId,
                    'bon_id' => $bonId,
                    'montant' => $total,
                    'montant_avant' => $montantApres - $total,
                    'montant_apres' => $montantApres,
                    'user_id' => $user['id'],
                    'details' => "Bon non payé: $numeroBon"
                ]);
            }
            
            // Historique
            $stmt = $pdo->prepare("
                INSERT INTO historique (user_id, client_id, bon_id, action, type_action, details, ip_address)
                VALUES (:user_id, :client_id, :bon_id, :action, :type_action, :details, :ip_address)
            ");
            $stmt->execute([
                'user_id' => $user['id'],
                'client_id' => $clientId,
                'bon_id' => $bonId,
                'action' => 'Création d\'un bon',
                'type_action' => 'creation',
                'details' => "Bon créé: $numeroBon (Type: $typeBon, Total: $total DH)",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Bon créé avec succès!';
            header('Location: view.php?id=' . $bonId);
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur création bon: " . $e->getMessage());
            $errors[] = 'Une erreur est survenue lors de la création du bon.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Nouveau Bon</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Bons</a></li>
                <li class="breadcrumb-item active">Nouveau</li>
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
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informations du Bon</h5>
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
                
                <form method="POST" id="bonForm">
                    <!-- Choix du type de client -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Sélectionner le Client <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-check-card">
                                    <input class="form-check-input" type="radio" name="client_type" id="client_existant" value="existant" checked onchange="toggleClientSelection()">
                                    <label class="form-check-label w-100 p-3 border rounded" for="client_existant">
                                        <i class="bi bi-person-check fs-3 d-block mb-2"></i>
                                        <strong>Client Existant</strong>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-check-card">
                                    <input class="form-check-input" type="radio" name="client_type" id="client_nouveau" value="nouveau" onchange="toggleClientSelection()">
                                    <label class="form-check-label w-100 p-3 border rounded" for="client_nouveau">
                                        <i class="bi bi-person-plus fs-3 d-block mb-2"></i>
                                        <strong>Nouveau Client</strong>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Client existant -->
                    <div id="client-existant-section">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
                                <select class="form-select" id="client_id" name="client_id">
                                    <option value="">Sélectionner un client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id']; ?>" <?php echo $clientId == $client['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($client['type_client'] === 'entreprise' ? ($client['nom_entreprise'] ?? $client['nom']) : ($client['nom'] . ' ' . ($client['prenom'] ?? ''))); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Nouveau client -->
                    <div id="client-nouveau-section" style="display: none;">
                        <h5 class="mb-3">Informations du Nouveau Client</h5>
                        
                        <!-- Type de client -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Type de Client <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-check-card">
                                        <input class="form-check-input" type="radio" name="type_client" id="type_personne" value="personne" checked onchange="toggleNewClientType()">
                                        <label class="form-check-label w-100 p-3 border rounded" for="type_personne">
                                            <i class="bi bi-person fs-3 d-block mb-2"></i>
                                            <strong>Personne</strong>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-check-card">
                                        <input class="form-check-input" type="radio" name="type_client" id="type_entreprise" value="entreprise" onchange="toggleNewClientType()">
                                        <label class="form-check-label w-100 p-3 border rounded" for="type_entreprise">
                                            <i class="bi bi-building fs-3 d-block mb-2"></i>
                                            <strong>Entreprise</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Champs pour Personne -->
                        <div id="personne_fields">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom" name="nom">
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
                            <textarea class="form-control" id="adresse" name="adresse" rows="2"></textarea>
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
                        </div>
                        
                        <hr>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="type_bon" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="type_bon" name="type_bon" required>
                                <option value="">Sélectionner</option>
                                <option value="entree">Entrée (Réception)</option>
                                <option value="sortie">Sortie (Vente/Usage)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="statut_paiement" class="form-label">Statut Paiement</label>
                            <select class="form-select form-select-lg" id="statut_paiement" name="statut_paiement">
                                <option value="paye">Payé</option>
                                <option value="non_paye" selected>Non Payé</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="details" class="form-label">Détails / Notes</label>
                        <textarea class="form-control" id="details" name="details" rows="2"></textarea>
                    </div>
                    
                    <hr>
                    
                    <h5>Produits</h5>
                    <div id="produits-container">
                        <div class="produit-row mb-3 p-3 border rounded">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">Produit <span class="text-danger">*</span></label>
                                    <select class="form-select produit-select" name="produits[0][produit_id]" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($produits as $produit): ?>
                                            <option value="<?php echo $produit['id']; ?>" 
                                                    data-prix="<?php echo $produit['prix']; ?>"
                                                    data-stock="<?php echo $produit['stock']; ?>">
                                                <?php echo htmlspecialchars($produit['nom_produit'] . ' (' . $produit['nom_type'] . ' - ' . $produit['nom_couleur'] . ') - Stock: ' . $produit['stock']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Quantité <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control quantite-input" name="produits[0][quantite]" min="1" value="1" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Prix Unitaire</label>
                                    <input type="number" class="form-control prix-input" name="produits[0][prix]" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Sous-total</label>
                                    <input type="text" class="form-control sous-total" readonly>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-danger w-100 remove-produit" style="display: none;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-outline-primary mb-3" id="add-produit">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter un produit
                    </button>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Total: <span id="total-bon" class="text-primary">0.00</span> DH</h4>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary" id="submit-bon-btn">
                                <i class="bi bi-check-circle me-2"></i>Créer le Bon
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let produitIndex = 1;

document.getElementById('add-produit').addEventListener('click', function() {
    const container = document.getElementById('produits-container');
    const newRow = container.firstElementChild.cloneNode(true);
    
    // Mettre à jour les noms des champs
    newRow.querySelectorAll('select, input').forEach(input => {
        if (input.name) {
            input.name = input.name.replace('[0]', '[' + produitIndex + ']');
        }
        if (input.value && input.type !== 'number') {
            input.value = '';
        }
        if (input.type === 'number' && !input.classList.contains('sous-total')) {
            input.value = input.name.includes('quantite') ? '1' : '';
        }
    });
    
    newRow.querySelector('.sous-total').value = '';
    newRow.querySelector('.remove-produit').style.display = 'block';
    
    container.appendChild(newRow);
    produitIndex++;
    
    // Attacher les événements
    attachProduitEvents(newRow);
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-produit')) {
        const row = e.target.closest('.produit-row');
        if (document.querySelectorAll('.produit-row').length > 1) {
            row.remove();
            calculateTotal();
        }
    }
});

function attachProduitEvents(row) {
    const select = row.querySelector('.produit-select');
    const quantite = row.querySelector('.quantite-input');
    const prix = row.querySelector('.prix-input');
    const sousTotal = row.querySelector('.sous-total');
    
    select.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            prix.value = selectedOption.dataset.prix;
            calculateSousTotal(row);
        }
    });
    
    quantite.addEventListener('input', () => calculateSousTotal(row));
    prix.addEventListener('input', () => calculateSousTotal(row));
}

function calculateSousTotal(row) {
    const quantite = parseFloat(row.querySelector('.quantite-input').value) || 0;
    const prix = parseFloat(row.querySelector('.prix-input').value) || 0;
    const sousTotal = quantite * prix;
    row.querySelector('.sous-total').value = sousTotal.toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.sous-total').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('total-bon').textContent = total.toFixed(2);
}

// Attacher les événements au premier produit
attachProduitEvents(document.querySelector('.produit-row'));

// Afficher le bouton supprimer s'il y a plus d'un produit
if (document.querySelectorAll('.produit-row').length > 1) {
    document.querySelectorAll('.remove-produit').forEach(btn => btn.style.display = 'block');
}

// Toggle entre client existant et nouveau client
function toggleClientSelection() {
    const clientExistant = document.getElementById('client_existant');
    const clientExistantSection = document.getElementById('client-existant-section');
    const clientNouveauSection = document.getElementById('client-nouveau-section');
    const clientSelect = document.getElementById('client_id');
    
    if (!clientExistant || !clientExistantSection || !clientNouveauSection || !clientSelect) {
        console.error('Erreur: Éléments non trouvés dans toggleClientSelection');
        return;
    }
    
    const isExistant = clientExistant.checked;
    
    if (isExistant) {
        clientExistantSection.style.display = 'block';
        clientNouveauSection.style.display = 'none';
        clientSelect.setAttribute('required', 'required');
        console.log('✓ Mode: Client Existant - required ajouté');
        // Désactiver les champs requis du nouveau client
        document.querySelectorAll('#client-nouveau-section input[required], #client-nouveau-section textarea[required]').forEach(el => {
            el.removeAttribute('required');
        });
    } else {
        clientExistantSection.style.display = 'none';
        clientNouveauSection.style.display = 'block';
        clientSelect.removeAttribute('required');
        console.log('✓ Mode: Nouveau Client - required retiré');
        // Activer les champs requis du nouveau client
        const typePersonne = document.getElementById('type_personne');
        if (typePersonne && typePersonne.checked) {
            const nomField = document.getElementById('nom');
            if (nomField) nomField.setAttribute('required', 'required');
        } else {
            const nomEntrepriseField = document.getElementById('nom_entreprise');
            if (nomEntrepriseField) nomEntrepriseField.setAttribute('required', 'required');
        }
        const adresseField = document.getElementById('adresse');
        if (adresseField) adresseField.setAttribute('required', 'required');
    }
}

// Toggle entre personne et entreprise pour nouveau client
function toggleNewClientType() {
    const typePersonne = document.getElementById('type_personne').checked;
    const personneFields = document.getElementById('personne_fields');
    const entrepriseFields = document.getElementById('entreprise_fields');
    const nom = document.getElementById('nom');
    const nomEntreprise = document.getElementById('nom_entreprise');
    
    if (typePersonne) {
        personneFields.style.display = 'block';
        entrepriseFields.style.display = 'none';
        if (nomEntreprise) nomEntreprise.removeAttribute('required');
        if (nom) nom.setAttribute('required', 'required');
    } else {
        personneFields.style.display = 'none';
        entrepriseFields.style.display = 'block';
        if (nomEntreprise) nomEntreprise.setAttribute('required', 'required');
        if (nom) nom.removeAttribute('required');
    }
}

// Validation du formulaire avant soumission
document.addEventListener('DOMContentLoaded', function() {
    const bonForm = document.getElementById('bonForm');
    if (!bonForm) {
        console.error('ERREUR: Formulaire bonForm non trouvé!');
        return;
    }
    
    console.log('✓ Formulaire trouvé, ajout de l\'événement submit...');
    
    bonForm.addEventListener('submit', function(e) {
        console.log('=== SOUMISSION DU FORMULAIRE ===');
        
        try {
            const clientTypeRadio = document.querySelector('input[name="client_type"]:checked');
            if (!clientTypeRadio) {
                console.error('Aucun type de client sélectionné');
                e.preventDefault();
                alert('Veuillez sélectionner le type de client (Existant ou Nouveau).');
                return false;
            }
            
            const clientType = clientTypeRadio.value;
            console.log('Type de client:', clientType);
            
            // Vérifier le type de bon
            const typeBon = document.getElementById('type_bon');
            if (!typeBon || !typeBon.value) {
                console.error('Type de bon non sélectionné');
                e.preventDefault();
                alert('Veuillez sélectionner le type de bon.');
                if (typeBon) typeBon.focus();
                return false;
            }
            console.log('Type de bon:', typeBon.value);
            
            // Vérifier les produits
            let hasValidProduct = false;
            const produits = document.querySelectorAll('.produit-select');
            console.log('Nombre de produits:', produits.length);
            
            produits.forEach((select, index) => {
                if (select.value && select.value !== '') {
                    const row = select.closest('.produit-row');
                    if (row) {
                        const quantiteInput = row.querySelector('.quantite-input');
                        const prixInput = row.querySelector('.prix-input');
                        if (quantiteInput && prixInput) {
                            const quantite = parseFloat(quantiteInput.value) || 0;
                            const prix = parseFloat(prixInput.value) || 0;
                            console.log(`Produit ${index + 1}: quantite=${quantite}, prix=${prix}`);
                            if (quantite > 0 && prix > 0) {
                                hasValidProduct = true;
                            }
                        }
                    }
                }
            });
            
            if (!hasValidProduct) {
                console.error('Aucun produit valide trouvé');
                e.preventDefault();
                alert('Veuillez ajouter au moins un produit avec quantité et prix valides.');
                return false;
            }
            console.log('✓ Produits valides:', hasValidProduct);
            
            // Vérifier le client selon le type
            if (clientType === 'existant') {
                const clientSelect = document.getElementById('client_id');
                if (!clientSelect) {
                    console.error('ERREUR: Champ client_id non trouvé dans le DOM');
                    // Ne pas bloquer, laisser le serveur gérer
                    console.log('Champ non trouvé, soumission autorisée pour validation serveur');
                    return true;
                }
                
                const clientId = clientSelect.value ? clientSelect.value.trim() : '';
                console.log('Client ID sélectionné:', clientId);
                
                if (!clientId || clientId === '' || clientId === '0') {
                    console.error('Aucun client sélectionné');
                    e.preventDefault();
                    alert('Veuillez sélectionner un client existant.');
                    clientSelect.focus();
                    return false;
                }
                console.log('✓ Client validé:', clientId);
            } else {
                // Vérifier les champs du nouveau client
                const typeClientRadio = document.querySelector('input[name="type_client"]:checked');
                if (!typeClientRadio) {
                    e.preventDefault();
                    alert('Veuillez sélectionner le type de client (Personne ou Entreprise).');
                    return false;
                }
                
                const typeClient = typeClientRadio.value;
                const adresse = document.getElementById('adresse');
                
                if (!adresse || !adresse.value.trim()) {
                    e.preventDefault();
                    alert('L\'adresse est requise.');
                    if (adresse) adresse.focus();
                    return false;
                }
                
                if (typeClient === 'personne') {
                    const nom = document.getElementById('nom');
                    if (!nom || !nom.value.trim()) {
                        e.preventDefault();
                        alert('Le nom est requis pour une personne.');
                        if (nom) nom.focus();
                        return false;
                    }
                } else {
                    const nomEntreprise = document.getElementById('nom_entreprise');
                    if (!nomEntreprise || !nomEntreprise.value.trim()) {
                        e.preventDefault();
                        alert('Le nom de l\'entreprise est requis.');
                        if (nomEntreprise) nomEntreprise.focus();
                        return false;
                    }
                }
            }
            
            console.log('✓ Validation réussie, soumission du formulaire...');
            return true;
        } catch (error) {
            console.error('ERREUR dans la validation:', error);
            console.error('Stack:', error.stack);
            // En cas d'erreur, permettre la soumission pour que le serveur gère la validation
            alert('Une erreur est survenue lors de la validation. Le formulaire sera soumis pour validation côté serveur.');
            return true;
        }
    });
    
    console.log('✓ Événement submit ajouté au formulaire');
});

// Initialiser au chargement
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== INITIALISATION COMPLÈTE ===');
    
    // S'assurer que le champ client_id a l'attribut required si client existant est sélectionné
    const clientExistantRadio = document.getElementById('client_existant');
    if (clientExistantRadio && clientExistantRadio.checked) {
        const clientSelect = document.getElementById('client_id');
        if (clientSelect) {
            clientSelect.setAttribute('required', 'required');
            console.log('✓ Attribut required ajouté au champ client_id');
        }
    }
    
    toggleClientSelection();
    toggleNewClientType();
    
    // Test direct du bouton
    const submitBtn = document.getElementById('submit-bon-btn');
    if (submitBtn) {
        console.log('✓ Bouton submit trouvé');
        submitBtn.addEventListener('click', function(e) {
            console.log('=== CLIC DIRECT SUR LE BOUTON ===');
            console.log('Type d\'événement:', e.type);
            console.log('Bouton:', this);
        });
    } else {
        console.error('ERREUR: Bouton submit-bon-btn non trouvé!');
    }
    
    // Debug: vérifier l'état initial
    console.log('Client existant sélectionné:', clientExistantRadio ? clientExistantRadio.checked : 'N/A');
    console.log('Champ client_id required:', document.getElementById('client_id') ? document.getElementById('client_id').hasAttribute('required') : 'N/A');
    console.log('=== FIN INITIALISATION ===');
});
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

