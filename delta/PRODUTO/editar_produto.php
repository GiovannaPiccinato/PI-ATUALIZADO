<?php
//uma sessão é iniciada e verifica-se se um administrador está logado. Se não estiver, ele é redirecionado para a página de login.
session_start();

if (!isset($_SESSION['admin_logado'])) {
	header('Location: login.php');
	exit();
}

if (isset($_GET['logout'])) {
	session_destroy();
	header('location: ./login.php');
	exit();
};

//o script faz uma conexão com o banco de dados, usando os detalhes de configuração especificados em conexao.php
require_once('../conexao.php');

//busca categoria
try {
	$stmt_categoria = $pdo->prepare("SELECT * FROM CATEGORIA WHERE CATEGORIA_ATIVO= 1");
	$stmt_categoria->execute();
	$categorias = $stmt_categoria->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	echo "<p style='color:white;'> Erro ao buscar categorias:" . $e->getMessage() . "</p>";
}

// Se a página foi acessada via método GET, o script tenta recuperar os detalhes do produto com base no ID passado na URL.
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	if (isset($_GET['id'])) {
		$id = $_GET['id'];
		try {
			// Consulta para recuperar os detalhes do produto
			$stmt = $pdo->prepare("SELECT PRODUTO.*, CATEGORIA.CATEGORIA_NOME, PRODUTO_IMAGEM.IMAGEM_URL, PRODUTO_IMAGEM.IMAGEM_ORDEM, PRODUTO_ESTOQUE.PRODUTO_QTD 
            FROM PRODUTO 
            INNER JOIN CATEGORIA ON PRODUTO.CATEGORIA_ID = CATEGORIA.CATEGORIA_ID
            LEFT JOIN PRODUTO_IMAGEM ON PRODUTO.PRODUTO_ID = PRODUTO_IMAGEM.PRODUTO_ID
            LEFT JOIN PRODUTO_ESTOQUE ON PRODUTO.PRODUTO_ID = PRODUTO_ESTOQUE.PRODUTO_ID
            WHERE PRODUTO.PRODUTO_ID = :id");
			$stmt->bindParam(':id', $id, PDO::PARAM_INT);
			$stmt->execute();
			$produto = $stmt->fetch(PDO::FETCH_ASSOC);

			// Consulta para recuperar as imagens do produto específico
			$stmt_imagem = $pdo->prepare("SELECT * FROM PRODUTO_IMAGEM WHERE PRODUTO_ID = :id");
			$stmt_imagem->bindParam(':id', $id, PDO::PARAM_INT);
			$stmt_imagem->execute();
			$imagem_url = $stmt_imagem->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			echo "Erro: " . $e->getMessage();
		}
	} else {
		header('Location: listar_produtos.php');
		exit();
	}
}


