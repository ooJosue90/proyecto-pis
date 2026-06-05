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

    function agregarMensaje(texto, tipo, esError) {
        const mensaje = document.createElement('div');
        mensaje.className = 'ada-message ada-message--' + tipo + (esError ? ' ada-message--error' : '');
        mensaje.textContent = texto;
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
            agregarMensaje(data.respuesta || 'ADA no recibió una respuesta válida.', 'bot', data.ok === false);
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
