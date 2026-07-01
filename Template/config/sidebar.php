<li <?= $this->app->checkMenuSelection('ConfigController', 'show', 'KanAI') ?>>
    <?= $this->url->link(t('KanAI'), 'ConfigController', 'show', ['plugin' => 'KanAI']) ?>
</li>
