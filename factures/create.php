<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Nouvelle Facture';
$errors = [];
$clientId = intval($_GET['client_id'] ?? 0);
try {
    $pdo = getDBConnection();
    $clients = $pdo->query("SELECT id, nom, prenom, nom_entreprise, type_client FROM clients WHERE actif = 1 ORDER BY nom")->fetchAll();
    $produits = $pdo->query("SELECT p.*, tp.nom_type, c.nom_couleur FROM produits p LEFT JOIN types_produits tp ON p.type_id = tp.id LEFT JOIN couleurs c ON p.couleur_id = c.id WHERE p.actif = 1 ORDER BY p.nom_produit")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
    $clients = [];
    $produits = [];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = intval($_POST['client_id'] ?? 0);
    $bonId = intval($_POST['bon_id'] ?? 0);
    $produitsData = $_POST['produits'] ?? [];
    if ($clientId <= 0) $errors[] = 'Client requis.';
    if (empty($produitsData)) $errors[] = 'Au moins un produit requis.';
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $user = getCurrentUser();
            $stmt = $pdo->query("SELECT COUNT(*) FROM factures WHERE DATE(date_facture) = CURDATE()");
            $numeroFacture = 'FAC-' . date('Ymd') . '-' . str_pad($stmt->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);
            $total = 0;
            foreach ($produitsData as $prod) {
                $quantite = intval($prod['quantite'] ?? 0);
                $prix = floatval($prod['prix'] ?? 0);
                $total += $quantite * $prix;
            }
            $stmt = $pdo->prepare("INSERT INTO factures (numero_facture, client_id, bon_id, user_id, total) VALUES (:numero, :client_id, :bon_id, :user_id, :total)");
            $stmt->execute(['numero' => $numeroFacture, 'client_id' => $clientId, 'bon_id' => $bonId ?: null, 'user_id' => $user['id'], 'total' => $total]);
            $factureId = $pdo->lastInsertId();
            foreach ($produitsData as $prod) {
                $produitId = intval($prod['produit_id'] ?? 0);
                $quantite = intval($prod['quantite'] ?? 0);
                $prixUnitaire = floatval($prod['prix'] ?? 0);
                if ($produitId > 0 && $quantite > 0) {
                    $pdo->prepare("INSERT INTO factures_details (facture_id, produit_id, quantite, prix_unitaire, sous_total) VALUES (:facture_id, :produit_id, :quantite, :prix_unitaire, :sous_total)")->execute(['facture_id' => $factureId, 'produit_id' => $produitId, 'quantite' => $quantite, 'prix_unitaire' => $prixUnitaire, 'sous_total' => $quantite * $prixUnitaire]);
                }
            }
            $pdo->prepare("INSERT INTO historique (user_id, client_id, facture_id, action, type_action, details, ip_address) VALUES (:user_id, :client_id, :facture_id, 'Création d\'une facture', 'creation', :details, :ip_address)")->execute(['user_id' => $user['id'], 'client_id' => $clientId, 'facture_id' => $factureId, 'details' => "Facture créée: $numeroFacture", 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            $pdo->commit();
            $_SESSION['success_message'] = 'Facture créée!';
            header('Location: view.php?id=' . $factureId); exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur création facture: " . $e->getMessage());
            $errors[] = 'Erreur lors de la création.';
        }
    }
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Nouvelle Facture</h1></div><div><a href="index.php" class="btn btn-outline-secondary">Retour</a></div></div>
<div class="row"><div class="col-md-12"><div class="card"><div class="card-body"><?php if (!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="POST" id="factureForm"><div class="mb-3"><label class="form-label">Client <span class="text-danger">*</span></label><select class="form-select" name="client_id" required><option value="">Sélectionner</option><?php foreach ($clients as $client): ?><option value="<?php echo $client['id']; ?>" <?php echo $clientId == $client['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($client['type_client'] === 'entreprise' ? ($client['nom_entreprise'] ?? $client['nom']) : ($client['nom'] . ' ' . ($client['prenom'] ?? ''))); ?></option><?php endforeach; ?></select></div><hr><h5>Produits</h5><div id="produits-container"><div class="produit-row mb-3 p-3 border rounded"><div class="row g-3"><div class="col-md-5"><select class="form-select produit-select" name="produits[0][produit_id]" required><option value="">Sélectionner</option><?php foreach ($produits as $produit): ?><option value="<?php echo $produit['id']; ?>" data-prix="<?php echo $produit['prix']; ?>"><?php echo htmlspecialchars($produit['nom_produit']); ?></option><?php endforeach; ?></select></div><div class="col-md-2"><input type="number" class="form-control quantite-input" name="produits[0][quantite]" min="1" value="1" required></div><div class="col-md-2"><input type="number" class="form-control prix-input" name="produits[0][prix]" step="0.01" required></div><div class="col-md-2"><input type="text" class="form-control sous-total" readonly></div><div class="col-md-1"><button type="button" class="btn btn-danger w-100 remove-produit" style="display: none;"><i class="bi bi-trash"></i></button></div></div></div></div><button type="button" class="btn btn-outline-primary mb-3" id="add-produit"><i class="bi bi-plus-circle me-2"></i>Ajouter</button><hr><div class="row"><div class="col-md-6"><h4>Total: <span id="total-facture" class="text-primary">0.00</span> DH</h4></div><div class="col-md-6 text-end"><a href="index.php" class="btn btn-outline-secondary">Annuler</a><button type="submit" class="btn btn-primary">Créer</button></div></div></form></div></div></div></div>
<script>
let produitIndex = 1;
document.getElementById('add-produit').addEventListener('click', function() {
    const container = document.getElementById('produits-container');
    const newRow = container.firstElementChild.cloneNode(true);
    newRow.querySelectorAll('select, input').forEach(input => {
        if (input.name) input.name = input.name.replace('[0]', '[' + produitIndex + ']');
        if (input.value && input.type !== 'number') input.value = '';
        if (input.type === 'number' && !input.classList.contains('sous-total')) input.value = input.name.includes('quantite') ? '1' : '';
    });
    newRow.querySelector('.sous-total').value = '';
    newRow.querySelector('.remove-produit').style.display = 'block';
    container.appendChild(newRow);
    produitIndex++;
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
function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.sous-total').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('total-facture').textContent = total.toFixed(2);
}
document.querySelectorAll('.produit-select, .quantite-input, .prix-input').forEach(el => {
    el.addEventListener('change', function() {
        const row = this.closest('.produit-row');
        const select = row.querySelector('.produit-select');
        const quantite = parseFloat(row.querySelector('.quantite-input').value) || 0;
        const prixInput = row.querySelector('.prix-input');
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            prixInput.value = selectedOption.dataset.prix;
        }
        const prix = parseFloat(prixInput.value) || 0;
        row.querySelector('.sous-total').value = (quantite * prix).toFixed(2);
        calculateTotal();
    });
});
</script>
<?php require_once '../includes/footer.php'; ?>





