
(async function iniciarRedefinicaoSenha() {

    const SUPABASE_URL =
        'https://wcjmjyqadcevocathzjc.supabase.co';

    const SUPABASE_ANON_KEY =
        'sb_publishable_Y9VlPHzQ-GIdjFzdgOFczw_h75NuF6g';

    const clienteRecuperacao = window.supabase.createClient(
        SUPABASE_URL,
        SUPABASE_ANON_KEY
    );

    const formulario = document.getElementById('formSenha');
    const mensagem = document.getElementById('mensagem');

    function mostrarMensagem(texto, sucesso = false) {
        mensagem.textContent = texto;
        mensagem.className = sucesso
            ? 'text-center mt-4 text-green-700 text-sm'
            : 'text-center mt-4 text-red-600 text-sm';
    }

    const { data, error } =
        await clienteRecuperacao.auth.getSession();

    if (error || !data.session) {
        mostrarMensagem(
            'Link inválido ou expirado. Solicite outro e-mail.'
        );
        return;
    }

    mostrarMensagem(
        'Link validado! Você já pode cadastrar sua nova senha.',
        true
    );

    formulario.addEventListener('submit', async function(event) {

        event.preventDefault();

        const senha =
            document.getElementById('novaSenha').value;

        const confirmar =
            document.getElementById('confirmarSenha').value;

        if (senha.length < 6) {
            mostrarMensagem('A senha deve ter pelo menos 6 caracteres.');
            return;
        }

        if (senha !== confirmar) {
            mostrarMensagem('As senhas não coincidem.');
            return;
        }

        const { error } =
            await clienteRecuperacao.auth.updateUser({
                password: senha
            });

        if (error) {
            mostrarMensagem('Erro ao atualizar: ' + error.message);
            return;
        }

        mostrarMensagem(
            'Senha alterada com sucesso! Você já pode fazer login.',
            true
        );

        formulario.reset();
    });

})();
