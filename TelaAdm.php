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
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

$mensagem = '';
$tipo_mensagem = '';

// Processar ações (cadastrar, editar, excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'cadastrar' || $_POST['action'] === 'editar') {
        try {
            // Upload de imagens
            $imagens = [];
            
            // Se for edição, manter imagens existentes
            if ($_POST['action'] === 'editar' && isset($_POST['imagens_existentes'])) {
                $imagens = json_decode($_POST['imagens_existentes'], true);
            }
            
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

            $preco = floatval($_POST['preco']);
            $em_promocao = isset($_POST['em_promocao']) ? 1 : 0;
            $desconto = floatval($_POST['desconto_percentual']);
            $preco_promocional = $em_promocao ? $preco * (1 - $desconto / 100) : null;
            $destaque = isset($_POST['destaque']) ? 1 : 0;

            if ($_POST['action'] === 'cadastrar') {
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
                $mensagem = "Produto cadastrado com sucesso!";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE produtos SET
                        nome = :nome, descricao = :descricao, categoria = :categoria, 
                        marca = :marca, modelo = :modelo, cor = :cor, material = :material,
                        dimensoes = :dimensoes, peso = :peso, preco = :preco,
                        preco_promocional = :preco_promocional, em_promocao = :em_promocao,
                        desconto_percentual = :desconto_percentual, estoque = :estoque,
                        estoque_minimo = :estoque_minimo, sku = :sku,
                        imagens = :imagens, status = :status, destaque = :destaque
                    WHERE id = :id
                ");
                $mensagem = "Produto atualizado com sucesso!";
            }

            $params = [
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
            ];

            if ($_POST['action'] === 'editar') {
                $params[':id'] = $_POST['produto_id'];
            }

            $stmt->execute($params);
            $tipo_mensagem = "sucesso";
        } catch (PDOException $e) {
            $mensagem = "Erro ao processar: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    } elseif ($_POST['action'] === 'excluir') {
        try {
            $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
            $stmt->execute([':id' => $_POST['produto_id']]);
            $mensagem = "Produto excluído com sucesso!";
            $tipo_mensagem = "sucesso";
        } catch (PDOException $e) {
            $mensagem = "Erro ao excluir: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
    if ($stmt->execute($params)) {
        // Em vez de apenas definir $mensagem, salve na sessão
        $_SESSION['feedback'] = [
            'texto' => ($_POST['action'] === 'cadastrar') ? "Produto cadastrado com sucesso!" : "Produto atualizado com sucesso!",
            'tipo' => 'sucesso'
        ];
        
        // REDIRECIONAMENTO: Limpa o POST do navegador
        header("Location: TelaAdm.php"); 
        exit;
    }
}

// Buscar produto para edição
$produto_edicao = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id");
    $stmt->execute([':id' => $_GET['editar']]);
    $produto_edicao = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar produtos cadastrados
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY data_cadastro DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Realiza Móveis</title>
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

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: linear-gradient(135deg, var(--dourado-escuro) 0%, var(--dourado) 100%);
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0 30px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .admin-header h1 {
            color: var(--branco);
            font-size: 2.5em;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .admin-header p {
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

        .formulario-cadastro {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .formulario-cadastro h2 {
            color: var(--dourado-escuro);
            margin-bottom: 30px;
            font-size: 2em;
            border-bottom: 3px solid var(--dourado);
            padding-bottom: 15px;
        }

        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--dourado), transparent);
            margin: 30px 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--preto);
            font-size: 0.95em;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid var(--cinza);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--dourado);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .preco-section h3 {
            color: var(--dourado-escuro);
            margin-bottom: 15px;
        }

        .preco-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
            padding: 20px;
            background: var(--cinza-claro);
            border-radius: 10px;
        }

        .preco-display {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--dourado);
        }

        .preco-display label {
            font-size: 0.85em;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }

        .preco-display .valor {
            font-size: 1.5em;
            font-weight: 700;
            color: var(--dourado-escuro);
        }

        .preco-display.promocao {
            border-left-color: #E74C3C;
        }

        .preco-display.promocao .valor {
            color: #E74C3C;
        }

        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--dourado) 0%, var(--dourado-claro) 100%);
            color: var(white);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dourado-escuro) 0%, var(--dourado) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            font-size: 0.9em;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-edit {
            background: #ffc107;
            color: #000;
            padding: 8px 16px;
            font-size: 0.9em;
            margin-right: 10px;
        }

        .btn-edit:hover {
            background: #e0a800;
        }

        .produtos-lista {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .produtos-lista h2 {
            color: var(--dourado-escuro);
            margin-bottom: 30px;
            font-size: 2em;
            border-bottom: 3px solid var(--dourado);
            padding-bottom: 15px;
        }

        .produto-card {
            background: var(--cinza-claro);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 5px solid var(--dourado);
            transition: all 0.3s;
        }

        .produto-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .produto-card h3 {
            color: var(--preto);
            margin-bottom: 15px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7em;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.destaque {
            background: #FF9800;
            color: white;
        }

        .badge.promocao {
            background: #E74C3C;
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

        .produto-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            padding: 10px;
            background: white;
            border-radius: 6px;
            font-size: 0.95em;
        }

        .info-item strong {
            color: var(--dourado-escuro);
            display: block;
            margin-bottom: 5px;
        }

        .produto-acoes {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <!-- Header da Loja -->
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
        <a href="TelaAdm.php" style="color: var(--gold);">Administração</a>
        <a href="#">Contato</a>
    </nav>

    <!-- Container Administrativo -->
    <div class="admin-container">
        <div class="admin-header">
            <h1> Painel Administrativo</h1>
            <p>Gerencie os produtos da sua loja</p>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Cadastro/Edição -->
        <div class="formulario-cadastro">
            <h2><?php echo $produto_edicao ? ' Editar Produto' : ' Cadastrar Novo Produto'; ?></h2>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $produto_edicao ? 'editar' : 'cadastrar'; ?>">
                <?php if ($produto_edicao): ?>
                    <input type="hidden" name="produto_id" value="<?php echo $produto_edicao['id']; ?>">
                    <input type="hidden" name="imagens_existentes" value="<?php echo htmlspecialchars($produto_edicao['imagens']); ?>">
                <?php endif; ?>

                <!-- INFORMAÇÕES BÁSICAS -->
                <h3 style="color: var(--dourado-escuro); margin-bottom: 15px;">Informações Básicas</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome do Produto *</label>
                        <input type="text" id="nome" name="nome" required 
                               value="<?php echo $produto_edicao ? htmlspecialchars($produto_edicao['nome']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="categoria">Categoria *</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Selecione...</option>
                            <option value="sofa" <?php echo ($produto_edicao && $produto_edicao['categoria'] == 'sofa') ? 'selected' : ''; ?>>Sofá</option>
                            <option value="guarda-roupa" <?php echo ($produto_edicao && $produto_edicao['categoria'] == 'guarda-roupa') ? 'selected' : ''; ?>>Guarda-Roupa</option>
                            <option value="mesa" <?php echo ($produto_edicao && $produto_edicao['categoria'] == 'mesa') ? 'selected' : ''; ?>>Mesa</option>
                            <option value="cadeira" <?php echo ($produto_edicao && $produto_edicao['categoria'] == 'cadeira') ? 'selected' : ''; ?>>Cadeira</option>
                            <option value="rack" <?php echo ($produto_edicao && $produto_edicao['categoria'] == 'rack') ? 'selected' : ''; ?>>Rack</option>
                            <option value="estante" <?php echo ($produto_edicao && $produto_edicao['categoria'] == 'estante') ? 'selected' : ''; ?>>Estante</option>
                            <option value="poltrona" <?php echo ($produto_edicao && $produto_edicao['categoria'] == 'poltrona') ? 'selected' : ''; ?>>Poltrona</option>
                            <option value="armario" <?php echo ($produto_edicao && $produto_edicao['categoria'] == 'armario') ? 'selected' : ''; ?>>Armário</option>
                            <option value="comoda" <?php echo ($produto_edicao && $produto_edicao['categoria'] == 'comoda') ? 'selected' : ''; ?>>Cômoda</option>
                            <option value="escrivaninha" <?php echo ($produto_edicao && $produto_edicao['categoria'] == 'escrivaninha') ? 'selected' : ''; ?>>Escrivaninha</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="marca">Marca *</label>
                        <input type="text" id="marca" name="marca" required 
                               value="<?php echo $produto_edicao ? htmlspecialchars($produto_edicao['marca']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="modelo">Modelo *</label>
                        <input type="text" id="modelo" name="modelo" required 
                               value="<?php echo $produto_edicao ? htmlspecialchars($produto_edicao['modelo']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="sku">SKU/Código *</label>
                        <input type="text" id="sku" name="sku" required 
                               value="<?php echo $produto_edicao ? htmlspecialchars($produto_edicao['sku']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="ativo" <?php echo ($produto_edicao && $produto_edicao['status'] == 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo ($produto_edicao && $produto_edicao['status'] == 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição Completa *</label>
                    <textarea id="descricao" name="descricao" required><?php echo $produto_edicao ? htmlspecialchars($produto_edicao['descricao']) : ''; ?></textarea>
                </div>

                <div class="section-divider"></div>

                <!-- CARACTERÍSTICAS FÍSICAS -->
                <h3 style="color: var(--dourado-escuro); margin-bottom: 15px;">Características Físicas</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="cor">Cor</label>
                        <input type="text" id="cor" name="cor" 
                               value="<?php echo $produto_edicao ? htmlspecialchars($produto_edicao['cor']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="material">Material</label>
                        <input type="text" id="material" name="material" 
                               value="<?php echo $produto_edicao ? htmlspecialchars($produto_edicao['material']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="dimensoes">Dimensões (LxAxP)</label>
                        <input type="text" id="dimensoes" name="dimensoes" placeholder="Ex: 200x80x90 cm"
                               value="<?php echo $produto_edicao ? htmlspecialchars($produto_edicao['dimensoes']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="peso">Peso (kg)</label>
                        <input type="text" id="peso" name="peso" placeholder="Ex: 50"
                               value="<?php echo $produto_edicao ? htmlspecialchars($produto_edicao['peso']) : ''; ?>">
                    </div>
                </div>

                <div class="section-divider"></div>

                <!-- IMAGENS -->
                <h3 style="color: var(--dourado-escuro); margin-bottom: 15px;">Imagens</h3>
                <div class="form-group">
                    <label for="imagens">Upload de Imagens <?php echo $produto_edicao ? '(deixe em branco para manter as existentes)' : '*'; ?></label>
                    <input type="file" id="imagens" name="imagens[]" multiple accept="image/*" 
                           <?php echo !$produto_edicao ? 'required' : ''; ?>>
                    <small style="color: #666;">Você pode selecionar múltiplas imagens</small>
                </div>

                <div class="section-divider"></div>

                <!-- ESTOQUE -->
                <h3 style="color: var(--dourado-escuro); margin-bottom: 15px;">Controle de Estoque</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="estoque">Quantidade em Estoque *</label>
                        <input type="number" id="estoque" name="estoque" required min="0"
                               value="<?php echo $produto_edicao ? $produto_edicao['estoque'] : '0'; ?>">
                    </div>

                    <div class="form-group">
                        <label for="estoque_minimo">Estoque Mínimo *</label>
                        <input type="number" id="estoque_minimo" name="estoque_minimo" required min="0"
                               value="<?php echo $produto_edicao ? $produto_edicao['estoque_minimo'] : '5'; ?>">
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="destaque" name="destaque" 
                                   <?php echo ($produto_edicao && $produto_edicao['destaque']) ? 'checked' : ''; ?>>
                            <label for="destaque" style="margin: 0;">⭐ Produto Destaque</label>
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
                                   placeholder="0.00" onchange="calcularPromocao()"
                                   value="<?php echo $produto_edicao ? $produto_edicao['preco'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="desconto_percentual">Desconto (%)</label>
                            <input type="number" id="desconto_percentual" name="desconto_percentual" 
                                   step="0.01" min="0" max="100" onchange="calcularPromocao()"
                                   value="<?php echo $produto_edicao ? $produto_edicao['desconto_percentual'] : '0'; ?>">
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="em_promocao" name="em_promocao" 
                                       onchange="calcularPromocao()"
                                       <?php echo ($produto_edicao && $produto_edicao['em_promocao']) ? 'checked' : ''; ?>>
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

                <div style="text-align: center; margin-top: 30px; display: flex; gap: 15px; justify-content: center;">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $produto_edicao ? '💾 Salvar Alterações' : ' Cadastrar Produto'; ?>
                    </button>
                    <?php if ($produto_edicao): ?>
                        <a href="TelaAdm.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">
                            ❌ Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Lista de Produtos -->
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
                                <strong>Modelo:</strong> <?php echo htmlspecialchars($produto['modelo']); ?>
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
                        
                        <div class="produto-acoes">
                            <a href="TelaAdm.php?editar=<?php echo $produto['id']; ?>" class="btn btn-edit">
                                Editar
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                <input type="hidden" name="action" value="excluir">
                                <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                                <button type="submit" class="btn btn-danger">
                                    Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer da Loja -->
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
            
            const promocaoDisplay = document.querySelector('.preco-display.promocao');
            if (emPromocao && desconto > 0) {
                promocaoDisplay.style.borderLeft = '4px solid #E74C3C';
                promocaoDisplay.style.background = '#FFEBEE';
            } else {
                promocaoDisplay.style.borderLeft = '4px solid var(--dourado)';
                promocaoDisplay.style.background = 'white';
            }
        }
        
        window.addEventListener('load', calcularPromocao);
    </script>
</body>
</html>