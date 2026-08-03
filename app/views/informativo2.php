<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Guia do Usuário</title>

<style>

:root{
    --primary:#e07f00;
    --primary-dark:#414141;
    --primary-light:#60A5FA;
    --secondary:#0F172A;
    --accent:#F97316;

    --bg:#f5f7fb;
    --card:#ffffff;
    --border:#e6eaf2;
    --text:#334155;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;
}

body{
    background:var(--bg);
    color:var(--text);
    padding:40px 20px;
}

.guia{
    max-width:900px;
    margin:auto;
}

.cabecalho{
    background:linear-gradient(135deg,var(--secondary),var(--primary-dark));
    color:#fff;
    border-radius:18px;
    padding:35px;
    margin-bottom:30px;
}

.cabecalho h1{
    margin-bottom:12px;
    font-size:2rem;
}

.cabecalho p{
    color:#d9d9d9;
    line-height:1.7;
}

.bloco{
    background:var(--card);
    border-radius:16px;
    padding:30px;
    margin-bottom:25px;
    border:1px solid var(--border);
    box-shadow:0 10px 25px rgba(15,23,42,.06);
}

.bloco h2{
    color:var(--primary);
    margin-bottom:18px;
    display:flex;
    align-items:center;
    gap:10px;
}

.bloco p{
    line-height:1.8;
    margin-bottom:15px;
}

.lista{
    list-style:none;
}

.lista li{
    position:relative;
    padding-left:28px;
    margin:12px 0;
    line-height:1.7;
}

.lista li::before{
    content:"✔";
    position:absolute;
    left:0;
    color:var(--primary);
    font-weight:bold;
}

.destaque{
    margin-top:20px;
    background:#fff8ef;
    border-left:5px solid var(--accent);
    padding:18px;
    border-radius:10px;
}

.cards-dicas{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:20px;
    margin-top:20px;
}

.card{
    background:#fff;
    border:1px solid var(--border);
    border-top:4px solid var(--primary);
    border-radius:14px;
    padding:20px;
}

.card h3{
    color:var(--secondary);
    margin-bottom:10px;
}

.card p{
    margin:0;
    color:#64748b;
}

@media(max-width:700px){

    body{
        padding:20px 15px;
    }

    .cabecalho,
    .bloco{
        padding:22px;
    }

}

</style>

</head>
<body>

<div class="guia">

<div class="cabecalho">

<button onclick="window.history.back()" style="background:var(--primary); color:#fff; border:none; padding:10px 15px; border-radius:5px; cursor:pointer; margin-bottom:15px;">&larr; Voltar</button>
<h1 style="margin:0;">Guia do Usuário
     <img
        src="<?= $base ?>/TCC_RELPJAM/public/images/logotop.png"
        alt="Logo "
        style="height:120px;
        width:auto;
        display:flex;
        object-fit:contain;
        align-items:center;
        justify-content:space-between;
        margin-left:20px;"
    >
</h1>

<p>
Nesta página você encontra informações importantes, dicas e recomendações para aproveitar melhor nossa plataforma e fazer escolhas mais seguras na hora de pesquisar produtos.
</p>

</div>

<div class="bloco">

<h2>📖 Sobre este guia</h2>

<p>
O objetivo deste guia é reunir conteúdos educativos sobre tecnologia, ajudando você a entender melhor as características dos produtos antes de realizar uma compra.
</p>

<ul class="lista">
<li>Guias simples e objetivos.</li>
<li>Dicas atualizadas sobre produtos.</li>
<li>Informações para diferentes perfis de usuários.</li>
<li>Conteúdo gratuito e de fácil compreensão.</li>
</ul>

<div class="destaque">
<strong>Importante:</strong> As recomendações servem como orientação e podem variar conforme a necessidade de cada usuário.
</div>

</div>

<div class="bloco">

<h2>💻 Como escolher um notebook?</h2>

<div class="cards-dicas">

<div class="card">

<h3>1. Defina a finalidade de uso</h3>

<p>
<strong></strong>
Antes da compra, identifique como o notebook será utilizado. Para estudos e navegação, modelos básicos são suficientes. Para programação, edição de imagens ou jogos, prefira configurações mais robustas com processadores modernos e maior quantidade de memória RAM.

</p>

</div>

<div class="card">

<h3>2. Analise as especificações</h3>

<p>Verifique o processador, a quantidade de memória RAM, o armazenamento (dê preferência para SSD), a resolução da tela, a duração da bateria e a quantidade de portas disponíveis. Essas características influenciam diretamente no desempenho e na experiência de uso.
</p>

</div>

<div class="card">

<h3>3. Compare antes de comprar</h3>

<p>Pesquise avaliações de outros usuários, compare preços em diferentes lojas, confira a garantia oferecida pelo fabricante e escolha um modelo que ofereça o melhor custo-benefício de acordo com suas necessidades, evitando pagar por recursos que não serão utilizados.</p>
</div>

</div>

</div>

<div class="bloco">

<h2>📝 Recomendações</h2>

<ul class="lista">
<li>Compare especificações antes de escolher um produto.</li>
<li>Verifique avaliações e comentários de outros usuários.</li>
<li>Considere o custo-benefício e não apenas o menor preço.</li>
<li>Escolha produtos compatíveis com sua necessidade.</li>
<li>Mantenha-se informado através dos nossos guias e novidades.</li>
</ul>

</div>

</div>

</body>
</html>
