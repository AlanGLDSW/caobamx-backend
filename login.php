<?php
require "config.php";

if (usuarioActual() !== null) {
    header("Location: panel.php");
    exit;
}

$error       = "";
$correoLogin = isset($_GET["correo"]) ? $_GET["correo"] : "";
$registrado  = isset($_GET["registrado"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $correoLogin = strtolower(trim($_POST["correo"] ?? ""));
    $contrasena  = $_POST["contrasena"] ?? "";

    $usuarios = cargarUsuarios();

    if (isset($usuarios[$correoLogin]) &&
        password_verify($contrasena, $usuarios[$correoLogin]["contrasena"])) {

        session_regenerate_id(true);

        $cookieName = "ultimoLogin_" . md5($correoLogin);
        $usuarios[$correoLogin]["loginAnterior"] = isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : "";
        guardarUsuarios($usuarios);

        setcookie($cookieName, date("Y-m-d H:i:s"), time() + (86400 * 30), "/");

        $_SESSION["usuarioActual"] = $correoLogin;

        header("Location: panel.php");
        exit;
    }

    $error = "El correo o la contraseña no coinciden. Revisa tus datos o crea una cuenta.";
}

encabezado("Iniciar sesión");
?>
      <div class="card">
        <div class="section-title">Iniciar sesión</div>
        <div class="section-sub">Ingresa con tu correo y contraseña</div>

        <?php if ($registrado): ?>
          <div class="alerta-ok">¡Cuenta creada! Ahora inicia sesión para entrar a tu panel.</div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
          <div class="alerta-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
          <div class="form-group">
            <label for="correo">Correo electrónico:</label>
            <input type="email" id="correo" name="correo" placeholder="tu@correo.com"
                   value="<?php echo e($correoLogin); ?>" required>
          </div>

          <div class="form-group">
            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" placeholder="Tu contraseña" required
                   <?php echo $correoLogin !== "" ? "autofocus" : ""; ?>>
          </div>

          <button type="submit" class="btn">Iniciar sesión</button>
        </form>

        <p class="link-ver">¿No tienes cuenta? <a href="registro.php">Crea una aquí</a></p>
      </div>
<?php pie(); ?>
