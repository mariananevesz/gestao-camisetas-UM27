<?php
require_once __DIR__ . '/config/bootstrap.php';
if (app_current_user()) app_redirect('view/gestaoPedidos.php');
app_redirect('view/login.php');
