<?php
session_start();
date_default_timezone_set("America/Mexico_City");

define("CARPETA_DATOS",    __DIR__ . "/datos");             
define("ARCHIVO_USUARIOS", CARPETA_DATOS . "/usuarios.json");
define("CARPETA_ARCHIVOS", __DIR__ . "/almacen");           
define("TAMANO_MAXIMO",    5 * 1024 * 1024);                

$TIPOS_PERMITIDOS = array(
    "jpg"  => "image/jpeg",
    "jpeg" => "image/jpeg",
    "png"  => "image/png",
    "gif"  => "image/gif",
    "webp" => "image/webp",
    "pdf"  => "application/pdf",
    "txt"  => "text/plain",
    "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

$GENEROS_DISPONIBLES = array("Acción", "Comedia", "Drama", "Ciencia Ficción",
                             "Terror", "Romance", "Animación", "Documental");

foreach (array(CARPETA_DATOS, CARPETA_ARCHIVOS) as $carpeta) {
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }

    if (!file_exists($carpeta . "/.htaccess")) {
        file_put_contents($carpeta . "/.htaccess", "Require all denied\n");
    }
}


function cargarUsuarios() {
    if (!file_exists(ARCHIVO_USUARIOS)) {
        return array();
    }
    $datos = json_decode(file_get_contents(ARCHIVO_USUARIOS), true);
    return is_array($datos) ? $datos : array();
}

function guardarUsuarios($usuarios) {
    file_put_contents(
        ARCHIVO_USUARIOS,
        json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function usuarioActual() {
    if (!isset($_SESSION["usuarioActual"])) {
        return null;
    }
    $usuarios = cargarUsuarios();
    $correo   = $_SESSION["usuarioActual"];
    return isset($usuarios[$correo]) ? $usuarios[$correo] : null;
}

function requiereSesion() {
    if (usuarioActual() === null) {
        unset($_SESSION["usuarioActual"]);
        header("Location: login.php");
        exit;
    }
}

function tokenCSRF() {
    if (!isset($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(16));
    }
    return $_SESSION["csrf"];
}

function tokenValido($token) {
    return isset($_SESSION["csrf"]) && is_string($token) && hash_equals($_SESSION["csrf"], $token);
}

function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

function carpetaDeUsuario($correo) {
    return CARPETA_ARCHIVOS . "/" . md5($correo);
}

function formatoTamano($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . " MB";
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . " KB";
    return $bytes . " B";
}

function esImagen($tipo) {
    return strpos($tipo, "image/") === 0;
}

function tipoReal($ruta) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipo  = finfo_file($finfo, $ruta);
    finfo_close($finfo);
    return $tipo;
}

function encabezado($titulo, $ancho = false) {
    $u = usuarioActual();
    ?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAOBA MX - <?php echo e($titulo); ?></title>
    <link rel="stylesheet" href="estilos.css">
  </head>
  <body>
    <header>
      <div class="header-inner">
        <a href="index.php" class="logo">
          <span class="logo-brand">CAOBA MX</span>
          <span class="logo-sub">Mueblería Mexicana</span>
        </a>
        <?php if ($u !== null): ?>
          <nav class="nav">
            <span class="nav-hola">Hola, <?php echo e($u["nombre"]); ?></span>
            <a href="panel.php">Mi panel</a>
            <form method="post" action="salir.php" class="nav-form">
              <input type="hidden" name="csrf" value="<?php echo e(tokenCSRF()); ?>">
              <button type="submit">Cerrar sesión</button>
            </form>
          </nav>
        <?php endif; ?>
      </div>
    </header>
    <main<?php echo $ancho ? ' class="ancho"' : ''; ?>>
    <?php
}

function pie() {
    ?>
    </main>
  </body>
</html>
    <?php
}
