<?php
require "config.php";
requiereSesion();

$correo = $_SESSION["usuarioActual"];

function tipoCoincide($extension, $tipoReal, $tiposPermitidos) {
    if ($tipoReal === $tiposPermitidos[$extension]) return true;
    if (in_array($extension, array("docx", "xlsx")) && $tipoReal === "application/zip") return true;
    if ($extension === "txt" && strpos($tipoReal, "text/") === 0) return true;
    return false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (empty($_POST) && isset($_SERVER["CONTENT_LENGTH"]) && $_SERVER["CONTENT_LENGTH"] > 0) {
        $_SESSION["flash"] = array("error", "El archivo es demasiado grande para el servidor.");
        header("Location: panel.php");
        exit;
    }

    if (!tokenValido($_POST["csrf"] ?? "")) {
        $_SESSION["flash"] = array("error", "La solicitud expiró. Intenta de nuevo.");
        header("Location: panel.php");
        exit;
    }

    $usuarios = cargarUsuarios();
    $accion   = $_POST["accion"] ?? "";

    if ($accion === "subir") {

        $error = "";

        if (!isset($_FILES["archivo"]) || $_FILES["archivo"]["error"] === UPLOAD_ERR_NO_FILE) {
            $error = "Selecciona un archivo antes de subirlo.";
        } elseif ($_FILES["archivo"]["error"] === UPLOAD_ERR_INI_SIZE || $_FILES["archivo"]["error"] === UPLOAD_ERR_FORM_SIZE) {
            $error = "El archivo supera el tamaño máximo de " . formatoTamano(TAMANO_MAXIMO) . ".";
        } elseif ($_FILES["archivo"]["error"] !== UPLOAD_ERR_OK) {
            $error = "Ocurrió un error al subir el archivo. Intenta de nuevo.";
        } else {
            $tmp            = $_FILES["archivo"]["tmp_name"];
            $nombreOriginal = basename($_FILES["archivo"]["name"]);
            $extension      = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            $tamano         = $_FILES["archivo"]["size"];

            if (!array_key_exists($extension, $TIPOS_PERMITIDOS)) {
                $error = "Tipo de archivo no permitido. Usa: " . strtoupper(implode(", ", array_keys($TIPOS_PERMITIDOS))) . ".";
            } elseif ($tamano > TAMANO_MAXIMO) {
                $error = "El archivo supera el tamaño máximo de " . formatoTamano(TAMANO_MAXIMO) . ".";
            } elseif (!tipoCoincide($extension, tipoReal($tmp), $TIPOS_PERMITIDOS)) {
                $error = "El contenido del archivo no corresponde a su extensión.";
            }
        }

        if ($error === "") {
            $carpeta = carpetaDeUsuario($correo);
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }

            $id             = bin2hex(random_bytes(8));
            $nombreGuardado = date("Ymd_His") . "_" . $id . "." . $extension;

            if (move_uploaded_file($tmp, $carpeta . "/" . $nombreGuardado)) {

                $usuarios[$correo]["archivos"][] = array(
                    "id"             => $id,
                    "nombreOriginal" => $nombreOriginal,
                    "nombreGuardado" => $nombreGuardado,
                    "tipo"           => $TIPOS_PERMITIDOS[$extension],
                    "tamano"         => $tamano,
                    "fechaSubida"    => date("Y-m-d H:i:s")
                );
                guardarUsuarios($usuarios);

                $_SESSION["flash"] = array("ok", "Se subió \"" . $nombreOriginal . "\" correctamente.");
            } else {
                $error = "No se pudo guardar el archivo en el servidor.";
            }
        }

        if ($error !== "") {
            $_SESSION["flash"] = array("error", $error);
        }
    }

    if ($accion === "borrar") {

        $id        = $_POST["id"] ?? "";
        $archivos  = $usuarios[$correo]["archivos"];
        $borrado   = null;

        foreach ($archivos as $indice => $a) {
            if ($a["id"] === $id) {
                $borrado = $a;
                $ruta    = carpetaDeUsuario($correo) . "/" . basename($a["nombreGuardado"]);
                if (file_exists($ruta)) {
                    unlink($ruta);                
                }
                unset($archivos[$indice]);         
                break;
            }
        }

        if ($borrado !== null) {
            $usuarios[$correo]["archivos"] = array_values($archivos); 
            guardarUsuarios($usuarios);
            $_SESSION["flash"] = array("ok", "Se eliminó \"" . $borrado["nombreOriginal"] . "\".");
        } else {
            $_SESSION["flash"] = array("error", "No se encontró ese archivo en tu cuenta.");
        }
    }

    header("Location: panel.php");
    exit;
}

$usuario  = usuarioActual();
$archivos = $usuario["archivos"];

usort($archivos, function ($x, $y) {
    return strcmp($y["fechaSubida"], $x["fechaSubida"]);
});

$totalArchivos = count($archivos);
$espacioUsado  = array_sum(array_column($archivos, "tamano"));
$totalImagenes = count(array_filter($archivos, function ($a) { return esImagen($a["tipo"]); }));

