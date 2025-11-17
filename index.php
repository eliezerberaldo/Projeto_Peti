<?php

require 'banco.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['cadastrar_projeto'])) {
        $nome = trim($_POST['nome_projeto']);
        $desc = trim($_POST['desc_projeto']);
        
        if (!empty($nome)) {
            $stmt = $db->prepare("INSERT INTO projetos (nome, descricao, status) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $desc, 'Planejado']);
        }
    }

    if (isset($_POST['mudar_status'])) {
        $id = $_POST['projeto_id'];
        $status = $_POST['novo_status'];
        $stmt = $db->prepare("UPDATE projetos SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    if (isset($_POST['cadastrar_atividade'])) {
        $titulo = trim($_POST['titulo_atividade']);
        $desc = trim($_POST['desc_atividade']); 
        $projeto_id = $_POST['projeto_id'];

        $stmtVerifica = $db->prepare("SELECT status FROM projetos WHERE id = ?");
        $stmtVerifica->execute([$projeto_id]);
        $status_projeto = $stmtVerifica->fetchColumn();

        if ($status_projeto !== 'Concluído' && $status_projeto !== 'Cancelado' && !empty($titulo)) {
            $stmt = $db->prepare("INSERT INTO atividades (titulo, descricao, status, projeto_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titulo, $desc, 'Em Andamento', $projeto_id]);
        }
    }

    if (isset($_POST['mudar_status_atividade'])) {
        $id = $_POST['atividade_id'];
        $status = $_POST['novo_status_atividade'];
        $stmt = $db->prepare("UPDATE atividades SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    if (isset($_POST['excluir_atividade'])) {
        $stmt = $db->prepare("DELETE FROM atividades WHERE id = ?");
        $stmt->execute([$_POST['atividade_id']]);
    }

    if (isset($_POST['excluir_projeto'])) {
        $stmt = $db->prepare("DELETE FROM projetos WHERE id = ?");
        $stmt->execute([$_POST['projeto_id']]);
    }

    header('Location: index.php');
    exit;
}

function getAtividades($db, $projeto_id) {
    $stmtAtividades = $db->prepare("SELECT * FROM atividades WHERE projeto_id = ? ORDER BY id ASC");
    $stmtAtividades->execute([$projeto_id]);
    return $stmtAtividades->fetchAll(PDO::FETCH_ASSOC);
}

$projetos_ativos = [];
$stmtAtivos = $db->query("SELECT * FROM projetos WHERE status IN ('Planejado', 'Em Andamento') ORDER BY id DESC");
foreach ($stmtAtivos->fetchAll(PDO::FETCH_ASSOC) as $projeto) {
    $projeto['atividades'] = getAtividades($db, $projeto['id']);
    $projetos_ativos[] = $projeto;
}

$projetos_arquivados = [];
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
            <form action="index.php" method="POST" class="form-novo-projeto">
                <div class="grupo-input">
                    <input type="text" name="nome_projeto" placeholder="Nome do Projeto" required />
                    <textarea name="desc_projeto" placeholder="Descrição do projeto (objetivos, escopo...)" rows="2"></textarea>
                </div>
                <button type="submit" name="cadastrar_projeto" value="1">Cadastrar Projeto</button>
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
                            <div class="projeto-titulo-area">
                                <h3><?= htmlspecialchars($projeto['nome']) ?></h3>
                                <?php if(!empty($projeto['descricao'])): ?>
                                    <p class="descricao-projeto"><?= nl2br(htmlspecialchars($projeto['descricao'])) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <form action="index.php" method="POST" onsubmit="return confirm('Excluir projeto?');">
                                <input type="hidden" name="projeto_id" value="<?= $projeto['id'] ?>">
                                <button type="submit" name="excluir_projeto" value="1" class="btn-excluir btn-excluir-projeto">X</button>
                            </form>
                        </div>
                        
                        <span class="status" data-status="<?= $projeto['status'] ?>"><?= $projeto['status'] ?></span>

                        <form action="index.php" method="POST" class="form-status">
                            <input type="hidden" name="projeto_id" value="<?= $projeto['id'] ?>">
                            <select name="novo_status">
                                <option value="Planejado" <?= $projeto['status'] === 'Planejado' ? 'selected' : '' ?>>Planejado</option>
                                <option value="Em Andamento" <?= $projeto['status'] === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                                <option value="Concluído" <?= $projeto['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                                <option value="Cancelado" <?= $projeto['status'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                            <button type="submit" name="mudar_status" value="1">Alterar</button>
                        </form>
                        
                        <hr>
                        <h4>Atividades:</h4>
                        
                        <?php if (!empty($projeto['atividades'])): ?>
                            <ul>
                                <?php foreach ($projeto['atividades'] as $atividade): ?>
                                    <li>
                                        <div class="info-atividade">
                                            <strong><?= htmlspecialchars($atividade['titulo']) ?></strong>
                                            <?php if(!empty($atividade['descricao'])): ?>
                                                <p><?= nl2br(htmlspecialchars($atividade['descricao'])) ?></p>
                                            <?php endif; ?>
                                            <small><i>(<?= $atividade['status'] ?>)</i></small>
                                        </div>
                                        
                                        <div class="controles-atividade">
                                            <form action="index.php" method="POST" class="form-status-atividade">
                                                <input type="hidden" name="atividade_id" value="<?= $atividade['id'] ?>">
                                                <select name="novo_status_atividade">
                                                    <option value="Em Andamento" <?= $atividade['status'] === 'Em Andamento' ? 'selected' : '' ?>>Andamento</option>
                                                    <option value="Concluído" <?= $atividade['status'] === 'Concluído' ? 'selected' : '' ?>>Feito</option>
                                                    <option value="Cancelado" <?= $atividade['status'] === 'Cancelado' ? 'selected' : '' ?>>Cancel</option>
                                                </select>
                                                <button type="submit" name="mudar_status_atividade" value="1">OK</button>
                                            </form>
                                            <form action="index.php" method="POST" onsubmit="return confirm('Excluir?');">
                                                <input type="hidden" name="atividade_id" value="<?= $atividade['id'] ?>">
                                                <button type="submit" name="excluir_atividade" value="1" class="btn-excluir">X</button>
                                            </form>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <form action="index.php" method="POST" class="form-atividade">
                            <input type="hidden" name="projeto_id" value="<?= $projeto['id'] ?>">
                            <div class="inputs-atividade">
                                <input type="text" name="titulo_atividade" placeholder="Título da atividade" required>
                                <textarea name="desc_atividade" placeholder="Detalhes..." rows="1"></textarea>
                            </div>
                            <button type="submit" name="cadastrar_atividade" value="1">+</button>
                        </form>

                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="container-projetos container-arquivados">
            <h2>Projetos Arquivados</h2>
            <div id="painel-projetos-arquivados">
                <?php foreach ($projetos_arquivados as $projeto): ?>
                    <div class="projeto-card card-arquivado">
                        <h3><?= htmlspecialchars($projeto['nome']) ?></h3>
                        <?php if(!empty($projeto['descricao'])): ?>
                                <p class="descricao-projeto"><?= nl2br(htmlspecialchars($projeto['descricao'])) ?></p>
                        <?php endif; ?>
                        <span class="status" data-status="<?= $projeto['status'] ?>"><?= $projeto['status'] ?></span>
                        
                        <hr>
                        <h4>Atividades Realizadas:</h4>
                        <?php if (!empty($projeto['atividades'])): ?>
                            <ul class="lista-atividades-arquivadas">
                                <?php foreach ($projeto['atividades'] as $atividade): ?>
                                    <li>
                                        <div class="info-atividade">
                                            <strong><?= htmlspecialchars($atividade['titulo']) ?></strong>
                                            <small> - <?= $atividade['status'] ?></small>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                         <form action="index.php" method="POST" style="margin-top:10px" onsubmit="return confirm('Excluir permanentemente?');">
                            <input type="hidden" name="projeto_id" value="<?= $projeto['id'] ?>">
                            <button type="submit" name="excluir_projeto" value="1" class="btn-excluir">Apagar Projeto</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>