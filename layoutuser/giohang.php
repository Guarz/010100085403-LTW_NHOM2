<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once 'dbuser.php';

$db = new dbuser();
$conn = $db->conn;

if (empty($_SESSION['user_id'])) {
  header("Location: taikhoan.php");
  exit;
}

$maKH = intval($_SESSION['user_id']);
$cart = new Cart($conn);

if (!empty($_POST['maSP']) && !empty($_POST['soLuong']) && !empty($_POST['donGia'])) {
  $maSP = $_POST['maSP'];
  $soLuong = max(1, intval($_POST['soLuong']));
  
  $gia_string = $_POST['donGia']; 
  $gia = preg_replace('/[^\d]/', '', $gia_string);
  $gia = floatval($gia);                           

  $cart->addToCart($maKH, $maSP, $soLuong, $gia);
  header("Location: giohang.php"); 
  exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'delete_item' && isset($_POST['maSP_to_delete'])) {
  $maSP_to_delete = $_POST['maSP_to_delete'];
  $cart->deleteItem($maKH, $maSP_to_delete);
  header('Location: giohang.php');
  exit;
}

if (isset($_SESSION['user_name'])) {
  $userName = $_SESSION['user_name'];
} else {
  $stmtUser = $conn->prepare("SELECT HoTen FROM khachhang WHERE MaKH = ? LIMIT 1");
  $stmtUser->bind_param("i", $maKH);
  $stmtUser->execute();
  $resultUser = $stmtUser->get_result();

  if (!$resultUser || $resultUser->num_rows === 0) {
    session_unset();
    session_destroy();
    header("Location: taikhoan.php");
    exit;
  }
  $user = $resultUser->fetch_assoc();
  $userName = $user["HoTen"];
  $_SESSION['user_name'] = $userName;
}


$resultCart = $cart->getCartByUser($maKH);
if (!$resultCart) {
  die("Lỗi truy vấn giỏ hàng: " . $conn->error);
}

