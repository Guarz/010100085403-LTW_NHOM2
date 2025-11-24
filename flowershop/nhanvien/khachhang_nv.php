<?php
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);

session_start();
require_once '../DatabaseManager.php';

$dbManager = new DatabaseManager();

$currentUserName = $_SESSION['user_name'] ?? 'Nhân viên';

if (isset($_POST['action']) || isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: Chưa đăng nhập.']);
        exit();
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'delete' && isset($_POST['MaKH'])) {
            $maKH = $_POST['MaKH'];
            if ($dbManager->deleteKhachHang($maKH)) {
                echo json_encode(['success' => true, 'message' => 'Xóa khách hàng thành công. (Đã xóa đơn hàng liên quan)']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi: Không thể xóa khách hàng. Đã xảy ra lỗi CSDL không xác định.']);
            }
            exit();
        }

        if ($action === 'get_details' && isset($_POST['MaKH'])) {
            $maKH = $_POST['MaKH'];
            $customer = $dbManager->getKhachHangById($maKH);
            if ($customer) {
                $customer['NgaySinh'] = date('Y-m-d', strtotime($customer['NgaySinh']));
                echo json_encode(['success' => true, 'data' => $customer]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng.']);
            }
            exit();
        }

        if (($action === 'update' || $action === 'add') &&
            isset($_POST['HoTen'], $_POST['NgaySinh'], $_POST['DT'], $_POST['Email'])
        ) {
            $hoTen = $_POST['HoTen'];
            $ngaySinh = $_POST['NgaySinh'];
            $sdt = $_POST['DT'];
            $email = $_POST['Email'];

            $success = false;

            if ($action === 'update') {
                $maKH = $_POST['MaKH'] ?? null;
                if ($maKH) {
                    $success = $dbManager->updateKhachHang($maKH, $hoTen, $ngaySinh, $sdt, $email);
                }
                $msg = 'Cập nhật khách hàng thành công.';
            } else {
                $success = $dbManager->addKhachHang($hoTen, $ngaySinh, $sdt, $email);
                $msg = 'Thêm khách hàng thành công.';
            }

            if ($success) {
                echo json_encode(['success' => true, 'message' => $msg]);
            } else {
                echo json_encode(['success' => false, 'message' => $errorMsg]);
            }
            exit();
        }
    }
}

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../Role_login.php");
    exit();
}
if (($_SESSION['user_role'] ?? '') !== 'employee') {
    header("Location: ../chủ/qlsp.php");
    exit();
}


$searchQuery = $_GET['search'] ?? '';

