document.addEventListener('DOMContentLoaded', () => {

    const btnVoltar =
        document.getElementById('btn-voltar');


    if (!btnVoltar) {
        return;
    }


    btnVoltar.addEventListener(
        'click',
        () => {

            /*
             * Verifica se existe uma página anterior
             * dentro do histórico do navegador.
             */

            if (window.history.length > 1) {

                window.history.back();

                return;
            }


            /*
             * Se não existir histórico,
             * volta para a página inicial.
             */

            window.location.href =
                'inicio.html';

        }
    );

});