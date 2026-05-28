<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$conn = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $dados = json_decode($json, true);

    if ($dados) {
        $nome      = $conn->real_escape_string($dados['nome']);
        $whatsapp  = $conn->real_escape_string($dados['whatsapp']);
        $endereco  = $conn->real_escape_string($dados['endereco']);
        $cep       = $conn->real_escape_string($dados['cep']);
        $pagamento = $conn->real_escape_string($dados['pagamento']);
        $resumo    = $conn->real_escape_string($dados['resumo_carrinho']);

        $sql = "INSERT INTO pedidos (cliente_nome, cliente_whatsapp, endereco, cep, forma_pagamento, itens_pedido, data_pedido) 
                VALUES ('$nome', '$whatsapp', '$endereco', '$cep', '$pagamento', '$resumo', NOW())";

        if ($conn->query($sql)) {
            $seu_numero = "5516981928132";
            
            $mensagem = "*NOVO PEDIDO - MDSURF* 🚀\n\n";
            $mensagem .= "*Cliente:* " . $dados['nome'] . "\n";
            $mensagem .= "*Endereço:* " . $dados['endereco'] . "\n";
            $mensagem .= "*Pagamento:* " . $dados['pagamento'] . "\n\n";
            $mensagem .= "*Itens:*\n" . $dados['resumo_carrinho'];

            $url_whatsapp = "https://wa.me/$seu_numero?text=" . urlencode($mensagem);

            echo json_encode([
                'sucesso' => true,
                'url_whatsapp' => $url_whatsapp
            ]);
        } else {
            echo json_encode(['sucesso' => false, 'erro' => 'Erro no Banco: ' . $conn->error]);
        }
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'Dados não recebidos']);
    }
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Método inválido']);
}
$conn->close();
?>