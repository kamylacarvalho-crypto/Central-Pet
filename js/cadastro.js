document.addEventListener('DOMContentLoaded', () => {
    inicializarCadastro();
});


function inicializarCadastro() {

    const form =
        document.getElementById('formCadastro');

    const card =
        document.getElementById('cardCadastro');

    const emailInput =
        document.getElementById('user-email');

    const diaInput =
        document.getElementById('nascimento-dia');

    const mesInput =
        document.getElementById('nascimento-mes');

    const anoInput =
        document.getElementById('nascimento-ano');

    const senhaInput =
        document.getElementById('senha');

    const confirmarSenhaInput =
        document.getElementById('confirmarSenha');

    const submitBtn =
        document.getElementById('confirmarCadastro');

    const barraForcaSenha =
        document.getElementById(
            'barraForcaSenha'
        );

    const textoForcaSenha =
        document.getElementById(
            'textoForcaSenha'
        );

    const forcaSenha =
        document.getElementById(
            'forcaSenha'
        );


    if (
        !form ||
        !card ||
        !emailInput ||
        !diaInput ||
        !mesInput ||
        !anoInput ||
        !senhaInput ||
        !confirmarSenhaInput ||
        !submitBtn
    ) {

        console.error(
            'Não foi possível inicializar o formulário de cadastro.'
        );

        return;
    }


    // =====================================================
    // BOTÕES PARA MOSTRAR / OCULTAR SENHA
    // =====================================================

    criarBotaoMostrarSenha(
        senhaInput,
        'Mostrar senha'
    );

    criarBotaoMostrarSenha(
        confirmarSenhaInput,
        'Mostrar confirmação da senha'
    );


    // =====================================================
    // CAMPOS DE DATA
    // =====================================================

    configurarCamposData(
        diaInput,
        mesInput,
        anoInput
    );


    // =====================================================
    // MEDIDOR DE SENHA
    // =====================================================

    senhaInput.addEventListener(
        'input',
        () => {

            atualizarForcaSenha(
                senhaInput.value,
                forcaSenha,
                barraForcaSenha,
                textoForcaSenha
            );

            removerErro(
                senhaInput
            );

            removerSucesso(
                senhaInput
            );

            if (
                confirmarSenhaInput.value
            ) {

                validarConfirmacaoSenha(
                    senhaInput,
                    confirmarSenhaInput
                );

            }

        }
    );


    // =====================================================
    // E-MAIL
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
    // CONFIRMAÇÃO DA SENHA
    // =====================================================

    confirmarSenhaInput.addEventListener(
        'input',
        () => {

            validarConfirmacaoSenha(
                senhaInput,
                confirmarSenhaInput
            );

        }
    );


    // =====================================================
    // SUBMISSÃO
    // =====================================================

    form.addEventListener(
        'submit',
        event => {

            event.preventDefault();

            let valido = true;


            // ---------------------------------------------
            // E-MAIL
            // ---------------------------------------------

            const email =
                emailInput.value.trim();

            if (!validarEmail(email)) {

                mostrarErro(
                    emailInput,
                    'Digite um e-mail válido.'
                );

                valido = false;

            } else {

                marcarSucesso(
                    emailInput
                );

            }


            // ---------------------------------------------
            // TIPO DE CONTA
            // ---------------------------------------------

            const tipoConta =
                document.querySelector(
                    'input[name="tipo_conta"]:checked'
                );


            if (!tipoConta) {

                mostrarErroNoTipoDeConta();

                valido = false;

            } else {

                removerErroTipoDeConta();

            }


            // ---------------------------------------------
            // DATA DE NASCIMENTO
            // ---------------------------------------------

            const resultadoData =
                validarDataEIdade(
                    diaInput.value,
                    mesInput.value,
                    anoInput.value
                );


            if (!resultadoData.valido) {

                mostrarErro(
                    diaInput,
                    resultadoData.mensagem
                );

                removerSucesso(mesInput);
                removerSucesso(anoInput);

                valido = false;

            } else {

                marcarSucesso(diaInput);
                marcarSucesso(mesInput);
                marcarSucesso(anoInput);

            }


            // ---------------------------------------------
            // SENHA
            // ---------------------------------------------

            const senha =
                senhaInput.value;


            if (!senha) {

                mostrarErro(
                    senhaInput,
                    'Crie uma senha.'
                );

                valido = false;

            } else if (senha.length < 6) {

                mostrarErro(
                    senhaInput,
                    'A senha deve ter pelo menos 6 caracteres.'
                );

                valido = false;

            } else {

                marcarSucesso(
                    senhaInput
                );

            }


            // ---------------------------------------------
            // CONFIRMAÇÃO
            // ---------------------------------------------

            const senhaConfirmada =
                confirmarSenhaInput.value;


            if (!senhaConfirmada) {

                mostrarErro(
                    confirmarSenhaInput,
                    'Confirme sua senha.'
                );

                valido = false;

            } else if (
                senhaConfirmada !== senha
            ) {

                mostrarErro(
                    confirmarSenhaInput,
                    'As senhas não coincidem.'
                );

                valido = false;

            } else {

                marcarSucesso(
                    confirmarSenhaInput
                );

            }


            // =================================================
            // FORMULÁRIO INVÁLIDO
            // =================================================

            if (!valido) {

                animarErroCard(
                    card
                );

                return;
            }


            // =================================================
            // BOTÃO DE CARREGAMENTO
            // =================================================

            // =================================================
// CADASTRO REAL PELO PHP + SUPABASE
// =================================================

iniciarCarregamento(submitBtn);

fetch("../php/cadastro.php", {
    method: "POST",

    headers: {
        "Content-Type": "application/json"
    },

    body: JSON.stringify({
        email: emailInput.value.trim(),
        senha: senhaInput.value,
        tipo_conta: tipoConta.value
    })
})

.then(async resposta => {

    const resultado = await resposta.json();

    if (!resposta.ok || !resultado.sucesso) {
        throw new Error(
            resultado.erro || "Não foi possível realizar o cadastro."
        );
    }

    return resultado;

})

.then(resultado => {

    mostrarMensagemSucesso();

    console.log(
        "Tipo de conta cadastrado:",
        resultado.tipo_conta
    );

    setTimeout(() => {

        window.location.href = "login.html?cadastro=sucesso";

    }, 1200);

})

.catch(erro => {

    console.error("Erro no cadastro:", erro);

    alert(erro.message);

})

.finally(() => {

    submitBtn.disabled = false;

    submitBtn.classList.remove(
        "cursor-not-allowed",
        "opacity-80"
    );

    submitBtn.textContent = "Cadastrar";

});


            // =================================================
            // SIMULAÇÃO DO CADASTRO
            // =================================================

            setTimeout(() => {

                finalizarCadastro(
                    form,
                    submitBtn,
                    barraForcaSenha,
                    textoForcaSenha,
                    forcaSenha
                );

            }, 1200);

        }
    );

}