if ($usuario["loginAnterior"] !== "") {
    $seg     = time() - strtotime($usuario["loginAnterior"]);
    $dias    = floor($seg / 86400);
    $horas   = floor(($seg % 86400) / 3600);
    $minutos = floor(($seg % 3600) / 60);
    $mensajeTiempo = "Hace " . $dias . " día(s), " . $horas . " hora(s) y " . $minutos . " minuto(s)";
} else {
    $mensajeTiempo = "Primer inicio de sesión, ¡bienvenido(a)!";
}

$flash = $_SESSION["flash"] ?? null;
unset($_SESSION["flash"]);

$token = tokenCSRF();

encabezado("Mi panel", true);
?>
      <?php if ($flash !== null): ?>
        <div class="<?php echo $flash[0] === "ok" ? "alerta-ok" : "alerta-error"; ?>">
          <?php echo e($flash[1]); ?>
        </div>
      <?php endif; ?>

      <div class="resumen">
        <div><b><?php echo $totalArchivos; ?></b><span>Archivos subidos</span></div>
        <div><b><?php echo formatoTamano($espacioUsado); ?></b><span>Espacio usado</span></div>
        <div><b><?php echo $totalImagenes; ?></b><span>Imágenes</span></div>
      </div>

      <div class="card">
        <div class="subtitle">Agregar un archivo</div>
        <form method="post" action="panel.php" enctype="multipart/form-data" class="zona-subida">
          <input type="hidden" name="csrf" value="<?php echo e($token); ?>">
          <input type="hidden" name="accion" value="subir">
          <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo TAMANO_MAXIMO; ?>">
          <label for="archivo"><strong>Elige un archivo de tu equipo</strong></label><br>
          <input type="file" id="archivo" name="archivo" required
                 accept="<?php echo "." . implode(",.", array_keys($TIPOS_PERMITIDOS)); ?>">
          <div class="hint">
            Permitidos: <?php echo strtoupper(implode(", ", array_keys($TIPOS_PERMITIDOS))); ?>.
            Máximo <?php echo formatoTamano(TAMANO_MAXIMO); ?>.
          </div>
          <button type="submit" class="btn">Subir archivo</button>
        </form>
      </div>

      <div class="card">
        <div class="subtitle">Mis archivos</div>

        <?php if ($totalArchivos === 0): ?>
          <p class="vacio">Todavía no has subido archivos. Usa el formulario de arriba para agregar el primero.</p>
        <?php else: ?>
          <div class="tabla-wrap">
            <table>
              <thead>
                <tr>
                  <th></th>
                  <th>NOMBRE</th>
                  <th>TAMAÑO</th>
                  <th class="col-fecha">SUBIDO</th>
                  <th>ACCIONES</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($archivos as $a): ?>
                  <tr>
                    <td>
                      <?php if (esImagen($a["tipo"])): ?>
                        <img class="miniatura" src="ver.php?id=<?php echo e($a["id"]); ?>" alt="">
                      <?php else: ?>
                        <div class="icono-archivo"><?php echo e(strtoupper(pathinfo($a["nombreGuardado"], PATHINFO_EXTENSION))); ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="nombre-archivo"><?php echo e($a["nombreOriginal"]); ?></td>
                    <td><?php echo formatoTamano($a["tamano"]); ?></td>
                    <td class="col-fecha"><?php echo date("d-m-Y H:i", strtotime($a["fechaSubida"])); ?></td>
                    <td>
                      <div class="acciones-fila">
                        <a class="btn-mini" href="ver.php?id=<?php echo e($a["id"]); ?>" target="_blank">Ver</a>
                        <a class="btn-mini" href="ver.php?id=<?php echo e($a["id"]); ?>&descargar=1">Descargar</a>
                        <form method="post" action="panel.php"
                              onsubmit="return confirm('¿Seguro que quieres borrar este archivo?');">
                          <input type="hidden" name="csrf" value="<?php echo e($token); ?>">
                          <input type="hidden" name="accion" value="borrar">
                          <input type="hidden" name="id" value="<?php echo e($a["id"]); ?>">
                          <button type="submit" class="btn-mini borrar">Borrar</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="subtitle">Mis datos</div>
        <p><strong>Correo:</strong> <?php echo e($usuario["correo"]); ?></p>
        <p><strong>Fecha de nacimiento:</strong> <?php echo date("d-m-Y", strtotime($usuario["fechaNacimiento"])); ?></p>
        <p><strong>Géneros favoritos:</strong>
          <?php echo count($usuario["generosFavoritos"]) > 0 ? e(implode(", ", $usuario["generosFavoritos"])) : "Ninguno"; ?>
        </p>
        <p><strong>Miembro desde:</strong> <?php echo date("d-m-Y", strtotime($usuario["fechaRegistro"])); ?></p>
        <p><strong>Último inicio de sesión:</strong> <?php echo e($mensajeTiempo); ?></p>
      </div>
<?php pie(); ?>
