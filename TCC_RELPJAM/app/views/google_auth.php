<?php

session_start();

require_once __DIR__ . "/config.php";

header("Content-Type: application/json; charset=utf-8");



/*
====================================================
RECEBER DADOS DO JAVASCRIPT
====================================================
*/


$dados = json_decode(
    file_get_contents("php://input"),
    true
);



if(
    !$dados ||
    !isset($dados["token_google"])
){

    echo json_encode([

        "status" => "erro",

        "mensagem" => "Token não recebido"

    ]);

    exit;

}



$token = $dados["token_google"];





/*
====================================================
VALIDAR TOKEN GOOGLE
====================================================
*/


$url = 
"https://oauth2.googleapis.com/tokeninfo?id_token="
. urlencode($token);



$resposta = file_get_contents($url);



if(!$resposta){


    echo json_encode([

        "status"=>"erro",

        "mensagem"=>"Falha ao validar token Google"

    ]);

    exit;

}



$google = json_decode(
    $resposta,
    true
);



if(
    !isset($google["email"])
){


    echo json_encode([

        "status"=>"erro",

        "mensagem"=>"Token Google inválido"

    ]);


    exit;

}





/*
====================================================
DADOS DO GOOGLE
====================================================
*/


$email = $google["email"];

$nome = $google["name"] ?? "Usuário Google";

$foto = $google["picture"] ?? null;





try {


/*
====================================================
VERIFICAR USUÁRIO EXISTENTE
====================================================
*/


    $buscar = $pdo->prepare("

        SELECT *

        FROM usuarios

        WHERE email = :email

        LIMIT 1

    ");



    $buscar->execute([

        "email"=>$email

    ]);



    $usuario = $buscar->fetch(PDO::FETCH_ASSOC);






/*
====================================================
CRIAR USUÁRIO NOVO
====================================================
*/


    if(!$usuario){



        $senhaHash = null;



        $insert = $pdo->prepare("


            INSERT INTO usuarios

            (

                nome,

                email,

                senha_hash,

                tipo_usuario,

                status,

                login_tipo,

                foto_perfil,

                ultimo_login,

                created_at,

                updated_at

            )


            VALUES


            (

                :nome,

                :email,

                :senha_hash,

                'cliente',

                'ativo',

                'google',

                :foto_perfil,

                NOW(),

                NOW(),

                NOW()

            )


        ");




        $insert->execute([


            "nome"=>$nome,

            "email"=>$email,

            "senha_hash"=>$senhaHash,

            "foto_perfil"=>$foto


        ]);




        $id = $pdo->lastInsertId();





        $usuario = [


            "id"=>$id,

            "nome"=>$nome,

            "email"=>$email


        ];



    }





/*
====================================================
ATUALIZAR LOGIN
====================================================
*/


    $update = $pdo->prepare("


        UPDATE usuarios

        SET

            ultimo_login = NOW(),

            login_tipo = 'google',

            foto_perfil = :foto_perfil,

            updated_at = NOW()


        WHERE id = :id


    ");




    $update->execute([


        "foto_perfil"=>$foto,


        "id"=>$usuario["id"]


    ]);







/*
====================================================
CRIAR SESSÃO
====================================================
*/


    $_SESSION["usuario_id"] = $usuario["id"];

    $_SESSION["usuario_nome"] = $usuario["nome"];

    $_SESSION["usuario_email"] = $usuario["email"];





    echo json_encode([


        "status"=>"ok",

        "nome"=>$usuario["nome"],

        "email"=>$usuario["email"]


    ]);



}


catch(PDOException $e){



    echo json_encode([


        "status"=>"erro",

        "mensagem"=>"Erro banco: ".$e->getMessage()


    ]);



}