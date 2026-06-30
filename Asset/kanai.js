// KanAI chat behaviour: thinking indicator during the (possibly slow) LLM call,
// Enter-to-send on the composer, and auto-scroll to the latest message.
(function () {
    function showThinking(root) {
        if (document.querySelector('.kanai-thinking')) {
            return;
        }
        var box = document.createElement('div');
        box.className = 'kanai-thinking';
        box.innerHTML = '<span class="kanai-spinner"></span><span>KanAI is thinking… a local model can take up to a minute.</span>';
        var thread = root.querySelector('.kanai-thread');
        if (thread) {
            thread.parentNode.insertBefore(box, thread.nextSibling);
        } else {
            root.appendChild(box);
        }
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (! form || ! form.classList) {
            return;
        }
        if (form.classList.contains('kanai-ask') || form.classList.contains('kanai-skill-form')) {
            var root = document.querySelector('.kanai') || document.body;
            root.querySelectorAll('.kanai-skill, .kanai-ask button[type=submit]').forEach(function (b) {
                b.disabled = true;
            });
            root.classList.add('kanai-busy');
            showThinking(root);
        }
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

    // After a reply loads, jump to the newest message.
    window.addEventListener('load', function () {
        var msgs = document.querySelectorAll('.kanai-thread .kanai-msg');
        if (msgs.length) {
            msgs[msgs.length - 1].scrollIntoView({ block: 'center' });
        }
    });
})();
