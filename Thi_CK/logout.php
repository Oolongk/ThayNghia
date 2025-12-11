<?php
session_start();
unset($_SESSION['user']); // Xóa session user
header("Location: index.php");
exit();
?>