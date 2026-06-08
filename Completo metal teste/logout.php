<?php
/* ================================================================
   logout.php  –  METAL Financeiro
   Encerra a sessão de forma segura
   ================================================================ */
session_start();

// Apaga todos os dados da sessão
$_SESSION = [];

// Remove o cookie de sessão
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();

header('Location: login.php');
exit();
