<?php
session_start();
require_once '../DatabaseManager.php';

$dbManager = new DatabaseManager();

if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: Chưa đăng nhập.']);
        exit();
    }

    if ($_POST['action'] === 'delete' && isset($_POST['MaKH'])) {
        $maKH = $_POST['MaKH'];
        if ($dbManager->deleteKhachHang($maKH)) {
            echo json_encode(['success' => true, 'message' => 'Xóa khách hàng thành công. (Đã xóa cả đơn hàng liên quan)']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi xóa khách hàng: Vui lòng kiểm tra log CSDL.']);
        }
        exit();
    }

    if (($_POST['action'] === 'update' || $_POST['action'] === 'add') &&
        isset($_POST['HoTen'], $_POST['NgaySinh'], $_POST['DT'], $_POST['Email'])
    ) {
        $maKH = $_POST['MaKH'] ?? null;

        $hoTen = $_POST['HoTen'];
        $ngaySinh = $_POST['NgaySinh'];
        $dt = $_POST['DT'];
        $email = $_POST['Email'];

        $success = false;
        $msg = '';

        if ($_POST['action'] === 'update' && $maKH) {
            $success = $dbManager->updateKhachHang($maKH, $hoTen, $ngaySinh, $dt, $email);
            $msg = 'Cập nhật khách hàng thành công.';
        } else if ($_POST['action'] === 'add') {
            $success = $dbManager->addKhachHang($hoTen, $ngaySinh, $dt, $email);
            $msg = 'Thêm khách hàng thành công.';
        }

        if ($success) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            $error_msg = ($_POST['action'] === 'add') ? 'Lỗi: Thao tác thêm mới thất bại (Kiểm tra kết nối CSDL).' : 'Lỗi: Thao tác cập nhật thất bại.';
            echo json_encode(['success' => false, 'message' => $error_msg]);
        }
        exit();
    }

    if ($_POST['action'] === 'get_details' && isset($_POST['MaKH'])) {
        $maKH = $_POST['MaKH'];
        $customer = $dbManager->getKhachHangById($maKH);
        if ($customer) {
            echo json_encode(['success' => true, 'data' => $customer]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng.']);
        }
        exit();
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login_admin.php");
    exit();
}

$searchQuery = $_GET['search'] ?? '';

$khachHangResult = $dbManager->getListKhachHang($searchQuery);
$stats = $dbManager->getCustomerStats();

$currentAdminName = $_SESSION['admin_fullname'] ?? 'Quản trị viên chính';

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Quản lý Khách hàng - Hoa Tươi Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
    <style>
        .stats-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-grow: 1;
            border-left: 5px solid #e91e63;
        }

        .stat-card strong {
            font-size: 32px;
            color: #e91e63;
        }

        .stat-card p {
            color: #888;
            font-size: 14px;
        }

        .stat-card .icon-box {
            font-size: 30px;
            color: #ff66a3;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        #customerModal label[for="customer-id"],
        #customer-id {
            display: block;
        }


        .customer-list table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .customer-list th,
        .customer-list td {
            border: 1px solid #eee;
            padding: 12px;
            text-align: left;
        }

        .customer-list th {
            background-color: #f8f8f8;
            color: #333;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: #fff;
            border: 1px dashed #ffd6e8;
            border-radius: 12px;
            margin-top: 20px;
        }

        .empty-state i {
            font-size: 40px;
            color: #e91e63;
            margin-bottom: 15px;
        }

        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-section input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            width: 300px;
        }

        .sidebar-logout-btn {
            background-color: #e91e63;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            margin: 20px 10px 10px 10px;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 10px rgba(233, 30, 99, 0.4);
            width: calc(100% - 20px);
            font-weight: bold;
        }

        .sidebar-logout-btn:hover {
            background-color: #ff66a3;
            box-shadow: 0 6px 15px rgba(233, 30, 99, 0.5);
        }

        .sidebar-logout-btn i {
            margin-right: 10px;
            font-size: 18px;
        }

        .welcome-message {
            color: #ccc;
            padding: 15px 15px 5px 15px;
            font-size: 14px;
            border-bottom: 1px solid #333;
            margin-bottom: 10px;
        }

        .welcome-message span {
            color: white;
            font-weight: bold;
            display: inline-block;
            margin-left: 5px;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
        }
    </style>
