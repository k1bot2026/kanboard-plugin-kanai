// KanAI: show a "thinking" indicator while a (possibly slow) LLM request runs.
// The ask/skill forms POST and then redirect, so the page is busy until reload —
// surface that to the user instead of a frozen button.
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
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (! form || ! form.classList) {
            return;
        }
        if (form.classList.contains('kanai-ask') || form.classList.contains('kanai-skill-form')) {
            var root = document.querySelector('.kanai') || document.body;
            // Disable the skill buttons + the ask button so the action can't be double-fired.
            root.querySelectorAll('.kanai-skill, .kanai-ask button[type=submit]').forEach(function (b) {
                b.disabled = true;
            });
            root.classList.add('kanai-busy');
            showThinking(root);
        }
    });
})();
