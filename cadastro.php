<?php
// Recebe os dados enviados pelo formulário
$email      = $_POST['email'] ?? '';
$tipo_conta = $_POST['tipo_conta'] ?? '';
$dia        = $_POST['nascimento_dia'] ?? '';
$mes        = $_POST['nascimento_mes'] ?? '';
$ano        = $_POST['nascimento_ano'] ?? '';
$senha      = $_POST['senha'] ?? '';

// Verifica se os campos obrigatórios foram preenchidos
if (!empty($email) && !empty($senha)) {
    
    // Redireciona diretamente para o painel principal
    header("Location: CentralPet.html");
    exit();

} else {
    echo "<h1 style='color: red;'>Por favor, preencha todos os campos do cadastro!</h1>";
    echo "<a href='cadastro.html'>Voltar para o cadastro</a>";
}
?>