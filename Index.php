<?php
// index.php

// Helper para sanear salida HTML
function e($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Si querés enviar el formulario a otro servidor en vez de procesarlo localmente,
// reemplaza null por la URL (ej: "https://mi-servidor.com/endpoint.php").
// Si $remoteUrl es null el archivo procesa localmente.
$remoteUrl = null; // <-- si querés enviar a remoto, poner la URL aquí.

$resultOutput = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave = isset($_POST['clave']) ? (string)$_POST['clave'] : '';

    if ($remoteUrl) {
        // Enviar al servidor remoto y mostrar lo que devuelva (POST)
        $postData = http_build_query(['clave' => $clave]);
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                             "Content-Length: " . strlen($postData) . "\r\n",
                'content' => $postData,
                'timeout' => 10
            ]
        ];
        $context  = stream_context_create($opts);
        $response = @file_get_contents($remoteUrl, false, $context);

        if ($response === false) {
            $resultOutput = "Error: no se pudo conectar con el servidor remoto.";
        } else {
            // Si el servidor remoto devuelve HTML o texto, lo mostramos tal cual.
            $resultOutput = $response;
        }
    } else {
        // Procesamiento local: generamos MD5 y SHA256
        $md5   = hash('md5', $clave);
        $sha256 = hash('sha256', $clave);

        // Formato de salida similar a la imagen que mostraste
        $resultOutput = "<p>Clave: " . e($clave) . "</p>\n";
        $resultOutput .= "<p>Clave encriptada en md5 (128 bits o 16 octetos o 16 pares hexadecimales):<br>" . e($md5) . "</p>\n";
        $resultOutput .= "<p>Clave: " . e($clave) . "</p>\n";
        $resultOutput .= "<p>Clave encriptada en sha256 (256 bits o 32 octetos o 32 pares hexadecimales):<br>" . e($sha256) . "</p>\n";
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Encriptar Clave - Ejemplo</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    label { display:block; margin-bottom:6px; }
    input[type="text"] { padding:4px; width:300px; }
    button { margin-top:8px; padding:6px 10px; }
    .resultado { margin-top:18px; white-space:pre-wrap; }
  </style>
</head>
<body>
  <h2>Ingrese la clave a encriptar:</h2>

  <!--
    Si querés enviar el formulario a otro servidor solo
    - pon la URL en $remoteUrl arriba, o
    - cambiá action="https://mi-servidor.com/endpoint.php"
  -->
  <form method="post" action="">
    <label for="clave">Clave:</label>
    <input id="clave" name="clave" type="text" required autocomplete="off" value="<?php if(!empty($_POST['clave'])) echo e($_POST['clave']); ?>">
    <br>
    <button type="submit">Obtener encriptación</button>
  </form>

  <div class="resultado">
    <?php
      if ($resultOutput !== '') {
          // Si $resultOutput contiene HTML del servidor remoto, lo mostramos tal cual.
          echo $resultOutput;
      }
    ?>
  </div>

  <hr>
  <small>Nota: MD5 no es seguro para almacenar contraseñas en producción. Para contraseñas reales usar <code>password_hash()</code> y <code>password_verify()</code>.</small>
</body>
</html>
