    </div> <!-- End main-content -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- PWA Service Worker Registration -->
    <script>
        // Enregistrer le Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo url('service-worker.js'); ?>')
                    .then(function(registration) {
                        console.log('Service Worker registered successfully:', registration.scope);
                        
                        // Vérifier les mises à jour du service worker
                        registration.addEventListener('updatefound', function() {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', function() {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // Nouveau service worker disponible
                                    if (confirm('Une nouvelle version est disponible. Voulez-vous recharger la page ?')) {
                                        newWorker.postMessage({ type: 'SKIP_WAITING' });
                                        window.location.reload();
                                    }
                                }
                            });
                        });
                    })
                    .catch(function(error) {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }
        
        // Gestion de l'installation PWA
        let deferredPrompt;
        let installButton = document.getElementById('install-pwa-btn');
        
        // Fonction pour afficher le bouton
        function showInstallButton() {
            if (installButton) {
                installButton.style.display = 'block';
                installButton.style.visibility = 'visible';
            }
        }
        
        // Fonction pour cacher le bouton
        function hideInstallButton() {
            if (installButton) {
                installButton.style.display = 'none';
            }
        }
        
        window.addEventListener('beforeinstallprompt', function(e) {
            console.log('beforeinstallprompt event fired');
            // Empêcher l'affichage automatique du prompt
            e.preventDefault();
            deferredPrompt = e;
            
            // Afficher le bouton d'installation
            showInstallButton();
            
            // Afficher aussi un message dans la console
            console.log('PWA install button should be visible now');
        });
        
        // Gestion du clic sur le bouton d'installation
        if (installButton) {
            installButton.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Install button clicked');
                
                if (deferredPrompt) {
                    // Afficher le prompt d'installation
                    deferredPrompt.prompt();
                    
                    // Attendre la réponse de l'utilisateur
                    deferredPrompt.userChoice.then(function(choiceResult) {
                        console.log('User choice:', choiceResult.outcome);
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                            alert('Installation en cours...');
                        } else {
                            console.log('User dismissed the install prompt');
                        }
                        deferredPrompt = null;
                        hideInstallButton();
                    });
                } else {
                    // Si pas de deferredPrompt, essayer d'ouvrir les instructions
                    alert('Pour installer l\'application:\n\n1. Chrome/Edge: Cherchez l\'icône + dans la barre d\'adresse\n2. Menu (⋮) > Installer l\'application\n3. Ou utilisez le diagnostic: pwa-install-diagnostic.php');
                }
            });
        }
        
        // Vérifier si l'app est déjà installée
        window.addEventListener('appinstalled', function(evt) {
            console.log('PWA installed successfully');
            hideInstallButton();
        });
        
        // Vérifier au chargement si l'app est déjà installée
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                            window.navigator.standalone === true;
        
        if (isStandalone) {
            console.log('App is already installed (standalone mode)');
            hideInstallButton();
        } else {
            // Si pas installé, vérifier après un délai si beforeinstallprompt s'est déclenché
            setTimeout(function() {
                if (!deferredPrompt && installButton) {
                    // Le bouton n'est pas visible, afficher un message d'aide
                    console.log('Install button not shown. beforeinstallprompt may not have fired.');
                    console.log('Try: 1. Check browser console for errors');
                    console.log('2. Verify manifest.json is accessible');
                    console.log('3. Check service worker is registered');
                }
            }, 2000);
        }
        
        // Debug: Log l'état du bouton
        console.log('Install button element:', installButton);
        console.log('Is standalone:', isStandalone);
    </script>
    
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
        
        // Settings dropdown toggle
        document.addEventListener('DOMContentLoaded', function() {
            const settingsToggle = document.getElementById('settings-toggle');
            const settingsMenu = document.querySelector('.settings-menu');
            
            if (settingsToggle && settingsMenu) {
                settingsToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    settingsMenu.classList.toggle('active');
                });
                
                // Fermer le menu si on clique ailleurs
                document.addEventListener('click', function(e) {
                    if (!settingsMenu.contains(e.target)) {
                        settingsMenu.classList.remove('active');
                    }
                });
            }
        });
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>

