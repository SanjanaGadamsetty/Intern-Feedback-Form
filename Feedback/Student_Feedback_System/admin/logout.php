<?php
require_once '../config/db.php';
startSecureSession();
session_unset();
session_destroy();
header('Location: login.php?msg=logged_out');
exit();
?>
