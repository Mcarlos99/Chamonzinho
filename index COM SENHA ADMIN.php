<?php
require_once 'config.php';

$message = '';
$messageType = '';

// Processar formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validar e sanitizar dados
        $nome = sanitizeInput($_POST['nome']);
        $cidade = sanitizeInput($_POST['cidade']);
        $cargo = sanitizeInput($_POST['cargo']);
        $telefone = sanitizeInput($_POST['telefone']);
        $data_nascimento_input = sanitizeInput($_POST['nascimento']);
        $observacoes = sanitizeInput($_POST['observacoes']);
        
        // Converter data do formato DD/MM/AAAA para YYYY-MM-DD (se necessário)
        $data_nascimento = $data_nascimento_input;
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data_nascimento_input)) {
            $partes = explode('/', $data_nascimento_input);
            $data_nascimento = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
        }
        
        // Validações
        if (empty($nome) || empty($cidade) || empty($cargo) || empty($telefone) || empty($data_nascimento)) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
        }
        
        if (!preg_match('/^\(\d{2}\) \d{4,5}-\d{4}$/', $telefone)) {
            throw new Exception('Formato de telefone inválido.');
        }
        
        // Conectar ao banco e inserir dados
        $db = new Database();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->prepare("INSERT INTO cadastros (nome, cidade, cargo, telefone, data_nascimento, observacoes, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $result = $stmt->execute([
            $nome,
            $cidade,
            $cargo,
            $telefone,
            $data_nascimento,
            $observacoes,
            getClientIP()
        ]);
        
        if ($result) {
            $message = 'Cadastro realizado com sucesso!';
            $messageType = 'success';
            
            // Log da ação
            logActivity('cadastro', "Novo cadastro: $nome de $cidade");
        } else {
            throw new Exception('Erro ao salvar cadastro.');
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #003366, #0066cc);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #003366, #0066cc);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.6; }
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .form-container {
            padding: 40px;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #003366;
            font-weight: bold;
            font-size: 1.1em;
            transition: color 0.3s ease;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0066cc;
            background: white;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.2);
            transform: translateY(-2px);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #003366, #0066cc);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 102, 204, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 102, 204, 0.4);
            background: linear-gradient(135deg, #0066cc, #0080ff);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .form-container {
                padding: 20px;
            }
        }

        .admin-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .admin-link a {
            color: #999;
            text-decoration: none;
            font-size: 1.2em;
            padding: 10px;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .admin-link a:hover {
            color: #003366;
            transform: scale(1.1);
        }

        /* Modal de Acesso Admin */
        .modal-admin {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-admin.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-admin-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .modal-admin h3 {
            color: #003366;
            margin-bottom: 20px;
        }

        .modal-admin input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 1em;
        }

        .modal-admin .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-admin .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-admin-enter {
            background: #003366;
            color: white;
        }

        .btn-admin-cancel {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Chamonzinho</h1>
            <p>Deputado Estadual pelo Pará - MDB</p>
        </div>
        
        <div class="form-container">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome Completo *</label>
                        <input type="text" id="nome" name="nome" required maxlength="255">
                    </div>
                    
                    <div class="form-group">
                        <label for="cidade">Cidade *</label>
                        <input type="text" id="cidade" name="cidade" required maxlength="100">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cargo">Cargo *</label>
                        <input type="text" id="cargo" name="cargo" required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Telefone *</label>
                        <input type="tel" id="telefone" name="telefone" required maxlength="15" placeholder="(00) 00000-0000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="nascimento">Data de Nascimento *</label>
                    <input type="text" id="nascimento" name="nascimento" required placeholder="DD/MM/AAAA" pattern="\d{2}/\d{2}/\d{4}" maxlength="10">
                </div>
                
                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" placeholder="Digite suas observações aqui..."></textarea>
                </div>
                
                <div class="form-group" style="text-align: center;">
                    <button type="submit" class="btn-submit">Cadastrar</button>
                </div>
            </form>
            
            <div class="admin-link">
                <a href="#" onclick="mostrarAcessoAdmin()" style="color: #666; text-decoration: none; font-size: 0.8em;">⚙️</a>
            </div>
        </div>
    </div>

    <!-- Modal de Acesso Administrativo -->
    <div id="modalAdmin" class="modal-admin">
        <div class="modal-admin-content">
            <h3>🔒 Acesso Administrativo</h3>
            <p style="color: #666; margin-bottom: 20px;">Digite a senha de acesso:</p>
            <input type="password" id="senhaAdmin" placeholder="Digite a senha..." onkeypress="if(event.key==='Enter') verificarSenhaAdmin()">
            <div class="btn-group">
                <button class="btn btn-admin-enter" onclick="verificarSenhaAdmin()">Entrar</button>
                <button class="btn btn-admin-cancel" onclick="fecharModalAdmin()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Rodapé do Desenvolvedor -->
    <footer style="background: #f8f9fa; border-top: 1px solid #dee2e6; padding: 20px 0; margin-top: 40px;">
        <div style="max-width: 800px; margin: 0 auto; text-align: center; padding: 0 20px;">
            <p style="margin: 0; color: #6c757d; font-size: 0.9em;">
                <strong>Sistema desenvolvido por:</strong><br>
                <a href="https://wa.me/5594981709809?text=Olá, vim através do sistema do Deputado Chamonzinho e gostaria de mais informações sobre desenvolvimento de sistemas." 
                   target="_blank" 
                   style="color: #25D366; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; margin-top: 5px;">
                    📱 Mauro Carlos - (94) 98170-9809
                </a>
            </p>
            <p style="margin: 8px 0 0 0; color: #adb5bd; font-size: 0.8em;">
                Desenvolvimento de sistemas web e aplicativos
            </p>
        </div>
    </footer>

    <script>
        // Função para mostrar modal de acesso admin
        function mostrarAcessoAdmin() {
            document.getElementById('modalAdmin').classList.add('show');
            document.getElementById('senhaAdmin').focus();
        }

        // Função para fechar modal admin
        function fecharModalAdmin() {
            document.getElementById('modalAdmin').classList.remove('show');
            document.getElementById('senhaAdmin').value = '';
        }

        // Função para verificar senha admin
        async function verificarSenhaAdmin() {
            const senha = document.getElementById('senhaAdmin').value.trim();
            
            if (senha === '') {
                alert('⚠️ Por favor, digite uma senha.');
                return;
            }

            try {
                const response = await fetch('verificar_acesso.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ senha: senha })
                });

                const resultado = await response.json();

                if (resultado.sucesso) {
                    // Acesso autorizado
                    fecharModalAdmin();
                    window.location.href = resultado.redirect;
                } else {
                    // Acesso negado
                    if (resultado.bloqueado) {
                        alert('🚫 ' + resultado.erro);
                        fecharModalAdmin();
                    } else {
                        alert('❌ ' + resultado.erro + 
                              (resultado.tentativas_restantes ? 
                               `\n\nTentativas restantes: ${resultado.tentativas_restantes}` : ''));
                        document.getElementById('senhaAdmin').value = '';
                        document.getElementById('senhaAdmin').focus();
                    }
                }
            } catch (error) {
                console.error('Erro na verificação:', error);
                alert('❌ Erro de conexão. Tente novamente.');
            }
        }

        // Fechar modal clicando fora
        document.getElementById('modalAdmin').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalAdmin();
            }
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalAdmin();
            }
        });

        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });

        // Máscara para data de nascimento
        document.getElementById('nascimento').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '$1/$2');
            value = value.replace(/(\d{2})\/(\d{2})(\d)/, '$1/$2/$3');
            e.target.value = value;
        });

        // Validação da data no envio do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const dataInput = document.getElementById('nascimento');
            const dataValue = dataInput.value;
            
            // Verificar formato DD/MM/AAAA
            const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
            const match = dataValue.match(regex);
            
            if (!match) {
                e.preventDefault();
                alert('Por favor, digite a data no formato DD/MM/AAAA');
                dataInput.focus();
                return false;
            }
            
            const dia = parseInt(match[1]);
            const mes = parseInt(match[2]);
            const ano = parseInt(match[3]);
            
            // Validações básicas
            if (mes < 1 || mes > 12) {
                e.preventDefault();
                alert('Mês inválido. Digite um valor entre 01 e 12.');
                dataInput.focus();
                return false;
            }
            
            if (dia < 1 || dia > 31) {
                e.preventDefault();
                alert('Dia inválido. Digite um valor entre 01 e 31.');
                dataInput.focus();
                return false;
            }
            
            if (ano < 1900 || ano > new Date().getFullYear()) {
                e.preventDefault();
                alert('Ano inválido. Digite um ano válido.');
                dataInput.focus();
                return false;
            }
            
            // Verificar se a data é válida
            const dataObj = new Date(ano, mes - 1, dia);
            if (dataObj.getDate() !== dia || dataObj.getMonth() !== mes - 1 || dataObj.getFullYear() !== ano) {
                e.preventDefault();
                alert('Data inválida. Verifique o dia, mês e ano.');
                dataInput.focus();
                return false;
            }
            
            // Converter para formato YYYY-MM-DD para o servidor
            const dataFormatada = ano + '-' + mes.toString().padStart(2, '0') + '-' + dia.toString().padStart(2, '0');
            dataInput.value = dataFormatada;
        });

        // Auto-hide message
        <?php if ($message && $messageType == 'success'): ?>
        setTimeout(function() {
            document.querySelector('.message').style.display = 'none';
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>