<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!empty($_SESSION['user_id'])) {

  header("Location: dang-xuat.php");
  exit;
}
require_once "dbuser.php";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tài khoản - Năm Anh Em Bán Hoa</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<body>
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
        <a href="giohang.php" class="user-action-item">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
            <path d="M24 48C10.7 48 0 58.7 0 72C0 85.3 10.7 96 24 96L69.3 96C73.2 96 76.5 98.8 77.2 102.6L129.3 388.9C135.5 423.1 165.3 448 200.1 448L456 448C469.3 448 480 437.3 480 424C480 410.7 469.3 400 456 400L200.1 400C188.5 400 178.6 391.7 176.5 380.3L171.4 352L475 352C505.8 352 532.2 330.1 537.9 299.8L568.9 133.9C572.6 114.2 557.5 96 537.4 96L124.7 96L124.3 94C119.5 67.4 96.3 48 69.2 48L24 48zM208 576C234.5 576 256 554.5 256 528C256 501.5 234.5 480 208 480C181.5 480 160 501.5 160 528C160 554.5 181.5 576 208 576zM432 576C458.5 576 480 554.5 480 528C480 501.5 458.5 480 432 480C405.5 480 384 501.5 384 528C384 554.5 405.5 576 432 576z" />
          </svg>
          <span>Giỏ hàng</span>
        </a>
        <a href="taikhoan.php" class="user-action-item active">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
            <path d="M463 448.2C440.9 409.8 399.4 384 352 384L288 384C240.6 384 199.1 409.8 177 448.2C212.2 487.4 263.2 512 320 512C376.8 512 427.8 487.3 463 448.2zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320zM320 336C359.8 336 392 303.8 392 264C392 224.2 359.8 192 320 192C280.2 192 248 224.2 248 264C248 303.8 280.2 336 320 336z" />
          </svg>
          <span>Tài khoản</span>
        </a>
      </div>
    </div>
  </header>

  <main class="container">
    <div class="account-layout">
      <?php if (!empty($message)): ?>
        <div class="alert">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>
      <div class="account-box" id="login-form">
        <h2>Đăng nhập</h2>
        <form action="taikhoan.php" method="POST">
          <div class="form-group">
            <label for="login-email">Email</label>
            <input type="email" name="email" id="login-email" placeholder="Nhập email của bạn" required>
          </div>

          <div class="form-group">
            <label for="login-pass">Mật khẩu</label>
            <input type="password" name="password" id="login-pass" placeholder="Nhập mật khẩu của bạn" required>
          </div>

          <div class="form-options">
            <a href="#" id="show-reset">Quên mật khẩu?</a>
          </div>

          <button type="submit" name="login" class="btn-submit">Đăng nhập</button>
          <p class="form-toggle-link">
            Chưa có tài khoản?
            <a href="#" id="show-register">Đăng ký ngay</a>
          </p>
        </form>
      </div>
      <div class="account-box register-box" id="register-form">
        <h2>Tài khoản mới</h2>
        <p>Đăng ký tài khoản để theo dõi đơn hàng và nhận nhiều ưu đãi hấp dẫn!</p>
        <form action="taikhoan.php" method="POST">
          <div class="form-group">
            <label for="reg-name">Họ và tên</label>
            <input type="text" name="name" id="reg-name" placeholder="Nhập họ và tên" required>
          </div>

          <div class="form-group">
            <label for="reg-email">Email</label>
            <input type="email" name="email" id="reg-email" placeholder="Nhập email" required>
          </div>

          <div class="form-group">
            <label for="reg-pass">Mật khẩu</label>
            <input type="password" name="password" id="reg-pass" placeholder="Tạo mật khẩu" required>
          </div>

          <button type="submit" name="register" class="btn-submit">Đăng ký</button>
          <p class="form-toggle-link">
            Đã có tài khoản?
            <a href="#" id="show-login">Đăng nhập</a>
          </p>
        </form>
      </div>
      <div class="account-box" id="reset-form">
        <h2>Quên mật khẩu</h2>
        <p>Vui lòng nhập email của bạn để nhận liên kết khôi phục mật khẩu.</p>

        <form action="taikhoan.php" method="POST">
          <div class="form-group">
            <label for="reset-email">Email</label>
            <input type="email" name="email" id="reset-email" placeholder="Nhập email của bạn" required>
          </div>

          <button type="submit" name="reset" class="btn-submit">Gửi liên kết khôi phục</button>
          <p class="form-toggle-link">
            Nhớ mật khẩu?
            <a href="#" id="back-to-login">Đăng nhập</a>
          </p>
        </form>
      </div>
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
    document.addEventListener("DOMContentLoaded", function() {
      const loginForm = document.getElementById("login-form");
      const registerForm = document.getElementById("register-form");
      const resetForm = document.getElementById("reset-form");

      const showRegisterLink = document.getElementById("show-register");
      const showLoginLink = document.getElementById("show-login");
      const showResetLink = document.getElementById("show-reset");
      const backToLoginLink = document.getElementById("back-to-login");

  
      if (showRegisterLink) {
        showRegisterLink.addEventListener("click", function(event) {
          event.preventDefault();
          loginForm.style.display = "none";
          resetForm.style.display = "none";
          registerForm.style.display = "block";
        });
      }

      if (showLoginLink) {
        showLoginLink.addEventListener("click", function(event) {
          event.preventDefault();
          registerForm.style.display = "none";
          resetForm.style.display = "none";
          loginForm.style.display = "block";
        });
      }

      if (showResetLink) {
        showResetLink.addEventListener("click", function(event) {
          event.preventDefault();
          loginForm.style.display = "none";
          registerForm.style.display = "none";
          resetForm.style.display = "block";
        });
      }

      if (backToLoginLink) {
        backToLoginLink.addEventListener("click", function(event) {
          event.preventDefault();
          resetForm.style.display = "none";
          loginForm.style.display = "block";
        });
      }
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