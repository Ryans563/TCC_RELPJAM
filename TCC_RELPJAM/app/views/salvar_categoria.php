
<?php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome_categoria']);

    $slug = strtolower($nome);
    $slug = preg_replace('/[^a-zA-Z0-9]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);

    $sql = $pdo->prepare("
        INSERT INTO categorias (
            nome,
            slug,
            ativo
        )
        VALUES (
            :nome,
            :slug,
            TRUE
        )
    ");

    $sql->execute([
        ':nome' => $nome,
        ':slug' => $slug
    ]);

    header('Location: add_produto.php');
    exit;
}
