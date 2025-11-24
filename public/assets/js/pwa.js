/**
 * Enregistrement du Service Worker pour PWA
 */

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('Service Worker enregistré:', registration.scope);
            })
            .catch((error) => {
                console.error('Erreur d\'enregistrement du Service Worker:', error);
            });
    });
}

// Gestion de l'installation PWA
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Afficher un bouton d'installation si nécessaire
    const installButton = document.getElementById('install-pwa');
    if (installButton) {
        installButton.style.display = 'block';
        installButton.addEventListener('click', () => {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('PWA installée');
                }
                deferredPrompt = null;
                installButton.style.display = 'none';
            });
        });
    }
});

window.addEventListener('appinstalled', () => {
    console.log('PWA installée avec succès');
    deferredPrompt = null;
});

