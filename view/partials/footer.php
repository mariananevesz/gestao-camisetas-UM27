        </main>
    </div>
</div>
<script src="<?= e(app_url('js/app.js')) ?>"></script>
<script src="<?= e(app_url('js/modules.js')) ?>"></script>
<?php foreach (($scriptsAdicionais ?? []) as $script): ?>
<script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
