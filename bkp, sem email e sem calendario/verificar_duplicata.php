<?php
// verificar_duplicata.php - Sistema para verificar e prevenir cadastros duplicados

header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$nome = sanitizeInput($input['nome'] ?? '');
$telefone = sanitizeInput($input['telefone'] ?? '');
$data_nascimento = sanitizeInput($input['data_nascimento'] ?? '');

if (empty($nome) || empty($telefone)) {
    echo json_encode(['duplicata' => false]);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Verificar duplicatas por diferentes critérios
    $duplicatas = [];
    
    // 1. Verificar por telefone (mais confiável)
    $stmt = $pdo->prepare("SELECT id, nome, cidade, cargo, data_cadastro, status FROM cadastros WHERE telefone = ? AND status = 'ativo'");
    $stmt->execute([$telefone]);
    $cadastro_telefone = $stmt->fetch();
    
    if ($cadastro_telefone) {
        $duplicatas[] = [
            'tipo' => 'telefone',
            'criterio' => 'Mesmo telefone',
            'cadastro' => $cadastro_telefone,
            'confiabilidade' => 'alta'
        ];
    }
    
    // 2. Verificar por nome completo + data nascimento
    if (!empty($data_nascimento)) {
        $stmt = $pdo->prepare("SELECT id, nome, cidade, cargo, telefone, data_cadastro, status FROM cadastros WHERE nome = ? AND data_nascimento = ? AND status = 'ativo'");
        $stmt->execute([$nome, $data_nascimento]);
        $cadastro_nome_data = $stmt->fetch();
        
        if ($cadastro_nome_data) {
            $duplicatas[] = [
                'tipo' => 'nome_data',
                'criterio' => 'Mesmo nome e data de nascimento',
                'cadastro' => $cadastro_nome_data,
                'confiabilidade' => 'alta'
            ];
        }
    }
    
    // 3. Verificar nomes similares (soundex para nomes parecidos)
    $stmt = $pdo->prepare("SELECT id, nome, cidade, cargo, telefone, data_cadastro, status FROM cadastros WHERE SOUNDEX(nome) = SOUNDEX(?) AND status = 'ativo' AND nome != ?");
    $stmt->execute([$nome, $nome]);
    $cadastros_similares = $stmt->fetchAll();
    
    foreach ($cadastros_similares as $cadastro_similar) {
        // Calcular similaridade usando Levenshtein
        $similaridade = 1 - (levenshtein(strtolower($nome), strtolower($cadastro_similar['nome'])) / max(strlen($nome), strlen($cadastro_similar['nome'])));
        
        if ($similaridade > 0.8) { // 80% de similaridade
            $duplicatas[] = [
                'tipo' => 'nome_similar',
                'criterio' => 'Nome muito similar (' . round($similaridade * 100) . '% parecido)',
                'cadastro' => $cadastro_similar,
                'confiabilidade' => 'media',
                'similaridade' => $similaridade
            ];
        }
    }
    
    // 4. Verificar por nome + cidade (menos confiável)
    if (!empty($input['cidade'])) {
        $cidade = sanitizeInput($input['cidade']);
        $stmt = $pdo->prepare("SELECT id, nome, cidade, cargo, telefone, data_cadastro, status FROM cadastros WHERE nome = ? AND cidade = ? AND status = 'ativo'");
        $stmt->execute([$nome, $cidade]);
        $cadastro_nome_cidade = $stmt->fetch();
        
        if ($cadastro_nome_cidade && !$cadastro_telefone && !$cadastro_nome_data) {
            $duplicatas[] = [
                'tipo' => 'nome_cidade',
                'criterio' => 'Mesmo nome e cidade',
                'cadastro' => $cadastro_nome_cidade,
                'confiabilidade' => 'baixa'
            ];
        }
    }
    
    if (!empty($duplicatas)) {
        // Ordenar por confiabilidade (alta -> media -> baixa)
        usort($duplicatas, function($a, $b) {
            $ordem = ['alta' => 3, 'media' => 2, 'baixa' => 1];
            return $ordem[$b['confiabilidade']] - $ordem[$a['confiabilidade']];
        });
        
        echo json_encode([
            'duplicata' => true,
            'duplicatas' => $duplicatas,
            'total' => count($duplicatas)
        ]);
    } else {
        echo json_encode(['duplicata' => false]);
    }
    
} catch (Exception $e) {
    error_log("Erro ao verificar duplicata: " . $e->getMessage());
    echo json_encode(['duplicata' => false, 'erro' => 'Erro interno']);
}
?>