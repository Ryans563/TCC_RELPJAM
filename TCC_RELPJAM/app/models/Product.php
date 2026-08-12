public function buscarTop10MaisVendidos()
{
    $sql = "
        SELECT
            p.id,
            p.nome,
            p.imagem,
            COALESCE(SUM(oi.quantidade),0) AS total_vendas

        FROM produtos p

        LEFT JOIN order_items oi
            ON oi.produto_id = p.id

        GROUP BY
            p.id,
            p.nome,
            p.imagem

        ORDER BY total_vendas DESC

        LIMIT 10
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
