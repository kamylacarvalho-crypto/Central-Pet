document.addEventListener('DOMContentLoaded', () => {
    inicializarLucide();
    configurarHeader();
    configurarMenuMobile();
    configurarRolagemSuave();
    configurarScrollSpy();
});

function inicializarLucide() {
    if (
        window.lucide &&
        typeof window.lucide.createIcons === 'function'
    ) {
        window.lucide.createIcons();
    }
}

function configurarHeader() {
    const header = document.getElementById('header');

    if (!header) return;

    function atualizarHeader() {
        if (window.scrollY > 20) {
            header.classList.add(
                'shadow-md',
                'border-orange-200'
            );

            header.classList.remove(
                'border-transparent'
            );
        } else {
            header.classList.remove(
                'shadow-md',
                'border-orange-200'
            );

            header.classList.add(
                'border-transparent'
            );
        }
    }

    window.addEventListener(
        'scroll',
        atualizarHeader,
        { passive: true }
    );

    atualizarHeader();
}

function configurarMenuMobile() {
    const botao = document.getElementById('btn-menu');
    const menu = document.getElementById('mobile-menu');

    if (!botao || !menu) return;

    botao.addEventListener('click', () => {
        const aberto =
            !menu.classList.contains('hidden');

        if (aberto) {
            fecharMenuMobile();
        } else {
            abrirMenuMobile();
        }
    });

    document
        .querySelectorAll('.mobile-link')
        .forEach(link => {
            link.addEventListener('click', () => {
                fecharMenuMobile();
            });
        });
}

function abrirMenuMobile() {
    const menu =
        document.getElementById('mobile-menu');

    const botao =
        document.getElementById('btn-menu');

    if (!menu) return;

    menu.classList.remove('hidden');
    menu.classList.add('animate-fadeInDown');

    if (botao) {
        botao.innerHTML = `
            <i
                data-lucide="x"
                class="h-5 w-5"
            ></i>
        `;

        botao.setAttribute(
            'aria-label',
            'Fechar menu'
        );

        inicializarLucide();
    }
}

function fecharMenuMobile() {
    const menu =
        document.getElementById('mobile-menu');

    const botao =
        document.getElementById('btn-menu');

    if (!menu) return;

    menu.classList.add('hidden');
    menu.classList.remove('animate-fadeInDown');

    if (botao) {
        botao.innerHTML = `
            <i
                data-lucide="menu"
                class="h-5 w-5"
            ></i>
        `;

        botao.setAttribute(
            'aria-label',
            'Abrir menu'
        );

        inicializarLucide();
    }
}

function configurarRolagemSuave() {
    const links =
        document.querySelectorAll(
            'a[href^="#"]'
        );

    links.forEach(link => {
        link.addEventListener('click', event => {

            const destino =
                link.getAttribute('href');

            if (
                !destino ||
                destino === '#'
            ) {
                return;
            }

            const elemento =
                document.querySelector(destino);

            if (!elemento) {
                return;
            }

            event.preventDefault();

            const header =
                document.getElementById('header');

            const alturaHeader =
                header
                    ? header.offsetHeight
                    : 0;

            const posicao =
                elemento.getBoundingClientRect().top +
                window.scrollY -
                alturaHeader;

            window.scrollTo({
                top: posicao,
                behavior: 'smooth'
            });

            fecharMenuMobile();
        });
    });
}

function configurarScrollSpy() {
    const secoes =
        document.querySelectorAll(
            'main > section[id]'
        );

    const links =
        document.querySelectorAll(
            '.nav-link'
        );

    if (
        !secoes.length ||
        !links.length
    ) {
        return;
    }

    const observer =
        new IntersectionObserver(
            entries => {
                entries.forEach(entry => {

                    if (!entry.isIntersecting) {
                        return;
                    }

                    const id =
                        entry.target.id;

                    links.forEach(link => {

                        const ativo =
                            link.dataset.nav === id;

                        link.classList.toggle(
                            'bg-[#FFEFE5]',
                            ativo
                        );

                        link.classList.toggle(
                            'text-pet-red',
                            ativo
                        );

                        link.classList.toggle(
                            'text-gray-700',
                            !ativo
                        );

                    });

                });
            },
            {
                root: null,
                rootMargin: '-20% 0px -65% 0px',
                threshold: 0
            }
        );

    secoes.forEach(secao => {
        observer.observe(secao);
    });
}

window.addEventListener('resize', () => {

    if (window.innerWidth >= 1024) {
        fecharMenuMobile();
    }

});