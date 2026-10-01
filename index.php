<?php
require "config.php";
header("Location: " . (usuarioActual() !== null ? "panel.php" : "login.php"));
exit;
