<?php
session_start();

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

// Processar formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cadastrar') {
        try {
            // Upload de imagens
            $imagens = [];
            if (isset($_FILES['imagens'])) {
                $upload_dir = 'uploads/produtos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach ($_FILES['imagens']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['imagens']['error'][$key] === 0) {
                        $filename = uniqid() . '_' . $_FILES['imagens']['name'][$key];
                        $filepath = $upload_dir . $filename;
                        if (move_uploaded_file($tmp_name, $filepath)) {
                            $imagens[] = $filepath;
                        }
                    }
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO produtos (
                    nome, descricao, categoria, marca, modelo,
                    cor, material, dimensoes, peso,
                    preco, preco_promocional, em_promocao, desconto_percentual,
                    estoque, estoque_minimo, sku,
                    imagens, status, destaque, data_cadastro
                ) VALUES (
                    :nome, :descricao, :categoria, :marca, :modelo,
                    :cor, :material, :dimensoes, :peso,
                    :preco, :preco_promocional, :em_promocao, :desconto_percentual,
                    :estoque, :estoque_minimo, :sku,
                    :imagens, :status, :destaque, NOW()
                )
            ");
            
            $preco = floatval($_POST['preco']);
            $em_promocao = isset($_POST['em_promocao']) ? 1 : 0;
            $desconto = floatval($_POST['desconto_percentual']);
            $preco_promocional = $em_promocao ? $preco * (1 - $desconto/100) : null;
            $destaque = isset($_POST['destaque']) ? 1 : 0;
            
            $stmt->execute([
                ':nome' => $_POST['nome'],
                ':descricao' => $_POST['descricao'],
                ':categoria' => $_POST['categoria'],
                ':marca' => $_POST['marca'],
                ':modelo' => $_POST['modelo'],
                ':cor' => $_POST['cor'],
                ':material' => $_POST['material'],
                ':dimensoes' => $_POST['dimensoes'],
                ':peso' => $_POST['peso'],
                ':preco' => $preco,
                ':preco_promocional' => $preco_promocional,
                ':em_promocao' => $em_promocao,
                ':desconto_percentual' => $desconto,
                ':estoque' => $_POST['estoque'],
                ':estoque_minimo' => $_POST['estoque_minimo'],
                ':sku' => $_POST['sku'],
                ':imagens' => json_encode($imagens),
                ':status' => $_POST['status'],
                ':destaque' => $destaque
            ]);
            
            $mensagem = "Produto cadastrado com sucesso!";
            $tipo_mensagem = "sucesso";
        } catch(PDOException $e) {
            $mensagem = "Erro ao cadastrar: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
}

