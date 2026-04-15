<?php
/* index.php — entry point, always redirect to landing page */
define('BASE_URL', '/macguyver_inventory/');
header('Location: ' . BASE_URL . 'landing.php');
exit();
