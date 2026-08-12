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

// imagem placeholder para produtos sem imagem principal cadastrada
$placeholder = 'https://placehold.co/400x400/f1f5f9/64748b?text=Sem+imagem';

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

<header class="header">

    <div class="container header-container">

        <a href="/TCC_RELPJAM/app/views/home.php" class="logo">
              <img
        src="<?= $base ?>/public/images/logotop.png"
        alt="Logo "
        style="height:120px;width:auto;display:block;object-fit:contain;"
    >
         </a>

        <nav class="menu">
<a href="<?= $base ?>/app/views/categorias.php">Categorias</a>
<a href="<?= $base ?>">Ofertas</a>
<a href="<?= $base ?>/app/views/lojas.php">Lojas</a>
<a href="<?= $base ?>/app/views/contactme.php">Contato</a>
        </nav>

        <div class="search-box">

            <svg xmlns="http://www.w3.org/2000/svg"
                 width="20"
                 height="20"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor">

                <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-5.2-5.2M10.8 18A7.2 7.2 0 1010.8 3.6a7.2 7.2 0 000 14.4z"/>

            </svg>

            <input
                id="campoBusca"
                type="text"
                placeholder="Buscar produtos..." >

        </div>

        <div class="header-icons">

            <button class="icon-btn"> ❤ </button>
            <button class="icon-btn">🔔</button>
            <button class="icon-btn">🛒</button>
            <?php if (($_SESSION['tipo_usuario'] ?? '') === 'vendedor'): ?>
            <a href="<?= $base ?>/app/views/vendedor.php" class="profile-btn">Minha Loja</a>
            <?php else: ?>
            <a href="<?= $base ?>/app/views/sign.php" class="profile-btn">Perfil</a>
            <?php endif; ?>

        </div>

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

<section class="categorias-section">

<div class="container">

<div class="section-title">

<h2>

Explore por Categorias

</h2>

<p>

Encontre rapidamente aquilo que procura.

</p>

</div>

<nav class="categorias">

<ul>

<li>

<button class="btn-categoria ativo"
data-categoria="Todos">

<span class="categoria-icon">

🏠

</span>

<span>

Todos

</span>

</button>

</li>

<?php

$icones = [

    "Notebook"   => "💻",
    "Celular"    => "📱",
    "Informática"=> "🖥️",
    "TV"         => "📺",
    "Games"      => "🎮",
    "Moda"       => "👕",
    "Casa"       => "🏡",
    "Veículos"   => "🚗",
    "Esportes"   => "⚽",
    "Livros"     => "📚"

];

?>

<?php foreach($categorias as $categoria): ?>

<li>

<button
class="btn-categoria"
data-categoria="<?= htmlspecialchars(strtolower($categoria['nome'])) ?>">

<span class="categoria-icon">

<?= $icones[$categoria['nome']] ?? "🛍️"; ?>

</span>

<span>

<?= htmlspecialchars($categoria['nome']) ?>

</span>

</button>

</li>

<?php endforeach; ?>

</ul>

</nav>

</div>

</section>

<!-- ================= PRODUTOS ================= -->
<main class="produtos" id="containerProdutos"></main>


<!-- ================= OFERTAS RELÂMPAGO ================= -->

<section class="secao-market">

<h2>🔥 Ofertas Relâmpago</h2>

<div class="lista-produtos">

<?php foreach(array_slice($produtos,0,4) as $produto): ?>

<div class="mini-produto">

<img src="<?= htmlspecialchars($produto['imagem'] ?: $placeholder) ?>"
     alt="<?= htmlspecialchars($produto['nome']) ?>"
     onerror="this.onerror=null;this.src='<?= $placeholder ?>';">

<h3><?= htmlspecialchars($produto['nome']) ?></h3>

<span>
R$ <?= number_format($produto['preco'],2,",",".") ?>
</span>

<button>
Comprar
</button>

</div>

<?php endforeach; ?>

</div>

</section>



<!-- ================= MAIS VENDIDOS ================= -->


<section class="secao-market">

<h2>📦 Mais Vendidos</h2>


<div class="lista-produtos">

<?php foreach(array_slice($produtos,4,4) as $produto): ?>

<div class="mini-produto">

