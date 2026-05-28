<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

ini_set('post_max_size', '10M');
ini_set('upload_max_filesize', '10M');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$host   = '127.0.0.1';
$user   = 'root';
$pass   = 'root';
$dbname = 'mdsurf';

$conn = new mysqli($host, $user, $pass);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(json_encode(["erro" => "Falha na conexão: " . $conn->connect_error]));
}

$conn->query("SET GLOBAL max_allowed_packet = 16777216");
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
$conn->select_db($dbname);

$conn->query("CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255),
    preco DECIMAL(10,2),
    categoria VARCHAR(100),
    descricao TEXT,
    foto MEDIUMTEXT,
    tamanhos VARCHAR(255)
)");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM produtos ORDER BY id DESC");
    $produtos = [];
    while ($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }
    echo json_encode($produtos, JSON_UNESCAPED_UNICODE);
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados     = json_decode(file_get_contents("php://input"), true);
    $nome      = $dados['nome'] ?? '';
    $preco     = floatval($dados['preco'] ?? 0);
    $categoria = $dados['categoria'] ?? '';
    $descricao = $dados['descricao'] ?? '';
    $foto      = $dados['foto'] ?? '';
    $tamanhos  = implode(',', $dados['tamanhos'] ?? []);

    if (!$nome || !$preco || !$categoria) {
        die(json_encode(["erro" => "Campos obrigatórios faltando."]));
    }

    $stmt = $conn->prepare("INSERT INTO produtos (nome, preco, categoria, descricao, foto, tamanhos) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssss", $nome, $preco, $categoria, $descricao, $foto, $tamanhos);

    if ($stmt->execute()) {
        echo json_encode(["sucesso" => true, "id" => $stmt->insert_id]);
    } else {
        echo json_encode(["erro" => $stmt->error]);
    }
    $stmt->close();
}

elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if ($conn->query("DELETE FROM produtos WHERE id = $id")) {
        echo json_encode(["sucesso" => true]);
    } else {
        echo json_encode(["erro" => $conn->error]);
    }
}

$conn->close();
?>