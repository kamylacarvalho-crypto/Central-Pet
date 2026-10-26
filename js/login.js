document.addEventListener('DOMContentLoaded', () => {
    inicializarLogin();
});


function inicializarLogin() {

    // =====================================================
    // ELEMENTOS
    // =====================================================

    const form = document.querySelector('form');

    const card = document.querySelector('section');

    const emailInput =
        document.getElementById('user-email');

    const senhaInput =
        document.getElementById('senha');

    const lembrarCheckbox =
        document.getElementById('lembrar');

    const submitBtn =
        document.getElementById('confirmarLogin');


    if (
        !form ||
        !card ||
        !emailInput ||
        !senhaInput ||
        !lembrarCheckbox ||
        !submitBtn
    ) {
        console.error(
            'Não foi possível inicializar o formulário de login.'
        );

        return;
    }


    // =====================================================
    // MOSTRAR / OCULTAR SENHA
    // =====================================================

    criarBotaoMostrarSenha(
        senhaInput
    );


    // =====================================================
    // RECUPERAR E-MAIL SALVO
    // =====================================================

    carregarEmailSalvo(
        emailInput,
        lembrarCheckbox
    );


    // =====================================================
    // LIMPAR ERROS ENQUANTO O USUÁRIO DIGITA
    // =====================================================

    emailInput.addEventListener(
        'input',
        () => {

            removerErro(emailInput);

            removerSucesso(emailInput);

        }
    );


    senhaInput.addEventListener(
        'input',
        () => {

            removerErro(senhaInput);

            removerSucesso(senhaInput);

        }
    );


    // =====================================================
    // SUBMISSÃO DO FORMULÁRIO
    // =====================================================

    form.addEventListener(
        'submit',
        event => {

            event.preventDefault();

            const email =
                emailInput.value.trim();

            const senha =
                senhaInput.value;

            let formularioValido = true;


            // ---------------------------------------------
            // VALIDAÇÃO DO E-MAIL
            // ---------------------------------------------

            if (!validarEmail(email)) {

                mostrarErro(
                    emailInput,
                    'Insira um e-mail válido.'
                );

                formularioValido = false;

            } else {

                marcarSucesso(
                    emailInput
                );

            }


            // ---------------------------------------------
            // VALIDAÇÃO DA SENHA
            // ---------------------------------------------

            if (!senha) {

                mostrarErro(
                    senhaInput,
                    'Digite sua senha.'
                );

                formularioValido = false;

            } else if (senha.length < 6) {

                mostrarErro(
                    senhaInput,
                    'A senha deve conter pelo menos 6 caracteres.'
                );

                formularioValido = false;

            } else {

                marcarSucesso(
                    senhaInput
                );

            }


            // =================================================
            // SE HOUVER ERRO
            // =================================================

            if (!formularioValido) {

                animarErroCard(card);

                return;
            }


            // =================================================
            // LEMBRAR E-MAIL
            // =================================================

            if (lembrarCheckbox.checked) {

                localStorage.setItem(
                    'centralpet_email',
                    email
                );

            } else {

                localStorage.removeItem(
                    'centralpet_email'
                );

            }


            // =================================================
            // BOTÃO DE CARREGAMENTO
            // =================================================

            iniciarCarregamento(
                submitBtn
            );


                        // =================================================
            // LOGIN REAL COM PHP E SUPABASE
            // =================================================

            fetch('../php/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    senha: senha
                })
            })
            .then(async resposta => {

                const dados = await resposta.json();

                if (!resposta.ok || dados.error || dados.erro || !dados.access_token) {
                    throw new Error(
                        dados.msg ||
                        dados.error_description ||
                        dados.error ||
                        dados.erro ||
                        'E-mail ou senha incorretos.'
                    );
                }

                // Recupera os dados da conta autenticada.
                const usuario = dados.user || {};
                const metadados = usuario.user_metadata || {};

                // Normaliza o tipo da conta.
                const tipoRecebido =
                    metadados.tipo ||
                    metadados.tipo_conta ||
                    'usuario';

                const tipoPerfil =
                    tipoRecebido === 'orgao_legal'
                        ? 'orgao'
                        : tipoRecebido;

                // Guarda os dados necessários para o perfil.
                localStorage.setItem(
                    'centralpet_perfil',
                    JSON.stringify({
                        id: usuario.id || '',
                        nome: metadados.nome || metadados.nome_completo || '',
                        email: usuario.email || email,
                        telefone: metadados.telefone || '',
                        cidade: metadados.cidade || '',
                        tipo: tipoPerfil,
                        foto: metadados.foto || ''
                    })
                );

                // Guarda o token para futuras requisições autenticadas.
                sessionStorage.setItem(
                    'centralpet_access_token',
                    dados.access_token
                );

                mostrarMensagemSucesso();

                setTimeout(() => {
                    window.location.href = 'CentralPet.html';
                }, 800);

            })
            .catch(erro => {

                console.error('Erro no login:', erro);

                mostrarErro(
                    senhaInput,
                    erro.message || 'Não foi possível realizar o login.'
                );

                animarErroCard(card);

            })
            .finally(() => {

                submitBtn.disabled = false;

                submitBtn.classList.remove(
                    'cursor-not-allowed',
                    'opacity-80'
                );

                submitBtn.innerHTML = 'Entrar';

            });

        }
    );

}


// =========================================================
// BOTÃO MOSTRAR / OCULTAR SENHA
// =========================================================

