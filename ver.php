<?php
require "config.php";
requiereSesion();

$usuario = usuarioActual();
$id      = $_GET["id"] ?? "";
$archivo = null;

foreach ($usuario["archivos"] as $a) {
    if ($a["id"] === $id) {
        $archivo = $a;
        break;
    }
}

$ruta = $archivo !== null ? carpetaDeUsuario($usuario["correo"]) . "/" . basename($archivo["nombreGuardado"]) : "";

if ($archivo === null || !file_exists($ruta)) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}

$modo = isset($_GET["descargar"]) ? "attachment" : "inline";

header("Content-Type: " . $archivo["tipo"]);
header("Content-Length: " . filesize($ruta));
header("Content-Disposition: " . $modo . "; filename*=UTF-8''" . rawurlencode($archivo["nombreOriginal"]));
header("X-Content-Type-Options: nosniff");
readfile($ruta);
exit;
