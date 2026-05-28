<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

ini_set('post_max_size', '20M');
ini_set('upload_max_filesize', '20M');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$host   = '127.0.0.1';
$user   = 'root';
$pass   = 'root';
$dbname = 'mdsurf';

$conn = new mysqli('127.0.0.1', $user, $pass, '', 3306);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(json_encode(["erro" => "Falha na conexão: " . $conn->connect_error]));
}

$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
$conn->select_db($dbname);

$conn->query("CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255),
    preco DECIMAL(10,2),
    categoria VARCHAR(100),
    descricao TEXT,
    foto TEXT,
    tamanhos VARCHAR(255)
)");

// Pasta onde as imagens serão salvas
$pastaFotos = __DIR__ . '/fotos/';
if (!is_dir($pastaFotos)) {
    mkdir($pastaFotos, 0777, true);
}

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
    $fotoInput = $dados['foto'] ?? '';
    $tamanhos  = implode(',', $dados['tamanhos'] ?? []);

    if (!$nome || !$preco || !$categoria) {
        die(json_encode(["erro" => "Campos obrigatórios faltando."]));
    }

    // Se for base64, salva como arquivo
    $fotoFinal = '';
    if (strpos($fotoInput, 'data:image') === 0) {
        // Extrai extensão e dados
        preg_match('/data:image\/(\w+);base64,/', $fotoInput, $matches);
        $ext      = $matches[1] ?? 'jpg';
        $base64   = preg_replace('/data:image\/\w+;base64,/', '', $fotoInput);
        $binario  = base64_decode($base64);
        $nomeArq  = uniqid('foto_') . '.' . $ext;
        $caminho  = $pastaFotos . $nomeArq;

        if (file_put_contents($caminho, $binario)) {
            $protocolo = isset($_SERVER['HTTPS']) ? 'https' : 'http';
            $host_url  = $_SERVER['HTTP_HOST'];
            $fotoFinal = $protocolo . '://' . $host_url . '/fotos/' . $nomeArq;
        }
    } else {
        // É uma URL normal, guarda direto
        $fotoFinal = $fotoInput;
    }

    $stmt = $conn->prepare("INSERT INTO produtos (nome, preco, categoria, descricao, foto, tamanhos) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssss", $nome, $preco, $categoria, $descricao, $fotoFinal, $tamanhos);

    if ($stmt->execute()) {
        echo json_encode(["sucesso" => true, "id" => $stmt->insert_id]);
    } else {
        echo json_encode(["erro" => $stmt->error]);
    }
    $stmt->close();
}

elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);

    // Busca foto antes de deletar para remover o arquivo
    $res = $conn->query("SELECT foto FROM produtos WHERE id = $id");
    if ($res && $row = $res->fetch_assoc()) {
        $foto = $row['foto'];
        // Se for arquivo local, deleta
        if (strpos($foto, '/fotos/') !== false) {
            $nomeArq = basename($foto);
            $arquivoLocal = $pastaFotos . $nomeArq;
            if (file_exists($arquivoLocal)) {
                unlink($arquivoLocal);
            }
        }
    }

    if ($conn->query("DELETE FROM produtos WHERE id = $id")) {
        echo json_encode(["sucesso" => true]);
    } else {
        echo json_encode(["erro" => $conn->error]);
    }
}

$conn->close();
?>