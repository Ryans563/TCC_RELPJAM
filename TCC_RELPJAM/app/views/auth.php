
<?php

$host = 'aws-1-us-west-2.pooler.supabase.com';
$port = '5432';
$db   = 'postgres';
$user = 'postgres.enkfnnaebiiqyycmegyp';
$pass = 'KU74wvnR7Zd4x6VeEoaZ';

try {

    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$db;sslmode=require",
        $user,
        $pass
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conectado com sucesso!";

} catch (PDOException $e) {

    die("Erro na conexão: " . $e->getMessage());

}

$mensagem = '';
$tipoMensagem = '';

if (isset($_POST['entrar'])) {

    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {

        $mensagem = 'Por favor, preencha todos os campos.';
        $tipoMensagem = 'erro';

    } else {

        try {

            $sql = "SELECT id, nome, senha
                    FROM usuarios
                    WHERE email = :email";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'email' => $email
            ]);

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {

                if (password_verify($senha, $usuario['senha'])) {

                    $_SESSION['id'] = $usuario['id'];
                    $_SESSION['nome'] = $usuario['nome'];

                    header("Location: home.php");
                    exit();

                } else {

                    $mensagem = 'Senha incorreta!';
                    $tipoMensagem = 'erro';
                }

            } else {

                $mensagem = 'Usuário não encontrado!';
                $tipoMensagem = 'erro';
            }

        } catch (PDOException $e) {

            $mensagem = 'Erro no banco de dados: ' . $e->getMessage();
            $tipoMensagem = 'erro';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>RELPJAM - Login</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="public/assets/css/styleauth.css">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body class="auth-body">

    <header class="auth-header">

        <h1>RELPJAM</h1>

        <p>Sua Loja de Compras Online</p>

    </header>

    <nav class="auth-nav-menu">

        <a
            href="auth.php"
            class="auth-nav-selected">
            Login
        </a>

        <a href="vendedor.php">
            Vendedor
        </a>

    </nav>

    <div class="auth-container">

        <section class="auth-login-section">

            <h2>Entrar</h2>

            <form
                action="auth.php"
                method="POST">

                <div class="auth-form-group">

                    <input
                        class="auth-form-control"
                        type="email"
                        name="email"
                        placeholder="Digite seu e-mail"
                        required>

                </div>

                <div class="auth-form-group">

                    <input
                        class="auth-form-control"
                        type="password"
                        name="senha"
                        placeholder="Digite sua senha"
                        maxlength="18"
                        required>

                </div>

                <div class="auth-form-group">

                    <button
                        class="auth-btn-login"
                        type="submit"
                        name="entrar">

                        Entrar

                    </button>

                </div>

            </form>

            <div class="auth-text-center">

                Não tem conta ainda?

                <a href="cadastro.php">
                    Criar conta
                </a>

            </div>

            <div class="auth-divider">
                ou
            </div>

            <button
                class="auth-google-btn"
                onclick="loginGoogle()">

                <img
                    class="auth-google-logo"
                    src="https://cdn-icons-png.flaticon.com/512/2991/2991148.png"
                    alt="Google">

                Entrar com Google

            </button>

            <div class="auth-guest">

                <a href="home.php">
                    Continuar como visitante
                </a>

            </div>

        </section>

    </div>

    <script>

        function loginGoogle() {

            Swal.fire({
                title: 'Google Login',
                text: 'Redirecionando para autenticação Google...',
                icon: 'info',
                confirmButtonColor: '#00d9a5'
            });

            setTimeout(() => {
                window.location.href = "home.php";
            }, 1500);
        }

    </script>

</body>
</html>
```
