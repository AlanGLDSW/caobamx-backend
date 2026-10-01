<?php
require "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && tokenValido($_POST["csrf"] ?? "")) {
    $_SESSION = array();
    session_destroy();
}
header("Location: login.php");
exit;