// Se o formulário de edição foi submetido, a página é acessada via método POST, e o script tenta atualizar os detalhes do produto no banco de dados com as informações fornecidas no formulário.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$id = $_POST['id'];
	$nome = $_POST['nome'];
	$descricao = $_POST['descricao'];
	$preco = $_POST['preco'];
	$desconto = $_POST['desconto'];
	$categoria_id = $_POST['categoria_id'];
	$produto_qtd = $_POST['qtd'];
	$ativo = isset($_POST['ativo']) ? 1 : 0;
	$imagem_url = $_POST['imagem_url'];
	$imagem_ordem = $_POST['imagem_ordem'];
	$quantidade = $_POST['quantidade'];

	// Verifique se há novas imagens para adicionar
	if (!empty($_POST['nova_imagem_url']) && !empty($_POST['nova_imagem_ordem'])) {
		foreach ($_POST['nova_imagem_url'] as $index => $url) {
			$ordem = $_POST['nova_imagem_ordem'][$index];
			if (!empty($url) && isset($_POST['nova_imagem_ordem'][$index])) {
				// Inserir nova imagem
				$stmt_imagem = $pdo->prepare("INSERT INTO PRODUTO_IMAGEM (IMAGEM_URL, PRODUTO_ID, IMAGEM_ORDEM) VALUES (:imagem_url, :produto_id, :imagem_ordem)");
				$stmt_imagem->bindParam(':imagem_url', $url, PDO::PARAM_STR);
				$stmt_imagem->bindParam(':produto_id', $id, PDO::PARAM_INT);
				$stmt_imagem->bindParam(':imagem_ordem', $ordem, PDO::PARAM_INT);
				$stmt_imagem->execute();
			}
		}
	}

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (isset($_POST['imagem_id'])) {
			$imagem_id = $_POST['imagem_id'];

			// Faça a conexão com o banco de dados e execute a exclusão da imagem
			require_once('../conexao.php');

			try {
				$stmt = $pdo->prepare("DELETE FROM PRODUTO_IMAGEM WHERE IMAGEM_ID = :imagem_id");
				$stmt->bindParam(':imagem_id', $imagem_id, PDO::PARAM_INT);
				$stmt->execute();
				// Responda com sucesso se a exclusão for bem-sucedida
				echo "success";
			} catch (PDOException $e) {
				// Responda com erro se ocorrer algum problema durante a exclusão
				echo "error";
			}
		}
	}

	// Atualizar as imagens existentes
	if (isset($_POST['imagem_id']) && isset($_POST['imagem_url']) && isset($_POST['imagem_ordem'])) {
		foreach ($_POST['imagem_id'] as $index => $imagem_id) {
			$url = $_POST['imagem_url'][$index];
			$ordem = $_POST['imagem_ordem'][$index];
			if (!empty($url) && isset($_POST['imagem_ordem'][$index])) {
				// Atualizar a imagem existente
				$stmt_imagem = $pdo->prepare("UPDATE PRODUTO_IMAGEM SET IMAGEM_URL = :imagem_url, IMAGEM_ORDEM = :imagem_ordem WHERE PRODUTO_ID = :id AND IMAGEM_ID = :imagem_id");
				$stmt_imagem->bindParam(':imagem_url', $url, PDO::PARAM_STR);
				$stmt_imagem->bindParam(':imagem_ordem', $ordem, PDO::PARAM_INT);
				$stmt_imagem->bindParam(':id', $id, PDO::PARAM_INT);
				$stmt_imagem->bindParam(':imagem_id', $imagem_id, PDO::PARAM_INT);
				$stmt_imagem->execute();
			}
		}
	}

	try {
		if (isset($_POST['imagem_url']) && isset($_POST['imagem_ordem'])) {
			foreach ($_POST['imagem_url'] as $index => $url) {
				if (!empty($url) && isset($_POST['imagem_ordem'][$index])) {
					$ordem = $_POST['imagem_ordem'][$index];
					$imagem_id = $_POST['imagem_id'][$index]; // Certifique-se de incluir campos ocultos com imagem_id em seu formulário

					// Atualizar cada entrada de imagem
					$stmt_imagem = $pdo->prepare("UPDATE PRODUTO_IMAGEM SET IMAGEM_ORDEM = :imagem_ordem, IMAGEM_URL = :imagem_url WHERE PRODUTO_ID = :id AND IMAGEM_ID = :imagem_id");

					$stmt_imagem->bindParam(':imagem_url', $url, PDO::PARAM_STR);
					$stmt_imagem->bindParam(':imagem_ordem', $ordem, PDO::PARAM_INT);
					$stmt_imagem->bindParam(':id', $id, PDO::PARAM_INT);
					$stmt_imagem->bindParam(':imagem_id', $imagem_id, PDO::PARAM_INT);
					$stmt_imagem->execute();
				}
			}
		}

		$stmtProdutoEstoque = $pdo->prepare("UPDATE PRODUTO_ESTOQUE SET PRODUTO_QTD = :qtd WHERE PRODUTO_ID = :id");
		$stmtProdutoEstoque->bindParam(':qtd', $produto_qtd, PDO::PARAM_STR);
		$stmtProdutoEstoque->bindParam(':id', $id, PDO::PARAM_INT);
		$stmtProdutoEstoque->execute();

		$stmt = $pdo->prepare("UPDATE PRODUTO SET PRODUTO_NOME = :nome, PRODUTO_DESC = :descricao, PRODUTO_PRECO = :preco, PRODUTO_DESCONTO = :desconto, CATEGORIA_ID = :categoria_id,  PRODUTO_ATIVO = :ativo WHERE PRODUTO_ID = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
		$stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
		$stmt->bindParam(':preco', $preco, PDO::PARAM_STR);
		$stmt->bindParam(':desconto', $desconto, PDO::PARAM_STR);
		$stmt->bindParam(':categoria_id', $categoria_id, PDO::PARAM_INT);
		$stmt->bindParam(':ativo', $ativo, PDO::PARAM_INT);
		$stmt->execute();
		header('Location: listar_produto.php');

		//Inserindo imagens no BD
		foreach ($imagem_url as $index => $url) {
			$ordem = $imagem_ordem[$index];
			$sql_imagem = "INSERT INTO PRODUTO_IMAGEM (IMAGEM_URL, PRODUTO_ID, IMAGEM_ORDEM)
								VALUES (:imagem_url, :produto_id, :imagem_ordem)";

			$stmt_imagem = $pdo->prepare($sql_imagem);

			$stmt_imagem->bindParam(':imagem_url', $url, PDO::PARAM_STR);
			$stmt_imagem->bindParam(':produto_id', $produto_id, PDO::PARAM_STR);
			$stmt_imagem->bindParam(':imagem_ordem', $ordem, PDO::PARAM_STR);
			$stmt_imagem->execute();
			// URL da API do Imgur
			$api_url = "https://api.imgur.com/3/image";
			$client_id = '827a127f37b1a18'; // Substitua pelo seu Client ID

			$ch = curl_init($api_url);

			$image = file_get_contents($tmp_name);
		}
		echo "<p style='color:green;'> Produto editado com sucesso!</p>";

		foreach ($quantidade as $quantidades) {
			$sql_quantidade = "INSERT INTO PRODUTO_ESTOQUE (PRODUTO_ID, PRODUTO_QTD)
								VALUES (:produto_id, :quantidade)";

			$stmt_quantidade = $pdo->prepare($sql_quantidade);

			$stmt_quantidade->bindParam(':produto_id', $produto_id, PDO::PARAM_STR);
			$stmt_quantidade->bindParam(':quantidade', $quantidades, PDO::PARAM_STR);
			$stmt_quantidade->execute();
		}

		exit();
	} catch (PDOException $e) {
		echo "Erro: " . $e->getMessage();
	}
}


