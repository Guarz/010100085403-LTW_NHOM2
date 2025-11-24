<?php
session_start();
require_once '../DatabaseManager.php';

$dbManager = new DatabaseManager();

$currentAdminName = $_SESSION['admin_fullname'] ?? 'Quản trị viên chính';

if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: Chưa đăng nhập.']);
        exit();
    }

    if ($_POST['action'] === 'get_details' && isset($_POST['MaNV'])) {
        $maNV = $_POST['MaNV'];
        $account = $dbManager->getTaiKhoanNVById($maNV);

        $account
            ? print json_encode(['success' => true, 'data' => $account])
            : print json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']);
        exit();
    }

    if ($_POST['action'] === 'get_log' && isset($_POST['MaNV'])) {
        $maNV = $_POST['MaNV'];
        $log = $dbManager->getEmployeeActionLog($maNV);

        !empty($log)
            ? print json_encode(['success' => true, 'data' => $log])
            : print json_encode(['success' => false, 'message' => 'Không có lịch sử thao tác nào được ghi nhận.']);
        exit();
    }

    if ($_POST['action'] === 'delete' && isset($_POST['MaNV'])) {
        $maNV = $_POST['MaNV'];
        if ($dbManager->deleteTaiKhoanNV($maNV)) {
            echo json_encode(['success' => true, 'message' => 'Xóa tài khoản nhân viên thành công.']);
        } else {
            $errorMsg = 'Lỗi: Không thể xóa tài khoản. Vui lòng kiểm tra log CSDL.';
            if ($dbManager->db->errno == 1451) {
                $errorMsg = 'Lỗi Khóa ngoại: Tài khoản này đang liên kết với dữ liệu khác (ví dụ: sản phẩm, đơn hàng).';
            } else if ($dbManager->db->error) {
                $errorMsg = 'Lỗi CSDL: ' . $dbManager->db->error;
            }
            echo json_encode(['success' => false, 'message' => $errorMsg]);
        }
        exit();
    }

    if (($_POST['action'] === 'update' || $_POST['action'] === 'add') &&
        isset($_POST['MaNV'], $_POST['TaiKhoan']) &&
        (isset($_POST['MatKhau']) || $_POST['action'] === 'update')
    ) {
        $maNV = $_POST['MaNV'];
        $taiKhoan = $_POST['TaiKhoan'];
        $matKhau = $_POST['MatKhau'] ?? '';

        $success = false;
        $msg = '';

        if ($_POST['action'] === 'update') {
            $success = $dbManager->updateTaiKhoanNV($maNV, $taiKhoan, $matKhau);
            $msg = 'Cập nhật tài khoản thành công.';
        } else { 
            if (empty($matKhau)) {
                print json_encode(['success' => false, 'message' => 'Lỗi: Mật khẩu là bắt buộc khi thêm mới.']);
                exit();
            }
            $success = $dbManager->addTaiKhoanNV($maNV, $taiKhoan, $matKhau);
            $msg = 'Thêm tài khoản thành công.';
        }

        if ($success) {
            print json_encode(['success' => true, 'message' => $msg]);
        } else {
            $errorMsg = 'Lỗi: Thao tác thất bại.';
            if ($dbManager->db->errno == 1062) {
                $errorMsg = 'Lỗi: Mã NV hoặc Tài khoản đã tồn tại (MaNV phải là duy nhất).';
            } else if ($dbManager->db->errno == 1452) {
                $errorMsg = 'Lỗi: Mã NV không hợp lệ (Có thể Mã NV đã tồn tại hoặc thiếu các trường dữ liệu bắt buộc khác).';
            } else if ($dbManager->db->error) {
                $errorMsg = 'Lỗi CSDL: ' . $dbManager->db->error;
            }
            print json_encode(['success' => false, 'message' => $errorMsg]);
        }
        exit();
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login_admin.php");
    exit();
}

