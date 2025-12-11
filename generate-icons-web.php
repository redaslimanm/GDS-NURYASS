<?php
/**
 * Générateur d'icônes PWA - Version Web
 * Accédez à: http://localhost/GDS-NURYASS/generate-icons-web.php
 * 
 * Cette version peut être exécutée directement dans le navigateur
 */

// Vérifier si GD est disponible
if (!extension_loaded('gd')) {
    header('Location: activate-gd-guide.php');
    exit();
}

$logoPath = __DIR__ . '/images/logo.png';
$outputDir = __DIR__ . '/images/';
$messages = [];
$errors = [];

// Créer le dossier images s'il n'existe pas
if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0755, true)) {
        $errors[] = "Impossible de créer le dossier images/";
    }
}

// Tailles d'icônes requises
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'generate';
    
    if ($action === 'generate') {
        // Si le logo n'existe pas, créer une icône par défaut
        if (!file_exists($logoPath)) {
            createDefaultIcon($outputDir . 'icon-512x512.png', 512);
            $logoPath = $outputDir . 'icon-512x512.png';
            $messages[] = "Icône par défaut créée (512x512)";
        }
        
        // Charger l'image source
        $sourceImage = @imagecreatefromstring(file_get_contents($logoPath));
        if (!$sourceImage) {
            $errors[] = "Impossible de charger l'image source. Format non supporté ou fichier corrompu.";
        } else {
            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);
            
            // Générer chaque taille d'icône
            foreach ($sizes as $size) {
                $outputPath = $outputDir . "icon-{$size}x{$size}.png";
                
                // Créer une nouvelle image
                $newImage = imagecreatetruecolor($size, $size);
                
                // Rendre le fond transparent
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
                imagefill($newImage, 0, 0, $transparent);
                
                // Calculer les dimensions pour garder les proportions
                $ratio = min($size / $sourceWidth, $size / $sourceHeight);
                $newWidth = $sourceWidth * $ratio;
                $newHeight = $sourceHeight * $ratio;
                $x = ($size - $newWidth) / 2;
                $y = ($size - $newHeight) / 2;
                
                // Redimensionner et copier l'image
                imagecopyresampled(
                    $newImage, $sourceImage,
                    $x, $y, 0, 0,
                    $newWidth, $newHeight,
                    $sourceWidth, $sourceHeight
                );
                
                // Sauvegarder l'icône
                if (imagepng($newImage, $outputPath)) {
                    $messages[] = "✅ Créé: icon-{$size}x{$size}.png";
                } else {
                    $errors[] = "❌ Erreur lors de la création de icon-{$size}x{$size}.png";
                }
                
                imagedestroy($newImage);
            }
            
            imagedestroy($sourceImage);
        }
    }
}

/**
 * Créer une icône par défaut
 */
function createDefaultIcon($path, $size) {
    $image = imagecreatetruecolor($size, $size);
    
    // Fond dégradé
    for ($y = 0; $y < $size; $y++) {
        $ratio = $y / $size;
        $r = (int)(102 + (118 - 102) * $ratio);
        $g = (int)(126 + (75 - 126) * $ratio);
        $b = (int)(234 + (162 - 234) * $ratio);
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $y, $size, $y, $color);
    }
    
    // Ajouter du texte "GDS"
    $textColor = imagecolorallocate($image, 255, 255, 255);
    $fontSize = $size / 4;
    $text = "GDS";
    
    // Calculer la position du texte (centré)
    $textWidth = imagefontwidth(5) * strlen($text) * ($fontSize / 10);
    $textHeight = imagefontheight(5) * ($fontSize / 10);
    $x = ($size - $textWidth) / 2;
    $y = ($size - $textHeight) / 2;
    
    // Utiliser imagestring pour le texte
    imagestring($image, 5, $x, $y - 20, $text, $textColor);
    
    imagepng($image, $path);
    imagedestroy($image);
}

// Vérifier quelles icônes existent déjà
$existingIcons = [];
$missingIcons = [];
foreach ($sizes as $size) {
    $iconPath = $outputDir . "icon-{$size}x{$size}.png";
    if (file_exists($iconPath)) {
        $existingIcons[] = $size;
    } else {
        $missingIcons[] = $size;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générateur d'Icônes PWA - GDS NURYASS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            padding: 20px;
        }
        .icon-preview {
            width: 64px;
            height: 64px;
            border: 2px solid #ddd;
            border-radius: 8px;
            display: inline-block;
            margin: 5px;
            object-fit: contain;
        }
        .icon-status {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .icon-status.exists {
            background: #d4edda;
            color: #155724;
        }
        .icon-status.missing {
            background: #f8d7da;
            color: #721c24;
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
                            <i class="bi bi-image me-2"></i>
                            Générateur d'Icônes PWA
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($messages)): ?>
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle me-2"></i>Résultats :</h5>
                                <ul class="mb-0">
                                    <?php foreach ($messages as $msg): ?>
                                        <li><?php echo htmlspecialchars($msg); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5><i class="bi bi-exclamation-triangle me-2"></i>Erreurs :</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <h5 class="mt-4">État actuel des icônes :</h5>
                        <div class="row">
                            <?php foreach ($sizes as $size): ?>
                                <?php
                                $iconPath = $outputDir . "icon-{$size}x{$size}.png";
                                $exists = file_exists($iconPath);
                                $iconUrl = "images/icon-{$size}x{$size}.png";
                                ?>
                                <div class="col-md-3 mb-3">
                                    <div class="icon-status <?php echo $exists ? 'exists' : 'missing'; ?>">
                                        <div class="d-flex align-items-center">
                                            <?php if ($exists): ?>
                                                <img src="<?php echo htmlspecialchars($iconUrl); ?>" 
                                                     alt="Icon <?php echo $size; ?>x<?php echo $size; ?>" 
                                                     class="icon-preview me-2">
                                            <?php else: ?>
                                                <div class="icon-preview me-2 bg-light d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-x-lg text-danger"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo $size; ?>x<?php echo $size; ?></strong>
                                                <br>
                                                <small><?php echo $exists ? '✅ Présent' : '❌ Manquant'; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle me-2"></i>Instructions :</h5>
                            <ol>
                                <li>Si vous avez un logo, placez-le dans <code>images/logo.png</code></li>
                                <li>Cliquez sur le bouton ci-dessous pour générer toutes les icônes</li>
                                <li>Si aucun logo n'est trouvé, une icône par défaut sera créée</li>
                            </ol>
                        </div>
                        
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="action" value="generate">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-magic me-2"></i>
                                Générer toutes les icônes
                            </button>
                        </form>
                        
                        <div class="mt-4">
                            <a href="check-pwa.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Retour à la vérification PWA
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </div>
                        
                        <?php if (file_exists($logoPath)): ?>
                            <div class="mt-4">
                                <h6>Logo source détecté :</h6>
                                <img src="images/logo.png" alt="Logo" style="max-width: 200px; border: 1px solid #ddd; border-radius: 5px; padding: 10px;">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

