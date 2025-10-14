<?php
// index.php - Interfaz AJAX con cuenta regresiva antes de encriptar
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Encriptar (AJAX) con cuenta regresiva</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    :root {
      --w: 1100px;
      --h-top: 260px;
      --h-bottom: 220px;
      font-family: "Georgia", serif;
    }
    body { margin:20px; }
    .container {
      width: var(--w);
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      grid-template-rows: var(--h-top) var(--h-bottom);
      gap: 0;
    }
    .inputPanel { background:#9b9b9b; padding:25px; box-sizing:border-box; }
    .encryptPanel { background:#0634ff; color:#fff; text-align:center; padding:25px; box-sizing:border-box; }
    .resultPanel { background:#fff200; padding:25px; box-sizing:border-box; }
    .statusPanel { background:#3a3a3b; color:#fff; padding:25px; box-sizing:border-box; }
    .bigArea { background: #c6e6f1; padding:25px; box-sizing:border-box; }

    h1 { margin:0 0 18px 0; font-size:28px; }
    label { display:block; margin-bottom:8px; font-size:20px; }
    input[type="text"], input[type="number"] { width:260px; padding:6px; font-size:16px; }
    button.iconBtn {
      border:none; background:transparent; cursor:pointer; margin-top:18px;
    }
    .arrowWrap { display:flex; justify-content:center; align-items:center; height:100%; }
    .resText { font-family: monospace; white-space:pre-wrap; font-size:14px; color:#000; }

    .statusTitle { font-size:20px; margin-bottom:12px; color:#fff; }
    .resultTitle { font-size:26px; margin-bottom:12px; }

    /* botón grande con texto y contador */
    #startBtn {
      display:inline-block;
      padding:12px 18px;
      background:#fff;
      color:#0634ff;
      border-radius:6px;
      font-size:18px;
      cursor:pointer;
      border:3px solid rgba(0,0,0,0.15);
    }
    #startBtn[disabled] { opacity:0.5; cursor:not-allowed; }

    .small { font-size:13px; color:#222; margin-top:8px; display:block; }
    .progressBar {
      width: 260px;
      height: 12px;
      background: #eee;
      border-radius: 6px;
      overflow:hidden;
      margin: 12px auto 0;
      box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
    }
    .progressFill {
      height:100%;
      width:0%;
      background: linear-gradient(90deg, #4caf50, #8bc34a);
      transition: width 0.2s linear;
    }
  </style>
</head>
<body>
  <div class="container" role="main">
    <div class="inputPanel">
      <h1>Ingrese dato de entrada:</h1>
      <label for="clave">Clave</label>
      <input id="clave" type="text" autocomplete="off" />

      <label for="delay" style="margin-top:18px;">Tiempo antes de encriptar (segundos)</label>
      <input id="delay" type="number" min="0" max="60" value="5" />
      <span class="small">Pon 0 para encriptar inmediatamente.</span>
    </div>

    <div class="encryptPanel">
      <h1>Encriptar</h1>
      <div class="arrowWrap" style="flex-direction:column;">
        <!-- botón visible que inicia la cuenta regresiva -->
        <button id="startBtn" title="Iniciar encriptado">Iniciar encriptado</button>
        <div class="progressBar" aria-hidden="true">
          <div id="progressFill" class="progressFill"></div>
        </div>
        <div id="countdown" style="margin-top:10px; font-size:20px; font-weight:bold;"></div>
      </div>
    </div>

    <div class="resultPanel">
      <h2 class="resultTitle">Resultado:</h2>
      <div id="resultado" class="resText"></div>
    </div>

    <div class="statusPanel">
      <h2 class="statusTitle">Estado del requerimiento:</h2>
      <div id="estado" style="font-size:15px; line-height:1.6;">
        Esperando acción...
      </div>
    </div>

    <div class="bigArea">
      <div id="extraInfo" style="color:#000; font-size:15px;"></div>
    </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('startBtn');
  const claveInput = document.getElementById('clave');
  const delayInput = document.getElementById('delay');
  const estado = document.getElementById('estado');
  const resultado = document.getElementById('resultado');
  const extraInfo = document.getElementById('extraInfo');
  const countdownEl = document.getElementById('countdown');
  const progressFill = document.getElementById('progressFill');

  let timer = null;
  let startTime = null;
  let totalMs = 0;

  // función que hace la petición al servidor (igual que antes)
  async function doEncrypt() {
    const clave = claveInput.value || '';
    resultado.textContent = '';
    extraInfo.textContent = '';

    if (!clave.trim()) {
      estado.innerText = 'Error: la clave está vacía.';
      return;
    }

    estado.innerText = 'Conectando al servidor...';

    try {
      const resp = await fetch('encrypt.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ clave })
      });

      if (!resp.ok) {
        estado.innerText = 'Error HTTP: ' + resp.status + ' ' + resp.statusText;
        return;
      }

      const data = await resp.json();
      if (!data.success) {
        estado.innerText = 'Error del servidor: ' + (data.message || 'sin detalle');
        return;
      }

      let out = "";
      out += "Clave: " + data.raw + "\n";
      out += "Clave encriptada en md5 (128 bits o 16 octetos o 16 pares hexadecimales):\n" + data.md5 + "\n\n";
      out += "Clave: " + data.raw + "\n";
      out += "Clave encriptada en sha256 (256 bits o 32 octetos o 32 pares hexadecimales):\n" + data.sha256 + "\n";

      resultado.textContent = out;
      estado.innerText = 'Operación completada correctamente.';
      extraInfo.textContent = 'Timestamp: ' + new Date().toLocaleString();

    } catch (err) {
      console.error(err);
      estado.innerText = 'Error: ' + err.message;
    } finally {
      resetCountdownUI();
    }
  }

  // inicia la cuenta regresiva y luego llama a doEncrypt()
  function startCountdownAndEncrypt() {
    // si ya está en curso, ignorar
    if (timer) return;

    const delaySec = Math.max(0, Math.floor(Number(delayInput.value) || 0));
    const clave = claveInput.value || '';

    if (!clave.trim()) {
      estado.innerText = 'Error: la clave está vacía.';
      return;
    }

    // Si delay es 0 hacemos la petición inmediatamente
    if (delaySec === 0) {
      disableControls(true);
      estado.innerText = 'Enviando...';
      doEncrypt();
      return;
    }

    // preparar UI de cuenta regresiva
    disableControls(true);
    estado.innerText = 'Cuenta regresiva iniciada...';
    countdownEl.textContent = `Encriptando en ${delaySec} s`;
    startTime = Date.now();
    totalMs = delaySec * 1000;
    progressFill.style.width = '0%';

    // usar interval de 100ms para progreso fluido
    timer = setInterval(() => {
      const elapsed = Date.now() - startTime;
      const remainingMs = Math.max(0, totalMs - elapsed);
      const remainingSec = Math.ceil(remainingMs / 1000);
      countdownEl.textContent = `Encriptando en ${remainingSec} s`;

      const pct = Math.min(100, (elapsed / totalMs) * 100);
      progressFill.style.width = pct + '%';

      if (remainingMs <= 0) {
        clearInterval(timer);
        timer = null;
        countdownEl.textContent = 'Encriptando ahora...';
        progressFill.style.width = '100%';
        estado.innerText = 'Enviando...';
        // llamamos al endpoint
        doEncrypt();
      }
    }, 100);
  }

  // re-habilita controles y limpia UI de countdown
  function resetCountdownUI() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
    countdownEl.textContent = '';
    progressFill.style.width = '0%';
    disableControls(false);
  }

  function disableControls(disable) {
    btn.disabled = disable;
    delayInput.disabled = disable;
    claveInput.disabled = disable;
    if (!disable) {
      btn.textContent = 'Iniciar encriptado';
    } else {
      btn.textContent = 'Procesando...';
    }
  }

  // click inicia la cuenta regresiva
  btn.addEventListener('click', startCountdownAndEncrypt);

  // Enter en el input inicia también
  claveInput.addEventListener('keydown', function(e){
    if (e.key === 'Enter') {
      e.preventDefault();
      startCountdownAndEncrypt();
    }
  });

  // Si se quiere cancelar con Escape
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && timer) {
      // cancelar cuenta regresiva
      clearInterval(timer);
      timer = null;
      estado.innerText = 'Cuenta regresiva cancelada.';
      resetCountdownUI();
    }
  });
});
</script>
</body>
</html>
