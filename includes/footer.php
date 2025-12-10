    </div> <!-- End main-content -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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

