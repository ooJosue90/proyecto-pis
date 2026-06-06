(function (window, document) {
    'use strict';

    const chat = document.querySelector('[data-ada-chat]');
    if (!chat) return;

    const toggle = chat.querySelector('[data-ada-toggle]');
    const closeButton = chat.querySelector('[data-ada-close]');
    const form = chat.querySelector('[data-ada-form]');
    const input = chat.querySelector('[data-ada-input]');
    const messages = chat.querySelector('[data-ada-messages]');
    const sendButton = chat.querySelector('[data-ada-send]');
    const endpoint = chat.dataset.endpoint || 'asistente/asistente_virtual.php';
    let typingNode = null;

    function abrirChat() {
        chat.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        setTimeout(() => input.focus(), 120);
    }

    function cerrarChat() {
        chat.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    function agregarTextoConFormato(contenedor, texto) {
        const fragmento = document.createDocumentFragment();
        const patron = /\*\*(.+?)\*\*/g;
        let posicion = 0;
        let coincidencia;

        while ((coincidencia = patron.exec(texto)) !== null) {
            fragmento.appendChild(document.createTextNode(texto.slice(posicion, coincidencia.index)));

            const destacado = document.createElement('strong');
            destacado.textContent = coincidencia[1];
            fragmento.appendChild(destacado);
            posicion = coincidencia.index + coincidencia[0].length;
        }

        fragmento.appendChild(document.createTextNode(
            texto.slice(posicion).replace(/\*\*/g, '')
        ));
        contenedor.appendChild(fragmento);
    }

    function renderizarRespuesta(contenedor, texto) {
        const lineas = texto.replace(/\r\n?/g, '\n').split('\n');
        let lista = null;

        function cerrarLista() {
            lista = null;
        }

        lineas.forEach(function (linea) {
            const contenido = linea.trim();

            if (!contenido) {
                cerrarLista();
                return;
            }

            const titulo = contenido.match(/^#{2,4}\s+(.+)$/);
            if (titulo) {
                cerrarLista();
                const encabezado = document.createElement('h4');
                agregarTextoConFormato(encabezado, titulo[1]);
                contenedor.appendChild(encabezado);
                return;
            }

            const elementoLista = contenido.match(/^(?:[-*•]|\d+\.)\s+(.+)$/);
            if (elementoLista) {
                if (!lista) {
                    lista = document.createElement('ul');
                    contenedor.appendChild(lista);
                }

                const elemento = document.createElement('li');
                agregarTextoConFormato(elemento, elementoLista[1]);
                lista.appendChild(elemento);
                return;
            }

            cerrarLista();
            const parrafo = document.createElement('p');
            agregarTextoConFormato(parrafo, contenido.replace(/^#{1,6}\s*/, ''));
            contenedor.appendChild(parrafo);
        });
    }

    function agregarEnlaces(contenedor, enlaces) {
        if (!Array.isArray(enlaces) || enlaces.length === 0) return;

        const acciones = document.createElement('div');
        acciones.className = 'ada-message__links';
        acciones.setAttribute('aria-label', 'Módulos relacionados');

        enlaces.forEach(function (enlace) {
            if (!enlace || typeof enlace.href !== 'string' || typeof enlace.label !== 'string') return;

            const accion = document.createElement('a');
            accion.className = 'ada-message__link';
            accion.href = enlace.href;

            if (typeof enlace.icon === 'string' && /^fas? fa-[a-z0-9-]+$/.test(enlace.icon)) {
                const icono = document.createElement('i');
                icono.className = enlace.icon;
                icono.setAttribute('aria-hidden', 'true');
                accion.appendChild(icono);
            }

            const etiqueta = document.createElement('span');
            etiqueta.textContent = enlace.label;
            accion.appendChild(etiqueta);
            acciones.appendChild(accion);
        });

        if (acciones.childElementCount > 0) {
            contenedor.appendChild(acciones);
        }
    }

    function agregarMensaje(texto, tipo, esError, enlaces) {
        const mensaje = document.createElement('div');
        mensaje.className = 'ada-message ada-message--' + tipo + (esError ? ' ada-message--error' : '');

        if (tipo === 'bot' && !esError) {
            renderizarRespuesta(mensaje, texto);
            agregarEnlaces(mensaje, enlaces);
        } else {
            mensaje.textContent = texto;
        }

        messages.appendChild(mensaje);

        requestAnimationFrame(function () {
            const esRespuestaLarga = tipo === 'bot' && mensaje.scrollHeight > messages.clientHeight * 0.7;

            if (esRespuestaLarga) {
                messages.scrollTop = Math.max(0, mensaje.offsetTop - messages.offsetTop - 12);
                return;
            }

            messages.scrollTop = messages.scrollHeight;
        });
    }

    function mostrarEscribiendo() {
        ocultarEscribiendo();
        typingNode = document.createElement('div');
        typingNode.className = 'ada-typing';
        typingNode.innerHTML = '<span></span><span></span><span></span> ADA está escribiendo...';
        messages.appendChild(typingNode);
        messages.scrollTop = messages.scrollHeight;
    }

    function ocultarEscribiendo() {
        if (!typingNode) return;
        typingNode.remove();
        typingNode = null;
    }

    function setCargando(cargando) {
        sendButton.disabled = cargando;
        input.disabled = cargando;
        sendButton.innerHTML = cargando
            ? '<i class="fas fa-circle-notch fa-spin"></i>'
            : '<i class="fas fa-paper-plane"></i>';
    }

    function navegarADestino(destino) {
        if (!destino || typeof destino.href !== 'string') return;

        window.setTimeout(function () {
            window.location.assign(destino.href);
        }, 650);
    }

    // Envia la pregunta al backend PHP y pinta la respuesta JSON.
    async function enviarPregunta(pregunta) {
        agregarMensaje(pregunta, 'user', false);
        setCargando(true);
        mostrarEscribiendo();

        try {
            const respuesta = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ pregunta }),
            });

            const data = await respuesta.json();

            if (!respuesta.ok) {
                ocultarEscribiendo();
                agregarMensaje(data.respuesta || 'El servidor no pudo procesar la pregunta.', 'bot', true);
                return;
            }

            ocultarEscribiendo();
            agregarMensaje(
                data.respuesta || 'ADA no recibió una respuesta válida.',
                'bot',
                data.ok === false,
                data.enlaces
            );
            navegarADestino(data.navegar_a);
        } catch (error) {
            ocultarEscribiendo();
            agregarMensaje('No pude comunicarme con el servidor. Verifica Apache y vuelve a intentarlo.', 'bot', true);
        } finally {
            setCargando(false);
            input.focus();
        }
    }

    toggle.addEventListener('click', function () {
        if (chat.classList.contains('is-open')) {
            cerrarChat();
        } else {
            abrirChat();
        }
    });

    closeButton.addEventListener('click', cerrarChat);

    chat.querySelectorAll('[data-ada-quick]').forEach((button) => {
        button.addEventListener('click', function () {
            abrirChat();
            enviarPregunta(button.dataset.adaQuick || button.textContent.trim());
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const pregunta = input.value.trim();
        if (!pregunta) return;

        input.value = '';
        enviarPregunta(pregunta);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') cerrarChat();
    });
})(window, document);
