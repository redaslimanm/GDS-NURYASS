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
    $clientId = intval($_POST['client_id'] ?? 0);
    $typeBon = $_POST['type_bon'] ?? '';
    $statutPaiement = $_POST['statut_paiement'] ?? 'non_paye';
    $details = trim($_POST['details'] ?? '');
    $produitsData = $_POST['produits'] ?? [];
    
    // Validation
    if ($clientId <= 0) {
        $errors[] = 'Le client est requis.';
    }
    
    if (empty($typeBon) || !in_array($typeBon, ['entree', 'sortie'])) {
        $errors[] = 'Le type de bon est requis.';
    }
    
    if (empty($produitsData)) {
        $errors[] = 'Au moins un produit est requis.';
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
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Sélectionner un client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>" <?php echo $clientId == $client['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['type_client'] === 'entreprise' ? ($client['nom_entreprise'] ?? $client['nom']) : ($client['nom'] . ' ' . ($client['prenom'] ?? ''))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="type_bon" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type_bon" name="type_bon" required>
                                <option value="">Sélectionner</option>
                                <option value="entree">Entrée (Réception)</option>
                                <option value="sortie">Sortie (Vente/Usage)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="statut_paiement" class="form-label">Statut Paiement</label>
                            <select class="form-select" id="statut_paiement" name="statut_paiement">
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
                            <button type="submit" class="btn btn-primary">
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
</script>

<?php require_once '../includes/footer.php'; ?>

