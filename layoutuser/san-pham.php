<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once "dbuser.php";

$db = new dbuser();
$conn = $db->conn;
$maKH = $_SESSION["user_id"] ?? null;

$product = new Product($conn);
$maLoai = $_GET['maLoai'] ?? null;
$donGia = $_GET['DonGia'] ?? null;
$products = $product->HienThiSP($maLoai, $donGia);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sản phẩm - Năm Anh Em Bán Hoa</title>
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
          <li><a href="san-pham.php" class="active">Sản phẩm</a></li>
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
        <a href="taikhoan.php" class="user-action-item">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
            <path d="M463 448.2C440.9 409.8 399.4 384 352 384L288 384C240.6 384 199.1 409.8 177 448.2C212.2 487.4 263.2 512 320 512C376.8 512 427.8 487.3 463 448.2zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320zM320 336C359.8 336 392 303.8 392 264C392 224.2 359.8 192 320 192C280.2 192 248 224.2 248 264C248 303.8 280.2 336 320 336z" />
          </svg>
          <span>Tài khoản</span>
        </a>
      </div>
    </div>
  </header>

  <main class="container shop-layout">
    <form action="san-pham.php" method="GET" id="filter-form">
      <div class="filter-group">
        <h5>Theo loại hoa</h5>
        <?php $product->HienThiLoaiSP(); ?>
      </div>
      <a href="san-pham.php">
        Xóa bộ lọc
      </a>
    </form>
    <section class="product-area">
      <div class="product-grid">
        <?php
        $maLoai_filter = null; 
        if (isset($_GET['MaLoai'])) { 
          $maLoai_filter = $_GET['MaLoai']; 
        }
        $products = $product->HienThiSP($maLoai_filter);
        if (count($products) > 0) {
          foreach ($products as $row) {
            $maSP = $row["MaSP"];
            $tenSP = $row["TenSP"];
            $donGia = number_format($row["DonGia"], 0, ",", ".");
            $soLuong = $row["SoLuong"];
            $hinhAnh = !empty($row['Anh']) ? 'data:image/jpeg;base64,' . base64_encode($row['Anh']) : 'assets/no-image.png';
            echo "
              <article class='product-card'>
                <a href='chitiet.php?MaSP={$maSP}'>
                  <div class='product-image-wrapper'>
                      <img src='{$hinhAnh}' alt='Sản phẩm' class='product-image' />
                  </div>
                </a>
                <div class='product-content'>
                  <h3><a href='chitiet.php?MaSP={$maSP}'>{$tenSP}</a></h3>
                  <p class='price'>{$donGia} VNĐ</p>
                  <p class='stock'>Số lượng: {$soLuong}</p>";
            if (!empty($_SESSION['user_id'])) {
              echo "
                <form method='POST' action='giohang.php'>
                  <input type='hidden' name='maSP' value='{$maSP}'>
                  <input type='hidden' name='soLuong' value='1'>
                  <input type='hidden' name='donGia' value='{$donGia}'>
                  <button type='submit' class='btn-add-cart'>Thêm vào giỏ</button>
                </form>";
            } else {
                echo "<a href='taikhoan.php' class='btn-add-cart'>Đăng nhập để mua</a>";
            }
          echo "
            </div>
            </article>";
          }
        } else {
          echo "<p>Không có sản phẩm nào.</p>";
        }
        ?>
      </div>
    </section>

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
  </main>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const modal = document.getElementById("contact-modal");
      const openLink = document.getElementById("contact-link");
      const closeBtn = document.getElementById("close-modal");
      if (openLink) openLink.addEventListener("click", e => {
        e.preventDefault();
        modal.style.display = "flex";
      });
      if (closeBtn) closeBtn.addEventListener("click", () => modal.style.display = "none");
      window.addEventListener("click", e => {
        if (e.target == modal) modal.style.display = "none";
      });
    });
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
      <p>Địa chỉ: CS2: Học Viện Hàng không Việt Nam</p>
      <p>Email: namanhembanhoa@gmail.com</p>
    </div>
    <div class="footer-right">
      <p>Điện thoại: 0123456789</p>
      <p>© 2025 NĂM ANH EM BÁN HOA. Bảo lưu mọi quyền.</p>
    </div>
  </div>
</footer>

</html>