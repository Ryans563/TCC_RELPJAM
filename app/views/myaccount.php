<?php

session_start();
require_once "config.php";

/* =========================
   AUTH
========================= */
if (!isset($_SESSION['usuario_id'])) {
    header("Location: sign.php");
    exit;
}

$id = $_SESSION['usuario_id'];

/* =========================
   UPDATE PERFIL
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['atualizar_perfil'])) {

    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $cpf = trim($_POST['cpf']);

    /* =========================
       FOTO ATUAL
    ========================= */
    $stmt = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $fotoNome = $stmt->fetchColumn();

    /* =========================
       UPLOAD SUPABASE STORAGE (PRIVATE)
    ========================= */
    if (!empty($_FILES['foto']['name'])) {

        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg','jpeg','png','webp'];

        if (in_array($ext, $permitidas)) {

            $fotoNome = 'user_' . uniqid() . '.' . $ext;

            $fileData = file_get_contents($_FILES['foto']['tmp_name']);

            $bucket = "avatars";
            $url = $SUPABASE_URL . "/storage/v1/object/" . $bucket . "/" . $fotoNome;

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer SUA SECRET KEY",
                "Content-Type: image/" . $ext,
                "x-upsert: true"
            ]);

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_exec($ch);
            curl_close($ch);
        }
    }

    /* =========================
       UPDATE USUÁRIO
    ========================= */
    $stmt = $pdo->prepare("
        UPDATE usuarios SET
            nome = :nome,
            email = :email,
            telefone = :telefone,
            cpf = :cpf,
            foto_perfil = :foto
        WHERE id = :id
    ");

    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':telefone' => $telefone,
        ':cpf' => $cpf,
        ':foto' => $fotoNome,
        ':id' => $id
    ]);

    header("Location: myaccount.php");
    exit;
}
/* =========================
   USER
========================= */
$stmt = $pdo->prepare("
    SELECT nome, email, telefone, cpf, foto_perfil, data_nascimento, tipo_usuario
    FROM usuarios
    WHERE id = :id
");

$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) exit("Usuário não encontrado.");

/* =========================
   IMAGEM (SIGNED URL PRIVATE BUCKET)
========================= */
$foto = "https://ui-avatars.com/api/?name=" . urlencode($user['nome']) . "&background=444&color=fff&size=256";

if (!empty($user['foto_perfil'])) {

    $file = $user['foto_perfil'];

    $url = $SUPABASE_URL . "/storage/v1/object/sign/avatars/" . $file;

    $payload = json_encode(["expiresIn" => 3600]);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
             "Authorization: Bearer " . $SUPABASE_KEY,
        "Content-Type: application/json"
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        $foto = "https://ui-avatars.com/api/?name=" . urlencode($user['nome']);
    } else {
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['signedURL']) && !empty($data['signedURL'])) {
            $foto = rtrim($SUPABASE_URL, '/') . $data['signedURL'];
        }
    }
}

/* =========================
   PEDIDOS
========================= */
$stmt = $pdo->prepare("
    SELECT id, status, total, created_at
    FROM pedidos
    WHERE usuario_id = :id
    ORDER BY id DESC
");

$stmt->execute([':id' => $id]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   MOCKS
========================= */
$compras = [];
$fav = [];
$lojas = [];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Minha Conta</title>

<link rel="stylesheet" href="/TCC_RELPJAM/public/assets/css/style_profile.css">
</head>

<body>

<div class="account-container">

    <!-- SIDEBAR -->
    <div class="account-sidebar">

        <div class="user-box">
            <img src="<?= $foto ?>" class="user-img">
            <h3><?= htmlspecialchars($user['nome']) ?></h3>
            <span><?= htmlspecialchars($user['email']) ?></span>
        </div>

        <button class="tab-btn active" data-tab="perfil">👤 Perfil</button>
        <button class="tab-btn" data-tab="pedidos">📦 Pedidos</button>
        <button class="tab-btn" data-tab="compras">🛒 Compras</button>
        <button class="tab-btn" data-tab="favoritos">❤️ Favoritos</button>
        <button class="tab-btn" data-tab="lojas">🏪 Lojas</button>
        <a href="logout.php" class="tab-btn">
    🚪 Sair do Perfil
</a>

    </div>

    <!-- CONTENT -->
    <div class="account-content">

        <!-- PERFIL -->
        <div class="tab active" id="perfil">

            <h2>Meu Perfil</h2>

            <form method="POST" enctype="multipart/form-data" class="perfil-form">

                <img src="<?= $foto ?>" class="user-img-large">

                <input type="file" name="foto">

                <p>Nome</p><input type="text" name="nome" value="<?= $user['nome'] ?>">
                <p>Email</p><input type="email" name="email" value="<?= $user['email'] ?>">
                <p>Telefone</p><input type="text" name="telefone" value="<?= $user['telefone'] ?>">
                <p>CPF</p><input type="text" name="cpf" value="<?= $user['cpf'] ?>">
                <p>Data de Nascimento</p><input type="text" name="data_nascimento" value="<?= $user['data_nascimento'] ?>">

                <button type="submit" name="atualizar_perfil">Salvar</button>

            </form>

        </div>

        <!-- PEDIDOS -->
        <div class="tab" id="pedidos">
            <h2>Meus Pedidos</h2>

            <?php if($pedidos): ?>
                <?php foreach($pedidos as $p): ?>
                    <div class="box-info">
                        #<?= $p['id'] ?><br>
                        <?= $p['status'] ?><br>
                        R$ <?= number_format($p['total'],2,',','.') ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="box-info">Nenhum pedido</div>
            <?php endif; ?>
        </div>

        <div class="tab" id="compras">
            <h2>Compras</h2>
            <div class="box-info">Nenhuma compra</div>
        </div>

        <div class="tab" id="favoritos">
            <h2>Favoritos</h2>
            <div class="box-info">Nenhum favorito</div>
        </div>

        <div class="tab" id="lojas">
            <h2>Lojas</h2>
            <div class="box-info">Nenhuma loja</div>
        </div>

    </div>
</div>

<script src="/TCC_RELPJAM/public/assets/js/myaccount.js"></script>

</body>
</html>