<li <?= $this->app->checkMenuSelection('ConfigController', 'show', ['plugin' => 'KanAI']) ?>>
    <?= $this->url->link(t('KanAI'), 'ConfigController', 'show', ['plugin' => 'KanAI']) ?>
</li>
