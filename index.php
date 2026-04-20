<?php
// ============================================
// CONEXÃO E BUSCA — topo do arquivo, antes do HTML
// ============================================
$host     = 'localhost';
$dbname       = 'realizaImoveis';
$user     = 'postgres';
$password = 'admin';


try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

try {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE destaque = true ORDER BY id DESC");
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar produtos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realiza Móveis</title>

    <link rel="stylesheet" href="assets/css/cardsPromo.css">
    
    <link rel="stylesheet" href="style.css">

    <script src="assets/js/cliqueCards.js" defer></script>
    <script src="assets/js/mostrarCards.js" defer></script>

  </head>
  <body>

   <div class="top-bar">
      <div class="top-bar-whatsapp">
        <a href=""> <img src="assets/imgs/wBranco.svg"  alt="Ícone do WhatsApp">WhatsApp +55 21 97977-1368  </a>
      </div>
      <div class="top-bar-loc">
        <img src="assets/imgs/locBranco.svg"  alt="Ícone de Localização">
        <a href> Estrada do Cabuçu 3448</a>

      </div>
    </div>
    <header>
      <img src="assets/imgs/LogoAchatada.svg"  class="logo" alt="Logo Realiza Móveis">
    </header>

    <nav>
      <a href="index.php">Início</a>
      <a href="produtos.php">Produtos</a>
      <a href="#">Contato</a>
    </nav>

    <button class="cart-button" id="cartBtn"
      onclick="window.location.href='cart.html'">
      <span class="cart-button-icon">🛒 Ver Carrinho</span> <span
        class="cart-count" id="cartCount">0</span>
    </button>

    <div class="banner">
      Qualidade e Sofisticação para sua Casa
    </div>


    <?php if (empty($produtos)): ?>
        <div class="sem-produtos">
            <h3>🔍 Nenhum produto encontrado</h3>
            <p>Tente ajustar os filtros de busca</p>
        </div>
    <?php else: ?>
        <div class="produtos-grid">
            <?php foreach ($produtos as $produto): ?>
                <div class="produto-card" onclick="verProduto(<?php echo $produto['id']; ?>)">

                    <div class="product-badge">
                        <?php echo $produto['em_promocao'] ? 'Oferta' : htmlspecialchars($produto['categoria']); ?>
                    </div>

                    <div class="produto-imagem">
                        <?php
                        $imagens = json_decode($produto['imagens'], true);
                        if ($imagens && count($imagens) > 0):
                        ?>
                            <img src="<?php echo $imagens[0]; ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                        <?php else: ?>
                            <div style="color: #ccc;">Sem imagem</div>
                        <?php endif; ?>
                    </div>

                    <div class="produto-conteudo">
                        <span class="product-category"><?php echo htmlspecialchars($produto['marca']); ?></span>

                        <h3 class="produto-titulo"><?php echo htmlspecialchars($produto['nome']); ?></h3>

                        <p class="produto-descricao">
                            <?php echo mb_strimwidth(htmlspecialchars($produto['descricao']), 0, 100, "..."); ?>
                        </p>

                        <div class="produto-preco-container">
                            <?php if ($produto['em_promocao']): ?>
                                <span class="preco-atual">R$ <?php echo number_format($produto['preco_promocional'], 2, ',', '.'); ?></span>
                                <span class="preco-original-riscado">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
                            <?php else: ?>
                                <span class="preco-atual">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
                            <?php endif; ?>
                        </div>

                        <button class="btn-comprar" onclick="event.stopPropagation(); comprar(<?php echo $produto['id']; ?>)">
                            ADICIONAR AO CARRINHO
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    

    <!-- Cards de Sessões -->
    <div class="sessoes-general">
      <div class="sessoes-header">
        <h2>Explore o Máximo da Qualidade da Nossa Loja</h2>
      </div>
        <div class="sessoes-container">
        
              <div class="sessao-card" id="guardaRoupaCard">
                <img src="assets/imgs/guardaRoupa.webp">
                <div class="sessao-card-text">
                  <h3>Guarda-Roupa</h3>
                </div>
              </div>

              <div class="sessao-card" id="mesaEstarCard">
                <img src="assets/imgs/mesaEstar.png">
                <div class="sessao-card-text">
                  <h3>Mesa de Estar</h3>
                </div>
              </div>

              <div class="sessao-card" id="sofaCard">
                <img src="assets\imgs\sofa.webp">
                <div class="sessao-card-text">
                  <h3>Sofas</h3>
                </div>
              </div>

              <div class="sessao-card">
                <img src="assets/imgs/armario.webp">
                <div class="sessao-card-text">
                  <h3>Armários</h3>
                </div>
              </div>

              <div class="sessao-card">
                <img src="assets/imgs/poltronas.webp">
                <div class="sessao-card-text">
                  <h3>Poltronas</h3>
                </div>
              </div>    

          
                
              <div class="sessao-card hidden">
                <img src="assets/imgs/cadeiras.webp">
                <div class="sessao-card-text">
                <h3>Cadeira</h3>
                </div>
              </div>

              <div class="sessao-card hidden">
                <img src="assets/imgs/rack-estante.webp">
                <div class="sessao-card-text">
                  <h3>Rack / Estante</h3>
                </div>
              </div>


              <div class="sessao-card hidden">
                <img src="assets/imgs/comoda.webp">
                <div class="sessao-card-text">
                  <h3>Cômoda</h3>
                </div>
              </div>


              <div class="sessao-card hidden">
                <img src="assets/imgs/escrivaninha.webp">
                <div class="sessao-card-text">
                  <h3>Escrivaninha</h3>
                </div>
              </div>
      
      
          
        </div>
        <div>
          <button class="ver-mais-btn" onclick="mostrarCards()" id="btnVerMais">Ver Mais</button>
          <button class="ver-menos-btn hidden" onclick="mostrarCards()" id="btnVerMenos">Ver Menos</button>
        </div>
    </div>




    <footer>
      <div class="footer-container">
        <div class="footer-section">
          <div class="footer-logo">
            <div class="footer-logo-icon">R</div>
            <div class="footer-logo-text">
              <strong>Realiza</strong>
              <span>Móveis</span>
            </div>
          </div>
          <p>Móveis de qualidade para transformar sua casa num lar especial há
            mais de 10 anos.</p>
        </div>

        <div class="footer-section">
          <h3>Links Rápidos</h3>
          <ul>
            <li><a href="#">Produtos</a></li>
            <li><a href="#">Sala de Estar</a></li>
            <li><a href="#">Sobre Nós</a></li>
          </ul>
        </div>

        <div class="footer-section">
          <h3>Contato</h3>
          <div class="footer-contact">
            <span>📍</span>
            <div>
              <div>Estrada do Cabuçu 3448</div>
            </div>
          </div>
          <div class="footer-contact">
            <span>📞</span>
            <div>(21) 97977-1368</div>
          </div>
          <div class="footer-contact">
            <span>✉️</span>
            <div>contato@realizamoveis.com.br</div>
          </div>
        </div>

        <div class="footer-section">
          <h3>Redes Sociais</h3>
          <div class="social-links">
            <a
              href="https://www.instagram.com/realizasonhomoveis?igsh=YmF1NXFiaTNjeWM4&utm_source=qr"
              target="_blank" title="Instagram">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                <path
                  d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                <circle cx="17.5" cy="6.5" r="1.5"></circle>
              </svg>
            </a>
            <a href="https://wa.me/message/DGFVY3FNTHA5B1" target="_blank"
              title="WhatsApp">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path
                  d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </a>
          </div>
        </div>
      </div>

      <div class="footer-copyright">
        © 2026 Realiza Móveis. Todos os direitos reservados.
      </div>
    </footer>

    <script>
let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Elementos do DOM
const cartCount = document.getElementById('cartCount');
const buyButtons = document.querySelectorAll('.buy-btn');

// Adicionar ao carrinho
buyButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    const name = btn.dataset.name;
    const price = parseInt(btn.dataset.price);
    
    const item = cart.find(item => item.name === name);
    
    if (item) {
      item.qty += 1;
    } else {
      cart.push({ name, price, qty: 1 });
    }
    
    saveCart();
    updateCartCount();
  });
});

// Salvar carrinho no localStorage
function saveCart() {
  localStorage.setItem('cart', JSON.stringify(cart));
}

// Atualizar contador do carrinho
function updateCartCount() {
  cartCount.textContent = cart.reduce((total, item) => total + item.qty, 0);
}

// Inicializar contador
updateCartCount();
</script>

  </body>
</html>
