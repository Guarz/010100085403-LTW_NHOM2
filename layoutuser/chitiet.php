<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once 'dbuser.php';
$db = new dbuser();
$conn = $db->conn;
if (!isset($_GET['MaSP']) || !is_numeric($_GET['MaSP'])) {
  die("ID sản phẩm không hợp lệ. <a href='san-pham.php'>Quay lại</a>");
}

$maSP = $_GET['MaSP'];
if (isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
  if (empty($_SESSION['user_id'])) {
    $_SESSION['return_to'] = "chitiet.php?MaSP=" . $maSP;
    header("Location: taikhoan.php");
    exit;
  }
  $maKH = $_SESSION['user_id'];
  $maSP_to_add = $_POST['maSP'];
  $soLuong = max(1, intval($_POST['soLuong']));
  $gia = floatval($_POST['gia']);
  $cart = new Cart($conn);
  $cart->addToCart($maKH, $maSP_to_add, $soLuong, $gia);
  header("Location: giohang.php");
  exit;
}
$productModel = new Product($conn);
$product = $productModel->ChiTietSP($maSP);
if (!$product) {
  die("Sản phẩm không tồn tại. <a href='san-pham.php'>Quay lại</a>");
}
$userName = null;
if (isset($_SESSION['user_id'])) {
  $maKH = $_SESSION['user_id'];
  if (!empty($_SESSION['user_name'])) {
    $userName = $_SESSION['user_name'];
  } else {
    $sqlUser = "SELECT HoTen FROM khachhang WHERE MaKH = $maKH LIMIT 1";
    $resultUser = $conn->query($sqlUser);
    if ($resultUser && $resultUser->num_rows > 0) {
      $user = $resultUser->fetch_assoc();
      $userName = $user["HoTen"];
      $_SESSION['user_name'] = $userName;
    }
  }
}


?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Năm Anh Em Bán Hoa</title>
  <link rel="stylesheet" href="style.css" />
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


  <main class="container">
    <div class="product-detail-layout">
      <section class="product-images">
        <?php
        $hinhAnh = !empty($product['Anh'])
          ? 'data:image/jpeg;base64,' . base64_encode($product['Anh'])
          : 'assets/no-image.png';
        echo "<div class='product-image-wrapper'><img src='{$hinhAnh}' alt='Sản phẩm' class='product-image' /></div>";
        ?>
      </section>

      <section class="product-info">
        <nav class="breadcrumbs">
          <a href="index.php">Trang chủ</a>
          <a href="san-pham.php">Sản phẩm</a>
          <span><?php echo htmlspecialchars($product['TenSP']); ?></span>
        </nav>

        <h1><?php echo htmlspecialchars($product['TenSP']); ?></h1>

        <p class="price-detail">
          <span class="new-price"><?php echo number_format($product['DonGia'], 0, ',', '.'); ?> VNĐ</span>
        </p>

        <p class="short-desc"><?php echo htmlspecialchars($product['MoTa']); ?></p>

        <form method="POST" action="chitiet.php?MaSP=<?php echo $maSP; ?>">
          <input type="hidden" name="action" value="add_to_cart">
          <input type="hidden" name="maSP" value="<?php echo $maSP; ?>">
          <input type="hidden" name="gia" value="<?php echo $product['DonGia']; ?>">

          <div class="quantity-selector">
            <label for="product-quantity">Số lượng:</label>
            <input type="number" id="product-quantity" name="soLuong" value="1" min="1" />
          </div>

          <button type="submit" class="btn-submit btn-add-to-cart">Thêm vào giỏ hàng</button>
        </form>

        <div class="special-offers">
          <h3>ƯU ĐÃI ĐẶC BIỆT</h3>
          <ul>
            <li><span class="offer-number">1</span> Tặng Banner hoặc Thiệp (Trị Giá 20.000đ - 50.000đ) Miễn Phí</li>
            <li><span class="offer-number">3</span> Miễn Phí Giao Hàng Nội Thành (Chi Tiết)</li>
            <li><span class="offer-number">4</span> Giao Gấp Trong Vòng 2 Giờ</li>
            <li><span class="offer-number">5</span> Cam Kết 100% Hoàn Lại Tiền Nếu Bạn Không Hài Lòng</li>
          </ul>
        </div>
      </section>
    </div>
  </main>

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

  <div id="contact-modal" class="modal-overlay">
    <div class="modal-content">
      <span class="modal-close" id="close-modal">&times;</span>
      <h2>Thông tin liên hệ</h2>
      <div class="contact-info-list">
        <div class="contact-item"><strong>Điện thoại:</strong> <a href="tel:0123456789">0123456789</a></div>
        <div class="contact-item"><strong>Zalo:</strong> <a href="https://zalo.me/0123456789" target="_blank">0123456789</a></div>
        <div class="contact-item"><strong>Facebook:</strong> <a href="https://facebook.com/namanhembanhoa" target="_blank">fb.com/namanhembanhoa</a></div>
        <div class="contact-item"><strong>Email:</strong> <a href="mailto:namanhembanhoa@gmail.com">namanhembanhoa@gmail.com</a></div>
        <div class="contact-item"><strong>Địa chỉ:</strong> <span>CS2: Học Viện Hàng không Việt Nam</span></div>
      </div>
    </div>
  </div>

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
        if (e.target === modal) modal.style.display = "none";
      });
    });
  </script>
</body>

</html>