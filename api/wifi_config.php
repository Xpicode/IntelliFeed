<?php
header('ngrok-skip-browser-warning: true');
// wifi_config.php — IntelliFeed WiFi & API Settings Page
session_start();
require_once 'api/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IntelliFeed · WiFi Setup</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #faf7f2;
  --card: rgba(255,255,255,0.9);
  --border: rgba(210,190,170,0.3);
  --text: #2c3e4f;
  --muted: #8a9aa8;
  --accent: #9b8c7c;
  --accent2: #b9a89b;
  --success: #10B981;
  --warning: #F59E0B;
  --danger: #EF4444;
  --info: #3B82F6;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Inter',sans-serif; background:radial-gradient(circle at top right,#f5efe6,#f0e9e0); min-height:100vh; color:var(--text); }

.header {
  background:var(--card); backdrop-filter:blur(10px);
  border-bottom:1px solid var(--border);
  padding:12px 20px;
  display:flex; align-items:center; gap:12px;
}
.header a { color:var(--accent); text-decoration:none; font-size:0.85rem; display:flex; align-items:center; gap:6px; }
.header a:hover { text-decoration:underline; }
.header h1 { font-size:1rem; font-weight:600; flex:1; }

.container { max-width:700px; margin:0 auto; padding:24px 16px; }

.card {
  background:var(--card); backdrop-filter:blur(10px);
  border-radius:20px; padding:24px;
  border:1px solid var(--border);
  box-shadow:0 4px 16px rgba(0,0,0,0.04);
  margin-bottom:16px;
}

.card-title { font-size:1rem; font-weight:600; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.card-title .icon { font-size:1.3rem; }

.status-bar {
  display:flex; align-items:center; gap:10px; padding:12px 16px;
  background:#f0f9f4; border:1px solid #a7f3d0; border-radius:12px; margin-bottom:20px;
  font-size:0.8rem;
}
.status-bar.disconnected { background:#fef9f0; border-color:#fcd34d; }
.status-bar.error { background:#fef2f2; border-color:#fca5a5; }

.dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.dot.green { background:var(--success); animation:pulse 2s infinite; }
.dot.yellow { background:var(--warning); }
.dot.red { background:var(--danger); }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }

.form-group { margin-bottom:16px; }
.form-group label { display:block; font-size:0.75rem; font-weight:600; color:var(--muted); margin-bottom:6px; letter-spacing:0.02em; }
.form-group input {
  width:100%; padding:12px 14px; border-radius:10px;
  border:1px solid var(--border); background:#fff;
  font-size:0.9rem; color:var(--text); outline:none;
  transition:border-color 0.2s;
}
.form-group input:focus { border-color:var(--accent); }
.form-group .hint { font-size:0.7rem; color:var(--muted); margin-top:5px; }

.btn {
  display:inline-flex; align-items:center; gap:8px;
  padding:12px 22px; border-radius:30px; border:none;
  font-size:0.85rem; font-weight:600; cursor:pointer;
  transition:all 0.2s;
}
.btn-primary { background:linear-gradient(135deg,var(--accent),var(--accent2)); color:white; }
.btn-primary:hover { transform:scale(1.02); box-shadow:0 4px 12px rgba(0,0,0,0.12); }
.btn-secondary { background:rgba(155,140,124,0.1); color:var(--accent); border:1px solid var(--border); }
.btn-danger { background:rgba(239,68,68,0.1); color:var(--danger); border:1px solid rgba(239,68,68,0.3); }
.btn-danger:hover { background:var(--danger); color:white; }

.btn-row { display:flex; gap:10px; flex-wrap:wrap; margin-top:20px; }

.info-box {
  background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.25);
  border-radius:14px; padding:14px 16px;
  font-size:0.78rem; line-height:1.7; color:#1d4ed8;
}
.info-box b { font-weight:600; display:block; margin-bottom:4px; }

.steps { list-style:none; counter-reset:steps; }
.steps li { counter-increment:steps; display:flex; gap:12px; margin-bottom:12px; align-items:flex-start; }
.steps li::before {
  content:counter(steps);
  background:var(--accent); color:white;
  border-radius:50%; width:24px; height:24px; font-size:0.75rem;
  display:flex; align-items:center; justify-content:center; flex-shrink:0; font-weight:700;
}
.steps li .step-text { font-size:0.82rem; line-height:1.5; padding-top:2px; }
.steps li .step-text b { color:var(--text); }

.badge { padding:3px 10px; border-radius:20px; font-size:0.68rem; font-weight:600; }
.badge-green { background:rgba(16,185,129,0.12); color:var(--success); }
.badge-yellow { background:rgba(245,158,11,0.12); color:var(--warning); }

.divider { height:1px; background:var(--border); margin:20px 0; }

.current-config {
  background:rgba(155,140,124,0.06); border-radius:12px; padding:14px;
  font-size:0.78rem; line-height:1.8; margin-bottom:16px;
}
.current-config .row { display:flex; gap:8px; }
.current-config .lbl { color:var(--muted); min-width:90px; flex-shrink:0; }
.current-config .val { color:var(--text); font-weight:500; word-break:break-all; }

#toast {
  position:fixed; top:16px; left:50%; transform:translateX(-50%);
  padding:12px 24px; border-radius:40px; box-shadow:0 4px 16px rgba(0,0,0,0.15);
  font-size:0.85rem; font-weight:500; z-index:9999; display:none;
  backdrop-filter:blur(10px); text-align:center;
}
</style>
</head>
<body>