$khachHangResult = $dbManager->getListKhachHang($searchQuery);
$stats = $dbManager->getCustomerStats();

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
    <link rel="stylesheet" href="../chủ/style.css" />
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
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
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

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
            font-size: 13px;
            margin-left: 5px;
        }

        .edit-btn {
            background-color: #ff66a3;
            color: white;
        }

        .edit-btn:hover {
            background-color: #e91e63;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .filter-section .action-btn {
            border-radius: 8px;
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

                <div id="ma-kh-group">
                    <label>Mã Khách hàng:</label>
                    <input type="text" id="customer-id" name="MaKH" required>
                </div>

                <label>Họ Tên:</label>
                <input type="text" id="customer-name" name="HoTen" required>

                <label>Ngày Sinh:</label>
                <input type="date" id="customer-dob" name="NgaySinh" required>

                <label>SĐT:</label>
                <input type="text" id="customer-phone" name="DT" required>

                <label>Email:</label>
                <input type="email" id="customer-email" name="Email" required>

                <div class="form-actions" style="margin-top: 20px; text-align: right;">
                    <button type="button" class="action-btn delete-btn" onclick="closeModal()"
                        style="background-color: #aaa;">Hủy</button>
                    <button type="submit" class="action-btn edit-btn">Lưu</button>
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
            <p class="welcome-message"> Xin chào, <span><?php echo htmlspecialchars($currentUserName); ?></span>!</p>
            <nav>
                <a href="qlsp_nv.php"><i class="fa-solid fa-leaf"></i> Quản lý Sản phẩm</a>
                <a href="donhang_nhanvien.php"><i class="fa-solid fa-receipt"></i> Đơn hàng</a>
                <a href="khachhang_nv.php" class="active"><i class="fa-solid fa-users"></i> Khách hàng</a>
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
                <div class="stat-card">
                    <div class="info">
                        <p>Khách hàng tiềm năng</p>
                        <strong><?php echo $stats['potential']; ?></strong>
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
        document.addEventListener('DOMContentLoaded', function() {
            const customerModal = document.getElementById('customerModal');
            const customerForm = document.getElementById('customer-form');
            const customerTableBody = document.getElementById('customer-table');

            function openModal(action, data = {}) {
                customerForm.reset();
                document.getElementById('form-action-type').value = action;
                document.getElementById('modalTitle').textContent = action === 'add' ? 'Thêm Khách hàng mới' : 'Sửa Khách hàng';

                const maKHGroup = document.getElementById('ma-kh-group');
                const maKHInput = document.getElementById('customer-id');

                if (action === 'update') {
                    maKHGroup.style.display = 'block';
                    maKHInput.readOnly = true;
                    maKHInput.required = true;

                    maKHInput.value = data.MaKH;
                    document.getElementById('customer-name').value = data.HoTen;
                    document.getElementById('customer-dob').value = data.NgaySinh ? new Date(data.NgaySinh).toISOString().substring(0, 10) : '';
                    document.getElementById('customer-phone').value = data.DT || '';
                    document.getElementById('customer-email').value = data.Email;
                } else {
                    maKHGroup.style.display = 'none';
                    maKHInput.readOnly = false;
                    maKHInput.required = false;
                    maKHInput.value = '';
                }
                customerModal.style.display = 'flex';
            }

            function closeModal() {
                customerModal.style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target == customerModal) {
                    closeModal();
                }
            }

            window.openModal = openModal;
            window.closeModal = closeModal;

            function toggleSidebar() {
                const body = document.body;
                body.classList.toggle("sidebar-visible");
            }
            window.toggleSidebar = toggleSidebar;

            function logoutConfirm() {
                if (confirm("Bạn có chắc chắn muốn đăng xuất khỏi hệ thống không?")) {
                    const loginPage = "../Role_login.php";
                    window.location.replace(loginPage);
                }
            }
            window.logoutConfirm = logoutConfirm;

            customerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const action = document.getElementById('form-action-type').value;
                const formData = new URLSearchParams(new FormData(customerForm));
                formData.append('action', action);

                if (action === 'add') {
                    formData.delete('MaKH');
                }

                fetch('khachhang_nv.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.includes("application/json")) {
                            return response.json();
                        } else {
                            return response.text().then(text => {
                                console.error('Lỗi PHP Server:', text);
                                alert(`Lỗi: Thao tác ${action === 'add' ? 'thêm' : 'sửa'} thất bại do lỗi server. Vui lòng kiểm tra console.`);
                                throw new Error('Invalid server response');
                            });
                        }
                    })
                    .then(data => {
                        alert(data.message);
                        if (data.success) {
                            closeModal();
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi lưu dữ liệu:', error);
                    });
            });

            if (customerTableBody) {
                customerTableBody.addEventListener('click', function(e) {
                    const target = e.target.closest('button');
                    if (!target) return;

                    const maKH = target.getAttribute('data-id');
                    if (!maKH) return;

                    if (target.classList.contains('delete-btn')) {
                        if (confirm(`Bạn có chắc chắn muốn xóa khách hàng Mã: ${maKH} không? Thao tác này sẽ xóa mọi đơn hàng liên quan.`)) {
                            const formData = new URLSearchParams();
                            formData.append('action', 'delete');
                            formData.append('MaKH', maKH);

                            fetch('khachhang_nv.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    alert(data.message);
                                    if (data.success) window.location.reload();
                                })
                                .catch(error => {
                                    console.error('Lỗi xóa dữ liệu:', error);
                                    alert('Có lỗi xảy ra khi xóa. Vui lòng kiểm tra console.');
                                });
                        }
                    }

                    if (target.classList.contains('edit-btn')) {
                        const formData = new URLSearchParams();
                        formData.append('action', 'get_details');
                        formData.append('MaKH', maKH);

                        fetch('khachhang_nv.php', {
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
                                alert('Có lỗi xảy ra khi tải chi tiết. Vui lòng kiểm tra console.');
                            });
                    }
                });
            }
        });
    </script>
</body>

</html>