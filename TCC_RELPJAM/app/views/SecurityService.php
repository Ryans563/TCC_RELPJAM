<?php

function e($valor)
{
    return htmlspecialchars(
        $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

function csrf_token()
{
    if(empty($_SESSION['csrf_token'])){

        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));

    }

    return $_SESSION['csrf_token'];
}

function csrf_validate()
{
    if(
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token']
        )
    ){
        die('CSRF inválido');
    }
}