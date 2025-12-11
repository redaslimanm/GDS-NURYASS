<?php
/**
 * Script de vérification de la configuration PWA
 * Accédez à: http://localhost/GDS-NURYASS/check-pwa.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification PWA - GDS NURYASS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            padding: 20px;
        }
        .check-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .check-item.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .check-item.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .check-item.warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="bi bi-check-circle me-2"></i>
                            Vérification de la Configuration PWA
                        </h3>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-4">Résultats de la vérification :</h5>
                        
                        <?php
                        $checks = [];
                        $allPassed = true;
                        
                        // Vérifier manifest.json
                        $manifestExists = file_exists(__DIR__ . '/manifest.json');
                        $manifestValid = false;
                        if ($manifestExists) {
                            $manifestContent = file_get_contents(__DIR__ . '/manifest.json');
                            $manifest = json_decode($manifestContent, true);
                            $manifestValid = json_last_error() === JSON_ERROR_NONE && isset($manifest['name']);
                        }
                        $checks[] = [
                            'name' => 'Fichier manifest.json',
                            'status' => $manifestExists && $manifestValid ? 'success' : ($manifestExists ? 'warning' : 'error'),
                            'message' => $manifestExists && $manifestValid 
                                ? 'Fichier présent et valide' 
                                : ($manifestExists ? 'Fichier présent mais JSON invalide' : 'Fichier manquant')
                        ];
                        if (!$manifestExists || !$manifestValid) $allPassed = false;
                        
                        // Vérifier service-worker.js
                        $swExists = file_exists(__DIR__ . '/service-worker.js');
                        $checks[] = [
                            'name' => 'Fichier service-worker.js',
                            'status' => $swExists ? 'success' : 'error',
                            'message' => $swExists ? 'Fichier présent' : 'Fichier manquant'
                        ];
                        if (!$swExists) $allPassed = false;
                        
                        // Vérifier offline.html
                        $offlineExists = file_exists(__DIR__ . '/offline.html');
                        $checks[] = [
                            'name' => 'Fichier offline.html',
                            'status' => $offlineExists ? 'success' : 'warning',
                            'message' => $offlineExists ? 'Fichier présent' : 'Fichier manquant (optionnel)'
                        ];
                        
                        // Vérifier les icônes
                        $iconSizes = [72, 96, 128, 144, 152, 192, 384, 512];
                        $iconsFound = 0;
                        $iconsMissing = [];
                        foreach ($iconSizes as $size) {
                            $iconPath = __DIR__ . "/images/icon-{$size}x{$size}.png";
                            if (file_exists($iconPath)) {
                                $iconsFound++;
                            } else {
                                $iconsMissing[] = $size;
                            }
                        }
                        $checks[] = [
                            'name' => 'Icônes PWA',
                            'status' => $iconsFound === count($iconSizes) ? 'success' : ($iconsFound > 0 ? 'warning' : 'error'),
                            'message' => $iconsFound === count($iconSizes) 
                                ? "Toutes les icônes présentes ({$iconsFound}/" . count($iconSizes) . ")" 
                                : "Icônes manquantes: " . implode(', ', $iconsMissing) . " ({$iconsFound}/" . count($iconSizes) . " trouvées)"
                        ];
                        if ($iconsFound === 0) $allPassed = false;
                        
                        // Vérifier le dossier images
                        $imagesDirExists = is_dir(__DIR__ . '/images');
                        $checks[] = [
                            'name' => 'Dossier images/',
                            'status' => $imagesDirExists ? 'success' : 'error',
                            'message' => $imagesDirExists ? 'Dossier présent' : 'Dossier manquant'
                        ];
                        if (!$imagesDirExists) $allPassed = false;
                        
                        // Vérifier HTTPS ou localhost
                        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                                   $_SERVER['HTTP_HOST'] === 'localhost' || 
                                   strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;
                        $checks[] = [
                            'name' => 'Connexion sécurisée (HTTPS/localhost)',
                            'status' => $isSecure ? 'success' : 'warning',
                            'message' => $isSecure 
                                ? 'Connexion sécurisée (localhost ou HTTPS)' 
                                : 'HTTPS recommandé pour la production'
                        ];
                        
                        // Afficher les résultats
                        foreach ($checks as $check) {
                            $icon = $check['status'] === 'success' ? 'check-circle-fill' : 
                                   ($check['status'] === 'warning' ? 'exclamation-triangle-fill' : 'x-circle-fill');
                            echo "<div class='check-item {$check['status']}'>";
                            echo "<i class='bi bi-{$icon} me-2'></i>";
                            echo "<strong>{$check['name']}:</strong> {$check['message']}";
                            echo "</div>";
                        }
                        ?>
                        
                        <hr>
                        
                        <div class="alert <?php echo $allPassed ? 'alert-success' : 'alert-warning'; ?>">
                            <h5>
                                <i class="bi bi-<?php echo $allPassed ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                                <?php echo $allPassed ? 'Configuration PWA prête !' : 'Configuration incomplète'; ?>
                            </h5>
                            <?php if ($allPassed): ?>
                                <p>Votre application est prête à être installée comme PWA.</p>
                                <p><strong>Prochaines étapes :</strong></p>
                                <ol>
                                    <li>Ouvrez l'application dans Chrome/Edge</li>
                                    <li>Cherchez l'icône d'installation dans la barre d'adresse</li>
                                    <li>Ou utilisez le bouton "Installer l'application" dans le menu</li>
                                </ol>
                            <?php else: ?>
                                <p>Veuillez corriger les erreurs ci-dessus avant de continuer.</p>
                                <?php if ($iconsFound === 0): ?>
                                    <p><strong>Pour générer les icônes :</strong></p>
                                    <p>Utilisez le générateur web : <a href="generate-icons-web.php" class="btn btn-sm btn-primary">Générer les icônes</a></p>
                                    <?php if (!extension_loaded('gd')): ?>
                                        <div class="alert alert-warning mt-2">
                                            <strong>Extension GD requise :</strong> 
                                            <a href="activate-gd-guide.php" class="alert-link">Guide d'activation de GD</a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i>Retour au Dashboard
                            </a>
                            <a href="generate-icons-web.php" class="btn btn-outline-secondary">
                                <i class="bi bi-image me-2"></i>Générer les icônes (Web)
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4 shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Informations techniques</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>URL actuelle:</th>
                                <td><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></td>
                            </tr>
                            <tr>
                                <th>User Agent:</th>
                                <td><small><?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'); ?></small></td>
                            </tr>
                            <tr>
                                <th>Support Service Worker:</th>
                                <td><span id="sw-support">Vérification...</span></td>
                            </tr>
                            <tr>
                                <th>Service Worker enregistré:</th>
                                <td><span id="sw-registered">Vérification...</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Vérifier le support du Service Worker
        const swSupport = 'serviceWorker' in navigator;
        document.getElementById('sw-support').textContent = swSupport ? '✅ Oui' : '❌ Non';
        document.getElementById('sw-support').className = swSupport ? 'text-success' : 'text-danger';
        
        // Vérifier si le Service Worker est enregistré
        if (swSupport) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                const isRegistered = registrations.length > 0;
                document.getElementById('sw-registered').textContent = isRegistered ? '✅ Oui' : '❌ Non';
                document.getElementById('sw-registered').className = isRegistered ? 'text-success' : 'text-danger';
            });
        } else {
            document.getElementById('sw-registered').textContent = 'N/A';
        }
    </script>
</body>
</html>

