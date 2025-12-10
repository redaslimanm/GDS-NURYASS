<?php
/**
 * Page d'accueil - Redirection automatique
 * GDS - Stock Management System
 */

require_once 'includes/session.php';

// Rediriger vers le dashboard si connecté, sinon vers login
if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit();

