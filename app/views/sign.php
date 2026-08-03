<?php

session_start();

require_once __DIR__ . '/config.php';


$erro = "";
$sucesso = "";


/*
|--------------------------------------------------------------------------
| PROCESSAMENTO
|--------------------------------------------------------------------------
*/


if ($_SERVER["REQUEST_METHOD"] == "POST") {


    /*
    |--------------------------------------------------------------------------
    | LOGIN NORMAL
    |--------------------------------------------------------------------------
    */


    if (isset($_POST['login'])) {


        $email = trim($_POST['loginEmail'] ?? '');
        $senha = trim($_POST['loginPassword'] ?? '');



        try {


            $sql = "

                SELECT *

                FROM usuarios

                WHERE email = :email

                LIMIT 1

            ";



            $stmt = $pdo->prepare($sql);


            $stmt->execute([

                "email" => $email

            ]);



            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);



            if ($usuario) {



                if (password_verify($senha, $usuario['senha_hash'])) {



                    $_SESSION['usuario_id'] = $usuario['id'];

                    $_SESSION['usuario_nome'] = $usuario['nome'];

                    $_SESSION['usuario_email'] = $usuario['email'];



                    $update = $pdo->prepare("

                        UPDATE usuarios

                        SET ultimo_login = NOW()

                        WHERE id = :id

                    ");



                    $update->execute([

                        "id" => $usuario['id']

                    ]);



                    header("Location: home.php");

                    exit;



                } else {


                    $erro = "Senha inválida!";


                }



            } else {


                $erro = "Usuário não encontrado!";


            }



        } catch(PDOException $e) {


            $erro = "Erro ao realizar login!";


        }


    }




    /*
    |--------------------------------------------------------------------------
    | CADASTRO NORMAL
    |--------------------------------------------------------------------------
    */


    if (isset($_POST['signup'])) {



        $nome = trim($_POST['signupName'] ?? '');

        $email = trim($_POST['signupEmail'] ?? '');

        $senha = trim($_POST['signupPassword'] ?? '');



        try {



            $check = $pdo->prepare("

                SELECT id

                FROM usuarios

                WHERE email = :email

                LIMIT 1

            ");



            $check->execute([

                "email"=>$email

            ]);




            if ($check->fetch()) {



                $erro = "Este e-mail já está cadastrado!";



            } else {



                $senhaHash = password_hash(

                    $senha,

                    PASSWORD_DEFAULT

                );



                $insert = $pdo->prepare("


                    INSERT INTO usuarios

                    (

                        nome,

                        email,

                        senha_hash,

                        tipo_usuario,

                        status,

                        login_tipo,

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

                        'email',

                        NOW(),

                        NOW()

                    )


                ");



                $insert->execute([


                    "nome"=>$nome,

                    "email"=>$email,

                    "senha_hash"=>$senhaHash


                ]);



                $sucesso = "Cadastro realizado com sucesso!";



            }



        } catch(PDOException $e) {



            $erro = "Erro ao cadastrar usuário!";

        }


    }


}



?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>Login RELPJAM</title>


<link rel="stylesheet"
href="/TCC_RELPJAM/public/assets/css/stylecad.css">


<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


<script src="https://accounts.google.com/gsi/client" async defer></script>


<script src="/TCC_RELPJAM/public/assets/js/scriptlogin.js" defer></script>


</head>


<body>


<div class="container" id="container">



<?php if($erro): ?>

<div class="mensagem erro">

<?= $erro ?>

</div>

<?php endif; ?>



<?php if($sucesso): ?>

<div class="mensagem sucesso">

<?= $sucesso ?>

</div>

<?php endif; ?>





<!-- ================= CADASTRO ================= -->


<div class="form-container sign-up-container">

<form method="POST">


<h1>Criar Conta</h1>
<a href="/TCC_RELPJAM/app/views/home.php" class="logo">
              <img
        src="<?= $base ?>/TCC_RELPJAM/public/images/logotop.png"
        alt="Logo "
        style="height:120px;width:auto;display:block;object-fit:contain;"
    >
         </a>


<div class="social-icons">


<a href="#"
class="icon"
onclick="loginGoogle(); return false;">

<i class="fa-brands fa-google"></i>

</a>

<!--
<a href="#" class="icon">

<i class="fa-brands fa-facebook-f"></i>

</a>


<a href="#" class="icon">

<i class="fa-brands fa-github"></i>

</a>


<a href="#" class="icon">

<i class="fa-brands fa-linkedin-in"></i>

</a>

-->
</div>



<span>

ou use seu e-mail para cadastro

</span>




<input

type="text"

name="signupName"

placeholder="Nome"

required

>



<input

type="email"

name="signupEmail"

placeholder="E-mail"

required

>



<input

type="password"

name="signupPassword"

placeholder="Senha"

required

>



<button

type="submit"

name="signup">

Cadastrar

</button>



</form>


</div>





<!-- ================= LOGIN ================= -->


<div class="form-container sign-in-container">


<button onclick="window.history.back()" style="background:black; color:#fff; border:none; padding:10px 15px; border-radius:5px; cursor:pointer; margin-bottom:15px;">&larr; Voltar</button>

<form method="POST">


<h1>Entrar</h1>




<div class="social-icons">



<a href="eu"

class="icon"

onclick="loginGoogle(); return false;">


<i class="fa-brands fa-google"></i>


</a>


<!--
<a href="#" class="icon">

<i class="fa-brands fa-facebook-f"></i>

</a>



<a href="#" class="icon">

<i class="fa-brands fa-github"></i>

</a>



<a href="#" class="icon">

<i class="fa-brands fa-linkedin-in"></i>

</a>

-->

</div>




<span>

ou use seu e-mail

</span>




<input

type="email"

name="loginEmail"

placeholder="E-mail"

required

>




<input

type="password"

name="loginPassword"

placeholder="Senha"

required

>




<a href="senhaesquece.php">

Esqueceu sua senha?

</a>




<button

type="submit"

name="login">

Entrar

</button>




</form>


</div>





<!-- ================= TOGGLE ================= -->


<div class="toggle-container">


<div class="toggle">



<div class="toggle-panel toggle-left">


<h1>Olá!</h1>


<p>

Faça login para acessar sua conta.

</p>



<button

class="hidden"

id="login">

Entrar

</button>


</div>





<div class="toggle-panel toggle-right">

<a href="/TCC_RELPJAM/app/views/home.php" class="logo">
              <img
        src="<?= $base ?>/TCC_RELPJAM/public/images/logotop.png"
        alt="Logo "
        style="height:120px;width:auto;display:block;object-fit:contain;"
    >
         </a>
<h1>Bem-vindo!</h1>


<p>

Cadastre-se para começar.

</p>



<button

class="hidden"

id="register">

Cadastrar

</button>



</div>



</div>


</div>



</div>
<script>

const container = document.getElementById('container');

const registerBtn = document.getElementById('register');

const loginBtn = document.getElementById('login');


if(registerBtn){

    registerBtn.addEventListener('click',()=>{

        container.classList.add("active");

    });

}


if(loginBtn){

    loginBtn.addEventListener('click',()=>{

        container.classList.remove("active");

    });

}



/*
====================================================
LOGIN GOOGLE
====================================================
*/


function loginGoogle(){


    google.accounts.id.initialize({

        client_id:
        "1031512207739-kjbkgmifm6jf00hagq6vnevrn4vqel2f.apps.googleusercontent.com",

        callback: respostaGoogle

    });



    google.accounts.id.prompt();



}



/*
====================================================
RESPOSTA GOOGLE
====================================================
*/


function respostaGoogle(response){


    console.log("TOKEN GOOGLE:");
    console.log(response);



    if(!response.credential){

        alert("Google não retornou token");

        return;

    }



    fetch("google_auth.php",{


        method:"POST",


        headers:{


            "Content-Type":"application/json"


        },


        body:JSON.stringify({


            token_google: response.credential


        })


    })



    .then(res=>res.text())


    .then(text=>{


        console.log("Resposta PHP:",text);



        let data;



        try{


            data = JSON.parse(text);


        }catch(e){


            alert("Erro PHP retornou HTML");

            return;

        }




        if(data.status==="ok"){


            window.location.href="home.php";


        }else{


            alert(data.mensagem);


        }


    })



    .catch(err=>{


        console.log(err);


        alert("Erro comunicação Google");


    });



}


</script>


</body>

</html>