// =========================================================
// MOSTRAR / OCULTAR SENHA
// =========================================================

function criarBotaoMostrarSenha(
    input,
    ariaLabel
) {

    const wrapper =
        document.createElement('div');

    wrapper.className = `
        relative
        w-full
    `;


    input.parentNode.insertBefore(
        wrapper,
        input
    );


    wrapper.appendChild(
        input
    );


    const button =
        document.createElement('button');

    button.type =
        'button';


    button.className = `
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


    button.setAttribute(
        'aria-label',
        ariaLabel
    );


    button.innerHTML =
        criarIconeOlho();


    wrapper.appendChild(
        button
    );


    button.addEventListener(
        'click',
        () => {

            const mostrando =
                input.type === 'text';


            if (mostrando) {

                input.type =
                    'password';

                button.setAttribute(
                    'aria-label',
                    ariaLabel
                );

                button.innerHTML =
                    criarIconeOlho();

            } else {

                input.type =
                    'text';

                button.setAttribute(
                    'aria-label',
                    'Ocultar senha'
                );

                button.innerHTML =
                    criarIconeOlhoFechado();

            }

        }
    );

}


// =========================================================
// ÍCONE OLHO
// =========================================================

function criarIconeOlho() {

    return `
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

}


function criarIconeOlhoFechado() {

    return `
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


// =========================================================
// CAMPOS DE DATA
// =========================================================

function configurarCamposData(
    diaInput,
    mesInput,
    anoInput
) {

    const campos = [
        diaInput,
        mesInput,
        anoInput
    ];


    campos.forEach(
        (input, index) => {

            input.addEventListener(
                'input',
                () => {

                    input.value =
                        input.value.replace(
                            /\D/g,
                            ''
                        );


                    removerErro(
                        input
                    );

                    removerSucesso(
                        input
                    );


                    if (
                        input.value.length >=
                        Number(input.maxLength) &&
                        index <
                        campos.length - 1
                    ) {

                        campos[index + 1]
                            .focus();

                    }

                }
            );


            input.addEventListener(
                'keydown',
                event => {

                    if (
                        event.key ===
                            'Backspace' &&
                        input.value === '' &&
                        index > 0
                    ) {

                        campos[index - 1]
                            .focus();

                    }

                }
            );

        }
    );

}


// =========================================================
// VALIDAR DATA E IDADE
// =========================================================

function validarDataEIdade(
    dia,
    mes,
    ano
) {

    const d =
        Number.parseInt(
            dia,
            10
        );

    const m =
        Number.parseInt(
            mes,
            10
        );

    const a =
        Number.parseInt(
            ano,
            10
        );


    if (
        !dia ||
        !mes ||
        !ano ||
        Number.isNaN(d) ||
        Number.isNaN(m) ||
        Number.isNaN(a)
    ) {

        return {
            valido: false,
            mensagem:
                'Preencha a data de nascimento completa.'
        };

    }


    if (
        d < 1 ||
        d > 31 ||
        m < 1 ||
        m > 12 ||
        a < 1900
    ) {

        return {
            valido: false,
            mensagem:
                'Insira uma data de nascimento válida.'
        };

    }


    const dataNascimento =
        new Date(
            a,
            m - 1,
            d
        );


    if (
        dataNascimento.getFullYear() !== a ||
        dataNascimento.getMonth() !== m - 1 ||
        dataNascimento.getDate() !== d
    ) {

        return {
            valido: false,
            mensagem:
                'Insira uma data válida.'
        };

    }


    const hoje =
        new Date();


    if (
        dataNascimento >
        hoje
    ) {

        return {
            valido: false,
            mensagem:
                'A data não pode ser no futuro.'
        };

    }


    let idade =
        hoje.getFullYear() -
        dataNascimento.getFullYear();


    const diferencaMes =
        hoje.getMonth() -
        dataNascimento.getMonth();


    if (
        diferencaMes < 0 ||
        (
            diferencaMes === 0 &&
            hoje.getDate() <
            dataNascimento.getDate()
        )
    ) {

        idade--;

    }


    if (idade < 18) {

        return {
            valido: false,
            mensagem:
                'Você precisa ter pelo menos 18 anos para se cadastrar.'
        };

    }


    return {
        valido: true
    };

}


// =========================================================
// VALIDAR E-MAIL
// =========================================================

function validarEmail(email) {

    const expressao =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return expressao.test(
        email
    );

}


// =========================================================
// VALIDAR CONFIRMAÇÃO
// =========================================================

function validarConfirmacaoSenha(
    senhaInput,
    confirmarSenhaInput
) {

    const senha =
        senhaInput.value;

    const confirmacao =
        confirmarSenhaInput.value;


    removerErro(
        confirmarSenhaInput
    );

    removerSucesso(
        confirmarSenhaInput
    );


    if (!confirmacao) {
        return;
    }


    if (
        confirmacao !== senha
    ) {

        mostrarErro(
            confirmarSenhaInput,
            'As senhas não coincidem.'
        );

    } else {

        marcarSucesso(
            confirmarSenhaInput
        );

    }

}


// =========================================================
// FORÇA DA SENHA
// =========================================================

function atualizarForcaSenha(
    senha,
    container,
    barra,
    texto
) {

    if (!senha) {

        container.classList.add(
            'hidden'
        );

        barra.style.width =
            '0%';

        texto.textContent =
            '';

        return;
    }


    container.classList.remove(
        'hidden'
    );


    let pontuacao = 0;


    if (
        senha.length >= 6
    ) {
        pontuacao += 25;
    }


    if (
        /[A-Z]/.test(senha)
    ) {
        pontuacao += 25;
    }


    if (
        /[0-9]/.test(senha)
    ) {
        pontuacao += 25;
    }


    if (
        /[^A-Za-z0-9]/.test(senha)
    ) {
        pontuacao += 25;
    }


    barra.style.width =
        `${pontuacao}%`;


    barra.classList.remove(
        'bg-red-500',
        'bg-orange-500',
        'bg-green-600'
    );


    if (pontuacao <= 25) {

        barra.classList.add(
            'bg-red-500'
        );

        texto.textContent =
            'Senha fraca';

        texto.className = `
            mt-1
            text-right
            text-[11px]
            text-red-600
        `;

    } else if (
        pontuacao <= 50
    ) {

        barra.classList.add(
            'bg-orange-500'
        );

        texto.textContent =
            'Senha média';

        texto.className = `
            mt-1
            text-right
            text-[11px]
            text-orange-600
        `;

    } else {

        barra.classList.add(
            'bg-green-600'
        );

        texto.textContent =
            'Senha forte';

        texto.className = `
            mt-1
            text-right
            text-[11px]
            text-green-600
        `;

    }

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
        'border-green-600',
        'ring-green-600/10'
    );


    elemento.classList.add(
        'border-pet-red',
        'ring-2',
        'ring-pet-red/10'
    );


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


    elemento.classList.remove(
        'border-pet-blue'
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
// ERRO DO TIPO DE CONTA
// =========================================================

function mostrarErroNoTipoDeConta() {

    const radios =
        document.querySelectorAll(
            'input[name="tipo_conta"]'
        );


    const container =
        radios[0]?.closest(
            'div.mb-\\[17px\\]'
        );


    if (!container) {
        return;
    }


    removerErroTipoDeConta();


    const erro =
        document.createElement(
            'p'
        );


    erro.dataset.erroTipo =
        'true';


    erro.className = `
        mt-1
        text-xs
        text-pet-red
        animate-fadeIn
    `;


    erro.textContent =
        'Selecione o tipo de conta.';


    container.appendChild(
        erro
    );

}


function removerErroTipoDeConta() {

    const erro =
        document.querySelector(
            '[data-erro-tipo="true"]'
        );


    if (erro) {
        erro.remove();
    }

}


// =========================================================
// ANIMAR ERRO DO CARD
// =========================================================

function animarErroCard(
    card
) {

    card.classList.remove(
        'animate-shake'
    );


    void card.offsetWidth;


    card.classList.add(
        'animate-shake'
    );

}


// =========================================================
// CARREGAMENTO
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
            Cadastrando...
        </span>
    `;

}