</head>

<body>
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2><span id="modalTitle">Thêm Khách hàng mới</span></h2>
            <form id="customer-form">
                <input type="hidden" id="form-action-type" value="add">

                <label for="customer-id">Mã Khách hàng:</label>
                <input type="text" id="customer-id" name="MaKH" required>

                <label>Họ Tên:</label>
                <input type="text" id="customer-name" name="HoTen" required>

                <label>Ngày Sinh:</label>
                <input type="date" id="customer-dob" name="NgaySinh" required>

                <label>SĐT:</label>
                <input type="text" id="customer-phone" name="DT" required>

                <label>Email:</label>
                <input type="email" id="customer-email" name="Email" required>

                <div class="form-actions" style="margin-top: 20px; text-align: right;">
                    <button type="button" class="action-btn" onclick="closeModal()"
                        style="background-color: #aaa;">Hủy</button>
                    <button type="submit" class="action-btn">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    <div id="product-form-overlay" style="display:none;">
    </div>

    <div class="content-wrapper">
        <div class="sidebar">
            <div class="shop-logo">
                <div class="logo-visual">
                    <i class="fas fa-spa"></i>
                </div>
                <h2 class="logo-title">NĂM ANH EM BÁN <span class="accent-glow">HOA</span></h2>
            </div>
            <p class="welcome-message"> Xin chào, <span><?php echo htmlspecialchars($currentAdminName); ?></span>!</p>
            <nav>
                <a href="qlsp.php"><i class="fa-solid fa-leaf"></i> Quản lý Sản phẩm</a>
                <a href="admin_donhang.php"><i class="fa-solid fa-receipt"></i> Đơn hàng</a>
                <a href="admin_khachhang.php" class="active"><i class="fa-solid fa-users"></i> Khách hàng</a>
                <a href="admin_nhanvien.php"><i class="fa-solid fa-user-tie"></i> Nhân viên</a>
                <a href="admin_thongke.php"><i class="fa-solid fa-chart-simple"></i> Thống kê</a>
                <a href="admin_taikhoan_nv.php"><i class="fa-solid fa-key"></i> Quản lý TK NV</a>
            </nav>
            <button class="sidebar-logout-btn" onclick="logoutConfirm()">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </button>
        </div>

        <div class="main">
            <div class="header">
                <h1>Quản lý Khách hàng</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="info">
                        <p>Tổng số Khách hàng</p>
                        <strong><?php echo $stats['total']; ?></strong>
                    </div>
                    <div class="icon-box">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="info">
                        <p>Khách hàng mới hôm nay</p>
                        <strong><?php echo $stats['new_today']; ?></strong>
                    </div>
                    <div class="icon-box">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                </div>
                <div class="stat-card" style="opacity: 0.5;">
                    <div class="info">
                        <p>Khách hàng tiềm năng (Đã loại bỏ)</p>
                        <strong>-</strong>
                    </div>
                    <div class="icon-box">
                        <i class="fa-solid fa-star"></i>
                    </div>
                </div>
            </div>

            <div class="filter-section">
                <button class="action-btn" onclick="openModal('add')" style="background-color: #007bff;">+ Thêm Khách hàng</button>
            </div>

            <div class="customer-list">
                <?php if ($khachHangResult && $khachHangResult->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã KH</th>
                                <th>Họ Tên</th>
                                <th>Ngày Sinh</th>
                                <th>SĐT</th>
                                <th>Email</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="customer-table">
                            <?php while ($row = $khachHangResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['MaKH']); ?></td>
                                    <td><?php echo htmlspecialchars($row['HoTen']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NgaySinh']); ?></td>
                                    <td><?php echo htmlspecialchars($row['DT']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td>
                                        <button class="action-btn edit-btn" data-id="<?php echo htmlspecialchars($row['MaKH']); ?>">Sửa</button>
                                        <button class="action-btn delete-btn" data-id="<?php echo htmlspecialchars($row['MaKH']); ?>">Xóa</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-box-open"></i>
                        <p>Chưa có thông tin khách hàng nào được ghi nhận.</p>
                        <small style="color: #c08497; display: block; margin-top: 10px;">Dữ liệu sẽ được tự động cập nhật khi có đơn hàng mới.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <p>Địa chỉ: Cs2-Học viện Hàng không Việt Nam</p>
                <p>Email: namanhembanhoa@gmail.com</p>
            </div>
            <div class="footer-col">
                <p>Điện thoại: 0123456789</p>
                <p>© 2025 NĂM ANH EM BÁN HOA. Bảo lưu mọi quyền.</p>
            </div>
        </div>
    </footer>
    <script>
        const customerModal = document.getElementById('customerModal');
        const customerForm = document.getElementById('customer-form');

        function toggleSidebar() {
            const body = document.body;
            body.classList.toggle("sidebar-visible");
        }

        function openModal(action, data = {}) {
            customerForm.reset();
            document.getElementById('form-action-type').value = action;
            document.getElementById('modalTitle').textContent = action === 'add' ? 'Thêm Khách hàng mới' : 'Sửa Khách hàng';

            const maKHInput = document.getElementById('customer-id');
            const maKHLabel = maKHInput.previousElementSibling;

            if (action === 'update') {
                maKHInput.readOnly = true;
                maKHInput.value = data.MaKH;
                maKHInput.style.display = 'block';
                maKHLabel.style.display = 'block';

                document.getElementById('customer-name').value = data.HoTen;
                document.getElementById('customer-dob').value = data.NgaySinh;
                document.getElementById('customer-phone').value = data.DT;
                document.getElementById('customer-email').value = data.Email;
            } else {
                maKHInput.readOnly = false;
                maKHInput.value = '';
                maKHInput.style.display = 'none';
                maKHLabel.style.display = 'none';
            }
            customerModal.style.display = 'block';
        }

        function closeModal() {
            customerModal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == customerModal) {
                closeModal();
            }
        }

        function logoutConfirm() {
            if (confirm("Bạn có chắc chắn muốn đăng xuất khỏi hệ thống không?")) {
                const loginPage = "../login_admin.php";
                window.history.replaceState(null, document.title, loginPage);
                window.location.replace(loginPage);
                alert("Đã đăng xuất! Chuyển hướng tới trang Đăng nhập...");
            }
        }


        customerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const action = document.getElementById('form-action-type').value;

            const formData = new URLSearchParams(new FormData(customerForm));
            formData.append('action', action);

            fetch('admin_khachhang.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        closeModal();
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Lỗi lưu dữ liệu:', error);
                    alert('Có lỗi xảy ra khi lưu dữ liệu khách hàng.');
                });
        });

        document.getElementById('customer-table')?.addEventListener('click', function(e) {
            const target = e.target;
            const maKH = target.getAttribute('data-id');

            if (!maKH) return;

            if (target.classList.contains('delete-btn')) {
                if (confirm(`Bạn có chắc chắn muốn xóa khách hàng Mã: ${maKH} không?`)) {
                    const formData = new URLSearchParams();
                    formData.append('action', 'delete');
                    formData.append('MaKH', maKH);

                    fetch('admin_khachhang.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.success) window.location.reload();
                        })
                        .catch(error => {
                            alert('Có lỗi xảy ra khi xóa.');
                        });
                }
            }

            if (target.classList.contains('edit-btn')) {
                const formData = new URLSearchParams();
                formData.append('action', 'get_details');
                formData.append('MaKH', maKH);

                fetch('admin_khachhang.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            openModal('update', data.data);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi tải chi tiết:', error);
                        alert('Có lỗi xảy ra khi tải chi tiết.');
                    });
            }
        });
    </script>
</body>

</html>