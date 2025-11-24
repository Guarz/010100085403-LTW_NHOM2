<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "dbuser.php";

if (empty($_SESSION['user_id'])) {
    header("Location: taikhoan.php");
    exit;
}

$user = new User($conn); 
$maKH = $_SESSION['user_id']; 

$message_info = ""; 
$isSuccess_info = false;
$message_pass = ""; 
$isSuccess_pass = false;


if (isset($_POST['action']) && $_POST['action'] == 'logout') {
    $user->logout(); 
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $hoTen_new = $_POST['hoTen'];
    $email_new = $_POST['email'];
    $ngaySinh_new = $_POST['ngaySinh'];
    $DT_new = $_POST['DT'];

    if ($user->updateUser($maKH, $hoTen_new, $email_new, $ngaySinh_new, $DT_new)) {
        $message_info = "Cập nhật thông tin thành công!";
        $isSuccess_info = true;
    } else {
        $message_info = "Cập nhật thất bại. Vui lòng thử lại.";
        $isSuccess_info = false;
    }
}

$currentUserData = $user->getUserDetails($maKH);
$matKhauHienTai = $currentUserData['PassKH'];

if (isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $matKhauCu = $_POST['matKhauCu'];
    $matKhauMoi = $_POST['matKhauMoi'];
    $xacNhanMatKhauMoi = $_POST['xacNhanMatKhauMoi'];

    if ($matKhauCu != $matKhauHienTai) {
        $message_pass = "Mật khẩu cũ không đúng!";
        $isSuccess_pass = false;
    } elseif (empty($matKhauMoi)) {
        $message_pass = "Mật khẩu mới không được để trống!";
        $isSuccess_pass = false;
    } elseif ($matKhauMoi != $xacNhanMatKhauMoi) {
        $message_pass = "Mật khẩu xác nhận không khớp!";
        $isSuccess_pass = false;
    } else {
        if ($user->updatePassword($maKH, $matKhauMoi)) {
            $message_pass = "Đổi mật khẩu thành công!";
            $isSuccess_pass = true;
        } else {
            $message_pass = "Đổi mật khẩu thất bại. Vui lòng thử lại.";
            $isSuccess_pass = false;
        }
    }
}
$userInfo = $user->getUserDetails($maKH);

if (!$userInfo) {
    $user->logout();
    exit;
}

$hoTen = $userInfo['HoTen'];
$email = $userInfo['Email'];
$ngaySinh = $userInfo['NgaySinh'];
$DT = $userInfo['DT'];

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản của bạn</title>
    <link rel="stylesheet" href="style.css">
</head>
<header>
    <div class="container">
        <div class="logo">NĂM ANH EM BÁN HOA</div>
        <nav>
            <ul>
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="san-pham.php">Sản phẩm</a></li>
                <li><a href="#" id="contact-link">Liên hệ</a></li>
            </ul>
        </nav>
        <div class="user-actions">
            <a href="taikhoan.php" class="user-action-item active">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                    <path d="M24 48C10.7 48 0 58.7 0 72C0 85.3 10.7 96 24 96L69.3 96C73.2 96 76.5 98.8 77.2 102.6L129.3 388.9C135.5 423.1 165.3 448 200.1 448L456 448C469.3 448 480 437.3 480 424C480 410.7 469.3 400 456 400L200.1 400C188.5 400 178.6 391.7 176.5 380.3L171.4 352L475 352C505.8 352 532.2 330.1 537.9 299.8L568.9 133.9C572.6 114.2 557.5 96 537.4 96L124.7 96L124.3 94C119.5 67.4 96.3 48 69.2 48L24 48zM208 576C234.5 576 256 554.5 256 528C256 501.5 234.5 480 208 480C181.5 480 160 501.5 160 528C160 554.5 181.5 576 208 576zM432 576C458.5 576 480 554.5 480 528C480 501.5 458.5 480 432 480C405.5 480 384 501.5 384 528C384 554.5 405.5 576 432 576z" />
                </svg>
                <span>Giỏ hàng</span>
            </a>
            <a href="taikhoan.php" class="user-action-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                    <path d="M463 448.2C440.9 409.8 399.4 384 352 384L288 384C240.6 384 199.1 409.8 177 448.2C212.2 487.4 263.2 512 320 512C376.8 512 427.8 487.3 463 448.2zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320zM320 336C359.8 336 392 303.8 392 264C392 224.2 359.8 192 320 192C280.2 192 248 224.2 248 264C248 303.8 280.2 336 320 336z" />
                </svg>
                <span>Tài khoản</span>
            </a>
        </div>
    </div>