<div class="header">
  <a href="index.php">← Dashboard</a>
  <h1>📶 WiFi & API Setup</h1>
  <span style="font-size:0.75rem; color:var(--muted);">👋 <?= htmlspecialchars($username) ?></span>
</div>

<div class="container">

  <!-- Connection Status -->
  <div class="card">
    <div class="card-title"><span class="icon">📡</span> Device Status</div>
    <div id="statusBar" class="status-bar disconnected">
      <div class="dot yellow" id="statusDot"></div>
      <div>
        <strong id="statusText">Checking connection...</strong>
        <span id="statusDetail" style="margin-left:8px; color:var(--muted);"></span>
      </div>
    </div>
    <div id="currentConfig" class="current-config" style="display:none;">
      <div class="row"><span class="lbl">ESP32 IP:</span><span class="val" id="cfgIp">--</span></div>
      <div class="row"><span class="lbl">SSID:</span><span class="val" id="cfgSsid">--</span></div>
      <div class="row"><span class="lbl">Weight:</span><span class="val" id="cfgWeight">--</span></div>
    </div>
    <div style="font-size:0.75rem; color:var(--muted);">
      ESP32 IP address is needed to update WiFi from here. If not connected, use the <strong>AP Setup</strong> method below.
    </div>
  </div>

  <!-- Update WiFi (when ESP32 is already connected) -->
  <div class="card">
    <div class="card-title"><span class="icon">🔄</span> Update WiFi via Dashboard</div>
    <div style="font-size:0.78rem; color:var(--muted); margin-bottom:16px;">
      Use this when the ESP32 is already online. Enter its current IP address below, then the new credentials.
    </div>

    <div class="form-group">
      <label>ESP32 IP Address</label>
      <input type="text" id="esp32Ip" placeholder="e.g. 192.168.1.50" value="">
      <div class="hint">Find this in your router's device list, or check the Arduino IDE serial monitor</div>
    </div>
    <div class="divider"></div>
    <div class="form-group">
      <label>New WiFi SSID</label>
      <input type="text" id="newSsid" placeholder="e.g. MyHomeWifi">
    </div>
    <div class="form-group">
      <label>New WiFi Password</label>
      <input type="password" id="newPassword" placeholder="Leave blank if open network">
    </div>
    <div class="form-group">
      <label>API Base URL (ngrok)</label>
      <input type="url" id="newApiBase" placeholder="https://xxxx.ngrok.io/IntelliFeed">
      <div class="hint">Your current ngrok URL. Don't include trailing slash.</div>
    </div>

    <div class="btn-row">
      <button class="btn btn-primary" onclick="updateWifi()">💾 Save & Restart ESP32</button>
      <button class="btn btn-secondary" onclick="testConnection()">🔍 Test Connection</button>
    </div>
  </div>

  <!-- First Time / AP Mode Setup -->
  <div class="card">
    <div class="card-title"><span class="icon">🆕</span> First-Time Setup (AP Mode)</div>
    <p style="font-size:0.8rem; color:var(--muted); margin-bottom:16px;">
      Use this method when the ESP32 has never been configured, or if you need to connect to a completely different network.
    </p>

    <ol class="steps">
      <li>
        <div class="step-text">
          Power on the ESP32. If no WiFi credentials are saved, it automatically creates a hotspot called
          <b>"IntelliFeed-Setup"</b> <span class="badge badge-yellow">password: intellifeed123</span>
        </div>
      </li>
      <li>
        <div class="step-text">
          On your phone or laptop, connect to the <b>IntelliFeed-Setup</b> WiFi network.
          A captive portal will open automatically (just like hotel WiFi). If it doesn't, open your browser and go to <b>http://192.168.4.1</b>
        </div>
      </li>
      <li>
        <div class="step-text">
          Enter your <b>home WiFi name and password</b>, plus your <b>ngrok URL</b>, then press <b>Save & Connect</b>.
        </div>
      </li>
      <li>
        <div class="step-text">
          The ESP32 restarts and connects to your WiFi automatically.
          Reconnect your phone/laptop back to your normal WiFi and open the IntelliFeed dashboard. ✅
        </div>
      </li>
    </ol>

    <div class="info-box" style="margin-top:20px;">
      <b>💡 Tip — Reset WiFi credentials anytime:</b>
      Open the Arduino IDE serial monitor, type <code style="background:rgba(0,0,0,0.06); padding:1px 6px; border-radius:4px;">clearwifi</code> and press Enter.
      The ESP32 will restart in AP mode so you can enter new credentials.
    </div>
  </div>

  <!-- Ngrok Instructions -->
  <div class="card">
    <div class="card-title"><span class="icon">🌐</span> Using ngrok</div>
    <ol class="steps">
      <li><div class="step-text">Download & install <b>ngrok</b> from <a href="https://ngrok.com" target="_blank" style="color:var(--accent);">ngrok.com</a> (free account needed)</div></li>
      <li><div class="step-text">Run your local server (XAMPP/WAMP/Laragon) on port 80</div></li>
      <li><div class="step-text">Open terminal and run: <code style="background:rgba(0,0,0,0.06); padding:2px 8px; border-radius:6px; font-size:0.9em;">ngrok http 80</code></div></li>
      <li><div class="step-text">Copy the <b>https://xxxx.ngrok.io</b> URL shown, and paste it into the ESP32 setup above (add <b>/IntelliFeed</b> at the end)</div></li>
      <li><div class="step-text">⚠️ Free ngrok URLs change every restart. When you get a new URL, use the <b>"Update WiFi via Dashboard"</b> section above to update the ESP32 without reflashing.</div></li>
    </ol>
  </div>

