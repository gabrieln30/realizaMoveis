<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realiza Móveis</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/cardsPromo.css">
    <script src="assets/js/cliqueCards.js" defer></script>
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
      <a href="#">Sofás</a>
      <a href="#">Quartos</a>
      <a href="#">Cozinha</a>
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

        <!--<div class="sessao-card">
          <img src="/assets/imgs/guardaRoupa.webp">
          <div class="sessao-card-text">
            <h3>Guarda-Roupa</h3>
          </div>
        </div>

        <div class="sessao-card">
          <img src="/assets/imgs/mesaEstar.png">
          <div class="sessao-card-text">
            <h3>Mesa de Estar</h3>
          </div>
        </div>

        <div class="sessao-card">
          <img src="/assets/imgs/sofa.webp">
          <div class="sessao-card-text">
            <h3>Sofas</h3>
          </div>
        </div>-->

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
