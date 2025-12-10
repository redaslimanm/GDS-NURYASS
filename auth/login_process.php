<?php
/**
 * Traitement de la connexion
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

// Rediriger si déjà connecté
redirectIfLoggedIn('../dashboard.php');

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('login.php'));
    exit();
}

// Récupérer et nettoyer les données
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validation
$errors = [];

if (empty($username)) {
    $errors[] = 'Le nom d\'utilisateur est requis.';
}

if (empty($password)) {
    $errors[] = 'Le mot de passe est requis.';
}

// Si erreurs, rediriger vers login
if (!empty($errors)) {
    $_SESSION['login_error'] = implode(' ', $errors);
    header('Location: ' . url('login.php'));
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Préparer la requête pour récupérer l'utilisateur
    $stmt = $pdo->prepare("
        SELECT id, username, password, role, actif 
        FROM users 
        WHERE username = :username
    ");
    
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    
    // Vérifier si l'utilisateur existe et si le mot de passe est correct
    if ($user && password_verify($password, $user['password'])) {
        // Vérifier si le compte est actif
        if (!$user['actif']) {
            $_SESSION['login_error'] = 'Votre compte a été désactivé. Contactez l\'administrateur.';
            header('Location: ' . url('login.php'));
            exit();
        }
        
        // Connexion réussie - définir la session
        setUserSession($user['id'], $user['username'], $user['role']);
        
        // Enregistrer dans l'historique
        try {
            $stmt = $pdo->prepare("
                INSERT INTO historique (user_id, action, type_action, details, ip_address)
                VALUES (:user_id, :action, :type_action, :details, :ip_address)
            ");
            
            $stmt->execute([
                'user_id' => $user['id'],
                'action' => 'Connexion au système',
                'type_action' => 'creation',
                'details' => 'Connexion réussie - Utilisateur: ' . $user['username'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (PDOException $e) {
            // Ne pas bloquer la connexion si l'historique échoue
            error_log("Erreur lors de l'enregistrement dans l'historique: " . $e->getMessage());
        }
        
        // Rediriger vers la page demandée ou le dashboard
        $redirectUrl = $_SESSION['redirect_after_login'] ?? url('dashboard.php');
        unset($_SESSION['redirect_after_login']);
        
        header('Location: ' . $redirectUrl);
        exit();
        
    } else {
        // Identifiants incorrects
        $_SESSION['login_error'] = 'Nom d\'utilisateur ou mot de passe incorrect.';
        
        // Délai pour éviter les attaques par force brute
        sleep(1);
        
        header('Location: ' . url('login.php'));
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la connexion: " . $e->getMessage());
    $_SESSION['login_error'] = 'Une erreur est survenue. Veuillez réessayer plus tard.';
    header('Location: ' . url('login.php'));
    exit();
}

