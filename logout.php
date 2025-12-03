<?php
require 'config.php';

session_destroy();
header('Location: ' . SITE_URL . 'login.php');
exit;
?>
