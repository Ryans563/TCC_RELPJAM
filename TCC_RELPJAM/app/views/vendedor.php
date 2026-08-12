<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$base = '/TCC_RELPJAM';

if (empty($_SESSION['usuario_id'])) {
    header('Location: sign.php');
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];

/* =========================================================
   CSRF
========================================================= */

if (empty($_SESSION['seller_csrf'])) {
    $_SESSION['seller_csrf'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['seller_csrf'];

/* Abas permitidas (usado para validação segura de parâmetros) */
$allowedTabs = [
    'dashboard',
    'produtos',
    'novo-produto',
    'categorias',
    'pedidos',
    'loja'
];


/* =========================================================
   FUNÇÕES AUXILIARES
========================================================= */

function seller_e(?string $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function seller_slug(string $text): string
{
    $text = trim(
        mb_strtolower(
            $text,
            'UTF-8'
        )
    );

    if (function_exists('iconv')) {
        $converted = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $text
        );

        if ($converted !== false) {
            $text = $converted;
        }
    }

    $text = preg_replace(
        '/[^a-z0-9]+/',
        '-',
        $text
    );

    return trim(
        (string) $text,
        '-'
    );
}


/* =========================================================
   UPLOAD DE IMAGENS
========================================================= */

function seller_upload(
    array $file,
    string $folder,
    string $base
): ?string {

    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'Falha no upload da imagem.'
        );
    }

    $size = (int) ($file['size'] ?? 0);

    if ($size <= 0) {
        throw new RuntimeException(
            'A imagem enviada é inválida.'
        );
    }

    if ($size > 6 * 1024 * 1024) {
        throw new RuntimeException(
            'Cada imagem pode ter no máximo 6 MB.'
        );
    }

    $tmp = $file['tmp_name'] ?? '';

    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException(
            'Arquivo de upload inválido.'
        );
    }

    $mime = (new finfo(
        FILEINFO_MIME_TYPE
    ))->file($tmp);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException(
            'Formato de imagem não permitido. Use JPG, PNG, WEBP ou GIF.'
        );
    }

    /*
     * Caminho físico:
     * /TCC_RELPJAM/public/uploads/...
     */
    $relativeDir =
        $base .
        '/public/uploads/' .
        trim($folder, '/') .
        '/';

    $absoluteDir =
        dirname(__DIR__, 2) .
        '/public/uploads/' .
        trim($folder, '/') .
        '/';

    if (!is_dir($absoluteDir)) {

        if (!mkdir(
            $absoluteDir,
            0755,
            true
        )) {
            throw new RuntimeException(
                'Não foi possível criar a pasta de uploads.'
            );
        }
    }

    $name =
        bin2hex(
            random_bytes(16)
        ) .
        '.' .
        $allowed[$mime];

    $destination =
        $absoluteDir .
        $name;

    if (!move_uploaded_file(
        $tmp,
        $destination
    )) {
        throw new RuntimeException(
            'Não foi possível salvar a imagem.'
        );
    }

    return $relativeDir . $name;
}


/* =========================================================
   REMOVER IMAGEM
========================================================= */

function seller_remove_file(
    ?string $path
): void {

    if (!$path) {
        return;
    }

    $base = '/TCC_RELPJAM';

    $prefix =
        $base .
        '/public/uploads/';

    if (!str_starts_with(
        $path,
        $prefix
    )) {
        return;
    }

    $relative =
        substr(
            $path,
            strlen($base . '/public')
        );

    $full =
        dirname(__DIR__, 2) .
        '/public' .
        $relative;

    if (is_file($full)) {
        @unlink($full);
    }
}


/* =========================================================
   LOCALIZAR VENDEDOR
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        v.*,
        u.nome AS usuario_nome,
        u.email
    FROM vendedores v
    INNER JOIN usuarios u
        ON u.id = v.usuario_id
    WHERE v.usuario_id = :uid
    LIMIT 1
");

$stmt->execute([
    'uid' => $usuarioId
]);

$loja = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/* =========================================================
   CRIAR LOJA AUTOMATICAMENTE
========================================================= */