$taiKhoanResult = $dbManager->getListTaiKhoanNV();
$taiKhoanArray = $taiKhoanResult ? $taiKhoanResult->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Quản lý Tài khoản Nhân viên - Hoa Tươi Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
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

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .form-actions {
            text-align: right;
            margin-top: 20px;
        }

        #employee-table-container {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        #employee-table-container th,
        #employee-table-container td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        #employee-table-container th {
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

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .view-log-btn {
            background-color: #007bff;
            color: white;
        }

        .add-product-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            display: block;
            margin: 20px auto;
        }

        .position-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: #fff;
            font-weight: bold;
        }

        .position-manager {
            background-color: #e91e63;
        }

        .position-staff {
            background-color: #3498db;
        }

        .position-shipper {
            background-color: #27ae60;
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
    <div id="accountModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('accountModal')">&times;</span>
            <h2 id="modalTitle">Thêm Tài khoản Nhân viên</h2>
            <form id="account-form">
                <input type="hidden" id="form-action-type" value="add">

                <div class="form-group">
                    <label for="employee-id">Mã Nhân Viên (MaNV):</label>
                    <input type="text" id="employee-id" name="MaNV" required>
                </div>

                <div class="form-group">
                    <label for="employee-username">Tài Khoản (Username):</label>
                    <input type="text" id="employee-username" name="TaiKhoan" required>
                </div>

                <div class="form-group">
                    <label for="employee-password">Mật Khẩu (Bắt buộc khi thêm mới):</label>
                    <input type="password" id="employee-password" name="MatKhau" required>
                </div>

                <div class="form-actions">
                    <button type="button" class="action-btn delete-btn" onclick="closeModal('accountModal')">Hủy</button>
                    <button type="submit" class="action-btn edit-btn">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <div id="logModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('logModal')">&times;</span>
            <h2>Lịch sử Thao tác: <span id="logMaNV"></span></h2>
            <div id="log-details-content">
                <p>Nội dung lịch sử...</p>
            </div>
        </div>
    </div>

    <button class="hamburger-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

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
                <a href="admin_khachhang.php"><i class="fa-solid fa-users"></i> Khách hàng</a>
                <a href="admin_nhanvien.php"><i class="fa-solid fa-user-tie"></i> Nhân viên</a>
                <a href="admin_thongke.php"><i class="fa-solid fa-chart-simple"></i> Thống kê</a>
                <a href="admin_taikhoan_nv.php" class="active"><i class="fa-solid fa-key"></i> Quản lý TK NV</a>
            </nav>
            <button class="sidebar-logout-btn" onclick="logoutConfirm()">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </button>
        </div>

        <div class="main">
            <div class="header">
                <h1>Quản lý Tài khoản Nhân viên</h1>
            </div>

            <table id="employee-table-container">
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Họ Tên</th>
                        <th>Vị trí</th>
                        <th>Tài Khoản</th>
                        <th>Mật Khẩu</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="employee-table">
                    <?php if (!empty($taiKhoanArray)): ?>
                        <?php foreach ($taiKhoanArray as $account):
                            $position = DatabaseManager::translateViTri1($account['ViTri']);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($account['MaNV']); ?></td>
                                <td><?php echo htmlspecialchars($account['HoTen']); ?></td>
                                <td><span class="position-badge <?php echo $position['class']; ?>"><?php echo $position['text']; ?></span></td>
                                <td><?php echo htmlspecialchars($account['taikhoan']); ?></td>
                                <td>********</td>
                                <td><?php echo date('d/m/Y', strtotime($account['created_at'])); ?></td>
                                <td>
                                    <button class="action-btn edit-btn" data-id="<?php echo htmlspecialchars($account['MaNV']); ?>">Sửa</button>
                                    <button class="action-btn delete-btn" data-id="<?php echo htmlspecialchars($account['MaNV']); ?>">Xóa</button>
                                    <button class="action-btn view-log-btn" data-id="<?php echo htmlspecialchars($account['MaNV']); ?>">Lịch sử</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="7" style="text-align:center; color:#888; padding: 20px;">Chưa có tài khoản nào được tạo.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <button class="add-product-button" onclick="openAccountForm('add')">+ Thêm Tài khoản</button>
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
        function openModal(id) {
            document.getElementById(id).style.display = "flex";
        }

        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }

        function toggleSidebar() {
            const body = document.body;
            body.classList.toggle("sidebar-visible");
        }

        function logoutConfirm() {
            if (confirm("Bạn có chắc chắn muốn đăng xuất khỏi hệ thống không?")) {
                const loginPage = "/flowershop/Role_login.php";
                window.history.replaceState(null, document.title, loginPage);
                window.location.replace(loginPage);
                alert("Đã đăng xuất! Chuyển hướng tới trang Đăng nhập...");
            }
        }


        function openAccountForm(action, data = {}) {
            const form = document.getElementById('account-form');
            form.reset();

            document.getElementById('form-action-type').value = action;
            document.getElementById('modalTitle').textContent = action === 'add' ? 'Thêm Tài khoản Nhân viên' : 'Cập nhật Tài khoản';

            const maNVInput = document.getElementById('employee-id');
            const passInput = document.getElementById('employee-password');
            const passLabel = document.querySelector('label[for="employee-password"]');


            maNVInput.readOnly = action === 'update';

            if (action === 'update') {
                maNVInput.value = data.MaNV;
                document.getElementById('employee-username').value = data.taikhoan;
                passInput.value = "";
                passInput.required = false; 
                passLabel.textContent = "Mật Khẩu (Để trống nếu không đổi):";
            } else {
                passInput.required = true;
                passLabel.textContent = "Mật Khẩu (Bắt buộc khi thêm mới):";
                maNVInput.value = "";
                document.getElementById('employee-username').value = "";
            }

            openModal('accountModal');
        }


        document.getElementById('account-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const action = document.getElementById('form-action-type').value;
            const formElement = document.getElementById('account-form');
            const formData = new URLSearchParams(new FormData(formElement));
            formData.append('action', action);

            fetch('admin_taikhoan_nv.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        closeModal('accountModal');
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Lỗi lưu dữ liệu:', error);
                    alert('Có lỗi xảy ra khi lưu dữ liệu tài khoản.');
                });
        });

        document.getElementById('employee-table').addEventListener('click', function(e) {
            const target = e.target;
            const maNV = target.getAttribute('data-id');

            if (!maNV) return;

            if (target.classList.contains("delete-btn")) {
                if (confirm(`Bạn có chắc chắn muốn xóa tài khoản Mã NV: ${maNV} không?`)) {
                    const deleteData = new URLSearchParams();
                    deleteData.append('action', 'delete');
                    deleteData.append('MaNV', maNV);

                    fetch('admin_taikhoan_nv.php', {
                            method: 'POST',
                            body: deleteData
                        })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.success) {
                                window.location.reload();
                            }
                        })
                        .catch(error => {
                            alert('Lỗi xóa tài khoản.');
                        });
                }
            }

            if (target.classList.contains("edit-btn")) {
                const fetchDetails = new URLSearchParams();
                fetchDetails.append('action', 'get_details');
                fetchDetails.append('MaNV', maNV);

                fetch('admin_taikhoan_nv.php', {
                        method: 'POST',
                        body: fetchDetails
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            openAccountForm('update', data.data);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        alert('Lỗi tải chi tiết tài khoản.');
                    });
            }

            if (target.classList.contains("view-log-btn")) {
                document.getElementById('logMaNV').textContent = maNV;
                document.getElementById('log-details-content').innerHTML = 'Đang tải lịch sử... (Chức năng này chỉ là mẫu)';

                const fetchLog = new URLSearchParams();
                fetchLog.append('action', 'get_log');
                fetchLog.append('MaNV', maNV);

                fetch('admin_taikhoan_nv.php', {
                        method: 'POST',
                        body: fetchLog
                    })
                    .then(response => response.json())
                    .then(data => {
                        const contentDiv = document.getElementById('log-details-content');
                        if (data.success) {
                            let logHTML = '<ul style="list-style-type: none; padding: 0;">';
                            data.data.forEach(item => {
                                logHTML += `<li style="margin-bottom: 8px; border-bottom: 1px dotted #ccc; padding-bottom: 5px;"><strong>[${item.timestamp}]</strong>: ${item.action}</li>`;
                            });
                            logHTML += '</ul>';
                            contentDiv.innerHTML = logHTML;
                        } else {
                            contentDiv.innerHTML = `<p style="color: #e91e63; text-align: center;">${data.message}</p>`;
                        }
                        openModal('logModal');
                    })
                    .catch(error => {
                        console.error('Lỗi tải log:', error);
                        alert('Lỗi tải lịch sử thao tác.');
                    });
            }
        });
    </script>
</body>

</html>