?>
<!-- Um formulário de edição é apresentado ao administrador, preenchido com os detalhes atuais do produto, permitindo que ele faça modificações e submeta o formulário para atualizar os detalhes do produto -->
<!DOCTYPE html>
<html lang="pt">

<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="../CSS/editar_produto.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<script src="../JS/ativo.js"></script>
	<script src="../JS/editar_produto.js"></script>
	<title>Editar Produto</title>
</head>

<body>
	<main>
		<nav class="lateral_menu">
			<div class="items">
				<ul>
					<li class="menu_item">
						<a href="../painel_admin.php">
							<span class="icon"><i class="bi bi-house"></i></span>
							<span class="text">HOME</span>
						</a>
					</li>

					<li class="menu_item ativo">
						<a href="../PRODUTO/listar_produto.php">
							<span class="icon"><i class="bi bi-tags"></i></span>
							<span class="text">PRODUTOS</span>
						</a>
					</li>

					<li class="menu_item">
						<a href="../CATEGORIA/listar_categoria.php">
							<span class="icon"><i class="bi bi-controller"></i></span>
							<span class="text">CATEGORIA</span>
						</a>
					</li>

					<li class="menu_item">
						<a href="../ADMINISTRADOR/listar_administrador.php">
							<span class="icon"><i class="bi bi-person-gear"></i></span>
							<span class="text">ADMINISTRADOR</span>
						</a>
					</li>

					<!-- <li class="menu_item">
						<a href="#">
							<span class="icon"><i class="bi bi-person"></i></span>
							<span class="text">PERFIL</span>
						</a>
					</li> -->
				</ul>
			</div>

			<div class="close">
				<a class="btn-sair" href="../painel_admin.php?logout">
					<span class="icon"><i class="bi bi-door-closed"></i></span>
					<span class="text">SAIR</span>
				</a>
			</div>
		</nav>

		<section class="painel">
			<header>
				<div class="welcome">
					<h1>Alteração dos nossos <span>Jogos</span></h1>
					<img src="../fotos/usuario.png" alt="Barra de Carregamento do usuario" />
				</div>
			</header>

			<div class="form_register">
				<form action="editar_produto.php" method="post" enctype="multipart/form-data">

					<div class="form_column">
						<div class="form_box">
							<input type="hidden" name="id" value="<?php echo $produto['PRODUTO_ID']; ?>">
						</div>

						<div class="form_box">
							<label for="nome">Nome do produto:</label>
							<input type="text" name="nome" id="nome" placeholder="Digite o nome do Produto" value="<?php echo $produto['PRODUTO_NOME']; ?>">
						</div>

						<div class="form_box">
							<label for="preco">Preço:</label>
							<input type="number" name="preco" id="preco" placeholder="Digite o valor do Produto" value="<?php echo $produto['PRODUTO_PRECO']; ?>">
						</div>

						<div class="form_box">
							<label for="desconto">Desconto:</label>
							<input type="number" name="desconto" id="desconto" placeholder="Digite o valor de desconto" value="<?php echo $produto['PRODUTO_DESCONTO']; ?>">
						</div>

						<div class="form_box">
							<label for="qtd">Quantidade em estoque:</label>
							<input type="number" id="qtd" name="qtd" step="1" min="0" placeholder="Digite a quantidade de itens" value="<?php echo $produto['PRODUTO_QTD']; ?>">
						</div>

						<div class="form_box">
							<label for="categoria_nome">Categoria:</label>
							<select name="categoria_id" id="categoria_id" required>
								<?php foreach ($categorias as $categoria) : ?>
									<option value="<?= $categoria['CATEGORIA_ID']; ?>" <?= ($categoria['CATEGORIA_ID'] == $produto['CATEGORIA_ID']) ? 'selected' : ''; ?>>
										<?= $categoria['CATEGORIA_NOME'] ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>


					</div>

					<div class="form_column">
						<div class="form_box form_box_image">
							<label for="url">Url da Imagem:</label>
							<div id="containerImagens">
								<?php
								foreach ($imagem_url as $imagem) {
									echo "<div class='imagem_container'>";
									echo "<input type='hidden' name='imagem_id[]' value='{$imagem['IMAGEM_ID']}'>";
									echo '<input type="text" name="imagem_url[]" value="' . $imagem['IMAGEM_URL'] . '">';
									echo '<input id="ordem" type="number" name="imagem_ordem[]" value="' . $imagem['IMAGEM_ORDEM'] . '">';

									echo "</div>";
								}
								?>
								<div class="imagem_container">
									<input type="text" name="nova_imagem_url[]" placeholder="URL Imagem">
									<input type="number" name="nova_imagem_ordem[]" placeholder="Ordem Imagem" min="1">
									<a class="remove-icon" onclick="removerImagem(this)"><i class="bi bi-x-circle-fill"></i></a>
								</div>
							</div>
							<button type="button" onclick="adicionarImagem()">Adicionar mais Imagens</button>
						</div>

						<div class="form_box">
							<label for="descricao">Descrição:</label>
							<textarea name="descricao" id="descricao"><?php echo $produto['PRODUTO_DESC']; ?></textarea>
						</div>

						<div class="form_check">
							<label for="ativo"> Ativo:</label>
							<input id="check" type="checkbox" name="ativo" id="ativo" value="1" checked <?= $produto['PRODUTO_ATIVO'] ? 'checked' : '' ?>>
						</div>

						<div class="form_box">
							<input type="submit" value="Atualizar Produto">
						</div>
					</div>
				</form>
			</div>
		</section>
	</main>
	<div>
	</div>
</body>
<script>
	let numImagens = <?php echo count($imagem_url); ?>; // Inicializa com o número de imagens existentes
</script>

</html>