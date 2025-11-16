<?php
require 'banco.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['cadastrar_projeto'])) {
        $nome = trim($_POST['nome_projeto']);
        if (!empty($nome)) {
            $stmt = $db->prepare("INSERT INTO projetos (nome, status) VALUES (?, ?)");
            $stmt->execute([$nome, 'Planejado']);
        }
    }

    if (isset($_POST['mudar_status'])) {
        $id = $_POST['projeto_id'];
        $status = $_POST['novo_status'];
        $stmt = $db->prepare("UPDATE projetos SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    if (isset($_POST['cadastrar_atividade'])) {
        $descricao = trim($_POST['descricao_atividade']);
        $projeto_id = $_POST['projeto_id'];

        $stmtVerifica = $db->prepare("SELECT status FROM projetos WHERE id = ?");
        $stmtVerifica->execute([$projeto_id]);
        $status_projeto = $stmtVerifica->fetchColumn();

        if ($status_projeto !== 'Concluído' && $status_projeto !== 'Cancelado' && !empty($descricao)) {
            $stmt = $db->prepare("INSERT INTO atividades (descricao, status, projeto_id) VALUES (?, ?, ?)");
            $stmt->execute([$descricao, 'Em Andamento', $projeto_id]);
        }
    }

    if (isset($_POST['mudar_status_atividade'])) {
        $id = $_POST['atividade_id'];
        $status = $_POST['novo_status_atividade'];
        $stmt = $db->prepare("UPDATE atividades SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    if (isset($_POST['excluir_atividade'])) {
        $id = $_POST['atividade_id'];
        $stmt = $db->prepare("DELETE FROM atividades WHERE id = ?");
        $stmt->execute([$id]);
    }

    if (isset($_POST['excluir_projeto'])) {
        $id = $_POST['projeto_id'];
        $stmt = $db->prepare("DELETE FROM projetos WHERE id = ?");
        $stmt->execute([$id]);
    }

    header('Location: index.php');
    exit;
}

$projetos_ativos = [];
$projetos_arquivados = [];

function getAtividades($db, $projeto_id) {
    $stmtAtividades = $db->prepare("SELECT * FROM atividades WHERE projeto_id = ? ORDER BY id ASC");
    $stmtAtividades->execute([$projeto_id]);
    return $stmtAtividades->fetchAll(PDO::FETCH_ASSOC);
}

$stmtAtivos = $db->query("SELECT * FROM projetos WHERE status IN ('Planejado', 'Em Andamento') ORDER BY id DESC");
foreach ($stmtAtivos->fetchAll(PDO::FETCH_ASSOC) as $projeto) {
    $projeto['atividades'] = getAtividades($db, $projeto['id']);
    $projetos_ativos[] = $projeto;
}

$stmtArquivados = $db->query("SELECT * FROM projetos WHERE status IN ('Concluído', 'Cancelado') ORDER BY id DESC");
foreach ($stmtArquivados->fetchAll(PDO::FETCH_ASSOC) as $projeto) {
    $projeto['atividades'] = getAtividades($db, $projeto['id']);
    $projetos_arquivados[] = $projeto;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Painel de Gestão PETI</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <header>
        <h1>Painel de Controle PETI</h1>
        <p>Gestão e Governança de Tecnologia</p>
    </header>

    <main>
        <section class="container-cadastro">
            <h2>Novo Projeto</h2>
            <form action="index.php" method="POST">
                <input
                    type="text"
                    name="nome_projeto"
                    placeholder="Nome do novo projeto"
                    required
                />
                <button type="submit" name="cadastrar_projeto" value="1">
                    Cadastrar Projeto
                </button>
            </form>
        </section>

        <section class="container-projetos">
            <h2>Projetos Ativos</h2>
            <div id="painel-projetos-ativos">
                
                <?php if (empty($projetos_ativos)): ?>
                    <p>Nenhum projeto ativo no momento.</p>
                <?php endif; ?>

                <?php foreach ($projetos_ativos as $projeto): ?>
                    <div class="projeto-card">
                        <div class="projeto-header">
                            <h3><?= htmlspecialchars($projeto['nome']) ?></h3>
                            <form action="index.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este PROJETO?');">
                                <input type="hidden" name="projeto_id" value="<?= $projeto['id'] ?>">
                                <button type="submit" name="excluir_projeto" value="1" class="btn-excluir btn-excluir-projeto" title="Excluir Projeto">X</button>
                            </form>
                        </div>
                        
                        <span class="status" data-status="<?= $projeto['status'] ?>">
                            <?= $projeto['status'] ?>
                        </span>

                        <form action="index.php" method="POST" class="form-status">
                            <input type="hidden" name="projeto_id" value="<?= $projeto['id'] ?>">
                            <select name="novo_status">
                                <option value="Planejado" <?= $projeto['status'] === 'Planejado' ? 'selected' : '' ?>>Planejado</option>
                                <option value="Em Andamento" <?= $projeto['status'] === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                                <option value="Concluído" <?= $projeto['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                                <option value="Cancelado" <?= $projeto['status'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                            <button type="submit" name="mudar_status" value="1">Mudar Status</button>
                        </form>
                        
                        <hr>
                        <h4>Atividades:</h4>
                        
                        <?php if (empty($projeto['atividades'])): ?>
                            <p>Nenhuma atividade cadastrada.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($projeto['atividades'] as $atividade): ?>
                                    <li>
                                        <span class="descricao-atividade">
                                            <?= htmlspecialchars($atividade['descricao']) ?>
                                            (<i><?= $atividade['status'] ?></i>)
                                        </span>
                                        <div class="controles-atividade">
                                            <form action="index.php" method="POST" class="form-status-atividade">
                                                <input type="hidden" name="atividade_id" value="<?= $atividade['id'] ?>">
                                                <select name="novo_status_atividade">
                                                    <option value="Em Andamento" <?= $atividade['status'] === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                                                    <option value="Concluído" <?= $atividade['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                                                    <option value="Cancelado" <?= $atividade['status'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                                </select>
                                                <button type="submit" name="mudar_status_atividade" value="1">Mudar</button>
                                            </form>
                                            <form action="index.php" method="POST" onsubmit="return confirm('Excluir esta atividade?');">
                                                <input type="hidden" name="atividade_id" value="<?= $atividade['id'] ?>">
                                                <button type="submit" name="excluir_atividade" value="1" class="btn-excluir" title="Excluir Atividade">X</button>
                                            </form>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <form action="index.php" method="POST" class="form-atividade">
                            <input type="hidden" name="projeto_id" value="<?= $projeto['id'] ?>">
                            <input type="text" name="descricao_atividade" placeholder="Nova atividade" required>
                            <button type="submit" name="cadastrar_atividade" value="1">Adicionar</button>
                        </form>

                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="container-projetos container-arquivados">
            <h2>Projetos Arquivados (Concluídos / Cancelados)</h2>
            <div id="painel-projetos-arquivados">
                
                <?php if (empty($projetos_arquivados)): ?>
                    <p>Nenhum projeto arquivado.</p>
                <?php endif; ?>

                <?php foreach ($projetos_arquivados as $projeto): ?>
                    <div class="projeto-card card-arquivado">
                        <div class="projeto-header">
                            <h3><?= htmlspecialchars($projeto['nome']) ?></h3>
                            <form action="index.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este PROJETO?');">
                                <input type="hidden" name="projeto_id" value="<?= $projeto['id'] ?>">
                                <button type="submit" name="excluir_projeto" value="1" class="btn-excluir btn-excluir-projeto" title="Excluir Projeto">X</button>
                            </form>
                        </div>
                        
                        <span class="status" data-status="<?= $projeto['status'] ?>">
                            <?= $projeto['status'] ?>
                        </span>
                        
                        <hr>
                        <h4>Atividades:</h4>
                        
                        <?php if (empty($projeto['atividades'])): ?>
                            <p>Nenhuma atividade cadastrada.</p>
                        <?php else: ?>
                            <ul class="lista-atividades-arquivadas">
                                <?php foreach ($projeto['atividades'] as $atividade): ?>
                                    <li>
                                        <span class="descricao-atividade">
                                            <?= htmlspecialchars($atividade['descricao']) ?>
                                            (<i><?= $atividade['status'] ?></i>)
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>