</header>

<body>
    <main class="container">
        <div class="account-container">

            <form method="POST" action="dang-xuat.php">
                <h1>Thông tin tài khoản</h1>

                <?php if (!empty($message_info)) : ?>
                    <div class="message <?php echo $isSuccess_info ? 'success' : 'error'; ?>">
                        <?php echo $message_info; ?>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="action" value="update">

                <div class="info-item">
                    <strong>Họ và tên:</strong>
                    <input type="text" name="hoTen" value="<?php echo htmlspecialchars($hoTen); ?>" required>
                </div>

                <div class="info-item">
                    <strong>Email:</strong>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="info-item">
                    <strong>Ngày sinh:</strong>
                    <input type="date" name="ngaySinh" value="<?php echo htmlspecialchars($ngaySinh); ?>">
                </div>

                <div class="info-item">
                    <strong>Số điện thoại:</strong>
                    <input type="text" name="DT" value="<?php echo htmlspecialchars($DT); ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-update">Cập nhật thông tin</button>
                </div>
            </form>


            <form method="POST" action="dang-xuat.php">
                <h2>Đổi mật khẩu</h2>

                <?php if (!empty($message_pass)) : ?>
                    <div class="message <?php echo $isSuccess_pass ? 'success' : 'error'; ?>">
                        <?php echo $message_pass; ?>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="action" value="change_password">

                <div class="info-item">
                    <strong>Mật khẩu cũ:</strong>
                    <input type="password" name="matKhauCu" required>
                </div>

                <div class="info-item">
                    <strong>Mật khẩu mới:</strong>
                    <input type="password" name="matKhauMoi" required>
                </div>

                <div class="info-item">
                    <strong>Xác nhận MK mới:</strong>
                    <input type="password" name="xacNhanMatKhauMoi" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-update">Đổi mật khẩu</button>
                </div>
            </form>
            <form class="logout-form" method="POST" action="dang-xuat.php">
                <input type="hidden" name="action" value="logout">
                <div class="form-actions">
                    <button type="submit" class="btn btn-logout">Đăng xuất</button>
                </div>
            </form>
        </div>
    </main>
    <div id="contact-modal" class="modal-overlay">
        <div class="modal-content">
            <span class="modal-close" id="close-modal">&times;</span>
            <h2>Thông tin liên hệ</h2>
            <div class="contact-info-list">
                <div class="contact-item">
                    <strong>Điện thoại:</strong>
                    <a href="tel:0123456789">0123456789</a>
                </div>
                <div class="contact-item">
                    <strong>Zalo:</strong>
                    <a href="https://zalo.me/0123456789" target="_blank">0123456789</a>
                </div>
                <div class="contact-item">
                    <strong>Facebook:</strong>
                    <a href="https://facebook.com/namanhembanhoa" target="_blank">fb.com/namanhembanhoa</a>
                </div>
                <div class="contact-item">
                    <strong>Email:</strong>
                    <a href="mailto:namanhembanhoa@gmail.com">namanhembanhoa@gmail.com</a>
                </div>
                <div class="contact-item">
                    <strong>Địa chỉ:</strong>
                    <span>CS2: Học Viện Hàng không Việt Nam</span>
                </div>
            </div>
        </div>
    </div>
    <script>
        const modal = document.getElementById("contact-modal");
        const openLink = document.getElementById("contact-link");
        const closeBtn = document.getElementById("close-modal");

        if (openLink) {
            openLink.addEventListener("click", function(event) {
                event.preventDefault();
                modal.style.display = "flex";
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener("click", function() {
                modal.style.display = "none";
            });
        }
        window.addEventListener("click", function(event) {
            if (event.target == modal) modal.style.display = "none";
        });
    </script>
</body>
<footer>
    <div class="container footer-layout">
        <div class="footer-left">
            <p>Địa chỉ: CS2 - Học Viện Hàng không Việt Nam</p>
            <p>Email: namanhembanhoa@gmail.com</p>
        </div>
        <div class="footer-right">
            <p>Điện thoại: 0123456789</p>
            <p>© 2025 NĂM ANH EM BÁN HOA. Bảo lưu mọi quyền.</p>
        </div>
    </div>
</footer>

</html>