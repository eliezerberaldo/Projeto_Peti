<?php
$dbFile = __DIR__ . '/peti.db';

try {
    $db = new PDO('sqlite:' . $dbFile);

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sqlProjetos = "
    CREATE TABLE IF NOT EXISTS projetos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        status TEXT DEFAULT 'Planejado' 
             CHECK(status IN ('Planejado', 'Em Andamento', 'Concluído', 'Cancelado'))
    );";

    $sqlAtividades = "
    CREATE TABLE IF NOT EXISTS atividades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        descricao TEXT NOT NULL,
        status TEXT DEFAULT 'Em Andamento' 
             CHECK(status IN ('Em Andamento', 'Concluído', 'Cancelado')),
        projeto_id INTEGER,
        FOREIGN KEY (projeto_id) REFERENCES projetos (id) ON DELETE CASCADE
    );";

    $db->exec($sqlProjetos);
    $db->exec($sqlAtividades);

} catch (PDOException $e) {
    die("Erro ao conectar ou configurar o banco de dados: " . $e->getMessage());
}
