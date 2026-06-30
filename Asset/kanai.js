document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form && form.classList &&
        (form.classList.contains('kanai-ask') || form.classList.contains('kanai-skill-form'))) {
        var btn = form.querySelector('button[type="submit"]');
        if (btn) { btn.disabled = true; btn.dataset.kanaiBusy = '1'; btn.textContent = 'KanAI…'; }
        form.classList.add('kanai-busy');
    }
});