</div>

<div id="toast"></div>

<script>
const ESP32_IP_KEY = 'intellifeed_esp32_ip';

// Load saved IP
document.getElementById('esp32Ip').value = localStorage.getItem(ESP32_IP_KEY) || '';

function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.display = 'block';
  t.style.background = type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#F59E0B';
  t.style.color = 'white';
  setTimeout(() => { t.style.display = 'none'; }, 3500);
}

async function testConnection() {
  const ip = document.getElementById('esp32Ip').value.trim();
  if (!ip) { showToast('Please enter the ESP32 IP address', 'warning'); return; }

  localStorage.setItem(ESP32_IP_KEY, ip);
  showToast('🔍 Testing connection...', 'warning');

  try {
    const res = await fetch(`http://${ip}/status`, { signal: AbortSignal.timeout(5000) });
    if (res.ok) {
      const data = await res.json();
      document.getElementById('cfgIp').textContent     = ip;
      document.getElementById('cfgSsid').textContent   = data.ssid || '--';
      document.getElementById('cfgWeight').textContent = (data.weight || 0).toFixed(1) + 'g';
      document.getElementById('currentConfig').style.display = 'block';

      const bar  = document.getElementById('statusBar');
      const dot  = document.getElementById('statusDot');
      const text = document.getElementById('statusText');
      bar.className  = 'status-bar';
      dot.className  = 'dot green';
      text.textContent = '✅ ESP32 is online and reachable';
      document.getElementById('statusDetail').textContent = `IP: ${ip} · SSID: ${data.ssid}`;
      showToast('✅ Connected to ESP32!', 'success');

      // Pre-fill SSID
      if (data.ssid) document.getElementById('newSsid').value = data.ssid;
    } else {
      throw new Error('HTTP ' + res.status);
    }
  } catch(e) {
    const bar  = document.getElementById('statusBar');
    const dot  = document.getElementById('statusDot');
    const text = document.getElementById('statusText');
    bar.className  = 'status-bar error';
    dot.className  = 'dot red';
    text.textContent = '❌ Cannot reach ESP32';
    document.getElementById('statusDetail').textContent = `at ${ip} — check IP and WiFi`;
    document.getElementById('currentConfig').style.display = 'none';
    showToast('❌ Cannot connect to ESP32 at ' + ip, 'error');
  }
}

