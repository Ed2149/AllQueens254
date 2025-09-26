<?php
session_start();
session_destroy();
header("Location: ../frontend/creator_login.php");
exit;
?>