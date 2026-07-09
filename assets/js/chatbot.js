(function () {
    'use strict';

    if (typeof rcbChatbot === 'undefined') {
        return;
    }

    const root = document.getElementById('rcb-chatbot');
    if (!root) {
        return;
    }

    if (rcbChatbot.primaryColor) {
        root.style.setProperty('--rcb-primary', rcbChatbot.primaryColor);
    }

    const toggle = document.getElementById('rcb-toggle');
    const panel = document.getElementById('rcb-panel');
    const greeting = document.getElementById('rcb-greeting');
    const closeBtn = document.getElementById('rcb-close');
    const messagesEl = document.getElementById('rcb-messages');
    const form = document.getElementById('rcb-form');
    const input = document.getElementById('rcb-input');
    const sendBtn = form.querySelector('.rcb-send');

    let history = [];
    let isOpen = false;
    let isSending = false;
    let greetingTimer = null;

    function dismissGreeting() {
        if (!greeting || greeting.hidden) {
            return;
        }
        greeting.hidden = true;
        if (greetingTimer) {
            clearTimeout(greetingTimer);
            greetingTimer = null;
        }
    }

    if (greeting) {
        greetingTimer = setTimeout(dismissGreeting, 8000);
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function appendMessage(role, text) {
        const el = document.createElement('div');
        el.className = 'rcb-message rcb-message--' + (role === 'user' ? 'user' : 'bot');
        el.textContent = text;
        messagesEl.appendChild(el);
        scrollToBottom();
        return el;
    }

    function appendTypingIndicator() {
        const el = document.createElement('div');
        el.className = 'rcb-message rcb-message--bot rcb-message--typing';
        el.setAttribute('aria-label', 'Assistant is typing');

        const dots = document.createElement('span');
        dots.className = 'rcb-typing';
        dots.innerHTML = '<span></span><span></span><span></span>';

        el.appendChild(dots);
        messagesEl.appendChild(el);
        scrollToBottom();
        return el;
    }

    function setOpen(open) {
        if (open === isOpen) {
            return;
        }

        isOpen = open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        root.classList.toggle('rcb-chatbot--open', open);

        if (open) {
            dismissGreeting();
            panel.hidden = false;
            toggle.classList.add('rcb-toggle--active');

            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    panel.classList.add('rcb-panel--open');
                });
            });

            if (messagesEl.children.length === 0 && rcbChatbot.welcomeMessage) {
                appendMessage('bot', rcbChatbot.welcomeMessage);
            }

            setTimeout(function () {
                input.focus();
            }, 350);
        } else {
            panel.classList.remove('rcb-panel--open');
            toggle.classList.remove('rcb-toggle--active');

            function onTransitionEnd(e) {
                if (e.target !== panel || isOpen) {
                    return;
                }
                panel.hidden = true;
                panel.removeEventListener('transitionend', onTransitionEnd);
            }

            panel.addEventListener('transitionend', onTransitionEnd);

            setTimeout(function () {
                if (!isOpen && !panel.classList.contains('rcb-panel--open')) {
                    panel.hidden = true;
                }
            }, 400);
        }
    }

    async function sendMessage(message) {
        if (isSending) {
            return;
        }

        isSending = true;
        sendBtn.disabled = true;

        appendMessage('user', message);
        const typingEl = appendTypingIndicator();

        try {
            // DEBUG: Log the endpoint being called
            console.log('[RCB] Sending to endpoint:', rcbChatbot.restUrl);
            console.log('[RCB] Message:', message);
            console.log('[RCB] History items:', history.length);

            const response = await fetch(rcbChatbot.restUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': rcbChatbot.nonce,
                },
                body: JSON.stringify({
                    message: message,
                    history: history,
                }),
            });

            const data = await response.json();
            typingEl.remove();

            // DEBUG: Log the response
            console.log('[RCB] Response status:', response.status);
            console.log('[RCB] Response data:', data);

            if (!response.ok) {
                const errorText = data.message || 'Sorry, something went wrong. Please try again.';
                appendMessage('bot', errorText);
                return;
            }

            const reply = data.reply || '';
            appendMessage('bot', reply);

            history.push({ role: 'user', content: message });
            history.push({ role: 'assistant', content: reply });

            if (history.length > 20) {
                history = history.slice(-20);
            }
        } catch (err) {
            typingEl.remove();
            console.error('[RCB] Error:', err);
            appendMessage('bot', 'Sorry, I could not connect. Please check your connection and try again.');
        } finally {
            isSending = false;
            sendBtn.disabled = false;
            input.focus();
        }
    }

    toggle.addEventListener('click', function () {
        setOpen(!isOpen);
    });

    closeBtn.addEventListener('click', function () {
        setOpen(false);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const message = input.value.trim();
        if (!message) {
            return;
        }
        input.value = '';
        sendMessage(message);
    });
    const joinchat = document.querySelector('.joinchat');

if (joinchat) {

    joinchat.addEventListener('click', function (e) {

        if (e.target.closest('.joinchat__button')) {
            root.style.display = 'none';
        }

        if (e.target.closest('.joinchat__close')) {
            root.style.display = '';
        }

    });

}
})();

