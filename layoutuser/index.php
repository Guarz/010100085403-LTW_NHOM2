<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once "dbuser.php";

$email = isset($_GET['email']) ? $_GET['email'] : '';
$password = isset($_GET['password']) ? $_GET['password'] : '';

$db = new dbuser();
$product = new Product($db->conn);

?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Trang chủ - Năm Anh Em Bán Hoa</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="icon" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ5CATj4-hGHNkIU3OldRFOSMPSw8EHG9nNwQ&s">
  </link>
</head>

<body>
  <header>
    <div class="container">
      <div class="logo">NĂM ANH EM BÁN HOA</div>
      <nav>
        <ul>
          <li><a href="index.php" class="active">Trang chủ</a></li>
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

        <a href="dang-xuat.php" class="user-action-item">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
            <path d="M463 448.2C440.9 409.8 399.4 384 352 384L288 384C240.6 384 199.1 409.8 177 448.2C212.2 487.4 263.2 512 320 512C376.8 512 427.8 487.3 463 448.2zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320zM320 336C359.8 336 392 303.8 392 264C392 224.2 359.8 192 320 192C280.2 192 248 224.2 248 264C248 303.8 280.2 336 320 336z" />
          </svg>
          <span>Tài khoản</span>
        </a>
      </div>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="container hero-layout">
        <div class="hero-content">
          <h1>Phục vụ 24/24</h1>
          <h2>Có dịch vụ giao nhanh trong 60 phút</h2>
          <ul>
            <li>60 mẫu giao gấp trong 60 phút</li>
            <li>Phục vụ 24/7</li>
            <li>Giá đã bao gồm VAT</li>
            <li>Miễn phí giao hàng nội thành</li>
          </ul>
          <a href="tel:18006353" class="hotline-btn">HOTLINE: 1800 6353</a>
        </div>

        <div class="consult-box">
          <h4>TƯ VẤN CHỌN HOA TƯƠI</h4>

          <form action="san-pham.php" method="GET" id="consult-form">
            <div class="form-group">
              <label for="chu-de">Chủ đề</label>
              <select id="chu-de" name="MaLoai">
                <option value="">Tất cả chủ đề</option>
                <?php echo $product->HienThiLoaiSP_Options(); ?>
              </select>
            </div>
            <button type="submit" class="btn-tim-kiem">Tìm kiếm</button>
          </form>
          <p class="consult-note">
            *Bạn có thể gọi nhanh cho chúng tôi theo số
            <strong>1800 6353</strong> để đặt hoa theo thiết kế riêng.
          </p>
        </div>
      </div>
    </section>
    <div class="intro-container">
      <h2>GIỚI THIỆU FLOWERSHOP</h2>
      <p>
        Flowershop là một shop tươi hoa Online uy tín và chất lượng tại Việt Nam.
        Chúng tôi tự hào cung cấp các sản phẩm hoa tươi đẹp mắt và độc đáo, giúp bạn thể hiện
        tình cảm và chia sẻ niềm vui với người thân yêu, bạn bè và đối tác kinh doanh.
      </p>
      <p>
        Với đội ngũ nhân viên chuyên nghiệp và đam mê yêu hoa, Flowershop cam kết
        mang đến cho bạn những sản phẩm hoa tươi tắn và sắc sảo nhất. Chúng tôi tự tin đảm bảo
        sự hài lòng và vui mừng cho người nhận mỗi khi nhận được những món quà thượng thặng từ chúng tôi.
      </p>
      <p>
        Tại shop hoa tươi Flowershop, bạn sẽ tìm thấy một loạt các loại hoa từ hoa hồng,
        hoa lily, hoa cẩm chướng, hoa ly, hoa lan, đến các bó hoa mix đa dạng về màu sắc và kiểu dáng.
        Bất kể dịp nào - sinh nhật, lễ kỷ niệm, Valentine, ngày của mẹ hay đơn giản là muốn bày tỏ lòng biết ơn -
        Flowershop luôn có sản phẩm phù hợp để làm hài lòng mọi nhu cầu của bạn.
      </p>
      <p>
        Ngoài ra, chúng tôi cũng cung cấp dịch vụ giao hoa nhanh chóng và đáng tin cậy.
        Bạn chỉ cần chọn sản phẩm yêu thích và đặt hàng, chúng tôi sẽ giúp bạn biến những ý tưởng
        tuyệt vời thành hiện thực và gửi đến người thân yêu một cách ân cần và chuyên nghiệp.
      </p>
    </div>
    <div class="intro-container">
      <h2>0% RỦI RO KHI ĐẶT HOA ONLINE</h2>
      <p>
        Chúng tôi cam kết đem đến cho bạn trải nghiệm mua sắm hoa tươi Online hoàn toàn an toàn và
        không có bất kỳ rủi ro nào. Với đội ngũ nhân viên chuyên nghiệp và kinh nghiệm, chúng tôi luôn
        đảm bảo chất lượng tốt nhất cho từng sản phẩm hoa tươi.
      </p>
      <p>
        Mỗi sản phẩm hoa tại Flowershop được chọn lựa kỹ càng, từ những loại hoa tươi tốt nhất
        đến các phụ liệu đi kèm, để đảm bảo hoa luôn tươi mới và lâu tàn. Chúng tôi không chỉ đáp ứng
        các yêu cầu về mẫu mã và màu sắc, mà còn đảm bảo sự tinh tế và độc đáo trong từng thiết kế.
      </p>
      <p>
        Nếu có bất kỳ vấn đề nào xảy ra trong quá trình mua sắm hoặc giao nhận, chúng tôi sẽ luôn
        sẵn lòng hỗ trợ bạn 24/7. Cam kết của chúng tôi là hoàn tiền hoặc đổi sản phẩm nếu bạn
        không hài lòng với chất lượng hoặc dịch vụ của chúng tôi.
      </p>
      <p>
        Tin tưởng và yên tâm mua hoa tươi tại Flowershop, chúng tôi luôn đặt lợi ích và
        sự hài lòng của khách hàng lên hàng đầu. Hãy để chúng tôi chăm sóc mọi nhu cầu về hoa tươi
        của bạn và tạo nên những khoảnh khắc đáng nhớ và ý nghĩa bên người thân yêu!
      </p>
      <p><strong>Cảm ơn bạn đã ủng hộ và tin tưởng Flowershop!</strong></p>
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
      const consultForm = document.getElementById("consult-form");
      const giaSelect = document.getElementById("muc-gia");
      if (consultForm && giaSelect) {
        consultForm.addEventListener("submit", function() {
          if (giaSelect.value === "") {
            giaSelect.disabled = true;
          }
        });
      }
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