async function updateWifi() {
  const ip      = document.getElementById('esp32Ip').value.trim();
  const ssid    = document.getElementById('newSsid').value.trim();
  const pass    = document.getElementById('newPassword').value;
  let   apiBase = document.getElementById('newApiBase').value.trim();

  if (!ip)      { showToast('Enter the ESP32 IP address first', 'warning'); return; }
  if (!ssid)    { showToast('WiFi SSID cannot be empty', 'warning'); return; }
  if (!apiBase) { showToast('API base URL cannot be empty', 'warning'); return; }
  if (!apiBase.startsWith('http')) { showToast('API URL must start with http/https', 'warning'); return; }
  if (apiBase.endsWith('/')) apiBase = apiBase.slice(0, -1);

  if (!confirm(`Save new settings to ESP32?\n\nSSID: ${ssid}\nAPI: ${apiBase}\n\nThe ESP32 will restart.`)) return;

  localStorage.setItem(ESP32_IP_KEY, ip);
  showToast('💾 Sending to ESP32...', 'warning');

  try {
    const res = await fetch(`http://${ip}/wifi_update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ssid, password: pass, api_base: apiBase }),
      signal: AbortSignal.timeout(8000)
    });
    if (res.ok) {
      const data = await res.json();
      if (data.success) {
        showToast('✅ ' + (data.message || 'Saved! ESP32 is restarting...'), 'success');
        // Also save to our DB for reference
        saveToDB(ssid, apiBase);
      } else {
        showToast('❌ Error: ' + (data.message || 'Unknown'), 'error');
      }
    } else {
      throw new Error('HTTP ' + res.status);
    }
  } catch(e) {
    showToast('❌ Failed to reach ESP32. Is the IP correct?', 'error');
  }
}

async function saveToDB(ssid, apiBase) {
  try {
    await fetch('api/save_wifi_config.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ssid, api_base: apiBase }),
      credentials: 'include'
    });
  } catch(e) { /* silent */ }
}

// Auto test if we have a saved IP
window.addEventListener('DOMContentLoaded', () => {
  const savedIp = localStorage.getItem(ESP32_IP_KEY);
  if (savedIp) {
    document.getElementById('esp32Ip').value = savedIp;
    testConnection();
  }
});
</script>
</body>
</html>