```javascript
async function finalizarCadastro(
    form,
    submitBtn,
    barraForcaSenha,
    textoForcaSenha,
    forcaSenha
) {
    const emailInput = document.getElementById('user-email');
    const senhaInput = document.getElementById('senha');

    const tipoContaSelecionado = document.querySelector(
        'input[name="tipo_conta"]:checked'
    );

    if (!tipoContaSelecionado) {
        alert('Selecione o tipo de conta.');
        return;
    }

    const dadosCadastro = {
        email: emailInput.value.trim(),
        senha: senhaInput.value,
        tipo_conta: tipoContaSelecionado.value
    };

    try {
        submitBtn.disabled = true;

        const resposta = await fetch('../php/cadastro.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dadosCadastro)
        });

        const dados = await resposta.json();

        if (!resposta.ok || !dados.sucesso) {
            throw new Error(
                dados.erro || 'Não foi possível realizar o cadastro.'
            );
        }

        mostrarMensagemSucesso();

        setTimeout(() => {
            window.location.href = 'login.html';
        }, 1000);

    } catch (erro) {
        console.error('Erro no cadastro:', erro);

        alert(erro.message || 'Erro ao realizar o cadastro.');

    } finally {
        submitBtn.disabled = false;
    }
}
```

// =========================================================
// MENSAGEM DE SUCESSO
// =========================================================

function mostrarMensagemSucesso() {

    const mensagem =
        document.createElement(
            'div'
        );


    mensagem.className = `
        fixed
        left-1/2
        top-5
        z-[9999]
        w-[calc(100%-40px)]
        max-w-[380px]
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
        'Cadastro realizado com sucesso!';


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

    }, 1800);

}