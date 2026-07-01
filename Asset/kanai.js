// KanAI chat behaviour: AJAX ask (no page reload), thinking indicator,
// Enter-to-send, rename toggle, and auto-scroll.
(function () {
    function q(sel) { return document.querySelector(sel); }

    function setBusy(busy) {
        var root = q('.kanai') || document.body;
        root.classList.toggle('kanai-busy', busy);
        root.querySelectorAll('.kanai-skill, .kanai-ask button[type=submit]').forEach(function (b) {
            b.disabled = busy;
        });
        var box = q('.kanai-thinking');
        if (busy && ! box) {
            box = document.createElement('div');
            box.className = 'kanai-thinking';
            box.innerHTML = '<span class="kanai-spinner"></span><span>KanAI is thinking…</span>';
            var thread = q('.kanai-thread');
            if (thread) {
                thread.parentNode.insertBefore(box, thread.nextSibling);
            } else {
                root.appendChild(box);
            }
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (! busy && box) {
            box.remove();
        }
    }

    function removeWelcome() {
        var w = q('.kanai-welcome');
        if (w) { w.remove(); }
    }

    function appendOptimisticUserBubble(text) {
        var thread = q('#kanai-thread');
        if (! thread) { return null; }
        removeWelcome();
        var div = document.createElement('div');
        div.className = 'kanai-msg kanai-msg-user kanai-msg-optimistic';
        var textDiv = document.createElement('div');
        textDiv.className = 'kanai-msg-text';
        textDiv.textContent = text; // textContent = safe
        var body = document.createElement('div');
        body.className = 'kanai-msg-body';
        body.appendChild(textDiv);
        div.innerHTML = '<div class="kanai-msg-avatar"><span class="kanai-ai">🧑</span></div>';
        div.appendChild(body);
        thread.appendChild(div);
        div.scrollIntoView({ block: 'center' });
        return div;
    }

    function showErrorBubble(message) {
        var thread = q('#kanai-thread');
        if (! thread) { return; }
        var div = document.createElement('div');
        div.className = 'kanai-msg kanai-msg-assistant';
        var body = document.createElement('div');
        body.className = 'kanai-msg-body';
        var textDiv = document.createElement('div');
        textDiv.className = 'kanai-msg-text';
        textDiv.textContent = message;
        body.appendChild(textDiv);
        div.innerHTML = '<div class="kanai-msg-avatar"><span class="kanai-ai">⚠️</span></div>';
        div.appendChild(body);
        thread.appendChild(div);
    }

    function handleAjaxReply(data, optimistic) {
        if (optimistic) { optimistic.remove(); }
        removeWelcome();
        var thread = q('#kanai-thread');
        if (thread && data.messages_html) {
            thread.insertAdjacentHTML('beforeend', data.messages_html);
        }
        var region = q('#kanai-proposals-region');
        if (region && typeof data.proposals_html === 'string') {
            region.innerHTML = data.proposals_html;
        }
        if (data.conversation_id) {
            document.querySelectorAll('input[name=conversation_id]').forEach(function (i) {
                i.value = data.conversation_id;
            });
            // Reflect a newly created conversation in the sidebar list.
            var list = q('.kanai-convlist ul');
            if (list && ! list.querySelector('.kanai-conv.is-active') && data.conversation_title) {
                var li = document.createElement('li');
                li.className = 'kanai-conv is-active';
                var a = document.createElement('a');
                a.className = 'kanai-conv-link';
                a.href = window.location.pathname + window.location.search;
                a.textContent = data.conversation_title;
                li.appendChild(a);
                var empty = list.querySelector('.kanai-conv-empty');
                if (empty) { empty.remove(); }
                list.insertBefore(li, list.firstChild);
            }
        }
        var input = q('#kanai-input');
        if (input) { input.value = ''; }
        var msgs = document.querySelectorAll('#kanai-thread .kanai-msg');
        if (msgs.length) {
            msgs[msgs.length - 1].scrollIntoView({ block: 'center' });
        }
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (! form || ! form.classList) {
            return;
        }
        var isAsk = form.classList.contains('kanai-ask');
        var isSkill = form.classList.contains('kanai-skill-form');
        if (! isAsk && ! isSkill) {
            return;
        }
        if (! window.fetch) {
            setBusy(true); // classic submit still shows the indicator
            return;
        }
        e.preventDefault();

        var optimistic = null;
        if (isAsk) {
            var input = q('#kanai-input');
            var text = input ? input.value.trim() : '';
            if (text === '') { return; }
            optimistic = appendOptimisticUserBubble(text);
        }
        setBusy(true);

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (r) {
            return r.json();
        }).then(function (data) {
            setBusy(false);
            if (data.error && ! data.messages_html) {
                if (optimistic) { optimistic.remove(); }
                showErrorBubble(data.error);
                return;
            }
            handleAjaxReply(data, optimistic);
        }).catch(function () {
            // Network/parse problem: fall back to a classic full-page submit.
            setBusy(false);
            form.submit();
        });
    });

    // Enter sends, Shift+Enter inserts a newline (chat convention).
    document.addEventListener('keydown', function (e) {
        var el = e.target;
        if (el && el.id === 'kanai-input' && e.key === 'Enter' && ! e.shiftKey) {
            e.preventDefault();
            var form = el.closest('form');
            if (form && typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else if (form) {
                form.submit();
            }
        }
    });

    // Toggle the conversation rename form.
    document.addEventListener('click', function (e) {
        if (! e.target.closest) {
            return;
        }
        var toggle = e.target.closest('.kanai-rename-toggle');
        var cancel = e.target.closest('.kanai-rename-cancel');
        if (! toggle && ! cancel) {
            return;
        }
        e.preventDefault();
        var form = document.getElementById('kanai-rename-form');
        var title = document.getElementById('kanai-conv-title');
        var show = !! toggle;
        if (form) { form.style.display = show ? 'flex' : 'none'; }
        if (title) { title.style.display = show ? 'none' : ''; }
        var input = form && form.querySelector('input[name=title]');
        if (show && input) {
            input.focus();
            input.select();
        }
    });

    // On full page load, jump to the newest message.
    window.addEventListener('load', function () {
        var msgs = document.querySelectorAll('#kanai-thread .kanai-msg');
        if (msgs.length) {
            msgs[msgs.length - 1].scrollIntoView({ block: 'center' });
        }
    });
})();