if (isset($_POST['confirm_checkout'])) {
  if ($cart->checkout($maKH)) {
    echo "<script>alert('Thanh toán thành công!'); window.location='giohang.php';</script>";
    exit;
  } else {
    echo "<script>alert('Thanh toán thất bại hoặc giỏ hàng trống.'); window.location='giohang.php';</script>";
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Giỏ hàng - Năm Anh Em Bán Hoa</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    .item-remove-form {
      margin-left: 15px;
    }

    .item-remove-btn {
      background: none;
      border: none;
      color: #888;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
      padding: 0;
    }

    .item-remove-btn:hover {
      color: #000;
    }

    .cart-item {
      align-items: center;
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
      background: #fff;
      margin: 10% auto;
      padding: 20px;
      border-radius: 10px;
      width: 80%;
      max-width: 600px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h2 {
      margin: 0;
    }

    .close {
      color: #888;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover {
      color: black;
    }

    .btn-confirm {
      background-color: #28a745;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
    }

    .btn-confirm:hover {
      background-color: #218838;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th,
    td {
      text-align: left;
      padding: 8px;
      border-bottom: 1px solid #ddd;
    }
  </style>
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
    <div class="cart-page-title">
      <h1>Giỏ hàng của <?php echo htmlspecialchars($userName); ?></h1>
    </div>

    <div class="cart-layout">
      <section class="cart-items">
        <?php
        $tong = 0;
        $cartItems = [];
        if ($resultCart && $resultCart->num_rows > 0) {
          while ($row = $resultCart->fetch_assoc()) {
            $hinhAnh = !empty($row['Anh']) ? 'data:image/jpeg;base64,' . base64_encode($row['Anh']) : 'assets/no-image.png';
            $thanhTien = $row["DonGia"] * $row["SoLuong"];
            $tong += $thanhTien;
            $cartItems[] = $row; 
            $tenSP = htmlspecialchars($row['TenSP']);
            $maSP = htmlspecialchars($row['MaSP']);
            $soLuong = intval($row['SoLuong']);
            $donGia = number_format($row['DonGia'], 0, ',', '.');
            $thanhTienF = number_format($thanhTien, 0, ',', '.');

            echo "
            <article class='cart-item'>
              <img src='{$hinhAnh}' alt='Sản phẩm' style='width:120px; height:120px; object-fit:cover; border-radius:10px;' />
              <div class='item-info'>
                <h4>{$tenSP}</h4>
                <p class='item-price'>{$donGia} VNĐ</p>
              </div>
              <div class='item-quantity'>
                <label>Số lượng:</label>
                <input type='number' value='{$soLuong}' min='1' readonly /> </div>
              <div class='item-total'>
                <h4>{$thanhTienF} VNĐ</h4>
              </div>

              <form method='POST' action='giohang.php' class='item-remove-form'>
                <input type='hidden' name='action' value='delete_item'>
                <input type='hidden' name='maSP_to_delete' value='{$maSP}'>
                <button type='submit' class='item-remove-btn' title='Xóa sản phẩm'>&times;</button>
              </form>
            </article>";
          }
        } else {
          echo "<p class='cart-empty-message'>Giỏ hàng của bạn trống!</p>";
        }
        ?>
      </section>

      <aside class="cart-summary">
        <h3>Tổng cộng giỏ hàng</h3>
        <div class="summary-row">
          <span>Tạm tính:</span>
          <strong><?= number_format($tong, 0, ',', '.') ?> VNĐ</strong>
        </div>
        <div class="summary-row">
          <span>Phí vận chuyển:</span>
          <strong>Miễn phí</strong>
        </div>
        <hr />
        <div class="summary-row total">
          <span>Thành tiền:</span>
          <strong><?= number_format($tong, 0, ',', '.') ?> VNĐ</strong>
        </div>

        <?php if ($tong > 0): ?>
          <button class="btn-submit" id="btnCheckout">Tiến hành thanh toán</button>
        <?php else: ?>
          <p style="text-align: center;">Giỏ hàng của bạn đang trống.</p>
        <?php endif; ?>
        <a href="san-pham.php" class="continue-shopping">Tiếp tục mua sắm</a>
      </aside>
    </div>
    <div id="checkoutModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Xác nhận thanh toán</h2>
          <span class="close" id="closeModal">&times;</span>
        </div>
        <p>Vui lòng kiểm tra lại đơn hàng của bạn:</p>
        <table>
          <tr>
            <th>Sản phẩm</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Thành tiền</th>
          </tr>
          <?php foreach ($cartItems as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['TenSP']) ?></td>
              <td><?= intval($item['SoLuong']) ?></td>
              <td><?= number_format($item['DonGia'], 0, ',', '.') ?> VNĐ</td>
              <td><?= number_format($item['SoLuong'] * $item['DonGia'], 0, ',', '.') ?> VNĐ</td>
            </tr>
          <?php endforeach; ?>
        </table>
        <h3>Tổng cộng: <?= number_format($tong, 0, ',', '.') ?> VNĐ</h3>

        <form method="POST" action="">
          <input type="hidden" name="confirm_checkout" value="1">
          <button type="submit" class="btn-confirm">Xác nhận thanh toán</button>
        </form>
      </div>
    </div>
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
    const modal = document.getElementById("checkoutModal");
    const btn = document.getElementById("btnCheckout");
    const close = document.getElementById("closeModal");

    if (btn) btn.onclick = () => modal.style.display = "block";
    if (close) close.onclick = () => modal.style.display = "none";
    window.onclick = (event) => {
      if (event.target == modal) modal.style.display = "none";
    }

    document.addEventListener("DOMContentLoaded", function() {
      const modal = document.getElementById("contact-modal");
      const openLink = document.getElementById("contact-link");
      const closeBtn = document.getElementById("close-modal");

      if (openLink) {
        openLink.addEventListener("click", function(event) {
          event.preventDefault();
          if (modal) modal.style.display = "flex";
        });
      }
      if (closeBtn) {
        closeBtn.addEventListener("click", function() {
          if (modal) modal.style.display = "none";
        });
      }
      window.addEventListener("click", function(event) {
        if (event.target == modal) {
          if (modal) modal.style.display = "none";
        }
      });
    });
  </script>
</body>
<footer>
  <div class="container footer-layout">
    <div class="footer-left">
      <p>Địa chỉ: CS2 - Học Viện Hàng Không Việt Nam</p>
      <p>Email: namanhembanhoa@gmail.com</p>
    </div>
    <div class="footer-right">
      <p>Điện thoại: 0123456789</p>
      <p>© 2025 NĂM ANH EM BÁN HOA. Bảo lưu mọi quyền.</p>
    </div>
  </div>
</footer>

</html>