<img src="<?= htmlspecialchars($produto['imagem'] ?: $placeholder) ?>"
     alt="<?= htmlspecialchars($produto['nome']) ?>"
     onerror="this.onerror=null;this.src='<?= $placeholder ?>';">

<h3><?= htmlspecialchars($produto['nome']) ?></h3>

<div class="avaliacao">
★★★★★
</div>

<span>
R$ <?= number_format($produto['preco'],2,",",".") ?>
</span>


</div>

<?php endforeach; ?>


</div>
<!--fim ofertas -->

</section>
<section class="hero">

    <div class="container hero-grid">

        <div class="hero-content">

            <span class="hero-tag">

                Marketplace Premium

            </span>

            <h1>

                Comprar e vender nunca foi tão moderno.

            </h1>

            <p>

                Descubra milhares de produtos com segurança,
                rapidez e uma experiência totalmente renovada.

            </p>

            <div class="hero-buttons">

                <button class="btn btn-primary">

                    Explorar Produtos

                </button>

                <button class="btn btn-outline">

                    Quero vender

                </button>

            </div>

            <div class="hero-stats">

                <div>

                    <strong>12k+</strong>

                    <span>Produtos</span>

                </div>

                <div>

                    <strong>2.800+</strong>

                    <span>Vendedores</span>

                </div>

                <div>

                    <strong>98%</strong>

                    <span>Avaliação</span>

                </div>

            </div>

        </div>

        <div class="hero-preview">

            <div class="floating-card card1">

                <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500" alt="Notebook Gamer">

                <h3>Notebook Gamer</h3>

                <span>R$ 4.999</span>

            </div>

            <div class="floating-card card2">

                <img src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500" alt="iPhone">

                <h3>iPhone</h3>

                <span>R$ 5.899</span>

            </div>

            <div class="floating-card card3">

                <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500" alt="Tênis">

                <h3>Tênis</h3>

                <span>R$ 399</span>

            </div>

        </div>

    </div>

</section>

<section class="secao-market">

<h2>
 Lançamentos
</h2>


<div class="lista-produtos">


<?php foreach(array_slice($produtos,8,4) as $produto): ?>


<div class="mini-produto">


<img src="<?= htmlspecialchars($produto['imagem'] ?: $placeholder) ?>"
     alt="<?= htmlspecialchars($produto['nome']) ?>"
     onerror="this.onerror=null;this.src='<?= $placeholder ?>';">


<h3>
<?= htmlspecialchars($produto['nome']) ?>
</h3>


<span>

R$ <?= number_format($produto['preco'],2,",",".") ?>

</span>


</div>


<?php endforeach; ?>


</div>

</section>
<section class="vendedores">


<h2>
Vendedores em Destaque
</h2>


<div class="lojas">


<div>
Loja Tech
<br>
★★★★★
</div>


<div>
Mundo Gamer
<br>
★★★★★
</div>


<div>
 Fashion Store
<br>
★★★★★
</div>


</div>


</section>
<section class="dicas">


<h2>
 Dicas e Novidades
</h2>


<div class="cards-dicas">


<article>

<a href='/TCC_RELPJAM/app/views/informativo2.php'>
<h3>
Como escolher um notebook
</h3>

<p>
Veja dicas para comprar melhor.
</p>
</a>
</article>



<article>

<a href='/TCC_RELPJAM/app/views/informativo.php'>
<h3>
Como montar um PC
</h3>

<p>
Confira nossos guias.
</p>
</a>
</article>



<article>
<a href='/TCC_RELPJAM/app/views/informativo3.php'>
<h3 >
Melhores celulares de 2026
</h3>

<p>
Veja os modelos mais procurados.
</p>
</a>

</article>


</div>


</section>

<script>
    //estilização
window.addEventListener("scroll",()=>{

    const header=document.querySelector(".header");

    if(window.scrollY>60){

        header.classList.add("scrolled");

    }else{

        header.classList.remove("scrolled");

    }

});
//estilização

