<?php
require 'includes/funciones.php';
$_SESSION = [];
setcookie(session_name(), '', time() - 3600, '/');
session_destroy();
header('Location: index.php?salida=1');
