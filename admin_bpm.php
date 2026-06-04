<?php
header('Content-Type: text/html; charset=utf-8');

$manual_file = __DIR__ . '/bpm_manual.json';
$missing_file = __DIR__ . '/bpm_missing.json';

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Sauvegarder un BPM manuel
    if ($action === 'save') {
        $spotify_id = trim($_POST['spotify_id'] ?? '');
        $bpm = floatval($_POST['bpm'] ?? 0);
        $key = trim($_POST['key'] ?? '');
        $camelot = trim($_POST['camelot'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $artist = trim($_POST['artist'] ?? '');
        
        if ($spotify_id && $bpm) {
            $manual = file_exists($manual_file) ? json_decode(file_get_contents($manual_file), true) : [];
            $manual[$spotify_id] = [
                'title' => $title,
                'artist' => $artist,
                'bpm' => $bpm,
                'key' => $key,
                'camelot' => $camelot,
                'added' => date('Y-m-d')
            ];
            file_put_contents($manual_file, json_encode($manual, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Supprimer de missing
            $missing = file_exists($missing_file) ? json_decode(file_get_contents($missing_file), true) : [];
            unset($missing[$spotify_id]);
            file_put_contents($missing_file, json_encode($missing, JSON_PRETTY_PRINT));
            
            echo json_encode(['success' => true]);
            exit;
        }
    }
    
    // Supprimer un BPM manuel
    if ($action === 'delete') {
        $spotify_id = trim($_POST['spotify_id'] ?? '');
        $manual = file_exists($manual_file) ? json_decode(file_get_contents($manual_file), true) : [];
        unset($manual[$spotify_id]);
        file_put_contents($manual_file, json_encode($manual, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        exit;
    }
}

$missing = file_exists($missing_file) ? json_decode(file_get_contents($missing_file), true) : [];
// DEBUG
echo "<!-- DEBUG: missing_file=$missing_file | exists=" . (file_exists($missing_file)?'YES':'NO') . " | count=" . count($missing) . " -->";
$manual = file_exists($manual_file) ? json_decode(file_get_contents($manual_file), true) : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ANWA DJ — Admin BPM</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  :root { --bg2: #1a1a1a; }
  body { background: #0f0f0f; color: #e0e0e0; font-family: 'Space Mono', monospace; padding: 1.5rem; }
  h1 { color: #1DB954; margin-bottom: 0.5rem; font-size: 1.4rem; }
  h2 { color: #fbbf24; font-size: 1rem; margin: 1.5rem 0 0.75rem; }
  .subtitle { color: #666; font-size: 12px; margin-bottom: 1.5rem; }
  .card { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 1rem; margin-bottom: 0.75rem; }
  .track-header { display: flex; align-items: center; gap: 10px; margin-bottom: 0.75rem; }
  .track-name { font-weight: bold; font-size: 14px; }
  .track-artist { color: #888; font-size: 12px; }
  .tunebat-btn { padding: 5px 12px; background: #ff6b35; color: #fff; border: none; border-radius: 20px; font-size: 11px; cursor: pointer; text-decoration: none; display: inline-block; }
  .form-row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
  .form-group { display: flex; flex-direction: column; gap: 4px; }
  .form-group label { font-size: 10px; color: #888; }
  .form-group input { padding: 8px 10px; background: #0f0f0f; border: 1px solid #333; border-radius: 8px; color: #e0e0e0; font-size: 13px; width: 100px; }
  .form-group input.wide { width: 140px; }
  .save-btn { padding: 8px 16px; background: #1DB954; color: #000; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; align-self: flex-end; }
  .save-btn:hover { background: #22d464; }
  .badge-ok { background: rgba(29,185,84,0.2); color: #1DB954; padding: 2px 8px; border-radius: 20px; font-size: 11px; }
  .badge-missing { background: rgba(244,63,94,0.2); color: #f43f5e; padding: 2px 8px; border-radius: 20px; font-size: 11px; }
  .delete-btn { padding: 5px 10px; background: transparent; border: 1px solid #f43f5e; color: #f43f5e; border-radius: 8px; cursor: pointer; font-size: 11px; }
  .stats { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
  .stat { background: #1a1a1a; border: 1px solid #333; border-radius: 10px; padding: 0.75rem 1.25rem; text-align: center; }
  .stat-num { font-size: 1.5rem; font-weight: bold; color: #1DB954; }
  .stat-label { font-size: 11px; color: #888; }
  .empty { color: #555; font-size: 13px; padding: 1rem; text-align: center; }
  .spotify-id { font-size: 10px; color: #555; }
</style>
</head>
<body>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
  <h1>🎵 ANWA DJ — Admin BPM</h1>
  <a href="index.html" style="padding:8px 16px;background:var(--bg2);border:1px solid #333;border-radius:8px;color:#888;font-size:12px;text-decoration:none;">← Retour à l'app</a>
</div>
<p class="subtitle">Saisie manuelle des BPM pour les titres non trouvés automatiquement</p>

<div class="stats">
  <div class="stat">
    <div class="stat-num"><?= count($missing) ?></div>
    <div class="stat-label">Titres manquants</div>
  </div>
  <div class="stat">
    <div class="stat-num"><?= count($manual) ?></div>
    <div class="stat-label">BPM saisis manuellement</div>
  </div>
</div>

<!-- TITRES MANQUANTS -->
<h2>⚠️ Titres sans BPM</h2>
<?php if (empty($missing)): ?>
  <div class="empty">✅ Tous les titres ont un BPM !</div>
<?php else: ?>
  <?php foreach ($missing as $spotify_id => $info): ?>
  <div class="card" id="card-<?= htmlspecialchars($spotify_id) ?>">
    <div class="track-header">
      <div style="flex:1">
        <div class="track-name"><?= htmlspecialchars($info['title'] ?? 'Titre inconnu') ?></div>
        <div class="track-artist"><?= htmlspecialchars($info['artist'] ?? '') ?></div>
        <div class="spotify-id"><?= htmlspecialchars($spotify_id) ?></div>
      </div>
      <a href="https://tunebat.com/Info/<?= urlencode(($info['title'] ?? '').' '.($info['artist'] ?? '')) ?>/<?= htmlspecialchars($spotify_id) ?>" 
         target="_blank" class="tunebat-btn">🔍 Tunebat</a>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>BPM *</label>
        <input type="number" id="bpm-<?= $spotify_id ?>" placeholder="120" step="0.1">
      </div>
      <div class="form-group">
        <label>Key</label>
        <input type="text" id="key-<?= $spotify_id ?>" placeholder="B Minor" class="wide">
      </div>
      <div class="form-group">
        <label>Camelot</label>
        <input type="text" id="camelot-<?= $spotify_id ?>" placeholder="10A">
      </div>
      <button class="save-btn" onclick="saveBPM('<?= $spotify_id ?>', '<?= addslashes($info['title'] ?? '') ?>', '<?= addslashes($info['artist'] ?? '') ?>')">
        ✅ Sauvegarder
      </button>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- BPM MANUELS EXISTANTS -->
<h2>✅ BPM saisis manuellement</h2>
<?php if (empty($manual)): ?>
  <div class="empty">Aucun BPM manuel pour le moment</div>
<?php else: ?>
  <?php foreach ($manual as $spotify_id => $info): ?>
  <div class="card">
    <div style="display:flex;align-items:center;gap:10px">
      <div style="flex:1">
        <div class="track-name"><?= htmlspecialchars($info['title'] ?? '') ?></div>
        <div class="track-artist"><?= htmlspecialchars($info['artist'] ?? '') ?></div>
        <div class="spotify-id"><?= htmlspecialchars($spotify_id) ?></div>
      </div>
      <span class="badge-ok"><?= $info['bpm'] ?> BPM</span>
      <?php if (!empty($info['camelot'])): ?>
      <span class="badge-ok"><?= htmlspecialchars($info['camelot']) ?></span>
      <?php endif; ?>
      <button class="delete-btn" onclick="deleteBPM('<?= $spotify_id ?>')">🗑️</button>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<script>
async function saveBPM(id, title, artist) {
  const bpm = document.getElementById('bpm-' + id).value;
  const key = document.getElementById('key-' + id).value;
  const camelot = document.getElementById('camelot-' + id).value;
  
  if (!bpm) { alert('BPM requis !'); return; }
  
  const form = new FormData();
  form.append('action', 'save');
  form.append('spotify_id', id);
  form.append('bpm', bpm);
  form.append('key', key);
  form.append('camelot', camelot);
  form.append('title', title);
  form.append('artist', artist);
  
  const r = await fetch('admin_bpm.php', { method: 'POST', body: form });
  const d = await r.json();
  if (d.success) {
    // Notifier la page principale via localStorage
    localStorage.setItem('anwa_bpm_updated', JSON.stringify({
      spotify_id: id, title: title, artist: artist,
      bpm: bpm, key: key, camelot: camelot,
      timestamp: Date.now()
    }));
    
    // Mettre à jour le compteur restant
    document.getElementById('card-' + id)?.remove();
    const remaining = document.querySelectorAll('.card[id^="card-"]').length;
    document.querySelector('.stat-num').textContent = remaining;
    
    // Afficher confirmation
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#1DB954;color:#000;padding:12px 20px;border-radius:10px;font-weight:bold;z-index:999;';
    toast.textContent = '✅ ' + title + ' — ' + bpm + ' BPM sauvegardé !';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
    
    // Si plus aucun titre manquant → bouton retour
    if (remaining === 0) {
      showReturnButton();
    }
  }
}

function showReturnButton() {
  const btn = document.createElement('a');
  btn.href = 'index.html';
  btn.style.cssText = 'display:block;text-align:center;margin:2rem auto;padding:14px 28px;background:#1DB954;color:#000;border-radius:12px;font-weight:bold;font-size:15px;text-decoration:none;max-width:300px;';
  btn.textContent = '🎵 Retour → Finaliser la playlist';
  document.body.appendChild(btn);
}

async function deleteBPM(id) {
  if (!confirm('Supprimer ce BPM manuel ?')) return;
  const form = new FormData();
  form.append('action', 'delete');
  form.append('spotify_id', id);
  const r = await fetch('admin_bpm.php', { method: 'POST', body: form });
  const d = await r.json();
  if (d.success) location.reload();
}
</script>
</body>
</html>
