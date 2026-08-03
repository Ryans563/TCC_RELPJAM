<?php //controle de permissões

function isLogged()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if(!isLogged()){

        header(
            "Location: index.php?page=auth"
        );

        exit;
    }
}

function requireAdmin()
{
    requireLogin();

    if($_SESSION['tipo'] !== 'administrador'){

        die('Acesso negado');
    }
}

function requireSeller()
{
    requireLogin();

    if($_SESSION['tipo'] !== 'vendedor'){

        die('Acesso negado');
    }
}