function criarBotaoMostrarSenha(senhaInput) {

    const wrapper =
        document.createElement('div');

    wrapper.className = `
        relative
        w-full
    `;

    senhaInput.parentNode.insertBefore(
        wrapper,
        senhaInput
    );

    wrapper.appendChild(
        senhaInput
    );


    const toggleBtn =
        document.createElement('button');

    toggleBtn.type = 'button';

    toggleBtn.className = `
        absolute
        right-3
        top-1/2
        -translate-y-1/2
        flex
        h-7
        w-7
        items-center
        justify-center
        rounded-md
        text-gray-500
        transition-all
        duration-200
        hover:bg-gray-100
        hover:text-pet-blue
        active:scale-95
    `;

    toggleBtn.setAttribute(
        'aria-label',
        'Mostrar senha'
    );

    toggleBtn.innerHTML = `
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
                d="M2.062 12.348a1 1 0 0 1 0-.696
                   10.75 10.75 0 0 1 19.876 0
                   1 1 0 0 1 0 .696
                   10.75 10.75 0 0 1-19.876 0"
            ></path>

            <circle
                cx="12"
                cy="12"
                r="3"
            ></circle>
        </svg>
    `;


    wrapper.appendChild(
        toggleBtn
    );


    toggleBtn.addEventListener(
        'click',
        () => {

            const senhaVisivel =
                senhaInput.type === 'text';


            if (senhaVisivel) {

                senhaInput.type =
                    'password';

                toggleBtn.setAttribute(
                    'aria-label',
                    'Mostrar senha'
                );

                toggleBtn.innerHTML = `
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
                            d="M2.062 12.348a1 1 0 0 1 0-.696
                               10.75 10.75 0 0 1 19.876 0
                               1 1 0 0 1 0 .696
                               10.75 10.75 0 0 1-19.876 0"
                        ></path>

                        <circle
                            cx="12"
                            cy="12"
                            r="3"
                        ></circle>
                    </svg>
                `;

            } else {

                senhaInput.type =
                    'text';

                toggleBtn.setAttribute(
                    'aria-label',
                    'Ocultar senha'
                );

                toggleBtn.innerHTML = `
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
                            d="M3 3l18 18"
                        ></path>

                        <path
                            d="M10.584 10.587
                               a2 2 0 0 0 2.829 2.828"
                        ></path>

                        <path
                            d="M9.88 4.24
                               A10.96 10.96 0 0 1 12 4
                               c5.5 0 9.5 5 10 8
                               a10.96 10.96 0 0 1-4.51 6.11"
                        ></path>

                        <path
                            d="M6.61 6.61
                               A10.96 10.96 0 0 0 2 12
                               c.5 3 4.5 8 10 8
                               1.5 0 2.86-.34 4.08-.93"
                        ></path>
                    </svg>
                `;
            }

        }
    );

}


// =========================================================
// CARREGAR E-MAIL SALVO
// =========================================================

function carregarEmailSalvo(
    emailInput,
    lembrarCheckbox
) {

    const emailSalvo =
        localStorage.getItem(
            'centralpet_email'
        );


    if (!emailSalvo) {
        return;
    }


    emailInput.value =
        emailSalvo;

    lembrarCheckbox.checked =
        true;

}


// =========================================================
// VALIDAR E-MAIL
// =========================================================

function validarEmail(email) {

    const expressao =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return expressao.test(email);

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


    const container =
        elemento.parentElement;


    elemento.classList.remove(
        'border-pet-blue',
        'border-green-600'
    );


    elemento.classList.add(
        'border-pet-red',
        'ring-2',
        'ring-pet-red/10'
    );


    const mensagemErro =
        document.createElement('p');


    mensagemErro.className = `
        mt-1
        flex
        items-center
        gap-1.5
        text-xs
        text-pet-red
        animate-fadeIn
    `;


    mensagemErro.innerHTML = `
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


    mensagemErro.dataset.erro =
        'true';


    container.appendChild(
        mensagemErro
    );

}


// =========================================================
// REMOVER ERRO
// =========================================================

function removerErro(elemento) {

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

function marcarSucesso(elemento) {

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

function removerSucesso(elemento) {

    elemento.classList.remove(
        'border-green-600',
        'ring-2',
        'ring-green-600/10'
    );

}


// =========================================================
// ANIMAÇÃO DE ERRO DO CARD
// =========================================================

function animarErroCard(card) {

    card.classList.remove(
        'animate-[shake_.4s_ease-in-out]'
    );

    void card.offsetWidth;

    card.classList.add(
        'animate-[shake_.4s_ease-in-out]'
    );

}


// =========================================================
// CARREGAMENTO DO BOTÃO
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

        <span class="ml-2">
            Entrando...
        </span>
    `;

}


// =========================================================
// FINALIZAR LOGIN
// =========================================================

function finalizarLogin(
    submitBtn
) {

    submitBtn.disabled =
        false;

    submitBtn.classList.remove(
        'cursor-not-allowed',
        'opacity-80'
    );


    submitBtn.innerHTML =
        'Entrar';


    mostrarMensagemSucesso();

}


// =========================================================
// MENSAGEM DE SUCESSO
// =========================================================

function mostrarMensagemSucesso() {

    const mensagem =
        document.createElement('div');


    mensagem.className = `
        fixed
        left-1/2
        top-5
        z-[9999]
        w-[calc(100%-40px)]
        max-w-[360px]
        -translate-x-1/2
        rounded-xl
        bg-pet-blue
        px-5
        py-4
        text-center
        text-sm
        font-medium
        text-white
        shadow-xl
        animate-fadeInDown
    `;


    mensagem.textContent =
        'Bem-vindo de volta à Central Pet!';


    document.body.appendChild(
        mensagem
    );


    setTimeout(() => {

        mensagem.classList.add(
            'opacity-0',
            'transition-opacity',
            'duration-300'
        );


        setTimeout(() => {
            mensagem.remove();
        }, 300);

    }, 2000);

}