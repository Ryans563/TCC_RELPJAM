<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$base = '/TCC_RELPJAM';
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim((string)($_POST['nome'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $senha = (string)($_POST['senha'] ?? '');
        $telefone = trim((string)($_POST['telefone'] ?? ''));
        $cpf = trim((string)($_POST['cpf_cnpj'] ?? ''));
        $lojaNome = trim((string)($_POST['loja_nome'] ?? ''));
        $categoriaNome = trim((string)($_POST['categoria'] ?? ''));
        $descricao = trim((string)($_POST['descricao'] ?? ''));
        $cep = trim((string)($_POST['cep'] ?? ''));
        $rua = trim((string)($_POST['endereco'] ?? ''));
        $numero = trim((string)($_POST['numero'] ?? ''));
        $cidade = trim((string)($_POST['cidade'] ?? ''));
        $estado = strtoupper(trim((string)($_POST['estado'] ?? '')));

        if (!$nome || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($senha)<6 || !$lojaNome || !$cep || !$rua || !$numero || !$cidade || strlen($estado)!==2) {
            throw new RuntimeException('Preencha os campos obrigatórios e use uma senha de pelo menos 6 caracteres.');
        }

        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email=? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) throw new RuntimeException('Este e-mail já está cadastrado.');

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome,email,telefone,cpf,senha_hash,tipo_usuario,status,created_at,updated_at)
            VALUES (?,?,?,?,?,'vendedor','ativo',NOW(),NOW())
        ");
        $stmt->execute([$nome,$email,$telefone ?: null,$cpf ?: null,password_hash($senha,PASSWORD_DEFAULT)]);

        $usuarioId = (int)$pdo->lastInsertId();

        $logoPath = null;
        if (!empty($_FILES['logo']['name'])) {
            $file = $_FILES['logo'];
            if (($file['size'] ?? 0) > 6*1024*1024) throw new RuntimeException('A logo deve ter no máximo 6 MB.');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!isset($ext[$mime])) throw new RuntimeException('Logo deve estar em JPG, PNG ou WEBP.');
            $dir = dirname(__DIR__,2).'/public/uploads/stores/';
            if (!is_dir($dir)) mkdir($dir,0755,true);
            $name = bin2hex(random_bytes(12)).'.'.$ext[$mime];
            if (!move_uploaded_file($file['tmp_name'],$dir.$name)) throw new RuntimeException('Não foi possível salvar a logo.');
            $logoPath = '/app/public/uploads/stores/'.$name;
        }

        $stmt = $pdo->prepare("
            INSERT INTO vendedores (usuario_id,nome_loja,descricao,cnpj,logo,status)
            VALUES (?,?,?,?,?,'ativo')
        ");
        $stmt->execute([$usuarioId,$lojaNome,$descricao ?: null,$cpf ?: null,$logoPath]);
        $vendedorId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO loja_endereco (vendedor_id,cep,rua,numero,cidade,estado)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([$vendedorId,$cep,$rua,$numero,$cidade,$estado]);

        // Se a categoria ainda não existir, cria uma categoria exclusiva da loja.
        if ($categoriaNome !== '') {
            $slug = preg_replace('/[^a-z0-9]+/','-',strtolower(iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$categoriaNome) ?: $categoriaNome));
            $slug = trim((string)$slug,'-').'-'.bin2hex(random_bytes(3));
            $stmt = $pdo->prepare("INSERT INTO categorias (nome,slug,vendedor_id,ativo) VALUES (?,?,?,1)");
            $stmt->execute([$categoriaNome,$slug,$vendedorId]);
        }

        $pdo->commit();

        $_SESSION['usuario_id'] = $usuarioId;
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_email'] = $email;
        $_SESSION['tipo_usuario'] = 'vendedor';

        header('Location: vendedor.php');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cadastro de vendedor | RELPJAM</title>
<link rel="stylesheet" href="<?= $base ?>/public/assets/css/seller.css">
<style>.register{max-width:900px;margin:40px auto;padding:0 18px}.register .brand{text-align:center;margin-bottom:25px}.register .brand strong{font-size:28px}.register-card{background:#fff;border:1px solid #e2e8f0;border-radius:22px;padding:28px;box-shadow:0 15px 40px rgba(15,23,42,.08)}.register h1{margin:0 0 8px}.register p{color:#64748b}.register .seller-form{margin-top:24px}.error{background:#fef2f2;color:#b91c1c;padding:12px;border-radius:10px;margin-bottom:15px}.register-footer{text-align:center;margin-top:18px;color:#64748b;font-size:13px}.register-footer a{color:#2563eb;font-weight:700}</style>
</head>
<body>
<div class="register">
<div class="brand"><strong>RELPJAM</strong><p>Crie sua loja e comece a vender.</p></div>
<div class="register-card">
<h1>Cadastro de vendedor</h1>
<p>Após o cadastro, você será direcionado diretamente para o painel <b>Minha Loja</b>.</p>
<?php if($erro): ?><div class="error"><?= htmlspecialchars($erro,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
<form class="seller-form" method="post" enctype="multipart/form-data">
<div class="form-card"><h3>Seus dados</h3><div class="form-grid two">
<label>Nome completo<input name="nome" required></label>
<label>E-mail<input type="email" name="email" required></label>
<label>Telefone<input name="telefone"></label>
<label>CPF/CNPJ<input name="cpf_cnpj"></label>
<label>Senha<input type="password" name="senha" minlength="6" required></label>
</div></div>
<div class="form-card"><h3>Dados da loja</h3><div class="form-grid two">
<label>Nome da loja<input name="loja_nome" required></label>
<label>Categoria principal<select name="categoria"><option value="">Selecione</option><option>Eletrônicos</option><option>Informática</option><option>Games</option><option>Moda</option><option>Casa</option><option>Esportes</option><option>Beleza</option><option>Automotivo</option></select></label>
<label>Logo<input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></label>
</div><label>Descrição<textarea name="descricao" rows="4" placeholder="Conte um pouco sobre sua loja"></textarea></label></div>
<div class="form-card"><h3>Endereço</h3><div class="form-grid two"><label>CEP<input name="cep" required></label><label>Rua<input name="endereco" required></label><label>Número<input name="numero" required></label><label>Cidade<input name="cidade" required></label><label>Estado<input name="estado" maxlength="2" required></label></div></div>
<div class="form-actions"><a class="secondary-btn" href="sign.php">Já tenho conta</a><button class="primary-btn" type="submit">Criar minha loja</button></div>
</form>
</div></div>
</body></html>
