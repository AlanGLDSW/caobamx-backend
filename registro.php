<?php
require "config.php";

if (usuarioActual() !== null) {
    header("Location: panel.php");
    exit;
}

$errores         = array();
$nombre          = "";
$correo          = "";
$fechaNacimiento = "";
$generos         = array();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre          = trim($_POST["nombre"] ?? "");
    $correo          = strtolower(trim($_POST["correo"] ?? ""));
    $contrasena      = $_POST["contrasena"] ?? "";
    $confirmar       = $_POST["confirmar"] ?? "";
    $fechaNacimiento = $_POST["fecha_nacimiento"] ?? "";
    $generos         = isset($_POST["generos"]) && is_array($_POST["generos"]) ? $_POST["generos"] : array();

    $generos = array_values(array_intersect($generos, $GENEROS_DISPONIBLES));

    $usuarios = cargarUsuarios();

    if ($nombre === "") {
        $errores[] = "Escribe tu nombre.";
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Escribe un correo válido, por ejemplo tu@correo.com.";
    } elseif (isset($usuarios[$correo])) {
        $errores[] = "Ese correo ya está registrado. Inicia sesión o usa otro correo.";
    }

    if (strlen($contrasena) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($contrasena !== $confirmar) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    if ($fechaNacimiento === "" || strtotime($fechaNacimiento) === false) {
        $errores[] = "Indica tu fecha de nacimiento.";
    } elseif (strtotime($fechaNacimiento) > time()) {
        $errores[] = "La fecha de nacimiento no puede ser posterior a hoy.";
    }

    if (count($errores) === 0) {

        $usuarios[$correo] = array(
            "nombre"           => $nombre,
            "correo"           => $correo,
            "contrasena"       => password_hash($contrasena, PASSWORD_DEFAULT), 
            "fechaNacimiento"  => $fechaNacimiento,
            "generosFavoritos" => $generos,
            "fechaRegistro"    => date("Y-m-d H:i:s"),
            "loginAnterior"    => "",
            "archivos"         => array()  
        );

        guardarUsuarios($usuarios);

        mkdir(carpetaDeUsuario($correo), 0755, true);

        header("Location: login.php?registrado=1&correo=" . urlencode($correo));
        exit;
    }
}

encabezado("Crear cuenta");
?>
      <div class="card">
        <div class="section-title">Crear cuenta</div>
        <div class="section-sub">Regístrate para guardar y administrar tus archivos</div>

        <?php if (count($errores) > 0): ?>
          <div class="alerta-error">
            <ul>
              <?php foreach ($errores as $error): ?>
                <li><?php echo e($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="registro.php">
          <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" placeholder="Tu nombre"
                   value="<?php echo e($nombre); ?>" required>
          </div>

          <div class="form-group">
            <label for="correo">Correo electrónico:</label>
            <input type="email" id="correo" name="correo" placeholder="tu@correo.com"
                   value="<?php echo e($correo); ?>" required>
          </div>

          <div class="fila">
            <div class="form-group">
              <label for="contrasena">Contraseña:</label>
              <input type="password" id="contrasena" name="contrasena" placeholder="Mínimo 6 caracteres" minlength="6" required>
            </div>
            <div class="form-group">
              <label for="confirmar">Confirmar contraseña:</label>
              <input type="password" id="confirmar" name="confirmar" placeholder="Repítela" minlength="6" required>
            </div>
          </div>

          <div class="form-group">
            <label for="fecha_nacimiento">Fecha de nacimiento:</label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                   max="<?php echo date("Y-m-d"); ?>"
                   value="<?php echo e($fechaNacimiento); ?>" required>
          </div>

          <fieldset>
            <legend>Géneros de películas favoritas:</legend>
            <div class="checkbox-group">
              <?php foreach ($GENEROS_DISPONIBLES as $g): ?>
                <label>
                  <input type="checkbox" name="generos[]" value="<?php echo e($g); ?>"
                         <?php echo in_array($g, $generos) ? "checked" : ""; ?>>
                  <?php echo e($g); ?>
                </label>
              <?php endforeach; ?>
            </div>
          </fieldset>

          <button type="submit" class="btn">Crear cuenta</button>
        </form>

        <p class="link-ver">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
      </div>
<?php pie(); ?>
