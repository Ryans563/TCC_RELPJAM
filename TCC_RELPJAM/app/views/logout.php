<?php

session_start();

/* remove todas as variáveis de sessão */
$_SESSION = [];

/* destrói a sessão */
session_destroy();

/* limpa cookie da sessão (boa prática) */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* volta para home */
header("Location: home.php");
exit;
?>