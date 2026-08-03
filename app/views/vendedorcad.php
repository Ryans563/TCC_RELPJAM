```php
<?php
session_start();

$host = 'localhost';
$db   = 'relpjam_marketplace';
$user = 'root';
$pass = '';

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user,
        $pass
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    die(
        "Erro na conexão com o banco de dados: " .
        $e->getMessage()
    );
}

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome       = trim($_POST['nome'] ?? '');
    $cpf_cnpj   = trim($_POST['cpf_cnpj'] ?? '');
    $telefone   = trim($_POST['telefone'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $senha      = trim($_POST['senha'] ?? '');

    $loja_nome  = trim($_POST['loja_nome'] ?? '');
    $categoria  = trim($_POST['categoria'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');

    $cep        = trim($_POST['cep'] ?? '');
    $endereco   = trim($_POST['endereco'] ?? '');
    $numero     = trim($_POST['numero'] ?? '');
    $cidade     = trim($_POST['cidade'] ?? '');
    $estado     = trim($_POST['estado'] ?? '');

    if (
        empty($nome) ||
        empty($cpf_cnpj) ||
        empty($telefone) ||
        empty($email) ||
        empty($senha) ||
        empty($loja_nome) ||
        empty($categoria) ||
        empty($cep) ||
        empty($endereco) ||
        empty($numero) ||
        empty($cidade) ||
        empty($estado)
    ) {

        $mensagem = 'Preencha todos os campos obrigatórios.';
        $tipoMensagem = 'erro';

    } else {

        $mensagem = 'Cadastro enviado com sucesso!';
        $tipoMensagem = 'sucesso';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>RELPJAM - Cadastro de Vendedor</title>

<link
rel="stylesheet"
href="public/assets/css/stylevend.css">

<link
href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body class="vend-body">

<header class="vend-header">

<h1 class="vend-logo">
RELPJAM
</h1>

<p class="vend-subtitle">
Cadastre sua loja em nosso marketplace
</p>

</header>

<nav class="vend-nav">

<a href="auth.php">
Login
</a>

<a
href="vendedor.php"
class="vend-selected">
Vendedor
</a>

</nav>

<main class="vend-container">

<section class="vend-card">

<h2 class="vend-title">
Cadastro de Vendedor
</h2>

<form
id="vendForm"
class="vend-form"
method="POST"
enctype="multipart/form-data">

<div class="vend-grid">

<div class="vend-group">
<label>Nome Completo</label>
<input
type="text"
name="nome"
class="vend-input"
required>
</div>

<div class="vend-group">
<label>CPF ou CNPJ</label>
<input
type="text"
id="cpf_cnpj"
name="cpf_cnpj"
class="vend-input"
required>
</div>

</div>

<div class="vend-grid">

<div class="vend-group">
<label>Telefone</label>
<input
type="text"
id="telefone"
name="telefone"
class="vend-input"
required>
</div>

<div class="vend-group">
<label>E-mail</label>
<input
type="email"
name="email"
class="vend-input"
required>
</div>

</div>

<div class="vend-group">

<label>Senha</label>

<input
type="password"
name="senha"
class="vend-input"
required>

</div>

<h3 class="vend-subtitle-form">
Dados da Loja
</h3>

<div class="vend-group">

<label>Nome da Loja</label>

<input
type="text"
name="loja_nome"
class="vend-input"
required>

</div>

<div class="vend-grid">

<div class="vend-group">

<label>Categoria</label>

<select
name="categoria"
class="vend-input"
required>

<option value="">
Selecione
</option>

<option value="roupas">
Roupas
</option>

<option value="tecnologia">
Tecnologia
</option>

<option value="games">
Games
</option>

<option value="beleza">
Beleza
</option>

<option value="calcados">
Calçados
</option>

</select>

</div>

<div class="vend-group">

<label>Logo da Loja</label>

<input
type="file"
id="logo"
name="logo"
class="vend-input"
accept="image/*">

</div>

</div>

<div class="vend-preview-area">

<img
id="previewLogo"
class="vend-preview-img"
alt="Logo">

</div>

<div class="vend-group">

<label>Descrição</label>

<textarea
name="descricao"
class="vend-textarea">
</textarea>

</div>

<div class="vend-grid">

<div class="vend-group">

<label>CEP</label>

<input
type="text"
id="cep"
name="cep"
class="vend-input"
required>

</div>

<div class="vend-group">

<label>Endereço</label>

<input
type="text"
name="endereco"
class="vend-input"
required>

</div>

</div>

<div class="vend-grid">

<div class="vend-group">

<label>Número</label>

<input
type="text"
name="numero"
class="vend-input"
required>

</div>

<div class="vend-group">

<label>Cidade</label>

<input
type="text"
name="cidade"
class="vend-input"
required>

</div>

</div>

<div class="vend-group">

<label>Estado</label>

<input
type="text"
name="estado"
maxlength="2"
class="vend-input"
required>

</div>

<div class="vend-terms">

<input
type="checkbox"
required>

<label>
Aceito os termos de uso
</label>

</div>

<button
type="submit"
class="vend-btn">

Cadastrar Loja

</button>

</form>

</section>

</main>

<?php if($mensagem != ''): ?>

<script>

Swal.fire({
    icon:'<?= $tipoMensagem == "sucesso" ? "success" : "error"; ?>',
    title:'<?= $tipoMensagem == "sucesso" ? "Sucesso" : "Erro"; ?>',
    text:'<?= $mensagem; ?>',
    confirmButtonColor:'#00d9a5'
});

</script>

<?php endif; ?>

<script>

$(document).ready(function(){

    $('#telefone').mask('(00) 00000-0000');

    $('#cep').mask('00000-000');

    $('#cpf_cnpj').on('input',function(){

        let value = $(this)
            .val()
            .replace(/\D/g,'');

        if(value.length <= 11){

            $(this)
            .mask('000.000.000-00');

        }else{

            $(this)
            .mask('00.000.000/0000-00');
        }

    });

    $('#logo').on('change',function(e){

        const file = e.target.files[0];

        if(file){

            const reader = new FileReader();

            reader.onload = function(event){

                $('#previewLogo')
                    .attr('src',event.target.result)
                    .fadeIn();

            };

            reader.readAsDataURL(file);
        }

    });

});

</script>

</body>
</html>
```
