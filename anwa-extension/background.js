const ADMIN_URL = 'https://dj.anwadance.com/admin.php';
let lastSentToken = null;

// Surveiller chrome.storage pour les nouveaux tokens
chrome.storage.onChanged.addListener(function(changes, area) {
  if (area === 'local' && changes.pending_token) {
    const token = changes.pending_token.newValue;
    if (token && token !== lastSentToken) {
      lastSentToken = token;
      console.log('ANWA DJ: Nouveau token détecté, envoi au serveur...');
      fetch(ADMIN_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'store_token', token: token })
      }).then(r => r.json()).then(data => {
        if (data.success) {
          console.log('ANWA DJ: ✅ Token stocké sur le serveur !');
          chrome.storage.local.set({
            lastToken: token.substring(0, 20) + '...',
            lastUpdate: new Date().toLocaleTimeString('fr-FR'),
            pending_token: null
          });
        }
      }).catch(e => console.log('ANWA DJ erreur:', e));
    }
  }
});

console.log('ANWA DJ: Background actif');
