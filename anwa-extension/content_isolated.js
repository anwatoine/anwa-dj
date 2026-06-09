// Tourne dans world: ISOLATED - accès à chrome.storage
const ADMIN_URL = 'https://dj.anwadance.com/admin.php';
let lastSent = 0;
let lastToken = null;

// Écouter les tokens capturés par content_main.js via CustomEvent
document.addEventListener('anwa_token', function(e) {
  const token = e.detail;
  const now = Date.now();
  if (token === lastToken && (now - lastSent) < 50 * 60 * 1000) return;
  lastToken = token;
  lastSent = now;
  
  console.log('%c✅ ANWA DJ: Token reçu, stockage...', 'color: #1DB954; font-weight: bold');
  
  // Stocker via chrome.storage pour que le background l'envoie
  chrome.storage.local.set({ 
    pending_token: token,
    pending_time: Date.now()
  });
});

console.log('%c🎵 ANWA DJ: Écoute active (ISOLATED)', 'color: #1DB954; font-weight: bold');
