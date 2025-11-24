<?php
session_start();

// Xóa tất cả biến trong session
$_SESSION = [];

// Nếu có cookie session, xóa luôn
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session hiện tại
session_destroy();

// Chuyển hướng về trang đăng nhập
header("Location: Role_login.php");
exit();
?>
