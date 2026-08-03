<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];

    $mail = new PHPMailer(true);

    try {
        // CONFIG SMTP GMAIL
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // 🔥 SEU GMAIL AQUI
        $mail->Username = 'r1294869@gmail.com';

        // 🔥 SENHA DE APP (NÃO é senha normal)
        $mail->Password = 'wmnr mjfb isyu ueri';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // REMETENTE
        $mail->setFrom('r1294869@gmail.com', 'Sistema RELPJAM');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Solicitacao de alteracao de senha!';

        $mail->Body = "
            <h2>Você entrou com solicitação de alterar senha</h2>
            <p>Se foi você, ignore este aviso e continue o processo.</p>
            <p>Se não foi você, sua conta pode estar em risco.</p>
        ";

        $mail->send();

        $msg = "
        <div class='alert-success'>
            <strong>E-mail enviado!</strong><br>
            Verifique sua caixa de entrada: <b>$email</b>
        </div>";

    } catch (Exception $e) {
        $msg = "
        <div class='alert-success' style='background:#fee2e2;color:#991b1b;'>
            Erro ao enviar e-mail: {$mail->ErrorInfo}
        </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar Senha</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:color(white);
    padding:20px;
}

.card{
    width:100%;
    max-width:430px;
    background:#fff;
    border-radius:20px;
    padding:40px;
    box-shadow:0 20px 50px rgba(0,0,0,.15);
    animation:fadeIn .4s ease;
}

.logo{
    width:80px;
    height:80px;
    margin:0 auto 20px;
    border-radius:50%;
    background:#eef2ff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:35px;
}

h1{
    text-align:center;
    color:#1f2937;
    margin-bottom:10px;
    font-size:28px;
}

.subtitle{
    text-align:center;
    color:#6b7280;
    margin-bottom:30px;
    line-height:1.5;
}

.form-group{
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    color:#374151;
    font-weight:600;
}

input{
    width:100%;
    padding:14px 16px;
    border:1px solid #d1d5db;
    border-radius:12px;
    outline:none;
    transition:.3s;
    font-size:15px;
}

input:focus{
    border-color:#6366f1;
    box-shadow:0 0 0 4px rgb(46, 48, 71);
}

button{
    width:100%;
    border:none;
    border-radius:12px;
    padding:15px;
    background:#4f46e5;
    color:white;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:.3s;
}

button:hover{
    background:#4338ca;
    transform:translateY(-2px);
}

button:active{
    transform:translateY(0);
}

.alert-success{
    margin-top:20px;
    background:#ecfdf5;
    color:#065f46;
    border:1px solidrgb(28, 255, 7);
    padding:15px;
    border-radius:12px;
    line-height:1.6;
}

.back-link{
    display:block;
    text-align:center;
    margin-top:20px;
    color:#6366f1;
    text-decoration:none;
    font-weight:600;
}

.back-link:hover{
    text-decoration:underline;
}

@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(20px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

</style>
</head>
<body>

<div class="card">

    <div class="logo">
        RELPJAM
    </div>

    <h1>Esqueceu a senha?</h1>

    <p class="subtitle">
        Informe seu e-mail e enviaremos instruções para recuperar o acesso à sua conta.
    </p>

    <form method="POST">

        <div class="form-group">
            <label>E-mail</label>
            <input
                type="email"
                name="email"
                placeholder="Digite seu e-mail"
                required
            >
        </div>

        <button type="submit">
            Enviar Link de Recuperação
        </button>

    </form>

    <?php echo $msg; ?>

    <a href="sign.php" class="back-link">
        ← Voltar para o login
    </a>

</div>

</body>
</html>
