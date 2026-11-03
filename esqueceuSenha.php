<?php
// Recebe o e-mail preenchido no formulário
$email = $_POST['email'] ?? '';

// Verifica se o e-mail foi preenchido
if (!empty($email)) {
    
    // Redireciona diretamente para o painel principal CentralPet.html
    header("Location: CentralPet.html");
    exit();

} else {
    echo "<h1 style='color: red;'>Por favor, introduza o seu e-mail!</h1>";
    echo "<a href='esqueceuSenha.html'>Voltar</a>";
}
?>