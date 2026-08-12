<?php

$base = "/TCC_RELPJAM";

require_once "config.php"; 

$sqlCategorias = $pdo->query("
    SELECT *
    FROM categorias
    WHERE ativo = TRUE
");

$sqlCarrossel = $pdo->query("
    SELECT 
        carrossel_imagens.*,
        produtos.id AS produto_id,
        produtos.nome AS produto_nome
    FROM carrossel_imagens
    LEFT JOIN produtos
        ON produtos.id = carrossel_imagens.produto_id
    WHERE carrossel_imagens.ativo = TRUE
    ORDER BY carrossel_imagens.ordem ASC
");

$carrossel = $sqlCarrossel->fetchAll(PDO::FETCH_ASSOC);

$categorias = $sqlCategorias->fetchAll(PDO::FETCH_ASSOC);

$sqlProdutos = $pdo->query("
    SELECT 
        produtos.id,
        produtos.nome,
        produtos.preco,
        categorias.nome AS categoria,
        produto_imagens.imagem

    FROM produtos

    INNER JOIN categorias
        ON categorias.id = produtos.categoria_id

    LEFT JOIN produto_imagens
        ON produto_imagens.produto_id = produtos.id
        AND produto_imagens.principal = TRUE

    WHERE produtos.status = 'ativo'
");

$produtos = $sqlProdutos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RELPJAM</title>
<link rel="stylesheet" href="<?= $base ?>/public/assets/css/style.css">
</head>
<body>

<header>
    <div class="logo">RELPJAM</div>

    <div class="pesquisa">
        <input type="text" id="campoBusca" placeholder="Buscar Produtos">
    </div>

    <div class="icones">
        
        <a href="adm_carrossel.php" class="">
            <div class="">+ Adicionar carrossel</div>
        </a>

        <span>Perfil</span>
    </div>
</header>

<!-- ================= CARROSSEL ================= -->
<div class="carrossel-container">

    <button class="carrossel-btn btn-prev" id="btnPrev">
        <svg viewBox="0 0 24 24">
            <path d="M15 18l-6-6 6-6"></path>
        </svg>
    </button>

    <div class="carrossel-wrapper">
        <div class="carrossel-track" id="carrosselTrack"></div>
    </div>

    <button class="carrossel-btn btn-next" id="btnNext">
        <svg viewBox="0 0 24 24">
            <path d="M9 6l6 6-6 6"></path>
        </svg>
    </button>

    <div class="carrossel-indicadores" id="carrosselIndicadores"></div>

</div>

<!-- ================= CATEGORIAS ================= -->

<nav class="categorias">
    <ul>

        <li class="ativo"></li>

        <button class="btn-categoria ativo" data-categoria="todos">
            Todos
        </button>

        <?php foreach($categorias as $categoria): ?>
        <li>
            <button 
                class="btn-categoria"
                data-categoria="<?= strtolower($categoria['nome']) ?>"
            >
                <?= $categoria['nome'] ?>
            </button>
        </li>
        <?php endforeach; ?>

    </ul>
</nav>

<!-- ================= PRODUTOS ================= -->
<main class="produtos" id="containerProdutos"></main>

<script>

const itensCarrossel = <?= json_encode(array_map(function($item){

    return [
        "nome" => $item["titulo"],
        "preco" => "",
        "imagem" => $item["imagem"],
        "emblema" => "Destaque",
        "produto_id" => $item["produto_id"] ?? null,
        "produto_nome" => $item["produto_nome"] ?? null
    ];

}, $carrossel), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        

// ================= ELEMENTOS =================
const track = document.getElementById("carrosselTrack");
const indicadoresContainer = document.getElementById("carrosselIndicadores");
const btnPrev = document.getElementById("btnPrev");
const btnNext = document.getElementById("btnNext");

let cards = [];
let indiceCentral = 0;
let intervaloAuto;
let totalCards = 0;
let larguraCard = 385;

// ================= CONSTRUIR CARROSSEL =================
function construirCarrossel() {

    track.innerHTML = '';

    const cardsDuplicados = [
        ...itensCarrossel,
        ...itensCarrossel,
        ...itensCarrossel
    ];
    
    cardsDuplicados.forEach((item, idx) => {

        const card = document.createElement('div');
        card.className = 'carrossel-card';

        if (idx === itensCarrossel.length) {
            card.classList.add('ativo');
        }

        card.innerHTML = `
            <div class="card-emblema">${item.emblema}</div>
            <img src="${item.imagem}" 
                 alt="${item.nome}" 
                 onerror="this.src='https://placehold.co/280x200/667eea/white?text=RELPJAM'">
            <span>${item.nome}</span>
            <div class="card-preco">${item.preco}</div>
        `;

        card.onclick = () => {

            if(item.produto_id){
                window.location.href = 'produto.php?id=' + item.produto_id;
                return;
            }

            alert(`Produto: ${item.nome}`);
        };

        track.appendChild(card);
    });

    cards = document.querySelectorAll('.carrossel-card');
    totalCards = itensCarrossel.length;
    indiceCentral = totalCards;
}

// ================= INDICADORES =================
function construirIndicadores() {

    indicadoresContainer.innerHTML = '';

    for (let i = 0; i < totalCards; i++) {

        const indicador = document.createElement('button');
        indicador.className = 'indicador';

        if (i === 0) indicador.classList.add('ativo');

        indicador.onclick = () => irParaSlide(i);

        indicadoresContainer.appendChild(indicador);
    }
}

// ================= ATUALIZAR CARROSSEL =================
function atualizarCarrossel() {

    const wrapper = document.querySelector('.carrossel-wrapper');

    const larguraWrapper = wrapper.offsetWidth;

    const cardAtivo = cards[indiceCentral];

    const larguraCardAtivo = cardAtivo.offsetWidth;

    const posicaoCentral = (larguraWrapper / 2) - (larguraCardAtivo / 2);

    const deslocamento = (indiceCentral * larguraCard) - posicaoCentral;

    track.style.transform = `translateX(-${deslocamento}px)`;

}

// ================= MOVIMENTAÇÃO =================
function moverCarrossel(direcao) {

    indiceCentral += direcao;

    atualizarCarrossel();

}

// ================= BOTÕES =================
btnPrev.addEventListener('click', () => moverCarrossel(-1));
btnNext.addEventListener('click', () => moverCarrossel(1));


// ================= BUSCA =================
const campoBusca = document.getElementById('campoBusca');

if (campoBusca) {

    campoBusca.addEventListener('input', function(e) {

        const termo = e.target.value.toLowerCase();

        const produtos = document.querySelectorAll('.produto-card');

        produtos.forEach(produto => {

            const nome = produto.querySelector('p')?.innerText.toLowerCase() || '';

            produto.style.display = nome.includes(termo) ? '' : 'none';
        });
    });
}

// ================= PRODUTOS =================
function construirProdutos() {

    const container = document.getElementById('containerProdutos');

    container.innerHTML = `

        <?php foreach($produtos as $produto): ?>

            <div class="produto-card">

                <img src="<?= $produto['imagem'] ?>">

                <p><?= $produto['nome'] ?></p>

                <span class="produto-categoria">
                <?= strtolower($produto['categoria']) ?>
                </span>

                <div class="produto-preco">
                    R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                </div>

            </div>

        <?php endforeach; ?>

    `;
}

// ================= INICIALIZAR =================
function iniciar() {

    construirCarrossel();
    construirIndicadores();
    construirProdutos();

    setTimeout(() => {
        atualizarCarrossel();
    }, 100);

    console.log("Carrossel iniciado");
}

const botoesCategoria = document.querySelectorAll('.btn-categoria');

botoesCategoria.forEach(btn => {

    btn.addEventListener('click', function () {

        const categoriaSelecionada = this.getAttribute('data-categoria').trim().toLowerCase();

        const produtos = document.querySelectorAll('.produto-card');

        // reset ativo
        botoesCategoria.forEach(b => b.classList.remove('ativo'));
        this.classList.add('ativo');

        produtos.forEach(produto => {

            const categoriaProdutoEl = produto.querySelector('.produto-categoria');

            if (!categoriaProdutoEl) return;

            const categoriaProduto = categoriaProdutoEl.textContent.trim().toLowerCase();

            // DEBUG (pode remover depois)
            console.log("Selecionado:", categoriaSelecionada, "Produto:", categoriaProduto);

            if (categoriaSelecionada === 'todos') {
                produto.style.display = '';
                return;
            }

            if (categoriaProduto === categoriaSelecionada) {
                produto.style.display = '';
            } else {
                produto.style.display = 'none';
            }
        });

    });
});
iniciar();

</script>

</body>
</html>
