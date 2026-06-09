// Tourne dans world: MAIN - accès au vrai window.fetch
let lastToken = null;

function captureToken(token) {
  if (token === lastToken) return;
  lastToken = token;
  console.log('%c✅ ANWA DJ: Token capturé !', 'color: #1DB954; font-weight: bold');
  // Communiquer via un CustomEvent vers le content script isolé
  document.dispatchEvent(new CustomEvent('anwa_token', { detail: token }));
}

const origFetch = window.fetch;
window.fetch = function(...args) {
  try {
    const opts = args[1] || {};
    const headers = opts.headers || {};
    let auth = null;
    if (headers instanceof Headers) auth = headers.get('authorization') || headers.get('Authorization');
    else if (typeof headers === 'object') auth = headers['authorization'] || headers['Authorization'];
    if (auth && auth.startsWith('Bearer ')) captureToken(auth.replace('Bearer ', ''));
  } catch(e) {}
  return origFetch.apply(this, args);
};

const origXHR = XMLHttpRequest.prototype.setRequestHeader;
XMLHttpRequest.prototype.setRequestHeader = function(name, value) {
  if (name && name.toLowerCase() === 'authorization' && value && value.startsWith('Bearer ')) {
    captureToken(value.replace('Bearer ', ''));
  }
  return origXHR.apply(this, arguments);
};

console.log('%c🎵 ANWA DJ: Interception active (MAIN)', 'color: #1DB954; font-weight: bold');
