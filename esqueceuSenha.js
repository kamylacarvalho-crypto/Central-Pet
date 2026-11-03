document.addEventListener('DOMContentLoaded', () => {
    inicializarRecuperacaoSenha();
});


function inicializarRecuperacaoSenha() {

    // =====================================================
    // ELEMENTOS
    // =====================================================

    const form =
        document.getElementById('formRecuperacao');

    const card =
        document.getElementById('cardRecuperacao');

    const emailInput =
        document.getElementById('user-email');

    const submitBtn =
        document.getElementById(
            'confirmarRecuperacao'
        );


    if (
        !form ||
        !card ||
        !emailInput ||
        !submitBtn
    ) {

        console.error(
            'Não foi possível inicializar a recuperação de senha.'
        );

        return;
    }


    // =====================================================
    // LIMPAR ERRO AO DIGITAR
    // =====================================================

    emailInput.addEventListener(
        'input',
        () => {

            removerErro(
                emailInput
            );

            removerSucesso(
                emailInput
            );

        }
    );


    // =====================================================
    // VALIDAR AO SAIR DO CAMPO
    // =====================================================

    emailInput.addEventListener(
        'blur',
        () => {

            const email =
                emailInput.value.trim();


            if (!email) {
                return;
            }


            if (!validarEmail(email)) {

                mostrarErro(
                    emailInput,
                    'Insira um e-mail válido.'
                );

            } else {

                marcarSucesso(
                    emailInput
                );

            }

        }
    );


    // =====================================================
    // ENVIO DO FORMULÁRIO
    // =====================================================

    form.addEventListener(
        'submit',
        event => {

            event.preventDefault();


            const email =
                emailInput.value.trim();


            // ---------------------------------------------
            // VALIDAÇÃO
            // ---------------------------------------------

            removerErro(
                emailInput
            );


            removerSucesso(
                emailInput
            );


            if (!email) {

                mostrarErro(
                    emailInput,
                    'Digite seu e-mail para continuar.'
                );

                animarErroCard(
                    card
                );

                emailInput.focus();

                return;
            }


            if (!validarEmail(email)) {

                mostrarErro(
                    emailInput,
                    'Insira um e-mail válido para continuar.'
                );

                animarErroCard(
                    card
                );

                emailInput.focus();

                return;
            }


            // ---------------------------------------------
            // CAMPO VÁLIDO
            // ---------------------------------------------

            marcarSucesso(
                emailInput
            );


            // ---------------------------------------------
            // CARREGAMENTO
            // ---------------------------------------------

            iniciarCarregamento(
                submitBtn
            );


            // ---------------------------------------------
            // SIMULAÇÃO
            // ---------------------------------------------

            setTimeout(() => {

                mostrarSucesso(
                    card,
                    form
                );

            }, 1200);

        }
    );

}


// =========================================================
// VALIDAR E-MAIL
// =========================================================

function validarEmail(email) {

    const regex =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


    return regex.test(
        email
    );

}


// =========================================================
// MOSTRAR ERRO
// =========================================================

function mostrarErro(
    elemento,
    mensagem
) {

    removerErro(
        elemento
    );


    elemento.classList.remove(
        'border-green-600',
        'ring-green-600/10'
    );


    elemento.classList.add(
        'border-pet-red',
        'ring-2',
        'ring-pet-red/10'
    );


    const container =
        elemento.parentElement;


    const erro =
        document.createElement(
            'p'
        );


    erro.dataset.erro =
        'true';


    erro.className = `
        mt-1
        flex
        items-center
        gap-1.5
        text-xs
        text-pet-red
        animate-fadeIn
    `;


    erro.innerHTML = `
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="h-3.5 w-3.5 shrink-0"
        >

            <circle
                cx="12"
                cy="12"
                r="10"
            ></circle>

            <line
                x1="12"
                y1="8"
                x2="12"
                y2="12"
            ></line>

            <line
                x1="12"
                y1="16"
                x2="12.01"
                y2="16"
            ></line>

        </svg>

        <span>
            ${mensagem}
        </span>
    `;


    container.appendChild(
        erro
    );

}


// =========================================================
// REMOVER ERRO
// =========================================================

function removerErro(
    elemento
) {

    const container =
        elemento.parentElement;


    const erro =
        container.querySelector(
            '[data-erro="true"]'
        );


    if (erro) {
        erro.remove();
    }


    elemento.classList.remove(
        'border-pet-red',
        'ring-2',
        'ring-pet-red/10'
    );

}


// =========================================================
// MARCAR SUCESSO
// =========================================================

function marcarSucesso(
    elemento
) {

    removerErro(
        elemento
    );


    elemento.classList.add(
        'border-green-600',
        'ring-2',
        'ring-green-600/10'
    );

}


// =========================================================
// REMOVER SUCESSO
// =========================================================

function removerSucesso(
    elemento
) {

    elemento.classList.remove(
        'border-green-600',
        'ring-2',
        'ring-green-600/10'
    );

}


// =========================================================
// ANIMAR CARD QUANDO HÁ ERRO
// =========================================================

function animarErroCard(
    card
) {

    card.classList.remove(
        'animate-[shake_.4s_ease-in-out]'
    );


    void card.offsetWidth;


    card.classList.add(
        'animate-[shake_.4s_ease-in-out]'
    );

}


// =========================================================
// BOTÃO DE CARREGAMENTO
// =========================================================

function iniciarCarregamento(
    submitBtn
) {

    submitBtn.disabled =
        true;


    submitBtn.classList.add(
        'cursor-not-allowed',
        'opacity-80'
    );


    submitBtn.innerHTML = `
        <span
            class="
                inline-block
                h-4
                w-4
                animate-spin
                rounded-full
                border-2
                border-white/30
                border-t-white
            "
        ></span>

        <span>
            Enviando...
        </span>
    `;

}


// =========================================================
// TELA DE SUCESSO
// =========================================================

function mostrarSucesso(
    card,form
) {

    card.innerHTML = `
        <div
            class="
                text-center
                animate-fadeIn
            "
        >

            <div
                class="
                    mx-auto
                    flex
                    h-16
                    w-16
                    items-center
                    justify-center
                    rounded-full
                    bg-green-100
                    text-green-600
                "
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="h-8 w-8"
                >

                    <path
                        d="M20 6 9 17l-5-5"
                    ></path>

                </svg>

            </div>


            <h2
                class="
                    mt-6
                    text-2xl
                    font-black
                    text-pet-blue
                "
            >
                Solicitação enviada!
            </h2>


            <p
                class="
                    mt-4
                    text-sm
                    leading-7
                    text-gray-600
                "
            >
                Se o endereço informado estiver cadastrado
                em nossa base, você receberá as instruções
                para redefinir sua senha.
            </p>


            <p
                class="
                    mt-3
                    text-xs
                    leading-5
                    text-gray-500
                "
            >
                Verifique também sua caixa de spam ou lixo
                eletrônico.
            </p>


            <a
                href="login.html"
                class="
                    mt-7
                    inline-flex
                    items-center
                    gap-2
                    rounded-xl
                    bg-pet-red
                    px-6
                    py-3
                    text-sm
                    font-bold
                    text-white
                    shadow-lg
                    shadow-red-500/30
                    transition-all
                    duration-300
                    hover:-translate-y-1
                    hover:bg-red-700
                    active:scale-95
                "
            >
                Voltar para o Login

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="h-4 w-4"
                >

                    <path
                        d="m9 18 6-6-6-6"
                    ></path>

                </svg>

            </a>

        </div>
    `;
    setTimeout(() => {
        mostrarSucesso(card, form);
    }, 1200);



}