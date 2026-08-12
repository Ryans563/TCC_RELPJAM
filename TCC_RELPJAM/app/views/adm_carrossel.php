<?php

require_once "config.php";

$base = "/TCC_RELPJAM";

$mensagem = "";

$vendedor_filtro = !empty($_GET['vendedor_id']) ? (int)$_GET['vendedor_id'] : null;


$sqlVendedores = $pdo->query("
    SELECT id, nome_loja
    FROM vendedores
    ORDER BY nome_loja ASC
");

$vendedores = $sqlVendedores->fetchAll(PDO::FETCH_ASSOC);

if ($vendedor_filtro) {

    $sqlProdutos = $pdo->prepare("
        SELECT 
            id,
            nome,
            vendedor_id
        FROM produtos
        WHERE status = 'ativo'
        AND vendedor_id = :vendedor_id
        ORDER BY nome ASC
    ");

    $sqlProdutos->execute([
        ':vendedor_id' => $vendedor_filtro
    ]);

} else {

    $sqlProdutos = $pdo->query("
        SELECT 
            id,
            nome,
            vendedor_id
        FROM produtos
        WHERE status = 'ativo'
        ORDER BY nome ASC
    ");
}

$produtos = $sqlProdutos->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = trim($_POST['titulo']);
    $produto_id = !empty($_POST['produto_id']) ? (int)$_POST['produto_id'] : null;

    $vendedor_id = !empty($_POST['vendedor_id']) ? (int)$_POST['vendedor_id'] : null;

    // ORDEM AUTOMÁTICA
    $sqlOrdem = $pdo->query("
        SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima_ordem
        FROM carrossel_imagens
    ");

    $proximaOrdem = $sqlOrdem->fetch(PDO::FETCH_ASSOC)['proxima_ordem'];

    $imagemUrl = null;


    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {

        $fileTmp = $_FILES['imagem']['tmp_name'];
        $fileName = uniqid() . '-' . basename($_FILES['imagem']['name']);
        $fileData = file_get_contents($fileTmp);

        $supabaseUrl = "https://enkfnnaebiiqyycmegyp.supabase.co";
        $bucket = "carrossel";
        $key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImVua2ZubmFlYmlpcXl5Y21lZ3lwIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4MTA2OTQ3NiwiZXhwIjoyMDk2NjQ1NDc2fQ.dsa2_kej67S5GG_lAXCw3nrSrg7Mvz5xNx_0KNTlMF0";

        $uploadUrl = $supabaseUrl . "/storage/v1/object/" . $bucket . "/" . $fileName;

        $ch = curl_init($uploadUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $key",
            "Content-Type: application/octet-stream",
            "x-upsert: true"
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode == 200 || $httpCode == 201) {

            $imagemUrl = $supabaseUrl . "/storage/v1/object/public/" . $bucket . "/" . $fileName;

        } else {
            die("Erro upload Supabase: " . $response);
        }
    }

    if (!$imagemUrl && $produto_id) {

        $sqlImg = $pdo->prepare("
            SELECT imagem
            FROM produto_imagens
            WHERE produto_id = :id
            AND principal = TRUE
            LIMIT 1
        ");

        $sqlImg->execute([
            ':id' => $produto_id
        ]);

        $img = $sqlImg->fetch(PDO::FETCH_ASSOC);

        if ($img && !empty($img['imagem'])) {
            $imagemUrl = $img['imagem'];
        }
    }


    if (!$imagemUrl) {
        die("Erro: é necessário enviar uma imagem ou selecionar um produto.");
    }

    $sql = $pdo->prepare("
        INSERT INTO carrossel_imagens
        (
            titulo,
            imagem,
            produto_id,
            vendedor_id,
            ordem,
            ativo
        )
        VALUES
        (
            :titulo,
            :imagem,
            :produto_id,
            :vendedor_id,
            :ordem,
            true
        )
    ");

    $sql->execute([
        ':titulo' => $titulo,
        ':imagem' => $imagemUrl,
        ':produto_id' => $produto_id,
        ':vendedor_id' => $vendedor_id,
        ':ordem' => $proximaOrdem
    ]);

    header("Location: adm_carrossel.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Adicionar Carrossel</title>

<link rel="stylesheet" href="<?= $base ?>/public/assets/css/style.css">
</head>

<body>

<div class="container">

<h1>Adicionar Banner</h1>

<button onclick="window.location.href='admin.php'">
    Voltar
</button>


<form method="GET">

    <label>Selecionar Vendedor</label>

    <select name="vendedor_id" onchange="this.form.submit()">

        <option value="">Todos os vendedores</option>

        <?php foreach ($vendedores as $v): ?>
            <option value="<?= $v['id'] ?>" 
                <?= ($vendedor_filtro == $v['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($v['nome_loja']) ?>
            </option>
        <?php endforeach; ?>

    </select>

</form>


<br><br>


<form method="POST" enctype="multipart/form-data">

    <input type="hidden" name="vendedor_id" value="<?= $vendedor_filtro ?>">

    <div class="form-grid">

        <div class="form-group">
            <label>Título</label>
            <input type="text" name="titulo" required>
        </div>

        <div class="form-group">

            <label>Produto do vendedor</label>

            <select name="produto_id">

                <option value="">Nenhum produto</option>

                <?php foreach($produtos as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['nome']) ?>
                    </option>
                <?php endforeach; ?>

            </select>

        </div>

        <div class="form-group form-group-full">

            <label>Imagem (opcional)</label>

            <input type="file" name="imagem" accept="image/*">

            <small>
                Se não enviar imagem, será usada a imagem do produto selecionado.
            </small>

        </div>

    </div>

    <button type="submit">
        Salvar Banner
    </button>

</form>

</div>

</body>
</html>