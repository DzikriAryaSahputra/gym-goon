<?php
session_start();
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
setcookie("user_logged_in", "", time() - 3600, "/");
setcookie("regular_logged_in", "", time() - 3600, "/");
header("Location: login");
exit;
?>
