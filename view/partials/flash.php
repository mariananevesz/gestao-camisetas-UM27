<?php foreach (obter_flashes() as $mensagem): ?>
    <div class="alerta alerta-<?= e($mensagem['tipo']) ?>" role="alert" data-alerta>
        <span><?= e($mensagem['mensagem']) ?></span>
        <button type="button" aria-label="Fechar mensagem" data-fechar-alerta>&times;</button>
    </div>
<?php endforeach; ?>