const itensCarrossel = <?= json_encode(array_map(function($item){
    return [
        "nome" => $item["titulo"] ?? "",
        "preco" => "",
        "imagem" => $item["imagem"] ?? "",
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
        if (!track || !itensCarrossel.length) return;

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
        if (!indicadoresContainer || !totalCards) return;

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
        if (!totalCards || !cards.length) return;

        const wrapper = document.querySelector('.carrossel-wrapper');
        const larguraWrapper = wrapper.offsetWidth;
        const cardAtivo = cards[indiceCentral];
        if (!cardAtivo) return;
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
        if (!totalCards) return;

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
        if (!totalCards) return;
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

                <img src="<?= htmlspecialchars($produto['imagem'] ?: $placeholder) ?>"
                     alt="<?= htmlspecialchars($produto['nome']) ?>"
                     onerror="this.onerror=null;this.src='<?= $placeholder ?>';">

                <p><?= htmlspecialchars($produto['nome']) ?></p>

                <span class="produto-categoria">
                    <?= htmlspecialchars($produto['categoria']) ?>
                </span>

                <div class="produto-preco">
                    R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                </div>

                <button class="produto-comprar">Comprar</button>

            </div>

        <?php endforeach; ?>


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
    if (btnPrev) btnPrev.addEventListener('click', slideAnterior);
    if (btnNext) btnNext.addEventListener('click', proximoSlide);

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

    if (sliderCategorias) {
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
    }

// ================= FILTRO POR CATEGORIA =================

    const botoesCategoria = document.querySelectorAll('.btn-categoria');

    botoesCategoria.forEach(botao => {

        botao.addEventListener('click', () => {


              document
.querySelectorAll(".btn-categoria")
.forEach(btn=>{

btn.classList.remove("ativo");

});

botao.classList.add("ativo");

            const categoria = botao.dataset.categoria.toLowerCase();

            const produtos = document.querySelectorAll('.produto-card');

            produtos.forEach(produto => {

                const nomeCategoria = produto
                    .querySelector('.produto-categoria')
                    ?.innerText
                    .toLowerCase() || '';

                if (categoria === 'todos') {
                    produto.style.display = '';
                    return;
                }

                if (nomeCategoria.includes(categoria)) {
                    produto.style.display = '';
                } else {
                    produto.style.display = 'none';
                }

            });

        });

    });

    iniciar();
</script>
<footer class="footer">


<div class="footer-grid">


<div>

<h2>
RELPJAM
</h2>

<p>
Marketplace de compra e venda.
</p>
<div class="footer-social">

    <!-- Instagram -->
    <a href="https://www.instagram.com/relpjam_oficial/"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Instagram">
        <img src="/TCC_RELPJAM/public/images/instagram.png" alt="Instagram">
    </a>

    <!-- E-mail com assunto e mensagem pré-preenchidos -->
    <a href="https://mail.google.com/mail/?view=cm&fs=1&to=relpjamtcc@gmail.com&su=Contato%20pelo%20site&body=Olá!%0A%0AEncontrei%20o%20site%20da%20RELPJAM%20e%20gostaria%20de%20obter%20mais%20informações.%0A%0AAguardo%20o%20retorno.%0A%0AAtenciosamente,"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="Gmail">
    <img src="/TCC_RELPJAM/public/images/gmail.png" alt="Gmail">
</a>

    <!-- WhatsApp com mensagem pré-programada -->
    <a href="https://wa.me/5514998894962?text=Olá!%20Encontrei%20o%20site%20da%20empresa%20e%20gostaria%20de%20obter%20mais%20informações."
       target="_blank"
       rel="noopener noreferrer"
       aria-label="WhatsApp">
        <img src="/TCC_RELPJAM/public/images/whatsapp.png" alt="WhatsApp">
    </a>

</div>

</div>


<div>

<h3>
Institucional
</h3>

<a href="paginaquemsomos">
Quem somos
</a>

<a href="paginadefeedback">
Feedback
</a>

<a href="paginadetermos">
Termos
</a>

</div>



<div>

<h3>
Ajuda
</h3>

<a href="centralcontatoajuda">
Central de ajuda
</a>

<a href="trocasedev">
Trocas e devoluções
</a>

<a href="tratamentodepriv">
Privacidade
</a>

</div>



<div>

<h3>
Pagamento
</h3>


<p>
💳 Cartão
</p>

<p>
PIX
</p>

<p>
Boleto
</p>


</div>



</div>



<div class="footer-final">

<span>© 2026 RELPJAM. Todos os direitos reservados.</span>

<span>Feito no TCC da Escola Prof Etec Terezinha Monteiro dos Santos</span>

</div>


</footer>
</body>
</html>
