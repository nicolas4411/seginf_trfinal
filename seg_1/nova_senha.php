<?php
session_start(); // Inicia a sessão para usar variáveis de sessão

require_once 'db.php'; // Conexão com MySQLi
require_once 'mail.php';

/**
 * Atualiza a senha do usuário no banco de dados.
 *
 * @param mysqli $mysqli Conexão MySQLi
 * @param string $email E-mail do usuário
 * @param string $senha_hash Senha já criptografada
 * @return bool True se a atualização foi bem-sucedida, False caso contrário
 */
function atualizarSenha($mysqli, $email, $senha_hash)
{
    $updateQuery = "UPDATE usuarios SET senha = ?, codigo_recuperacao = NULL, expiracao_codigo = NULL WHERE email = ?";
    $stmt = $mysqli->prepare($updateQuery);

    if ($stmt) {
        $stmt->bind_param("ss", $senha_hash, $email);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim($_POST['codigo']);
    $nova_senha = trim($_POST['nova_senha']);
    $email = trim($_POST['email']);

    if (empty($codigo) || empty($nova_senha) || empty($email)) {
        $_SESSION['erro'] = "Todos os campos são obrigatórios.";
        header('Location: nova_senha.php');
        exit();
    }

    $query = "SELECT * FROM usuarios WHERE email = ? AND codigo_recuperacao = ? AND expiracao_codigo > NOW()";
    $stmt = $mysqli->prepare($query);

    if ($stmt) {
        $stmt->bind_param("ss", $email, $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
    } else {
        $_SESSION['erro'] = "Erro ao preparar a consulta.";
        header('Location: nova_senha.php');
        exit();
    }

    if ($usuario) {
        // Debug: log do hash da senha antiga e senha nova
        error_log("Senha hash armazenada: " . $usuario['senha']);
        error_log("Nova senha digitada: " . $nova_senha);

        // Verifica se a nova senha é igual à antiga
        if (password_verify($nova_senha, $usuario['senha'])) {
            error_log("A nova senha é igual à antiga.");
            $_SESSION['erro'] = "A nova senha não pode ser a mesma que a antiga.";
            header('Location: nova_senha.php');
            exit();
        }

        // Cria o hash da nova senha
        $senha_hash = password_hash($nova_senha, PASSWORD_BCRYPT);

        if (atualizarSenha($mysqli, $email, $senha_hash)) {
            $_SESSION['sucesso'] = "Senha alterada com sucesso! Agora você pode fazer login.";
            header('Location: login.php');
            exit();
        } else {
            $_SESSION['erro'] = "Erro ao atualizar a senha. Tente novamente.";
            header('Location: nova_senha.php');
            exit();
        }
    } else {
        $_SESSION['erro'] = "Código inválido ou expirado.";
        header('Location: nova_senha.php');
        exit();
    }
}
?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - Novo Código</title>
</head>

<body>
    <form action="nova_senha.php" method="post">
        <label for="email">E-mail:</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <label for="codigo">Código de Recuperação:</label><br>
        <input type="text" id="codigo" name="codigo" required><br><br>

        <label for="nova_senha">nova Senha:</label><br>
        <input type="password" id="nova_senha" name="nova_senha" required oninput="verificarSenha();"><br><br>
        <label for="confirm_senha">Confirme a Senha:</label><br>
        <input type="password" id="confirm_senha" name="confirm_senha" required oninput="verificarSenha();"><br>

        <input type="submit" value="Alterar Senha">
    </form>

    <form action="login.php" method="get">
        <button type="submit">login</button>
    </form>
</body>

</html>