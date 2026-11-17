<?php
// Recebe os dados do formulário
$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

// Verifica se os campos foram preenchidos
if (!empty($email) && !empty($senha)) {
    // Redireciona diretamente para a CentralPet.html
    header("Location: CentralPet.html");
    exit();
} else {
    // Se algum campo estiver vazio, volta para a tela de login
    header("Location: login.html");
    exit();
}
?>