<?php

$base = "/TCC_RELPJAM";

require_once "config.php"; 

$sqlCategorias = $pdo->query("
    SELECT *
    FROM categorias
    WHERE ativo = TRUE
");

$sqlCarrossel = $pdo->query("
    SELECT *
    FROM carrossel_imagens
    WHERE ativo = TRUE
    ORDER BY ordem ASC
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
<title>RELPJAM </title>
<link rel="stylesheet" href="<?= $base ?>/public/assets/css/style.css">
</head>
<body>

<header>
    <div class="logo">RELPJAM</div>
    <div class="pesquisa">
        <input type="text" id="campoBusca" placeholder="Buscar Produtos">
    </div>
    <div class="icones">
        
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
            <button class="btn-categoria" data-categoria="Todos">
                Todos
            </button>
        </li>
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
        "emblema" => "Destaque"
    ];
}, $carrossel), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        

    // ELEMENTOS
    const track = document.getElementById("carrosselTrack");
    const indicadoresContainer = document.getElementById("carrosselIndicadores");
    const btnPrev = document.getElementById("btnPrev");
    const btnNext = document.getElementById("btnNext");

    let cards = [];
    let indiceCentral = 0;
    let intervaloAuto;
    let totalCards = 0;
    let larguraCard = 385;

    // CONSTRUIR CARROSSEL
    function construirCarrossel() {
        track.innerHTML = '';
        const cardsDuplicados = [...itensCarrossel, ...itensCarrossel, ...itensCarrossel];
        
        cardsDuplicados.forEach((item, idx) => {
            const card = document.createElement('div');
            card.className = 'carrossel-card';
            if (idx === itensCarrossel.length) card.classList.add('ativo');
            card.innerHTML = `
                <div class="card-emblema">${item.emblema}</div>
                <img src="${item.imagem}" alt="${item.nome}" 
                     onerror="this.src='https://placehold.co/280x200/667eea/white?text=RELPJAM'">
                <span>${item.nome}</span>
                <div class="card-preco">${item.preco}</div>
            `;
            card.onclick = () => alert(`Produto: ${item.nome}\nPreco: ${item.preco}`);
            track.appendChild(card);
        });
        
        cards = document.querySelectorAll('.carrossel-card');
        totalCards = itensCarrossel.length;
        indiceCentral = totalCards;
    }

    // CONSTRUIR INDICADORES
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

    // ATUALIZAR INDICADORES
    function atualizarIndicadores() {
        const indicadores = document.querySelectorAll('.indicador');
        const indiceOriginal = ((indiceCentral - totalCards) % totalCards + totalCards) % totalCards;
        
        indicadores.forEach((ind, idx) => {
            if (idx === indiceOriginal) {
                ind.classList.add('ativo');
            } else {
                ind.classList.remove('ativo');
            }
        });
        
        cards.forEach((card, idx) => {
            if (idx === indiceCentral) {
                card.classList.add('ativo');
            } else {
                card.classList.remove('ativo');
            }
        });
    }

    // ATUALIZAR POSICAO DO CARROSSEL
    function atualizarCarrossel() {
        const wrapper = document.querySelector('.carrossel-wrapper');
        const larguraWrapper = wrapper.offsetWidth;
        const cardAtivo = cards[indiceCentral];
        const larguraCardAtivo = cardAtivo.offsetWidth;
        
        const posicaoCentral = (larguraWrapper / 2) - (larguraCardAtivo / 2);
        const deslocamento = (indiceCentral * larguraCard) - posicaoCentral;
        
        track.style.transform = `translateX(-${deslocamento}px)`;
        atualizarIndicadores();
    }

    // IR PARA SLIDE ESPECIFICO
    function irParaSlide(indice) {
        const novoIndice = indice + totalCards;
        indiceCentral = novoIndice;
        atualizarCarrossel();
        reiniciarTimer();
    }

    // MOVER CARROSSEL
    function moverCarrossel(direcao) {
        const novoIndice = indiceCentral + direcao;
        indiceCentral = novoIndice;
        atualizarCarrossel();
        
        setTimeout(() => {
            if (indiceCentral >= totalCards * 2) {
                track.style.transition = 'none';
                indiceCentral = totalCards;
                atualizarCarrossel();
                setTimeout(() => {
                    track.style.transition = 'transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                }, 50);
            }
            if (indiceCentral < totalCards) {
                track.style.transition = 'none';
                indiceCentral = totalCards;
                atualizarCarrossel();
                setTimeout(() => {
                    track.style.transition = 'transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                }, 50);
            }
        }, 500);
        
        reiniciarTimer();
    }

    // PROXIMO SLIDE
    function proximoSlide() {
        moverCarrossel(1);
    }

    // SLIDE ANTERIOR
    function slideAnterior() {
        moverCarrossel(-1);
    }

    // REINICIAR TIMER AUTOMATICO
    function reiniciarTimer() {
        if (intervaloAuto) clearInterval(intervaloAuto);
        intervaloAuto = setInterval(() => {
            moverCarrossel(1);
        }, 6000);
    }

    function construirProdutos() {

    const container = document.getElementById('containerProdutos');

    container.innerHTML = `

        <?php foreach($produtos as $produto): ?>

            <div class="produto-card">

                <img src="<?= $produto['imagem'] ?>">

                <p><?= $produto['nome'] ?></p>

                <span class="produto-categoria">
                    <?= $produto['categoria'] ?>
                </span>

                <div class="produto-preco">
                    R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                </div>

            </div>

        <?php endforeach; ?>

        <a href="add_produto.php" class="produto-add-card">

            <div class="add-icone">+</div>

            <span>Adicionar Produto</span>

        </a>

    `;
    }

    // FUNCAO DE BUSCA
    const campoBusca = document.getElementById('campoBusca');
    if (campoBusca) {
        campoBusca.addEventListener('input', function(e) {
            const termo = e.target.value.toLowerCase();
            const produtos = document.querySelectorAll('.produto-card');
            produtos.forEach(produto => {
                const nome = produto.querySelector('p')?.innerText.toLowerCase() || '';
                if (nome.includes(termo)) {
                    produto.style.display = '';
                } else {
                    produto.style.display = 'none';
                }
            });
        });
    }

    // EVENTOS DOS BOTOES
    btnPrev.addEventListener('click', slideAnterior);
    btnNext.addEventListener('click', proximoSlide);

    // TECLADO
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            slideAnterior();
            reiniciarTimer();
        } else if (e.key === 'ArrowRight') {
            proximoSlide();
            reiniciarTimer();
        }
    });

    // REDIMENSIONAMENTO
    let tempoResize;
    window.addEventListener('resize', () => {
        clearTimeout(tempoResize);
        tempoResize = setTimeout(() => {
            larguraCard = 385;
            atualizarCarrossel();
        }, 200);
    });

    // INICIALIZAR
    function iniciar() {
        construirCarrossel();
        construirIndicadores();
        construirProdutos();
        setTimeout(() => {
            atualizarCarrossel();
        }, 100);
        reiniciarTimer();
        console.log("Carrossel iniciado");
    }

    // ================= SCROLL COM ARRASTAR =================

    const sliderCategorias = document.querySelector('.categorias');

    let segurando = false;
    let inicioX;
    let scrollInicial;

    sliderCategorias.addEventListener('mousedown', (e) => {
        segurando = true;
        sliderCategorias.classList.add('ativo-scroll');

        inicioX = e.pageX - sliderCategorias.offsetLeft;
        scrollInicial = sliderCategorias.scrollLeft;
    });

    sliderCategorias.addEventListener('mouseleave', () => {
        segurando = false;
    });

    sliderCategorias.addEventListener('mouseup', () => {
        segurando = false;
    });

    sliderCategorias.addEventListener('mousemove', (e) => {
        if (!segurando) return;

        e.preventDefault();

        const x = e.pageX - sliderCategorias.offsetLeft;
        const distancia = (x - inicioX) * 2;

        sliderCategorias.scrollLeft = scrollInicial - distancia;
    });

// ================= FILTRO POR CATEGORIA =================

    const botoesCategoria = document.querySelectorAll('.btn-categoria');

    botoesCategoria.forEach(botao => {

        botao.addEventListener('click', () => {

            botoesCategoria.forEach(btn => {
                btn.classList.remove('ativo');
            });

            botao.classList.add('ativo');

            const categoria = botao.dataset.categoria.toLowerCase();

            const produtos = document.querySelectorAll('.produto-card');

            produtos.forEach(produto => {

                const nomeCategoria = produto
                    .querySelector('.produto-categoria')
                    ?.innerText
                    .toLowerCase();

                if (categoria === 'todos') {
                    produto.style.display = 'block';
                    return;
                }

                if (nomeCategoria.includes(categoria)) {
                    produto.style.display = 'block';
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
