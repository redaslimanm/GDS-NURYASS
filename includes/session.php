<?php
/**
 * Gestion des sessions
 * GDS - Stock Management System
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifier si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

/**
 * Vérifier si l'utilisateur est admin
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Vérifier si l'utilisateur est caissier
 * @return bool
 */
function isCashier() {
    return isLoggedIn() && $_SESSION['role'] === 'caissier';
}

/**
 * Rediriger vers la page de login si non connecté
 * @param string $redirectUrl URL de redirection après login
 */
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        // Calculer le chemin vers login.php depuis le répertoire actuel
        if ($redirectUrl === 'login.php') {
            $scriptPath = $_SERVER['SCRIPT_NAME'];
            $scriptDir = dirname($scriptPath);
            
            // Compter le nombre de niveaux de profondeur (sans le premier /)
            $levels = substr_count(ltrim($scriptDir, '/'), '/');
            
            if ($levels > 0) {
                // On est dans un sous-dossier, remonter d'autant de niveaux
                $redirectUrl = str_repeat('../', $levels) . 'login.php';
            }
        }
        
        // Éviter les boucles infinies
        if (basename($_SERVER['SCRIPT_NAME']) === 'login.php') {
            return; // Ne pas rediriger si on est déjà sur login.php
        }
        
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Rediriger vers le dashboard si déjà connecté
 * @param string $dashboardUrl URL du dashboard
 */
function redirectIfLoggedIn($dashboardUrl = 'dashboard.php') {
    if (isLoggedIn()) {
        // Calculer le chemin vers dashboard.php depuis le répertoire actuel
        if ($dashboardUrl === 'dashboard.php') {
            $scriptPath = $_SERVER['SCRIPT_NAME'];
            $scriptDir = dirname($scriptPath);
            
            // Compter le nombre de niveaux de profondeur (sans le premier /)
            $levels = substr_count(ltrim($scriptDir, '/'), '/');
            
            if ($levels > 0) {
                // On est dans un sous-dossier, remonter d'autant de niveaux
                $dashboardUrl = str_repeat('../', $levels) . 'dashboard.php';
            }
        }
        
        // Éviter les boucles infinies
        if (basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php') {
            return; // Ne pas rediriger si on est déjà sur dashboard.php
        }
        
        header('Location: ' . $dashboardUrl);
        exit();
    }
}

/**
 * Obtenir les informations de l'utilisateur connecté
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? 0,
        'username' => $_SESSION['username'] ?? '',
        'role' => $_SESSION['role'] ?? ''
    ];
}

/**
 * Définir les informations de session de l'utilisateur
 * @param int $userId
 * @param string $username
 * @param string $role
 */
function setUserSession($userId, $username, $role) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['login_time'] = time();
}

/**
 * Détruire la session
 */
function destroySession() {
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Obtenir le chemin de base vers la racine du projet
 * @return string Chemin relatif vers la racine
 */
function getBasePath() {
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $scriptDir = dirname($scriptPath);
    
    // Compter le nombre de niveaux de profondeur (sans le premier /)
    $levels = substr_count(ltrim($scriptDir, '/'), '/');
    
    if ($levels > 0) {
        // On est dans un sous-dossier, remonter d'autant de niveaux
        return str_repeat('../', $levels);
    }
    
    // On est à la racine
    return '';
}

/**
 * Générer une URL vers un fichier depuis n'importe quel dossier
 * @param string $path Chemin depuis la racine (ex: "dashboard.php" ou "clients/index.php")
 * @return string Chemin relatif correct
 */
function url($path) {
    $basePath = getBasePath();
    // Enlever le / initial si présent
    $path = ltrim($path, '/');
    return $basePath . $path;
}

