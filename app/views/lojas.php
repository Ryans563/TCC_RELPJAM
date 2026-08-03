<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>relpjam — Lojas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --gray-900:#0F172A;
    --accent:#F97316;
    --secondary:#0F172A;

    --panel:#16213A;
    --panel-2:#1C2942;
    --border:#2A3B5C;
    --text:#F8FAFC;
    --muted:#93A2C3;
    --accent-dim: rgba(249,115,22,.14);
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html,body{height:100%;}
  body{
    background:
      radial-gradient(1100px 500px at 85% -10%, rgba(249,115,22,.14), transparent 60%),
      var(--gray-900);
    color:var(--text);
    font-family:'Inter', sans-serif;
    min-height:100vh;
    -webkit-font-smoothing:antialiased;
  }
  h1,h2,h3, .display{ font-family:'Space Grotesk', sans-serif; letter-spacing:-0.01em; }

  /* ---------- layout shell ---------- */
  .shell{ max-width:1120px; margin:0 auto; padding:0 24px 80px; }

  header.top{
    display:flex; align-items:center; justify-content:space-between;
    padding:26px 24px; max-width:1120px; margin:0 auto;
    border-bottom:1px solid var(--border);
  }
  .logo{ display:flex; align-items:center; gap:10px; }
  .logo .mark{
    width:34px; height:34px; border-radius:9px;
    background:var(--accent);
    display:flex; align-items:center; justify-content:center;
    font-family:'Space Grotesk',sans-serif; font-weight:700; color:var(--gray-900);
    transform:rotate(-6deg);
  }
  .logo .word{ font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:20px; }
  .logo .word span{ color:var(--accent); }

  nav.tabs{
    display:flex; gap:6px; background:var(--panel); border:1px solid var(--border);
    border-radius:999px; padding:4px;
  }
  nav.tabs button{
    border:none; background:transparent; color:var(--muted);
    font-family:'Inter',sans-serif; font-weight:600; font-size:14px;
    padding:9px 18px; border-radius:999px; cursor:pointer;
    transition:.2s;
  }
  nav.tabs button.active{ background:var(--accent); color:var(--gray-900); }
  nav.tabs button:not(.active):hover{ color:var(--text); }

  .screen{ display:none; }
  .screen.active{ display:block; }

  /* ---------- hero shared ---------- */
  .hero{ padding:64px 0 40px; border-bottom:1px solid var(--border); margin-bottom:48px; position:relative; }
  .eyebrow{
    display:inline-flex; align-items:center; gap:8px;
    font-size:12px; font-weight:600; letter-spacing:.14em; text-transform:uppercase;
    color:var(--accent); margin-bottom:18px;
  }
  .eyebrow::before{ content:''; width:16px; height:2px; background:var(--accent); display:inline-block; }
  .hero h1{ font-size:clamp(32px,5vw,50px); font-weight:700; line-height:1.06; max-width:640px; }
  .hero p.lead{ margin-top:16px; color:var(--muted); font-size:16px; max-width:520px; line-height:1.6; }

  /* ---------- LOJAS ---------- */
  .toolbar{ display:flex; gap:12px; flex-wrap:wrap; margin-bottom:28px; align-items:center; justify-content:space-between; }
  .search-box{ position:relative; flex:1; min-width:220px; max-width:360px; }
  .search-box input{
    width:100%; background:var(--panel); border:1px solid var(--border); border-radius:10px;
    padding:11px 14px 11px 38px; color:var(--text); font-size:14px;
  }
  .search-box input:focus{ outline:2px solid var(--accent); outline-offset:1px; }
  .search-box svg{ position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); }

  .chips{ display:flex; gap:8px; flex-wrap:wrap; }
  .chip{
    background:var(--panel); border:1px solid var(--border); color:var(--muted);
    font-size:13px; font-weight:600; padding:8px 15px; border-radius:999px; cursor:pointer; transition:.15s;
  }
  .chip.active{ background:var(--accent); color:var(--gray-900); border-color:var(--accent); }
  .chip:not(.active):hover{ color:var(--text); border-color:var(--muted); }

  .store-grid{ display:grid; grid-template-columns:repeat(3, 1fr); gap:18px; }
  @media (max-width:860px){ .store-grid{ grid-template-columns:repeat(2,1fr); } }
  @media (max-width:560px){ .store-grid{ grid-template-columns:1fr; } }

  .store-card{
    background:var(--panel); border:1px solid var(--border); border-radius:14px;
    padding:20px; position:relative; transition:.2s transform, .2s border-color;
  }
  .store-card:hover{ transform:translateY(-3px) rotate(-.4deg); border-color:var(--accent); }
  .store-card::before{
    content:''; position:absolute; top:16px; right:16px; width:9px; height:9px;
    border-radius:50%; background:var(--gray-900); border:1px solid var(--border);
  }
  .store-top{ display:flex; gap:12px; align-items:center; margin-bottom:14px; }
  .store-badge{
    width:46px; height:46px; border-radius:10px; background:var(--accent-dim); color:var(--accent);
    display:flex; align-items:center; justify-content:center; font-family:'Space Grotesk',sans-serif;
    font-weight:700; font-size:17px; flex-shrink:0;
  }
  .store-name{ font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:16px; }
  .store-city{ font-size:12px; color:var(--muted); }
  .store-desc{ font-size:13.5px; color:var(--muted); line-height:1.55; margin-bottom:16px; min-height:40px; }
  .store-tag{
    display:inline-block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    color:var(--accent); background:var(--accent-dim); padding:4px 10px; border-radius:999px; margin-bottom:14px;
  }
  .store-link{
    display:flex; align-items:center; justify-content:space-between; font-size:13px; font-weight:600;
    color:var(--text); text-decoration:none; padding-top:12px; border-top:1px dashed var(--border);
  }
  .store-link:hover{ color:var(--accent); }

  .empty, .loading, .error-box{
    grid-column:1/-1; text-align:center; padding:60px 20px; color:var(--muted);
    border:1px dashed var(--border); border-radius:14px;
  }
  .error-box{ color:#F87171; }
  .note{ font-size:12px; color:var(--muted); margin-top:18px; text-align:center; }
  .note code{ background:var(--panel-2); padding:2px 6px; border-radius:5px; color:var(--accent); }

  footer{ text-align:center; padding:40px 24px; color:var(--muted); font-size:13px; border-top:1px solid var(--border); }
  footer .accent{ color:var(--accent); }
</style>
</head>
<body>

<header class="top">
  <div class="logo">
    <a href="/TCC_RELPJAM/app/views/home.php" class="logo">
              <img
        src="<?= $base ?>/TCC_RELPJAM/public/images/logotop.png"
        alt="Logo "
        style="height:120px;width:auto;display:block;object-fit:contain;"
    >
         </a>
  </div>
  <nav class="tabs">
    <button class="tab-btn" data-tab="lojas">Lojas</button>
    
  </nav>
</header>

<div class="shell">
  <!-- ===================== TELA: LOJAS ===================== -->
  <section id="screen-lojas" class="active screen">
    <div class="hero">
      <span class="eyebrow">Vitrine relpjam</span>
      <h1>Lojas disponíveis</h1>
      <p class="lead">Conheça as marcas parceiras cadastradas no marketplace. Busque por nome ou filtre por categoria.</p>
    </div>

    <div class="toolbar">
      <div class="search-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input id="search-input" type="text" placeholder="Buscar loja...">
      </div>
      <div class="chips" id="category-chips">
        <button class="chip active" data-cat="todas">Todas</button>
      </div>
    </div>

    <div class="store-grid" id="store-grid">
      <div class="loading">Carregando lojas…</div>
    </div>

    <p class="note">Dados carregados da tabela <code>lojas</code> no Supabase. Configure <code>SUPABASE_URL</code> e <code>SUPABASE_ANON_KEY</code> no código para conectar ao seu projeto.</p>
  </section>

</div>

<footer>
  relpjam marketplace — <a class="accent" href="mailto:relpjamtcc@gmail.com" style="color:inherit; text-decoration:none;">relpjamtcc@gmail.com</a> · <span class="accent">@relpjammarketplace_oficial</span>
</footer>

<script>
/* ============================================================
   CONFIGURAÇÃO SUPABASE
   Substitua pelos dados do seu projeto (Project Settings > API)
   ============================================================ */
const SUPABASE_URL = "https://SEU-PROJETO.supabase.co";
const SUPABASE_ANON_KEY = "SUA_ANON_KEY_AQUI";

const isSupabaseConfigured = !SUPABASE_URL.includes("SEU-PROJETO");

async function supabaseRequest(path, options = {}) {
  const res = await fetch(`${SUPABASE_URL}/rest/v1/${path}`, {
    ...options,
    headers: {
      "apikey": SUPABASE_ANON_KEY,
      "Authorization": `Bearer ${SUPABASE_ANON_KEY}`,
      "Content-Type": "application/json",
      ...(options.headers || {})
    }
  });
  if (!res.ok) throw new Error(`Supabase error: ${res.status}`);
  return res.status === 204 ? null : res.json();
}



/* ---------------- Lojas: dados de exemplo (fallback) ---------------- */
const MOCK_STORES = [
  { nome:"Nortada Streetwear", categoria:"Streetwear", cidade:"São Paulo, SP", descricao:"Peças urbanas em edição limitada, do skate ao dia a dia.", website:"#" },
  { nome:"Alma Alfaiataria", categoria:"Social", cidade:"Curitiba, PR", descricao:"Alfaiataria contemporânea com corte sob medida.", website:"#" },
  { nome:"Bruma Denim", categoria:"Jeanswear", cidade:"Fortaleza, CE", descricao:"Jeans autoral, produção artesanal e lavagens exclusivas.", website:"#" },
  { nome:"Cerrado Basics", categoria:"Básicos", cidade:"Goiânia, GO", descricao:"Essenciais de algodão orgânico, caimento perfeito.", website:"#" },
  { nome:"Marear Praia", categoria:"Praia", cidade:"Florianópolis, SC", descricao:"Moda praia leve inspirada no litoral catarinense.", website:"#" },
  { nome:"Vento Norte Kids", categoria:"Infantil", cidade:"Belém, PA", descricao:"Roupinhas confortáveis para os pequenos exploradores.", website:"#" }
];

let allStores = [];
let activeCategory = "todas";
let searchTerm = "";

async function loadStores() {
  const grid = document.getElementById("store-grid");
  grid.innerHTML = '<div class="loading">Carregando lojas…</div>';

  try {
    if (!isSupabaseConfigured) throw new Error("not_configured");
    const data = await supabaseRequest("lojas?select=*&order=nome.asc");
    allStores = data || [];
    if (allStores.length === 0) throw new Error("empty");
  } catch (err) {
    allStores = MOCK_STORES;
  }

  buildCategoryChips();
  renderStores();
}

function buildCategoryChips() {
  const wrap = document.getElementById("category-chips");
  const cats = ["todas", ...new Set(allStores.map(s => s.categoria).filter(Boolean))];
  wrap.innerHTML = cats.map(c =>
    `<button class="chip ${c === activeCategory ? "active" : ""}" data-cat="${c}">${c === "todas" ? "Todas" : c}</button>`
  ).join("");

  wrap.querySelectorAll(".chip").forEach(chip => {
    chip.addEventListener("click", () => {
      activeCategory = chip.dataset.cat;
      wrap.querySelectorAll(".chip").forEach(c => c.classList.remove("active"));
      chip.classList.add("active");
      renderStores();
    });
  });
}

function renderStores() {
  const grid = document.getElementById("store-grid");
  let filtered = allStores.filter(s => {
    const matchCat = activeCategory === "todas" || s.categoria === activeCategory;
    const matchSearch = !searchTerm || (s.nome || "").toLowerCase().includes(searchTerm);
    return matchCat && matchSearch;
  });

  if (filtered.length === 0) {
    grid.innerHTML = '<div class="empty">Nenhuma loja encontrada com esse filtro.</div>';
    return;
  }

  grid.innerHTML = filtered.map(s => `
    <div class="store-card">
      <div class="store-top">
        <div class="store-badge">${(s.nome || "?").trim().charAt(0).toUpperCase()}</div>
        <div>
          <div class="store-name">${s.nome || "Loja sem nome"}</div>
          <div class="store-city">${s.cidade || ""}</div>
        </div>
      </div>
      ${s.categoria ? `<span class="store-tag">${s.categoria}</span>` : ""}
      <div class="store-desc">${s.descricao || "Sem descrição cadastrada."}</div>
      <a class="store-link" href="${s.website || "#"}" target="_blank" rel="noopener">
        Ver loja
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7M7 7h10v10"/></svg>
      </a>
    </div>
  `).join("");
}

document.getElementById("search-input").addEventListener("input", (e) => {
  searchTerm = e.target.value.trim().toLowerCase();
  renderStores();
});

loadStores();
</script>

</body>
</html>