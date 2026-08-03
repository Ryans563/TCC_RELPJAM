<?php

function vendedor() {
    require '../app/views/vendedor.php';
}


// ID do vendedor logado
$vendedorId = $_SESSION['user_id'];

// Buscar loja do vendedor
$sqlLoja = $pdo->prepare("
    SELECT id
    FROM lojas
    WHERE vendedor_id = ?
");

$sqlLoja->execute([$vendedorId]);

$loja = $sqlLoja->fetch(PDO::FETCH_ASSOC);

$topProdutos = [];

if ($loja) {

    $sqlTop = $pdo->prepare("
        SELECT
            p.id,
            p.nome,
            p.preco,
            p.estoque,
            p.imagem,
            COUNT(ip.id) AS total_vendas

        FROM produtos p

        LEFT JOIN itens_pedido ip
            ON ip.produto_id = p.id

        WHERE p.loja_id = ?

        GROUP BY
            p.id,
            p.nome,
            p.preco,
            p.estoque,
            p.imagem

        ORDER BY total_vendas DESC

        LIMIT 10
    ");

    $sqlTop->execute([$loja['id']]);

    $topProdutos = $sqlTop->fetchAll(PDO::FETCH_ASSOC);
}
?>