if (!$loja) {

    $nomePadrao =
        trim(
            (string) (
                $_SESSION['usuario_nome']
                ?? 'Minha Loja'
            )
        );

    $stmt = $pdo->prepare("
        INSERT INTO vendedores
        (
            usuario_id,
            nome_loja,
            descricao,
            status
        )
        VALUES
        (
            :uid,
            :nome,
            :descricao,
            'ativo'
        )
        RETURNING id
    ");

    $stmt->execute([
        'uid' => $usuarioId,
        'nome' =>
            $nomePadrao . ' Store',
        'descricao' =>
            'Bem-vindo à minha loja na RELPJAM.'
    ]);

    $vendedorId =
        (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT
            v.*,
            u.nome AS usuario_nome,
            u.email
        FROM vendedores v
        INNER JOIN usuarios u
            ON u.id = v.usuario_id
        WHERE v.id = :id
        LIMIT 1
    ");

    $stmt->execute([
        'id' => $vendedorId
    ]);

    $loja = $stmt->fetch(
        PDO::FETCH_ASSOC
    );
}

$vendedorId =
    (int) $loja['id'];


$mensagem = '';
$tipoMensagem = 'success';
/* =========================================================
   PROCESSAMENTO POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /* =====================================================
           CSRF
        ===================================================== */

        if (
            !hash_equals(
                $csrf,
                (string) (
                    $_POST['csrf'] ?? ''
                )
            )
        ) {
            throw new RuntimeException(
                'Sessão expirada. Atualize a página e tente novamente.'
            );
        }


        $acao =
            (string) (
                $_POST['acao'] ?? ''
            );


        /* =====================================================
           ADICIONAR CATEGORIA
        ===================================================== */

        if ($acao === 'categoria_add') {

            $nome =
                trim(
                    (string) (
                        $_POST['nome'] ?? ''
                    )
                );

            if (
                $nome === '' ||
                mb_strlen($nome) < 2
            ) {
                throw new RuntimeException(
                    'Informe um nome de categoria válido.'
                );
            }

            $slug =
                seller_slug($nome);

            if ($slug === '') {
                throw new RuntimeException(
                    'Não foi possível gerar o slug da categoria.'
                );
            }

            $check = $pdo->prepare("
                SELECT id
                FROM categorias
                WHERE slug = :slug
                LIMIT 1
            ");

            $check->execute([
                'slug' => $slug
            ]);

            if ($check->fetch()) {
                throw new RuntimeException(
                    'Essa categoria já existe.'
                );
            }

            /*
             * IMPORTANTE:
             * categorias NÃO possui vendedor_id no seu schema.
             */
            $stmt = $pdo->prepare("
                INSERT INTO categorias
                (
                    nome,
                    slug,
                    ativo
                )
                VALUES
                (
                    :nome,
                    :slug,
                    true
                )
                RETURNING id
            ");

            $stmt->execute([
                'nome' => $nome,
                'slug' => $slug
            ]);

            $stmt->fetchColumn();

            $mensagem =
                'Categoria adicionada com sucesso.';


        /* =====================================================
           ADICIONAR PRODUTO
        ===================================================== */

        } elseif ($acao === 'produto_add') {

            $nome =
                trim(
                    (string) (
                        $_POST['nome'] ?? ''
                    )
                );

            $descricao =
                trim(
                    (string) (
                        $_POST['descricao'] ?? ''
                    )
                );

            $categoriaId =
                (int) (
                    $_POST['categoria_id'] ?? 0
                );

            $sku =
                trim(
                    (string) (
                        $_POST['sku'] ?? ''
                    )
                );

            $preco =
                (float) str_replace(
                    ',',
                    '.',
                    (string) (
                        $_POST['preco'] ?? '0'
                    )
                );

            $precoPromocional =
                trim(
                    (string) (
                        $_POST['preco_promocional'] ?? ''
                    )
                );

            $precoPromocional =
                $precoPromocional !== ''
                    ? (float) str_replace(
                        ',',
                        '.',
                        $precoPromocional
                    )
                    : null;

            $estoque =
                max(
                    0,
                    (int) (
                        $_POST['estoque'] ?? 0
                    )
                );

            $marca =
                trim(
                    (string) (
                        $_POST['marca'] ?? ''
                    )
                );

            $codigoBarras =
                trim(
                    (string) (
                        $_POST['codigo_barras'] ?? ''
                    )
                );

            $peso =
                trim(
                    (string) (
                        $_POST['peso'] ?? ''
                    )
                );

            $peso =
                $peso !== ''
                    ? (float) str_replace(
                        ',',
                        '.',
                        $peso
                    )
                    : null;

            $altura =
                trim(
                    (string) (
                        $_POST['altura'] ?? ''
                    )
                );

            $altura =
                $altura !== ''
                    ? (float) str_replace(
                        ',',
                        '.',
                        $altura
                    )
                    : null;

            $largura =
                trim(
                    (string) (
                        $_POST['largura'] ?? ''
                    )
                );

            $largura =
                $largura !== ''
                    ? (float) str_replace(
                        ',',
                        '.',
                        $largura
                    )
                    : null;

            $comprimento =
                trim(
                    (string) (
                        $_POST['comprimento'] ?? ''
                    )
                );

            $comprimento =
                $comprimento !== ''
                    ? (float) str_replace(
                        ',',
                        '.',
                        $comprimento
                    )
                    : null;


            /* =================================================
               VALIDAÇÃO
            ================================================= */

            if (
                $nome === '' ||
                $descricao === '' ||
                $categoriaId <= 0 ||
                $sku === '' ||
                $preco <= 0
            ) {
                throw new RuntimeException(
                    'Preencha nome, descrição, categoria, SKU e preço.'
                );
            }


            /* =================================================
               VALIDAR CATEGORIA
            ================================================= */

            $cat = $pdo->prepare("
                SELECT id
                FROM categorias
                WHERE id = :id
                  AND ativo = true
                LIMIT 1
            ");

            $cat->execute([
                'id' => $categoriaId
            ]);

            if (!$cat->fetch()) {
                throw new RuntimeException(
                    'Categoria inválida.'
                );
            }


            /* =================================================
               SLUG
            ================================================= */

            $slugBase =
                seller_slug($nome);

            if ($slugBase === '') {
                $slugBase = 'produto';
            }

            $slug =
                $slugBase .
                '-' .
                bin2hex(
                    random_bytes(4)
                );


            /* =================================================
               TRANSAÇÃO
            ================================================= */

            $pdo->beginTransaction();


            /* =================================================
               INSERIR PRODUTO
            ================================================= */

            $stmt = $pdo->prepare("
                INSERT INTO produtos
                (
                    vendedor_id,
                    categoria_id,
                    nome,
                    slug,
                    descricao,
                    sku,
                    codigo_barras,
                    preco,
                    preco_promocional,
                    estoque,
                    peso,
                    altura,
                    largura,
                    comprimento,
                    marca,
                    status
                )
                VALUES
                (
                    :vendedor_id,
                    :categoria_id,
                    :nome,
                    :slug,
                    :descricao,
                    :sku,
                    :codigo_barras,
                    :preco,
                    :preco_promocional,
                    :estoque,
                    :peso,
                    :altura,
                    :largura,
                    :comprimento,
                    :marca,
                    'ativo'
                )
                RETURNING id
            ");

            $stmt->execute([
                'vendedor_id' =>
                    $vendedorId,

                'categoria_id' =>
                    $categoriaId,

                'nome' =>
                    $nome,

                'slug' =>
                    $slug,

                'descricao' =>
                    $descricao,

                'sku' =>
                    $sku,

                'codigo_barras' =>
                    $codigoBarras !== ''
                        ? $codigoBarras
                        : null,

                'preco' =>
                    $preco,

                'preco_promocional' =>
                    $precoPromocional,

                'estoque' =>
                    $estoque,

                'peso' =>
                    $peso,

                'altura' =>
                    $altura,

                'largura' =>
                    $largura,

                'comprimento' =>
                    $comprimento,

                'marca' =>
                    $marca !== ''
                        ? $marca
                        : null
            ]);


            $produtoId =
                (int) $stmt->fetchColumn();


            if ($produtoId <= 0) {
                throw new RuntimeException(
                    'Não foi possível criar o produto.'
                );
            }


            /* =================================================
               IMAGENS
               Até 10 imagens
            ================================================= */

            $files =
                $_FILES['imagens'] ?? null;

            $uploaded = 0;


            if (
                $files &&
                isset($files['name']) &&
                is_array($files['name'])
            ) {

                foreach (
                    $files['name']
                    as $i => $original
                ) {

                    if (
                        (
                            $files['error'][$i]
                            ?? UPLOAD_ERR_NO_FILE
                        )
                        === UPLOAD_ERR_NO_FILE
                    ) {
                        continue;
                    }

                    if ($uploaded >= 10) {
                        break;
                    }


                    $one = [
                        'name' =>
                            $files['name'][$i],

                        'type' =>
                            $files['type'][$i],

                        'tmp_name' =>
                            $files['tmp_name'][$i],

                        'error' =>
                            $files['error'][$i],

                        'size' =>
                            $files['size'][$i]
                    ];


                    $path =
                        seller_upload(
                            $one,
                            'products',
                            $base
                        );


                    if ($path) {

                        $stmtImg =
                            $pdo->prepare("
                                INSERT INTO produto_imagens
                                (
                                    produto_id,
                                    imagem,
                                    principal,
                                    ordem
                                )
                                VALUES
                                (
                                    :produto_id,
                                    :imagem,
                                    :principal,
                                    :ordem
                                )
                            ");

                        $stmtImg->execute([

                            'produto_id' =>
                                $produtoId,

                            'imagem' =>
                                $path,

                            'principal' =>
                                $uploaded === 0,

                            'ordem' =>
                                $uploaded
                        ]);

                        $uploaded++;
                    }
                }
            }


            /* =================================================
               GARANTIR IMAGEM PRINCIPAL
            ================================================= */

            if ($uploaded > 0) {

                $stmt =
                    $pdo->prepare("
                        UPDATE produto_imagens
                        SET principal = false
                        WHERE produto_id = ?
                    ");

                $stmt->execute([
                    $produtoId
                ]);


                $stmt =
                    $pdo->prepare("
                        UPDATE produto_imagens
                        SET principal = true
                        WHERE id = (
                            SELECT id
                            FROM produto_imagens
                            WHERE produto_id = ?
                            ORDER BY ordem ASC, id ASC
                            LIMIT 1
                        )
                    ");

                $stmt->execute([
                    $produtoId
                ]);
            }


            $pdo->commit();


            $mensagem =
                $uploaded > 0
                    ? "Produto publicado com sucesso com {$uploaded} imagem(ns)."
                    : 'Produto publicado com sucesso. Adicione imagens posteriormente.';


        /* =====================================================
           EDITAR PRODUTO
        ===================================================== */

        } elseif ($acao === 'produto_edit') {

            $id =
                (int) (
                    $_POST['produto_id'] ?? 0
                );

            $own =
                $pdo->prepare("
                    SELECT *
                    FROM produtos
                    WHERE id = :id
                      AND vendedor_id = :vid
                    LIMIT 1
                ");

            $own->execute([
                'id' => $id,
                'vid' => $vendedorId
            ]);

            $produto =
                $own->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$produto) {
                throw new RuntimeException(
                    'Produto não encontrado.'
                );
            }


            $nome =
                trim(
                    (string) (
                        $_POST['nome'] ?? ''
                    )
                );

            $descricao =
                trim(
                    (string) (
                        $_POST['descricao'] ?? ''
                    )
                );

            $categoriaId =
                (int) (
                    $_POST['categoria_id'] ?? 0
                );

            $sku =
                trim(
                    (string) (
                        $_POST['sku'] ?? ''
                    )
                );

            $preco =
                (float) str_replace(
                    ',',
                    '.',
                    (string) (
                        $_POST['preco'] ?? 0
                    )
                );

            $promo =
                trim(
                    (string) (
                        $_POST['preco_promocional'] ?? ''
                    )
                );

            $promo =
                $promo !== ''
                    ? (float) str_replace(
                        ',',
                        '.',
                        $promo
                    )
                    : null;

            $estoque =
                max(
                    0,
                    (int) (
                        $_POST['estoque'] ?? 0
                    )
                );

            $marca =
                trim(
                    (string) (
                        $_POST['marca'] ?? ''
                    )
                );

            $codigo =
                trim(
                    (string) (
                        $_POST['codigo_barras'] ?? ''
                    )
                );

            $status =
                $_POST['status'] ?? 'ativo';

            if (
                !in_array(
                    $status,
                    [
                        'ativo',
                        'inativo',
                        'pausado'
                    ],
                    true
                )
            ) {
                $status = 'ativo';
            }


            if (
                $nome === '' ||
                $descricao === '' ||
                $categoriaId <= 0 ||
                $sku === '' ||
                $preco <= 0
            ) {
                throw new RuntimeException(
                    'Preencha corretamente os dados obrigatórios.'
                );
            }


            $cat =
                $pdo->prepare("
                    SELECT id
                    FROM categorias
                    WHERE id = ?
                      AND ativo = true
                    LIMIT 1
                ");

            $cat->execute([
                $categoriaId
            ]);

            if (!$cat->fetch()) {
                throw new RuntimeException(
                    'Categoria inválida.'
                );
            }


            $stmt =
                $pdo->prepare("
                    UPDATE produtos
                    SET
                        categoria_id = :categoria_id,
                        nome = :nome,
                        descricao = :descricao,
                        sku = :sku,
                        codigo_barras = :codigo_barras,
                        preco = :preco,
                        preco_promocional = :promo,
                        estoque = :estoque,
                        marca = :marca,
                        status = :status,
                        updated_at = now()
                    WHERE id = :id
                      AND vendedor_id = :vid
                ");

            $stmt->execute([

                'categoria_id' =>
                    $categoriaId,

                'nome' =>
                    $nome,

                'descricao' =>
                    $descricao,

                'sku' =>
                    $sku,

                'codigo_barras' =>
                    $codigo !== ''
                        ? $codigo
                        : null,

                'preco' =>
                    $preco,

                'promo' =>
                    $promo,

                'estoque' =>
                    $estoque,

                'marca' =>
                    $marca !== ''
                        ? $marca
                        : null,

                'status' =>
                    $status,

                'id' =>
                    $id,

                'vid' =>
                    $vendedorId
            ]);
                        /* =================================================
               REMOVER IMAGENS
            ================================================= */

            $remover =
                $_POST['remover_imagens']
                ?? [];

            if (
                is_array($remover) &&
                count($remover) > 0
            ) {

                $remover =
                    array_values(
                        array_filter(
                            array_map(
                                'intval',
                                $remover
                            )
                        )
                    );

                if ($remover) {

                    $placeholders =
                        implode(
                            ',',
                            array_fill(
                                0,
                                count($remover),
                                '?'
                            )
                        );

                    $params =
                        array_merge(
                            [
                                $id,
                                $vendedorId
                            ],
                            $remover
                        );


                    $q =
                        $pdo->prepare("
                            SELECT
                                pi.id,
                                pi.imagem
                            FROM produto_imagens pi
                            INNER JOIN produtos p
                                ON p.id = pi.produto_id
                            WHERE p.id = ?
                              AND p.vendedor_id = ?
                              AND pi.id IN (
                                  $placeholders
                              )
                        ");

                    $q->execute(
                        $params
                    );


                    foreach (
                        $q->fetchAll(
                            PDO::FETCH_ASSOC
                        )
                        as $img
                    ) {

                        seller_remove_file(
                            $img['imagem']
                        );


                        $del =
                            $pdo->prepare("
                                DELETE FROM produto_imagens
                                WHERE id = ?
                            ");

                        $del->execute([
                            (int) $img['id']
                        ]);
                    }
                }
            }


            /* =================================================
               NOVAS IMAGENS
            ================================================= */

            $files =
                $_FILES['imagens'] ?? null;


            if (
                $files &&
                isset($files['name']) &&
                is_array($files['name'])
            ) {

                $q =
                    $pdo->prepare("
                        SELECT COUNT(*)
                        FROM produto_imagens
                        WHERE produto_id = ?
                    ");

                $q->execute([
                    $id
                ]);

                $ordem =
                    (int) $q->fetchColumn();


                foreach (
                    $files['name']
                    as $i => $original
                ) {

                    if (
                        (
                            $files['error'][$i]
                            ?? UPLOAD_ERR_NO_FILE
                        )
                        === UPLOAD_ERR_NO_FILE
                    ) {
                        continue;
                    }


                    if ($ordem >= 10) {
                        break;
                    }


                    $one = [

                        'name' =>
                            $files['name'][$i],

                        'type' =>
                            $files['type'][$i],

                        'tmp_name' =>
                            $files['tmp_name'][$i],

                        'error' =>
                            $files['error'][$i],

                        'size' =>
                            $files['size'][$i]
                    ];


                    $path =
                        seller_upload(
                            $one,
                            'products',
                            $base
                        );


                    if ($path) {

                        $ins =
                            $pdo->prepare("
                                INSERT INTO produto_imagens
                                (
                                    produto_id,
                                    imagem,
                                    principal,
                                    ordem
                                )
                                VALUES
                                (
                                    :produto_id,
                                    :imagem,
                                    false,
                                    :ordem
                                )
                            ");

                        $ins->execute([

                            'produto_id' =>
                                $id,

                            'imagem' =>
                                $path,

                            'ordem' =>
                                $ordem
                        ]);

                        $ordem++;
                    }
                }
            }


            /* =================================================
               GARANTIR IMAGEM PRINCIPAL
            ================================================= */

            $q =
                $pdo->prepare("
                    SELECT id
                    FROM produto_imagens
                    WHERE produto_id = ?
                    ORDER BY
                        principal DESC,
                        ordem ASC,
                        id ASC
                    LIMIT 1
                ");

            $q->execute([
                $id
            ]);

            $first =
                $q->fetchColumn();


            if ($first) {

                $pdo->prepare("
                    UPDATE produto_imagens
                    SET principal = false
                    WHERE produto_id = ?
                ")->execute([
                    $id
                ]);


                $pdo->prepare("
                    UPDATE produto_imagens
                    SET principal = true
                    WHERE id = ?
                ")->execute([
                    (int) $first
                ]);
            }


            $mensagem =
                'Produto atualizado com sucesso.';


        /* =====================================================
           DEFINIR IMAGEM PRINCIPAL
        ===================================================== */

        } elseif (
            $acao === 'imagem_principal'
        ) {

            $produtoId =
                (int) (
                    $_POST['produto_id'] ?? 0
                );

            $imagemId =
                (int) (
                    $_POST['imagem_id'] ?? 0
                );


            $q =
                $pdo->prepare("
                    SELECT pi.id
                    FROM produto_imagens pi
                    INNER JOIN produtos p
                        ON p.id = pi.produto_id
                    WHERE pi.id = :imagem
                      AND p.id = :produto
                      AND p.vendedor_id = :vendedor
                    LIMIT 1
                ");

            $q->execute([

                'imagem' =>
                    $imagemId,

                'produto' =>
                    $produtoId,

                'vendedor' =>
                    $vendedorId
            ]);


            if (!$q->fetch()) {
                throw new RuntimeException(
                    'Imagem não pertence ao seu produto.'
                );
            }


            $pdo->prepare("
                UPDATE produto_imagens
                SET principal = false
                WHERE produto_id = ?
            ")->execute([
                $produtoId
            ]);


            $pdo->prepare("
                UPDATE produto_imagens
                SET principal = true
                WHERE id = ?
            ")->execute([
                $imagemId
            ]);


            $mensagem =
                'Imagem principal alterada.';


        /* =====================================================
           STATUS DO PRODUTO
        ===================================================== */

        } elseif (
            $acao === 'produto_status'
        ) {

            $id =
                (int) (
                    $_POST['produto_id'] ?? 0
                );

            $status =
                $_POST['status'] ?? 'ativo';


            if (
                !in_array(
                    $status,
                    [
                        'ativo',
                        'inativo',
                        'pausado'
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Status inválido.'
                );
            }


            $stmt =
                $pdo->prepare("
                    UPDATE produtos
                    SET
                        status = :status,
                        updated_at = now()
                    WHERE id = :id
                      AND vendedor_id = :vid
                ");

            $stmt->execute([

                'status' =>
                    $status,

                'id' =>
                    $id,

                'vid' =>
                    $vendedorId
            ]);


            $mensagem =
                'Status do produto atualizado.';


        /* =====================================================
           ATUALIZAR LOJA
        ===================================================== */

        } elseif (
            $acao === 'loja_update'
        ) {

            $nome =
                trim(
                    (string) (
                        $_POST['nome_loja'] ?? ''
                    )
                );

            $descricao =
                trim(
                    (string) (
                        $_POST['descricao_loja'] ?? ''
                    )
                );


            if ($nome === '') {
                throw new RuntimeException(
                    'O nome da loja é obrigatório.'
                );
            }


            $logo =
                $loja['logo']
                ?? null;

            $banner =
                $loja['banner']
                ?? null;


            if (
                !empty(
                    $_FILES['logo']['name']
                    ?? ''
                )
            ) {

                $new =
                    seller_upload(
                        $_FILES['logo'],
                        'stores',
                        $base
                    );

                if ($new) {

                    seller_remove_file(
                        $logo
                    );

                    $logo = $new;
                }
            }


            if (
                !empty(
                    $_FILES['banner']['name']
                    ?? ''
                )
            ) {

                $new =
                    seller_upload(
                        $_FILES['banner'],
                        'stores',
                        $base
                    );

                if ($new) {

                    seller_remove_file(
                        $banner
                    );

                    $banner = $new;
                }
            }


            $stmt =
                $pdo->prepare("
                    UPDATE vendedores
                    SET
                        nome_loja = :nome,
                        descricao = :descricao,
                        logo = :logo,
                        banner = :banner,
                        updated_at = now()
                    WHERE id = :id
                      AND usuario_id = :uid
                ");

            $stmt->execute([

                'nome' =>
                    $nome,

                'descricao' =>
                    $descricao,

                'logo' =>
                    $logo,

                'banner' =>
                    $banner,

                'id' =>
                    $vendedorId,

                'uid' =>
                    $usuarioId
            ]);


            $mensagem =
                'Dados da loja atualizados.';


        } else {

            throw new RuntimeException(
                'Ação inválida.'
            );
        }


        $_SESSION['seller_flash'] = [
            'success',
            $mensagem
        ];


        // Redirecionamento seguro: valida a aba recebida via POST
        $targetTab = 'dashboard';
        if (isset($_POST['tab']) && is_scalar($_POST['tab'])) {
            $t = (string) $_POST['tab'];
            if (in_array($t, $allowedTabs, true)) {
                $targetTab = $t;
            }
        }

        header('Location: vendedor.php?tab=' . rawurlencode($targetTab));

        exit;


    } catch (Throwable $e) {

        if (
            $pdo->inTransaction()
        ) {
            $pdo->rollBack();
        }


        $_SESSION['seller_flash'] = [
            'error',
            $e->getMessage()
        ];


        // Redirecionamento seguro em caso de erro
        $targetTab = 'dashboard';
        if (isset($_POST['tab']) && is_scalar($_POST['tab'])) {
            $t = (string) $_POST['tab'];
            if (in_array($t, $allowedTabs, true)) {
                $targetTab = $t;
            }
        }

        header('Location: vendedor.php?tab=' . rawurlencode($targetTab));

        exit;
    }
}


/* =========================================================
   MENSAGEM
========================================================= */

if (
    isset(
        $_SESSION['seller_flash']
    )
) {

    [
        $tipoMensagem,
        $mensagem
    ] =
        $_SESSION['seller_flash'];

    unset(
        $_SESSION['seller_flash']
    );
}


/* =========================================================
   ABA
========================================================= */

$tab = 'dashboard';

if (isset($_GET['tab']) && is_scalar($_GET['tab'])) {
    $tab = (string) $_GET['tab'];
}

if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'dashboard';
}


/* =========================================================
   ESTATÍSTICAS
========================================================= */

$stats = [

    'produtos' =>
        0,

    'ativos' =>
        0,

    'estoque' =>
        0,

    'vendas' =>
        (int) (
            $loja['total_vendas']
            ?? 0
        ),

    'faturamento' =>
        0,

    'pedidos' =>
        0
];


$q =
    $pdo->prepare("
        SELECT
            COUNT(*) AS total,

            COUNT(*)
            FILTER (
                WHERE status = 'ativo'
            ) AS ativos,

            COALESCE(
                SUM(estoque),
                0
            ) AS estoque

        FROM produtos

        WHERE vendedor_id = ?
    ");

$q->execute([
    $vendedorId
]);


$row =
    $q->fetch(
        PDO::FETCH_ASSOC
    );


if ($row) {

    $stats['produtos'] =
        (int) $row['total'];

    $stats['ativos'] =
        (int) $row['ativos'];

    $stats['estoque'] =
        (int) $row['estoque'];
}


/* =========================================================
   FATURAMENTO
========================================================= */

$q =
    $pdo->prepare("
        SELECT
            COUNT(*) AS pedidos,

            COALESCE(
                SUM(subtotal),
                0
            ) AS faturamento

        FROM pedido_item

        WHERE vendedor_id = ?

          AND status <> 'cancelado'
    ");

$q->execute([
    $vendedorId
]);


$row =
    $q->fetch(
        PDO::FETCH_ASSOC
    );


if ($row) {

    $stats['pedidos'] =
        (int) $row['pedidos'];

    $stats['faturamento'] =
        (float) $row['faturamento'];
}
/* =========================================================
   CATEGORIAS
========================================================= */

$stmt =
    $pdo->prepare("
        SELECT
            id,
            nome,
            slug,
            ativo
        FROM categorias
        WHERE ativo = true
        ORDER BY nome ASC
    ");

$stmt->execute();

$categorias =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   PRODUTOS DO VENDEDOR
========================================================= */

$stmt =
    $pdo->prepare("
        SELECT

            p.*,

            c.nome AS categoria,

            (
                SELECT pi.imagem
                FROM produto_imagens pi
                WHERE pi.produto_id = p.id
                ORDER BY
                    pi.principal DESC,
                    pi.ordem ASC,
                    pi.id ASC
                LIMIT 1
            ) AS imagem,

            (
                SELECT COUNT(*)
                FROM produto_imagens pi2
                WHERE pi2.produto_id = p.id
            ) AS imagens_total

        FROM produtos p

        INNER JOIN categorias c
            ON c.id = p.categoria_id

        WHERE p.vendedor_id = ?

        ORDER BY
            p.updated_at DESC,
            p.id DESC
    ");

$stmt->execute([
    $vendedorId
]);

$produtos =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   PEDIDOS
========================================================= */

$stmt =
    $pdo->prepare("
        SELECT

            pi.id,
            pi.quantidade,
            pi.preco_unitario,
            pi.subtotal,
            pi.status,
            pi.produto_id,

            p.nome AS produto,

            (
                SELECT pim.imagem
                FROM produto_imagens pim
                WHERE pim.produto_id = p.id
                ORDER BY
                    pim.principal DESC,
                    pim.ordem ASC,
                    pim.id ASC
                LIMIT 1
            ) AS imagem,

            pe.numero_pedido,
            pe.created_at

        FROM pedido_item pi

        INNER JOIN produtos p
            ON p.id = pi.produto_id

        INNER JOIN pedidos pe
            ON pe.id = pi.pedido_id

        WHERE pi.vendedor_id = ?

        ORDER BY
            pe.created_at DESC

        LIMIT 50
    ");

$stmt->execute([
    $vendedorId
]);

$pedidos =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   PRODUTO EM EDIÇÃO
========================================================= */

$editId =
    (int) (
        $_GET['edit'] ?? 0
    );

$produtoEdit = null;

$imagensEdit = [];


if ($editId > 0) {

    $stmt =
        $pdo->prepare("
            SELECT *
            FROM produtos
            WHERE id = ?
              AND vendedor_id = ?
            LIMIT 1
        ");

    $stmt->execute([
        $editId,
        $vendedorId
    ]);

    $produtoEdit =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if ($produtoEdit) {

        $stmt =
            $pdo->prepare("
                SELECT *
                FROM produto_imagens
                WHERE produto_id = ?
                ORDER BY
                    principal DESC,
                    ordem ASC,
                    id ASC
            ");

        $stmt->execute([
            $editId
        ]);

        $imagensEdit =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        $tab =
            'novo-produto';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Minha Loja | RELPJAM
</title>

<link
    rel="stylesheet"
    href="<?= seller_e($base) ?>/public/assets/css/seller.css"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>

</head>

<body class="seller-page">

<div class="seller-shell">

<aside
    class="seller-sidebar"
    id="sellerSidebar"
>

    <a
        href="home.php"
        class="seller-brand"
    >
        <img src="/TCC_RELPJAM/public/images/logotop.png"
     style="width: 180px; height: auto; display: block; margin: 0 auto;">
    </a>


    <div class="seller-store-mini">

        <div class="seller-store-avatar">

            <?php if (!empty($loja['logo'])): ?>

                <img
                    src="<?= seller_e($loja['logo']) ?>"
                    alt="Logo da loja"
                >

            <?php else: ?>

                <i class="fa-solid fa-store"></i>

            <?php endif; ?>

        </div>


        <div>

            <strong>
                <?= seller_e($loja['nome_loja']) ?>
            </strong>

            <small>
                Minha loja
            </small>

        </div>

    </div>


    <nav class="seller-nav">

        <a
            class="<?= $tab === 'dashboard' ? 'active' : '' ?>"
            href="vendedor.php?tab=dashboard"
        >
            <i class="fa-solid fa-chart-line"></i>
            Visão geral
        </a>


        <a
            class="<?= $tab === 'produtos' ? 'active' : '' ?>"
            href="vendedor.php?tab=produtos"
        >
            <i class="fa-solid fa-box"></i>
            Meus produtos
        </a>


        <a
            class="<?= $tab === 'novo-produto' ? 'active' : '' ?>"
            href="vendedor.php?tab=novo-produto"
        >
            <i class="fa-solid fa-circle-plus"></i>
            Adicionar produto
        </a>


        <a
            class="<?= $tab === 'categorias' ? 'active' : '' ?>"
            href="vendedor.php?tab=categorias"
        >
            <i class="fa-solid fa-tags"></i>
            Categorias
        </a>


        <a
            class="<?= $tab === 'pedidos' ? 'active' : '' ?>"
            href="vendedor.php?tab=pedidos"
        >
            <i class="fa-solid fa-receipt"></i>
            Pedidos
        </a>


        <a
            class="<?= $tab === 'loja' ? 'active' : '' ?>"
            href="vendedor.php?tab=loja"
        >
            <i class="fa-solid fa-store"></i>
            Configurar loja
        </a>

    </nav>


    <div class="seller-sidebar-bottom">

        <a href="home.php">
            <i class="fa-solid fa-arrow-left"></i>
            Voltar ao marketplace
        </a>

        <a
            href="logout.php"
            class="logout"
        >
            <i class="fa-solid fa-right-from-bracket"></i>
            Sair
        </a>

    </div>

</aside>


<main class="seller-main">

<header class="seller-topbar">

    <button
        class="mobile-menu"
        id="mobileMenu"
        type="button"
    >
        <i class="fa-solid fa-bars"></i>
    </button>


    <div>

        <span class="eyebrow">
            PAINEL DO VENDEDOR
        </span>

        <h1>
            Minha Loja
        </h1>

    </div>


    <div class="seller-top-actions">

        <a
            href="lojas.php"
            target="_blank"
        >
            <i class="fa-solid fa-eye"></i>
            Ver loja
        </a>


        <div class="seller-user">

            <span>
                <?= seller_e($loja['usuario_nome']) ?>
            </span>

            <small>
                Vendedor
            </small>

        </div>

    </div>

</header>


<section class="seller-content">

<?php if ($mensagem): ?>

<div
    class="seller-alert <?= $tipoMensagem === 'error' ? 'error' : 'success' ?>"
>

    <i
        class="fa-solid <?= $tipoMensagem === 'error'
            ? 'fa-circle-exclamation'
            : 'fa-circle-check'
        ?>"
    ></i>

    <span>
        <?= seller_e($mensagem) ?>
    </span>

</div>

<?php endif; ?>


<?php if ($tab === 'dashboard'): ?>

<div class="welcome-card">

    <div>

        <span class="badge">
            MINHA LOJA
        </span>

        <h2>
            Olá,
            <?= seller_e($loja['usuario_nome']) ?>
            👋
        </h2>

        <p>
            Gerencie produtos, categorias,
            estoque, pedidos e sua loja.
        </p>

    </div>


    <a href="vendedor.php?tab=novo-produto" class="primary-btn">
        <i class="fa-solid fa-plus"></i>Novo Produto
    </a>

</div>


<div class="stats-grid">

    <div class="stat-card">

        <i class="fa-solid fa-box"></i>

        <div>

            <small>
                Produtos
            </small>

            <strong>
                <?= $stats['produtos'] ?>
            </strong>

            <span>
                <?= $stats['ativos'] ?>
                ativos
            </span>

        </div>

    </div>


    <div class="stat-card">

        <i class="fa-solid fa-warehouse"></i>

        <div>

            <small>
                Estoque total
            </small>

            <strong>
                <?= $stats['estoque'] ?>
            </strong>

            <span>
                unidades
            </span>

        </div>

    </div>


    <div class="stat-card">

        <i class="fa-solid fa-cart-shopping"></i>

        <div>

            <small>
                Itens vendidos
            </small>

            <strong>
                <?= $stats['vendas'] ?>
            </strong>

            <span>
                acumulado
            </span>

        </div>

    </div>


    <div class="stat-card">

        <i class="fa-solid fa-wallet"></i>

        <div>

            <small>
                Faturamento
            </small>

            <strong>
                R$
                <?= number_format(
                    $stats['faturamento'],
                    2,
                    ',',
                    '.'
                ) ?>
            </strong>

            <span>
                <?= $stats['pedidos'] ?>
                itens
            </span>

        </div>

    </div>

</div>


<div class="section-heading">

    <div>

        <span class="eyebrow">
            CATÁLOGO
        </span>

        <h2>
            Produtos recentes
        </h2>

    </div>

    <a href="vendedor.php?tab=produtos">
        Ver todos
    </a>

</div>


<div class="product-table-wrap">

<table class="product-table">

<thead>

<tr>

<th>
    Produto
</th>

<th>
    Categoria
</th>

<th>
    Preço
</th>

<th>
    Estoque
</th>

<th>
    Status
</th>

<th>
</th>

</tr>

</thead>

<tbody>

<?php foreach (
    array_slice($produtos, 0, 6)
    as $p
): ?>

<tr>

<td>

<div class="table-product">

<img
    src="<?= seller_e(
        $p['imagem']
        ?: $base .
        '/public/assets/images/placeholder.png'
    ) ?>"
    alt="<?= seller_e($p['nome']) ?>"
>

<div>

<strong>
    <?= seller_e($p['nome']) ?>
</strong>

<small>
    SKU:
    <?= seller_e($p['sku']) ?>
</small>

</div>

</div>

</td>


<td>
    <?= seller_e($p['categoria']) ?>
</td>


<td>
    R$
    <?= number_format(
        (float) $p['preco'],
        2,
        ',',
        '.'
    ) ?>
</td>


<td>
    <?= (int) $p['estoque'] ?>
</td>


<td>

<span
    class="status <?= seller_e($p['status']) ?>"
>
    <?= ucfirst(
        seller_e($p['status'])
    ) ?>
</span>

</td>


<td>

<a
    class="icon-action"
    href="vendedor.php?tab=novo-produto&edit=<?= (int) $p['id'] ?>"
    title="Editar produto"
>
    <i class="fa-solid fa-pen"></i>
</a>

</td>

</tr>

<?php endforeach; ?>


<?php if (!$produtos): ?>

<tr>

<td
    colspan="6"
    class="empty"
>

Você ainda não possui produtos.

<a href="vendedor.php?tab=novo-produto">
    Cadastrar primeiro produto
</a>

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>
<?php elseif ($tab === 'produtos'): ?>

<div class="section-heading">

    <div>

        <span class="eyebrow">
            CATÁLOGO
        </span>

        <h2>
            Meus produtos
        </h2>

        <p>
            Gerencie os produtos da sua loja.
        </p>

    </div>


    <a
        class="primary-btn"
        href="vendedor.php?tab=novo-produto"
    >
        <i class="fa-solid fa-plus"></i>
        Adicionar produto
    </a>

</div>


<div class="product-grid">

<?php foreach ($produtos as $p): ?>

<article class="product-card">

<div class="product-image">

<img
    src="<?= seller_e(
        $p['imagem']
        ?: $base .
        '/public/assets/images/placeholder.png'
    ) ?>"
    alt="<?= seller_e($p['nome']) ?>"
>


<span>
    <?= (int) $p['imagens_total'] ?>
    imagem(ns)
</span>

</div>


<div class="product-info">

<div class="product-cat">
    <?= seller_e($p['categoria']) ?>
</div>


<h3>
    <?= seller_e($p['nome']) ?>
</h3>


<div class="price">

R$
<?= number_format(
    (float) $p['preco'],
    2,
    ',',
    '.'
) ?>

</div>


<div class="product-meta">

<span>
    Estoque:
    <b>
        <?= (int) $p['estoque'] ?>
    </b>
</span>


<span
    class="status <?= seller_e($p['status']) ?>"
>
    <?= ucfirst(
        seller_e($p['status'])
    ) ?>
</span>

</div>

</div>


<div class="product-actions">

<a
    href="vendedor.php?tab=novo-produto&edit=<?= (int) $p['id'] ?>"
>
    <i class="fa-solid fa-pen"></i>
    Editar
</a>


<form method="post">

<input
    type="hidden"
    name="csrf"
    value="<?= seller_e($csrf) ?>"
>

<input
    type="hidden"
    name="acao"
    value="produto_status"
>

<input
    type="hidden"
    name="produto_id"
    value="<?= (int) $p['id'] ?>"
>

<input
    type="hidden"
    name="status"
    value="<?= $p['status'] === 'ativo'
        ? 'pausado'
        : 'ativo'
    ?>"
>

<input
    type="hidden"
    name="tab"
    value="produtos"
>

<button type="submit">

<i class="fa-solid fa-pause"></i>

<?= $p['status'] === 'ativo'
    ? 'Pausar'
    : 'Ativar'
?>

</button>

</form>

</div>

</article>

<?php endforeach; ?>

</div>


<?php elseif ($tab === 'novo-produto'): ?>

<div class="section-heading">

<div>

<span class="eyebrow">

<?= $produtoEdit
    ? 'EDIÇÃO'
    : 'NOVO PRODUTO'
?>

</span>


<h2>

<?= $produtoEdit
    ? 'Editar produto'
    : 'Adicionar produto'
?>

</h2>


<p>

<?= $produtoEdit
    ? 'Atualize os dados, estoque e imagens.'
    : 'Cadastre seu produto e publique imediatamente.'
?>

</p>

</div>

</div>


<form
    class="seller-form product-form"
    method="post"
    enctype="multipart/form-data"
>

<input
    type="hidden"
    name="csrf"
    value="<?= seller_e($csrf) ?>"
>


<input
    type="hidden"
    name="acao"
    value="<?= $produtoEdit
        ? 'produto_edit'
        : 'produto_add'
    ?>"
>


<input
    type="hidden"
    name="tab"
    value="novo-produto"
>


<?php if ($produtoEdit): ?>

<input
    type="hidden"
    name="produto_id"
    value="<?= (int) $produtoEdit['id'] ?>"
>

<?php endif; ?>


<div class="form-card">

<h3>
    <i class="fa-solid fa-circle-info"></i>
    Informações do produto
</h3>


<div class="form-grid two">


<label>

Nome do produto

<input
    type="text"
    name="nome"
    required
    value="<?= seller_e(
        $produtoEdit['nome'] ?? ''
    ) ?>"
    placeholder="Ex.: Smartphone Samsung Galaxy"
>

</label>


<label>

Categoria

<select
    name="categoria_id"
    required
>

<option value="">
    Selecione uma categoria
</option>


<?php foreach (
    $categorias
    as $categoria
): ?>

<option
    value="<?= (int) $categoria['id'] ?>"
    <?= (
        (int) (
            $produtoEdit['categoria_id']
            ?? 0
        )
        ===
        (int) $categoria['id']
    )
        ? 'selected'
        : ''
    ?>
>

<?= seller_e(
    $categoria['nome']
) ?>

</option>

<?php endforeach; ?>

</select>

</label>


<label>

SKU

<input
    type="text"
    name="sku"
    required
    value="<?= seller_e(
        $produtoEdit['sku'] ?? ''
    ) ?>"
    placeholder="SKU-00001"
>

</label>


<label>

Marca

<input
    type="text"
    name="marca"
    value="<?= seller_e(
        $produtoEdit['marca'] ?? ''
    ) ?>"
    placeholder="Ex.: Samsung"
>

</label>


<label>

Código de barras

<input
    type="text"
    name="codigo_barras"
    value="<?= seller_e(
        $produtoEdit['codigo_barras'] ?? ''
    ) ?>"
>

</label>

</div>


<label>

Descrição

<textarea
    name="descricao"
    rows="7"
    required
    placeholder="Descreva detalhadamente seu produto..."
><?= seller_e(
    $produtoEdit['descricao'] ?? ''
) ?></textarea>

</label>

</div>


<div class="form-card">

<h3>
    <i class="fa-solid fa-tag"></i>
    Preço e estoque
</h3>


<div class="form-grid four">


<label>

Preço

<input
    type="number"
    step="0.01"
    min="0.01"
    name="preco"
    required
    value="<?= seller_e(
        $produtoEdit['preco'] ?? ''
    ) ?>"
>

</label>


<label>

Preço promocional

<input
    type="number"
    step="0.01"
    min="0"
    name="preco_promocional"
    value="<?= seller_e(
        $produtoEdit['preco_promocional']
        ?? ''
    ) ?>"
>

</label>


<label>

Estoque

<input
    type="number"
    min="0"
    name="estoque"
    required
    value="<?= seller_e(
        $produtoEdit['estoque']
        ?? 0
    ) ?>"
>

</label>


<?php if ($produtoEdit): ?>

<label>

Status

<select name="status">

<?php foreach (
    [
        'ativo',
        'pausado',
        'inativo'
    ]
    as $status
): ?>

<option
    value="<?= $status ?>"
    <?= (
        $produtoEdit['status']
        === $status
    )
        ? 'selected'
        : ''
    ?>
>

<?= ucfirst($status) ?>

</option>

<?php endforeach; ?>

</select>

</label>

<?php endif; ?>

</div>

</div>


<div class="form-card image-upload-card">

<h3>

<i class="fa-solid fa-images"></i>

Imagens do produto

</h3>


<p class="form-help">

Adicione até <strong>10 imagens</strong>.
A primeira imagem será usada como imagem principal.

</p>


<label
    class="upload-zone"
    for="produtoImagens"
>

<div class="upload-icon">

<i class="fa-solid fa-cloud-arrow-up"></i>

</div>


<strong>
    Selecionar imagens
</strong>


<span>
    Clique aqui para escolher várias imagens
</span>


<small>
    JPG, PNG, WEBP ou GIF · máximo 6 MB por imagem
</small>


<input
    id="produtoImagens"
    type="file"
    name="imagens[]"
    accept="image/jpeg,image/png,image/webp,image/gif"
    multiple
>


</label>


<div
    id="previewImagens"
    class="image-preview-grid"
></div>


<?php if (
    $produtoEdit &&
    $imagensEdit
): ?>

<div class="existing-images">

<h4>
    Imagens atuais
</h4>


<div class="image-manager">

<?php foreach (
    $imagensEdit
    as $img
): ?>

<div class="image-item">

<img
    src="<?= seller_e(
        $img['imagem']
    ) ?>"
    alt="Imagem do produto"
>


<div class="image-item-actions">

<?php if ($img['principal']): ?>

<span class="principal-badge">
    <i class="fa-solid fa-star"></i>
    Principal
</span>

<?php else: ?>

<form method="post">

<input
    type="hidden"
    name="csrf"
    value="<?= seller_e($csrf) ?>"
>

<input
    type="hidden"
    name="acao"
    value="imagem_principal"
>

<input
    type="hidden"
    name="produto_id"
    value="<?= (int) $produtoEdit['id'] ?>"
>

<input
    type="hidden"
    name="imagem_id"
    value="<?= (int) $img['id'] ?>"
>

<input
    type="hidden"
    name="tab"
    value="novo-produto"
>

<button
    type="submit"
    class="image-main-btn"
>
    <i class="fa-solid fa-star"></i>
    Tornar principal
</button>

</form>

<?php endif; ?>


<label class="remove-image">

<input
    type="checkbox"
    name="remover_imagens[]"
    value="<?= (int) $img['id'] ?>"
>

Remover

</label>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

<?php endif; ?>

</div>


<div class="form-card">

<h3>

<i class="fa-solid fa-ruler-combined"></i>

Dimensões e peso

</h3>


<div class="form-grid four">


<label>

Peso

<input
    type="number"
    step="0.01"
    min="0"
    name="peso"
    value="<?= seller_e(
        $produtoEdit['peso'] ?? ''
    ) ?>"
    placeholder="kg"
>

</label>


<label>

Altura

<input
    type="number"
    step="0.01"
    min="0"
    name="altura"
    value="<?= seller_e(
        $produtoEdit['altura'] ?? ''
    ) ?>"
    placeholder="cm"
>

</label>


<label>

Largura

<input
    type="number"
    step="0.01"
    min="0"
    name="largura"
    value="<?= seller_e(
        $produtoEdit['largura'] ?? ''
    ) ?>"
    placeholder="cm"
>

</label>


<label>

Comprimento

<input
    type="number"
    step="0.01"
    min="0"
    name="comprimento"
    value="<?= seller_e(
        $produtoEdit['comprimento'] ?? ''
    ) ?>"
    placeholder="cm"
>

</label>

</div>

</div>


<div class="form-actions">

<a
    href="vendedor.php?tab=produtos"
    class="secondary-btn"
>

<i class="fa-solid fa-xmark"></i>

Cancelar

</a>


<button
    class="primary-btn publish-btn"
    type="submit"
>

<i class="fa-solid fa-cloud-arrow-up"></i>

<?= $produtoEdit
    ? 'Salvar alterações'
    : 'Publicar produto'
?>

</button>

</div>

</form>


<?php elseif ($tab === 'categorias'): ?>

<div class="section-heading">

<div>

<span class="eyebrow">
    CATÁLOGO
</span>

<h2>
    Categorias
</h2>

<p>
    Organize seus produtos por categoria.
</p>

</div>

</div>


<div class="two-columns">

<form
    class="form-card seller-form"
    method="post"
>

<input
    type="hidden"
    name="csrf"
    value="<?= seller_e($csrf) ?>"
>

<input
    type="hidden"
    name="acao"
    value="categoria_add"
>

<input
    type="hidden"
    name="tab"
    value="categorias"
>


<h3>
    Nova categoria
</h3>


<label>

Nome da categoria

<input
    type="text"
    name="nome"
    required
    placeholder="Ex.: Celulares"
>

</label>


<button
    class="primary-btn"
    type="submit"
>

<i class="fa-solid fa-plus"></i>

Adicionar categoria

</button>

</form>


<div class="form-card">

<h3>
    Categorias disponíveis
</h3>


<div class="category-list">

<?php foreach (
    $categorias
    as $categoria
): ?>

<div>

<span>

<i class="fa-solid fa-tag"></i>

<?= seller_e(
    $categoria['nome']
) ?>

</span>

<small>
    Disponível para produtos
</small>

</div>

<?php endforeach; ?>


<?php if (!$categorias): ?>

<p class="empty">
    Nenhuma categoria cadastrada.
</p>

<?php endif; ?>

</div>

</div>

</div>


<?php elseif ($tab === 'pedidos'): ?>

<div class="section-heading">

<div>

<span class="eyebrow">
    VENDAS
</span>

<h2>
    Pedidos
</h2>

<p>
    Acompanhe as vendas da sua loja.
</p>

</div>

</div>


<div class="product-table-wrap">

<table class="product-table">

<thead>

<tr>

<th>
    Pedido
</th>

<th>
    Produto
</th>

<th>
    Quantidade
</th>

<th>
    Total
</th>

<th>
    Status
</th>

<th>
    Data
</th>

</tr>

</thead>

<tbody>

<?php foreach (
    $pedidos
    as $p
): ?>

<tr>

<td>
    <strong>
        #<?= seller_e(
            $p['numero_pedido']
        ) ?>
    </strong>
</td>


<td>
    <?= seller_e(
        $p['produto']
    ) ?>
</td>


<td>
    <?= (int) $p['quantidade'] ?>
</td>


<td>

R$

<?= number_format(
    (float) $p['subtotal'],
    2,
    ',',
    '.'
) ?>

</td>


<td>

<span
    class="status <?= seller_e($p['status']) ?>"
>

<?= ucfirst(
    seller_e($p['status'])
) ?>

</span>

</td>


<td>

<?= date(
    'd/m/Y H:i',
    strtotime(
        $p['created_at']
    )
) ?>

</td>

</tr>

<?php endforeach; ?>


<?php if (!$pedidos): ?>

<tr>

<td
    colspan="6"
    class="empty"
>

Ainda não existem vendas.

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>


<?php elseif ($tab === 'loja'): ?>

<div class="section-heading">

<div>

<span class="eyebrow">
    IDENTIDADE
</span>

<h2>
    Configurar minha loja
</h2>

<p>
    Personalize a apresentação da sua loja.
</p>

</div>

</div>


<form
    class="seller-form"
    method="post"
    enctype="multipart/form-data"
>

<input
    type="hidden"
    name="csrf"
    value="<?= seller_e($csrf) ?>"
>

<input
    type="hidden"
    name="acao"
    value="loja_update"
>

<input
    type="hidden"
    name="tab"
    value="loja"
>


<div class="form-card">

<h3>
    Dados da loja
</h3>


<label>

Nome da loja

<input
    type="text"
    name="nome_loja"
    required
    value="<?= seller_e(
        $loja['nome_loja']
    ) ?>"
>

</label>


<label>

Descrição

<textarea
    name="descricao_loja"
    rows="5"
><?= seller_e(
    $loja['descricao'] ?? ''
) ?></textarea>

</label>

</div>


<div class="form-actions">

<button
    class="primary-btn"
    type="submit"
>

<i class="fa-solid fa-floppy-disk"></i>

Salvar loja

</button>

</div>

</form>

<?php endif; ?>

</section>

</main>

</div>


<script>

/* =========================================================
   MENU MOBILE
========================================================= */

const btn =
    document.getElementById(
        'mobileMenu'
    );

const sidebar =
    document.getElementById(
        'sellerSidebar'
    );


if (btn && sidebar) {

    btn.addEventListener(
        'click',
        () => {

            sidebar.classList.toggle(
                'open'
            );

        }
    );
}


document
    .querySelectorAll(
        '.seller-nav a'
    )
    .forEach(
        link => {

            link.addEventListener(
                'click',
                () => {

                    sidebar?.classList.remove(
                        'open'
                    );

                }
            );

        }
    );


/* =========================================================
   PREVISUALIZAÇÃO DAS IMAGENS
========================================================= */

const inputImagens =
    document.getElementById(
        'produtoImagens'
    );

const preview =
    document.getElementById(
        'previewImagens'
    );


if (
    inputImagens &&
    preview
) {

    inputImagens.addEventListener(
        'change',
        () => {

            preview.innerHTML = '';

            const files =
                Array.from(
                    inputImagens.files
                );


            if (files.length > 10) {

                alert(
                    'Você pode selecionar no máximo 10 imagens.'
                );

                inputImagens.value = '';

                return;
            }


            files.forEach(
                (file, index) => {

                    if (
                        !file.type.startsWith(
                            'image/'
                        )
                    ) {
                        return;
                    }


                    const reader =
                        new FileReader();


                    reader.onload =
                        event => {

                            const item =
                                document.createElement(
                                    'div'
                                );

                            item.className =
                                'preview-image';


                            item.innerHTML = `

                                <img
                                    src="${event.target.result}"
                                    alt="Pré-visualização"
                                >

                                <span>
                                    ${index === 0
                                        ? 'Principal'
                                        : 'Imagem ' + (index + 1)
                                    }
                                </span>

                            `;


                            preview.appendChild(
                                item
                            );
                        };


                    reader.readAsDataURL(
                        file
                    );

                }
            );

        }
    );
}


/* =========================================================
   CONFIRMAÇÃO DE REMOÇÃO
========================================================= */

document
    .querySelectorAll(
        'input[name="remover_imagens[]"]'
    )
    .forEach(
        checkbox => {

            checkbox.addEventListener(
                'change',
                () => {

                    if (
                        checkbox.checked &&
                        !confirm(
                            'Deseja remover esta imagem ao salvar?'
                        )
                    ) {

                        checkbox.checked =
                            false;
                    }

                }
            );

        }
    );

</script>

</body>

</html>