// Buscar produtos cadastrados
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY data_cadastro DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Produtos</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/cardsPromo.css">  
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --dourado: #D4AF37;
            --dourado-claro: #C5A059;
            --dourado-escuro: #856424;
            --preto: #000000;
            --branco: #FFFFFF;
            --cinza-claro: #F5F5F5;
            --cinza: #E0E0E0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #F5F5F5 0%, #E8E8E8 100%);
            color: var(--preto);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, var(--dourado-escuro) 0%, var(--dourado) 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        header h1 {
            color: var(--branco);
            font-size: 2.5em;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        header p {
            color: var(--branco);
            text-align: center;
            margin-top: 10px;
            font-size: 1.1em;
            opacity: 0.95;
        }

        .mensagem {
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
            animation: slideDown 0.5s ease;
        }

        .mensagem.sucesso {
            background: #4CAF50;
            color: white;
        }

        .mensagem.erro {
            background: #F44336;
            color: white;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            margin-bottom: 40px;
            border-top: 4px solid var(--dourado);
        }

        .form-container h2 {
            color: var(--dourado-escuro);
            margin-bottom: 30px;
            font-size: 1.8em;
            border-bottom: 2px solid var(--dourado);
            padding-bottom: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            color: var(--preto);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"],
        select,
        textarea {
            padding: 12px 15px;
            border: 2px solid var(--cinza);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--dourado);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--dourado);
        }

        .section-divider {
            background: linear-gradient(to right, var(--dourado-claro), var(--dourado), var(--dourado-claro));
            height: 3px;
            margin: 30px 0;
            border-radius: 2px;
        }

        .preco-section {
            background: var(--cinza-claro);
            padding: 20px;
            border-radius: 10px;
            border: 2px dashed var(--dourado);
        }

        .preco-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .preco-display {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid var(--dourado);
        }

        .preco-display label {
            font-size: 0.85em;
            color: var(--dourado-escuro);
        }

        .preco-display .valor {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--preto);
            margin-top: 5px;
        }

        .preco-display.promocao .valor {
            color: #E74C3C;
        }

        .estoque-section {
            background: #E8F5E9;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #4CAF50;
        }

        .btn {
            padding: 15px 35px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--dourado) 0%, var(--dourado-claro) 100%);
            color: var(--preto);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dourado-escuro) 0%, var(--dourado) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .produtos-lista {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }

        .produtos-lista h2 {
            color: var(--dourado-escuro);
            margin-bottom: 25px;
            font-size: 1.8em;
            border-bottom: 2px solid var(--dourado);
            padding-bottom: 10px;
        }

        .produto-card {
            background: var(--cinza-claro);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid var(--dourado);
            transition: all 0.3s ease;
        }

        .produto-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .produto-card h3 {
            color: var(--dourado-escuro);
            margin-bottom: 10px;
        }

        .produto-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .info-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9em;
        }

        .info-item strong {
            color: var(--dourado-escuro);
        }

        .badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-left: 10px;
        }

        .badge.promocao {
            background: #E74C3C;
            color: white;
        }

        .badge.destaque {
            background: #FF9800;
            color: white;
        }

        .badge.ativo {
            background: #4CAF50;
            color: white;
        }

        .badge.inativo {
            background: #9E9E9E;
            color: white;
        }

        .badge.estoque-baixo {
            background: #FF5722;
            color: white;
        }

        .badge.estoque-ok {
            background: #4CAF50;
            color: white;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            header h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
        <?php if (isset($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Cadastrar Novo Produto</h2>
            <form method="POST" enctype="multipart/form-data" id="formProduto">
                <input type="hidden" name="action" value="cadastrar">
                
                <!-- INFORMAÇÕES BÁSICAS -->
                <h3 style="color: var(--dourado-escuro); margin-bottom: 15px;">Informações Básicas</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="nome">Nome do Produto *</label>
                        <input type="text" id="nome" name="nome" required 
                               placeholder="Ex: Sofá Retrátil Premium 3 Lugares">
                    </div>

                    <div class="form-group full-width">
                        <label for="descricao">Descrição Completa *</label>
                        <textarea id="descricao" name="descricao" required 
                                  placeholder="Descreva todos os detalhes, características e diferenciais do produto..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="categoria">Categoria *</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Selecione...</option>
                            <option value="sofa">Sofá</option>
                            <option value="poltrona">Poltrona</option>
                            <option value="mesa">Mesa</option>
                            <option value="cadeira">Cadeira</option>
                            <option value="armario">Armário</option>
                            <option value="guarda-roupa">Guarda-Roupa</option>
                            <option value="cama">Cama</option>
                            <option value="rack">Rack/Estante</option>
                            <option value="aparador">Aparador</option>
                            <option value="comoda">Cômoda</option>
                            <option value="decoracao">Decoração</option>
                            <option value="iluminacao">Iluminação</option>
                            <option value="tapete">Tapete</option>
                            <option value="mesa-centro">Mesa de Centro</option>
                            <option value="escrivaninha">Escrivaninha</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="marca">Marca *</label>
                        <input type="text" id="marca" name="marca" required 
                               placeholder="Ex: Tok&Stok, Etna, etc">
                    </div>

                    <div class="form-group">
                        <label for="modelo">Modelo</label>
                        <input type="text" id="modelo" name="modelo" 
                               placeholder="Ex: Milano 2024">
                    </div>

                    <div class="form-group">
                        <label for="sku">SKU/Código *</label>
                        <input type="text" id="sku" name="sku" required 
                               placeholder="Ex: SOF-001-BLUE">
                    </div>
                </div>

                <div class="section-divider"></div>

                <!-- CARACTERÍSTICAS -->
                <h3 style="color: var(--dourado-escuro); margin-bottom: 15px;">Características</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="cor">Cor *</label>
                        <input type="text" id="cor" name="cor" required 
                               placeholder="Ex: Azul Marinho, Cinza, Bege">
                    </div>

                    <div class="form-group">
                        <label for="material">Material *</label>
                        <input type="text" id="material" name="material" required 
                               placeholder="Ex: Veludo, Couro, MDF, Madeira Maciça">
                    </div>

                    <div class="form-group">
                        <label for="dimensoes">Dimensões (LxAxP) *</label>
                        <input type="text" id="dimensoes" name="dimensoes" required 
                               placeholder="Ex: 200x85x95 cm">
                    </div>

                    <div class="form-group">
                        <label for="peso">Peso (kg)</label>
                        <input type="number" id="peso" name="peso" step="0.01" 
                               placeholder="Ex: 45.5">
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                            <option value="esgotado">Esgotado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="destaque" name="destaque">
                            <label for="destaque" style="margin: 0;">⭐ Produto em Destaque</label>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="imagens">Imagens do Produto (Múltiplas)</label>
                        <input type="file" id="imagens" name="imagens[]" multiple accept="image/*">
                    </div>
                </div>

                <div class="section-divider"></div>

                <!-- ESTOQUE -->
                <div class="estoque-section">
                    <h3 style="color: #2E7D32; margin-bottom: 15px;">Controle de Estoque</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="estoque">Quantidade em Estoque *</label>
                            <input type="number" id="estoque" name="estoque" required min="0" 
                                   placeholder="Ex: 25">
                        </div>

                        <div class="form-group">
                            <label for="estoque_minimo">Estoque Mínimo (Alerta) *</label>
                            <input type="number" id="estoque_minimo" name="estoque_minimo" required min="0" 
                                   placeholder="Ex: 5">
                        </div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <!-- PRECIFICAÇÃO -->
                <div class="preco-section">
                    <h3 style="color: var(--dourado-escuro); margin-bottom: 15px;">Precificação</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="preco">Preço Original (R$) *</label>
                            <input type="number" id="preco" name="preco" required step="0.01" 
                                   placeholder="0.00" onchange="calcularPromocao()">
                        </div>

                        <div class="form-group">
                            <label for="desconto_percentual">Desconto (%) </label>
                            <input type="number" id="desconto_percentual" name="desconto_percentual" 
                                   step="0.01" min="0" max="100" value="0" onchange="calcularPromocao()">
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="em_promocao" name="em_promocao" 
                                       onchange="calcularPromocao()">
                                <label for="em_promocao" style="margin: 0;">🔥 Ativar Promoção</label>
                            </div>
                        </div>
                    </div>

                    <div class="preco-info">
                        <div class="preco-display">
                            <label>Preço Original</label>
                            <div class="valor" id="preco_original_display">R$ 0,00</div>
                        </div>
                        <div class="preco-display">
                            <label>Desconto</label>
                            <div class="valor" id="desconto_display">0%</div>
                        </div>
                        <div class="preco-display">
                            <label>Economia</label>
                            <div class="valor" id="economia_display">R$ 0,00</div>
                        </div>
                        <div class="preco-display promocao">
                            <label>Preço Promocional</label>
                            <div class="valor" id="preco_promocional_display">R$ 0,00</div>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        Cadastrar Produto
                    </button>
                </div>
            </form>
        </div>

        <div class="produtos-lista">
            <h2> Produtos Cadastrados (<?php echo count($produtos); ?>)</h2>
            <?php if (empty($produtos)): ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    Nenhum produto cadastrado ainda.
                </p>
            <?php else: ?>
                <?php foreach ($produtos as $produto): ?>
                    <div class="produto-card">
                        <h3>
                            <?php echo htmlspecialchars($produto['nome']); ?>
                            
                            <?php if ($produto['destaque']): ?>
                                <span class="badge destaque">⭐ DESTAQUE</span>
                            <?php endif; ?>
                            
                            <?php if ($produto['em_promocao']): ?>
                                <span class="badge promocao">🔥 PROMOÇÃO</span>
                            <?php endif; ?>
                            
                            <span class="badge <?php echo $produto['status']; ?>">
                                <?php echo strtoupper($produto['status']); ?>
                            </span>
                            
                            <?php 
                            $estoque_baixo = $produto['estoque'] <= $produto['estoque_minimo'];
                            ?>
                            <span class="badge <?php echo $estoque_baixo ? 'estoque-baixo' : 'estoque-ok'; ?>">
                                📦 Estoque: <?php echo $produto['estoque']; ?>
                            </span>
                        </h3>
                        
                        <div class="produto-info">
                            <div class="info-item">
                                <strong>Categoria:</strong> <?php echo ucfirst($produto['categoria']); ?>
                            </div>
                            <div class="info-item">
                                <strong>Marca:</strong> <?php echo htmlspecialchars($produto['marca']); ?>
                            </div>
                            <div class="info-item">
                                <strong>SKU:</strong> <?php echo htmlspecialchars($produto['sku']); ?>
                            </div>
                            <div class="info-item">
                                <strong>Cor:</strong> <?php echo htmlspecialchars($produto['cor']); ?>
                            </div>
                            <div class="info-item">
                                <strong>Material:</strong> <?php echo htmlspecialchars($produto['material']); ?>
                            </div>
                            <div class="info-item">
                                <strong>Dimensões:</strong> <?php echo htmlspecialchars($produto['dimensoes']); ?>
                            </div>
                            <div class="info-item">
                                <strong>Preço:</strong> 
                                <?php if ($produto['em_promocao']): ?>
                                    <span style="text-decoration: line-through; color: #999;">
                                        R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                    </span>
                                    <br>
                                    <span style="color: #E74C3C; font-weight: bold;">
                                        R$ <?php echo number_format($produto['preco_promocional'], 2, ',', '.'); ?>
                                    </span>
                                    <br>
                                    <small style="color: #4CAF50;">
                                        Economize R$ <?php echo number_format($produto['preco'] - $produto['preco_promocional'], 2, ',', '.'); ?>
                                    </small>
                                <?php else: ?>
                                    R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="info-item">
                                <strong>Cadastrado em:</strong> 
                                <?php echo date('d/m/Y H:i', strtotime($produto['data_cadastro'])); ?>
                            </div>
                        </div>
                      
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function calcularPromocao() {
            const preco = parseFloat(document.getElementById('preco').value) || 0;
            const desconto = parseFloat(document.getElementById('desconto_percentual').value) || 0;
            const emPromocao = document.getElementById('em_promocao').checked;
            
            const precoPromocional = preco * (1 - desconto/100);
            const economia = preco - precoPromocional;
            
            document.getElementById('preco_original_display').textContent = 
                'R$ ' + preco.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            document.getElementById('desconto_display').textContent = desconto + '%';
            
            document.getElementById('economia_display').textContent = 
                'R$ ' + economia.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            document.getElementById('preco_promocional_display').textContent = 
                'R$ ' + precoPromocional.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            // Destacar se estiver em promoção
            const promocaoDisplay = document.querySelector('.preco-display.promocao');
            if (emPromocao && desconto > 0) {
                promocaoDisplay.style.borderLeft = '4px solid #E74C3C';
                promocaoDisplay.style.background = '#FFEBEE';
            } else {
                promocaoDisplay.style.borderLeft = '4px solid var(--dourado)';
                promocaoDisplay.style.background = 'white';
            }
        }
        
        // Atualizar preview de preços ao carregar
        window.addEventListener('load', calcularPromocao);
    </script>
</body>
</html>