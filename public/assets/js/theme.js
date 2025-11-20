/**
 * Gestion du thème clair/sombre
 */
(function() {
    'use strict';

    // Charger le thème sauvegardé
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    }

    // Écouter le bouton de changement de thème
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleTheme');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'dark';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });
        }
    });
})();
