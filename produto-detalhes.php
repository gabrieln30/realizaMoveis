<?php
// Configuração do banco de dados PostgreSQL
$host = 'localhost';
$dbname = 'realizaImoveis';
$user = 'postgres';
$password = 'admin';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Buscar produto
$produto_id = isset($_GET['id']) ? $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id AND status = 'ativo'");
$stmt->execute([':id' => $produto_id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header('Location: produtos.php');
    exit;
}

// Buscar produtos relacionados (mesma categoria)
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE categoria = :categoria AND id != :id AND status = 'ativo' LIMIT 4");
$stmt->execute([':categoria' => $produto['categoria'], ':id' => $produto_id]);
$relacionados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Decodificar imagens
$imagens = json_decode($produto['imagens'], true);

// Preparar mensagem WhatsApp
$whatsapp_numero = '5521979771368'; // Número sem espaços e com código do país
$preco_display = $produto['em_promocao'] ? $produto['preco_promocional'] : $produto['preco'];
$mensagem_whatsapp = urlencode(
    "Olá! Gostaria de encomendar o seguinte produto:\n\n" .
    "📦 *Produto:* " . $produto['nome'] . "\n" .
    "🏷️ *Marca:* " . $produto['marca'] . "\n" .
    "🔢 *Modelo:* " . $produto['modelo'] . "\n" .
    "📋 *Código (SKU):* " . $produto['sku'] . "\n" .
    "💰 *Preço:* R$ " . number_format($preco_display, 2, ',', '.') . "\n\n" .
    "Aguardo retorno!"
);
$link_whatsapp = "https://wa.me/{$whatsapp_numero}?text={$mensagem_whatsapp}";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produto['nome']); ?> - Realiza Móveis</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/cardsPromo.css">
    <style>
        .detalhes-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .breadcrumb {
            margin-bottom: 30px;
            font-size: 0.9em;
            color: #666;
        }

        .breadcrumb a {
            color: var(--gold);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .produto-detalhes {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 50px;
        }

        /* GALERIA DE IMAGENS */
        .galeria-imagens {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .imagem-principal {
            width: 100%;
            height: 500px;
            background: #f5f5f5;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .imagem-principal img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .miniaturas {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .miniatura {
            width: 100%;
            height: 100px;
            background: #f5f5f5;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s;
        }

        .miniatura:hover,
        .miniatura.ativa {
            border-color: var(--gold);
        }

        .miniatura img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* INFORMAÇÕES DO PRODUTO */
        .info-produto {
            display: flex;
            flex-direction: column;
        }

        .produto-categoria {
            color: var(--gold);
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .produto-nome {
            font-size: 2.5em;
            color: var(--dark);
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .produto-marca {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 20px;
        }

        .produto-badges {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.promocao {
            background: #E74C3C;
            color: white;
        }

        .badge.destaque {
            background: #FF9800;
            color: white;
        }

        .badge.categoria {
            background: var(--gold);
            color: white;
        }

        .preco-box {
            background: #f8f8f8;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .preco-original {
            text-decoration: line-through;
            color: #999;
            font-size: 1.2em;
            margin-bottom: 5px;
        }

        .preco-atual {
            font-size: 3em;
            color: var(--gold);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .preco-promocional {
            font-size: 3em;
            color: #E74C3C;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .economia {
            color: #4CAF50;
            font-size: 1em;
            font-weight: 600;
        }

        .descricao-produto {
            margin-bottom: 30px;
            line-height: 1.8;
            color: #555;
        }

        .descricao-produto h3 {
            color: var(--dark);
            margin-bottom: 15px;
        }

        .especificacoes {
            background: #f8f8f8;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .especificacoes h3 {
            color: var(--dark);
            margin-bottom: 20px;
        }

        .spec-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .spec-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .spec-label {
            font-weight: 600;
            color: var(--gold);
            font-size: 0.85em;
            text-transform: uppercase;
        }

        .spec-value {
            color: var(--dark);
            font-size: 1em;
        }

        .btn-encomendar {
            width: 100%;
            padding: 20px;
            background: #25D366;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.3em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-encomendar:hover {
            background: #1fb855;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 211, 102, 0.4);
        }

        .info-adicional {
            margin-top: 20px;
            padding: 15px;
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            border-radius: 8px;
            font-size: 0.9em;
            color: #2e7d32;
        }

        /* PRODUTOS RELACIONADOS */
        .relacionados {
            margin-top: 60px;
        }

        .relacionados h2 {
            font-size: 2em;
            color: var(--dark);
            margin-bottom: 30px;
            text-align: center;
        }

        .relacionados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        /* RESPONSIVO */
        @media (max-width: 968px) {
            .produto-detalhes {
                grid-template-columns: 1fr;
            }

            .spec-grid {
                grid-template-columns: 1fr;
            }

            .produto-nome {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="top-bar-whatsapp">
            <a href=""> <img src="assets/imgs/wBranco.svg" alt="Ícone do WhatsApp">WhatsApp +55 21 97977-1368</a>
        </div>
        <div class="top-bar-loc">
            <img src="assets/imgs/locBranco.svg" alt="Ícone de Localização">
            <a href> Estrada do Cabuçu 3448</a>
        </div>
    </div>
    
    <header>
        <img src="assets/imgs/LogoAchatada.svg" class="logo" alt="Logo Realiza Móveis">
    </header>

    <nav>
        <a href="index.php">Início</a>
        <a href="produtos.php">Produtos</a>
        <a href="#">Sofás</a>
        <a href="#">Quartos</a>
        <a href="#">Cozinha</a>
        <a href="#">Contato</a>
    </nav>

    <button class="cart-button" id="cartBtn" onclick="window.location.href='cart.html'">
        <span class="cart-button-icon">🛒 Ver Carrinho</span> 
        <span class="cart-count" id="cartCount">0</span>
    </button>

    <div class="detalhes-container">
        <!-- BREADCRUMB -->
        <div class="breadcrumb">
            <a href="index.php">Início</a> / 
            <a href="produtos.php">Produtos</a> / 
            <a href="produtos.php?categoria=<?php echo $produto['categoria']; ?>"><?php echo ucfirst($produto['categoria']); ?></a> / 
            <span><?php echo htmlspecialchars($produto['nome']); ?></span>
        </div>

        <!-- DETALHES DO PRODUTO -->
        <div class="produto-detalhes">
            <!-- GALERIA DE IMAGENS -->
            <div class="galeria-imagens">
                <div class="imagem-principal" id="imagemPrincipal">
                    <?php if ($imagens && count($imagens) > 0): ?>
                        <img src="<?php echo $imagens[0]; ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>" id="imgPrincipal">
                    <?php else: ?>
                        <div style="color: #ccc;">Sem imagem</div>
                    <?php endif; ?>
                </div>

                <?php if ($imagens && count($imagens) > 1): ?>
                <div class="miniaturas">
                    <?php foreach ($imagens as $index => $imagem): ?>
                        <div class="miniatura <?php echo $index === 0 ? 'ativa' : ''; ?>" onclick="trocarImagem('<?php echo $imagem; ?>', this)">
                            <img src="<?php echo $imagem; ?>" alt="Imagem <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- INFORMAÇÕES DO PRODUTO -->
            <div class="info-produto">
                <div class="produto-categoria"><?php echo ucfirst($produto['categoria']); ?></div>
                
                <h1 class="produto-nome"><?php echo htmlspecialchars($produto['nome']); ?></h1>
                
                <div class="produto-marca">
                    <strong>Marca:</strong> <?php echo htmlspecialchars($produto['marca']); ?> | 
                    <strong>Modelo:</strong> <?php echo htmlspecialchars($produto['modelo']); ?>
                </div>

                <div class="produto-badges">
                    <?php if ($produto['em_promocao']): ?>
                        <span class="badge promocao"> Em Promoção</span>
                    <?php endif; ?>
                    <?php if ($produto['destaque']): ?>
                        <span class="badge destaque"> Destaque</span>
                    <?php endif; ?>
                    <span class="badge categoria"><?php echo ucfirst($produto['categoria']); ?></span>
                </div>

                <!-- PREÇO -->
                <div class="preco-box">
                    <?php if ($produto['em_promocao']): ?>
                        <div class="preco-original">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></div>
                        <div class="preco-promocional">R$ <?php echo number_format($produto['preco_promocional'], 2, ',', '.'); ?></div>
                        <div class="economia">
                            💰 Economize R$ <?php echo number_format($produto['preco'] - $produto['preco_promocional'], 2, ',', '.'); ?> 
                            (<?php echo $produto['desconto_percentual']; ?>% OFF)
                        </div>
                    <?php else: ?>
                        <div class="preco-atual">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></div>
                    <?php endif; ?>
                </div>

                <!-- DESCRIÇÃO -->
                <div class="descricao-produto">
                    <h3>Descrição</h3>
                    <p><?php echo nl2br(htmlspecialchars($produto['descricao'])); ?></p>
                </div>

                <!-- ESPECIFICAÇÕES TÉCNICAS -->
                <div class="especificacoes">
                    <h3>Especificações Técnicas</h3>
                    <div class="spec-grid">
                        <div class="spec-item">
                            <span class="spec-label"> Código (SKU)</span>
                            <span class="spec-value"><?php echo htmlspecialchars($produto['sku']); ?></span>
                        </div>
                        
                        <?php if ($produto['cor']): ?>
                        <div class="spec-item">
                            <span class="spec-label"> Cor</span>
                            <span class="spec-value"><?php echo htmlspecialchars($produto['cor']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($produto['material']): ?>
                        <div class="spec-item">
                            <span class="spec-label"> Material</span>
                            <span class="spec-value"><?php echo htmlspecialchars($produto['material']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($produto['dimensoes']): ?>
                        <div class="spec-item">
                            <span class="spec-label"> Dimensões</span>
                            <span class="spec-value"><?php echo htmlspecialchars($produto['dimensoes']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($produto['peso']): ?>
                        <div class="spec-item">
                            <span class="spec-label"> Peso</span>
                            <span class="spec-value"><?php echo htmlspecialchars($produto['peso']); ?> kg</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="spec-item">
                            <span class="spec-label"> Estoque</span>
                            <span class="spec-value">
                                <?php 
                                if ($produto['estoque'] > 10) {
                                    echo "✅ Disponível";
                                } elseif ($produto['estoque'] > 0) {
                                    echo "⚠️ Últimas unidades ({$produto['estoque']} disponíveis)";
                                } else {
                                    echo "❌ Indisponível";
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- BOTÃO ENCOMENDAR VIA WHATSAPP -->
                <a href="<?php echo $link_whatsapp; ?>" target="_blank" class="btn-encomendar">
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Encomendar via WhatsApp
                </a>

                <div class="info-adicional">
                    <strong>✅ Ao clicar, você será redirecionado para o WhatsApp</strong> com uma mensagem pré-preenchida contendo:
                    <ul style="margin-top: 10px; margin-left: 20px;">
                        <li>Nome do produto</li>
                        <li>Modelo e código (SKU)</li>
                        <li>Preço atual</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- PRODUTOS RELACIONADOS -->
        <?php if (!empty($relacionados)): ?>
        <div class="relacionados">
            <h2> Produtos Relacionados</h2>
            <div class="relacionados-grid">
                <?php foreach ($relacionados as $rel): ?>
                    <div class="produto-card" onclick="window.location.href='produto-detalhes.php?id=<?php echo $rel['id']; ?>'">
                        <div class="product-badge">
                            <?php echo $rel['em_promocao'] ? 'Oferta' : htmlspecialchars($rel['categoria']); ?>
                        </div>

                        <div class="produto-imagem">
                            <?php 
                            $imagens_rel = json_decode($rel['imagens'], true);
                            if ($imagens_rel && count($imagens_rel) > 0): 
                            ?>
                                <img src="<?php echo $imagens_rel[0]; ?>" alt="<?php echo htmlspecialchars($rel['nome']); ?>">
                            <?php else: ?>
                                <div style="color: #ccc;">Sem imagem</div>
                            <?php endif; ?>
                        </div>

                        <div class="produto-conteudo">
                            <span class="product-category"><?php echo htmlspecialchars($rel['marca']); ?></span>
                            
                            <h3 class="produto-titulo"><?php echo htmlspecialchars($rel['nome']); ?></h3>

                            <div class="produto-preco-container">
                                <?php if ($rel['em_promocao']): ?>
                                    <span class="preco-atual">R$ <?php echo number_format($rel['preco_promocional'], 2, ',', '.'); ?></span>
                                    <span class="preco-original-riscado">R$ <?php echo number_format($rel['preco'], 2, ',', '.'); ?></span>
                                <?php else: ?>
                                    <span class="preco-atual">R$ <?php echo number_format($rel['preco'], 2, ',', '.'); ?></span>
                                <?php endif; ?>
                            </div>

                            <button class="btn-comprar" onclick="event.stopPropagation(); window.location.href='produto-detalhes.php?id=<?php echo $rel['id']; ?>'">
                                VER DETALHES
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
                <p>Móveis de qualidade para transformar sua casa num lar especial há mais de 10 anos.</p>
            </div>

            <div class="footer-section">
                <h3>Links Rápidos</h3>
                <ul>
                    <li><a href="produtos.php">Produtos</a></li>
                    <li><a href="index.php">Início</a></li>
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
                    <a href="https://www.instagram.com/realizasonhomoveis?igsh=YmF1NXFiaTNjeWM4&utm_source=qr" target="_blank" title="Instagram">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <circle cx="17.5" cy="6.5" r="1.5"></circle>
                        </svg>
                    </a>
                    <a href="https://wa.me/message/DGFVY3FNTHA5B1" target="_blank" title="WhatsApp">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
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
        const cartCount = document.getElementById('cartCount');

        function updateCartCount() {
            cartCount.textContent = cart.reduce((total, item) => total + item.qty, 0);
        }

        function trocarImagem(src, elemento) {
            document.getElementById('imgPrincipal').src = src;
            
            // Remove a classe 'ativa' de todas as miniaturas
            document.querySelectorAll('.miniatura').forEach(mini => {
                mini.classList.remove('ativa');
            });
            
            // Adiciona a classe 'ativa' na miniatura clicada
            elemento.classList.add('ativa');
        }

        updateCartCount();
    </script>
</body>
</html>