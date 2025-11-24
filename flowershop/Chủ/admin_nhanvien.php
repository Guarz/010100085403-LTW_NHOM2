<?php
session_start();
require_once '../DatabaseManager.php';

$dbManager = new DatabaseManager();

$currentAdminName = $_SESSION['admin_fullname'] ?? 'Admin';

if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: Chưa đăng nhập.']);
        exit();
    }

    if ($_POST['action'] === 'get_details' && isset($_POST['MaNV'])) {
        $maNV = $_POST['MaNV'];
        $employee = $dbManager->getNhanVienById($maNV);

        if ($employee) {
            $employee['NgaySinh'] = date('Y-m-d', strtotime($employee['NgaySinh']));
            $employee['NgayLam'] = date('Y-m-d', strtotime($employee['NgayLam']));
        }

        $employee
            ? print json_encode(['success' => true, 'data' => $employee])
            : print json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên.']);
        exit();
    }

    if ($_POST['action'] === 'delete' && isset($_POST['MaNV'])) {
        $maNV = $_POST['MaNV'];
        if ($dbManager->deleteNhanVien($maNV)) {
            echo json_encode(['success' => true, 'message' => 'Xóa nhân viên thành công.']);
        } else {
            echo json_encode(['success' => false, 'message' => $message]);
        }
        exit();
    }

    if (
        ($_POST['action'] === 'update' && isset($_POST['MaNV'])) ||
        ($_POST['action'] === 'add')
    ) {
        if (!isset($_POST['HoTen'], $_POST['NgaySinh'], $_POST['DT'], $_POST['ViTri'], $_POST['NgayLam'], $_POST['Luong'], $_POST['TrangThai'])) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: Thiếu dữ liệu bắt buộc.']);
            exit();
        }

        $maNV = $_POST['MaNV'] ?? null;
        $hoTen = $_POST['HoTen'];
        $ngaySinh = $_POST['NgaySinh'];
        $dt = $_POST['DT'];
        $viTri = $_POST['ViTri'];
        $ngayLam = $_POST['NgayLam'];
        $luong = (float)$_POST['Luong'];
        $trangThai = $_POST['TrangThai'];
        $matKhau = $_POST['MatKhau'] ?? null;

        $success = false;
        if ($_POST['action'] === 'update') {
            $success = $dbManager->updateNhanVien($maNV, $hoTen, $ngaySinh, $dt, $viTri, $ngayLam, $luong, $matKhau, $trangThai);
            $msg = 'Cập nhật nhân viên thành công.';
        } else {
            if (empty($matKhau)) {
                print json_encode(['success' => false, 'message' => 'Lỗi: Vui lòng nhập mật khẩu khi thêm nhân viên mới.']);
                exit();
            }
            $success = $dbManager->addNhanVien($hoTen, $ngaySinh, $dt, $viTri, $ngayLam, $luong, $matKhau, $trangThai);
            $msg = 'Thêm nhân viên thành công.';
        }

        if ($success) {
            print json_encode(['success' => true, 'message' => $msg]);
        } else {
            print json_encode(['success' => false, 'message' => $errorMsg]);
        }
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Yêu cầu hành động không hợp lệ hoặc thiếu tham số.']);
    exit();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login_admin.php");
    exit();
}

$nhanVienResult = $dbManager->getListNhanVien();
$nhanVienArray = $nhanVienResult ? $nhanVienResult->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Quản lý Nhân viên - Hoa Tươi Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
    <style>
        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .employee-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 20px;
            position: relative;
            transition: all 0.3s ease;
            border-top: 5px solid var(--accent-color, #e91e63);
        }

        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #eee;
        }

        .avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            font-size: 30px;
            text-align: center;
            line-height: 70px;
            font-weight: 700;
            color: white;
        }

        .name {
            font-size: 22px;
            font-weight: 700;
            color: #333;
        }

        .role {
            font-size: 14px;
            color: #888;
            margin-top: 5px;
            display: flex;
            align-items: center;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .active-status {
            background-color: #e6ffe6;
            color: #008000;
        }

        .inactive-status {
            background-color: #ffe6e6;
            color: #cc0000;
        }


        .card-details p {
            font-size: 14px;
            color: #555;
            margin: 8px 0;
            display: flex;
            align-items: center;
            border-bottom: 1px dotted #f5f5f5;
            padding-bottom: 3px;
        }

        .card-details i {
            width: 25px;
            color: #e91e63;
            margin-right: 5px;
        }

        .card-details p:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .card-actions {
            margin-top: 15px;
            padding-top: 10px;
            text-align: right;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
            font-size: 13px;
        }

        .edit-btn {
            background-color: #ff66a3;
            color: white;
            margin-right: 10px;
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .add-employee-button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .add-employee-button:hover {
            background-color: #0056b3;
        }

        .add-employee-button i {
            margin-right: 8px;
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

        .sidebar-logout-btn {
            background-color: #e91e63;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
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

        .form-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 550px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
            max-height: 90vh;
            overflow-y: auto;
        }

        .form-container h2 {
            text-align: center;
            color: #e91e63;
            margin-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 15px;
        }

        .form-group:only-child {
            flex: none;
            width: 100%;
        }

        .form-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-container input:not([type="hidden"]),
        .form-container select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .save-btn {
            background-color: #e91e63;
        }

        .save-btn:hover {
            background-color: #ff66a3;
        }
    </style>
</head>

<body>
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
                <a href="admin_khachhang.php"><i class="fa-solid fa-users"></i> Khách hàng</a>
                <a href="admin_nhanvien.php" class="active"><i class="fa-solid fa-user-tie"></i> Nhân viên</a>
                <a href="admin_thongke.php"><i class="fa-solid fa-chart-simple"></i> Thống kê</a>
                <a href="admin_taikhoan_nv.php"><i class="fa-solid fa-key"></i> Quản lý TK NV</a>
            </nav>
            </nav>
            <button class="sidebar-logout-btn" onclick="logoutConfirm()">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </button>
        </div>

        <div class="main">
            <div class="header">
                <h1>Quản lý Nhân viên</h1>
                <button class="add-employee-button" id="add-employee-btn" onclick="openForm('add')">
                    <i class="fa-solid fa-user-plus"></i> Thêm Nhân viên
                </button>
            </div>

            <div class="employee-grid" id="employee-grid">
                <?php
                if (!empty($nhanVienArray)):
                    foreach ($nhanVienArray as $employee):
                        $statusText = DatabaseManager::translateTrangThaiNV($employee['TrangThai']);
                        $statusClass = $employee['TrangThai'] === 'DangLam' ? 'active-status' : 'inactive-status';
                        $firstLetter = mb_substr($employee['HoTen'], 0, 1, 'UTF-8');
                        $viTriText = DatabaseManager::translateViTri($employee['ViTri']);

                        $avatarColor = substr(md5($employee['MaNV']), 0, 6);
                ?>
                        <div class="employee-card" data-id="<?php echo htmlspecialchars($employee['MaNV']); ?>">
                            <div class="card-header">
                                <div class="avatar" style="background-color: #<?php echo $avatarColor; ?>;"><?php echo htmlspecialchars($firstLetter); ?></div>
                                <div class="name"><?php echo htmlspecialchars($employee['HoTen']); ?></div>
                                <div class="role"><?php echo $viTriText; ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-details">
                                <p><i class="fa-solid fa-id-badge"></i> Mã NV: <?php echo htmlspecialchars($employee['MaNV']); ?></p>
                                <p><i class="fa-solid fa-calendar"></i> Sinh: <?php echo date('d/m/Y', strtotime($employee['NgaySinh'])); ?></p>
                                <p><i class="fa-solid fa-phone"></i> SĐT: <?php echo htmlspecialchars($employee['DT']); ?></p>
                                <p><i class="fa-solid fa-clock"></i> Vào làm: <?php echo date('d/m/Y', strtotime($employee['NgayLam'])); ?></p>
                                <p><i class="fa-solid fa-money-bill-wave"></i> Lương: <?php echo DatabaseManager::formatCurrencyPHP($employee['Luong']); ?> VNĐ</p>
                            </div>
                            <div class="card-actions">
                                <button class="action-btn edit-btn" data-id="<?php echo htmlspecialchars($employee['MaNV']); ?>">Sửa</button>
                                <button class="action-btn delete-btn" data-id="<?php echo htmlspecialchars($employee['MaNV']); ?>" style="background-color: #e74c3c;">Xóa</button>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <p style="text-align: center; color: #888; padding: 20px; grid-column: 1 / -1;">Chưa có nhân viên nào trong danh sách.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-overlay" id="employee-form-overlay" style="display: none;">
        <div class="form-container">
            <h2 id="form-title">Thêm Nhân viên</h2>
            <form id="employee-form">
                <input type="hidden" id="form-action-type" value="add">

                <div class="form-group">
                    <label for="employee-id">Mã NV</label>
                    <input type="text" id="employee-id" name="MaNV" required>
                </div>

                <div class="form-group">
                    <label for="employee-name">Họ và Tên</label>
                    <input type="text" id="employee-name" name="HoTen" required>
                </div>

                <div class="form-group" id="password-group">
                    <label for="employee-password">Mật khẩu (*Bắt buộc)</label>
                    <input type="password" id="employee-password" name="MatKhau" placeholder="Chỉ nhập khi thêm mới hoặc đổi mật khẩu">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="employee-dob">Ngày sinh</label>
                        <input type="date" id="employee-dob" name="NgaySinh" required>
                    </div>

                    <div class="form-group">
                        <label for="employee-phone">SĐT</label>
                        <input type="tel" id="employee-phone" name="DT" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="employee-role">Chức vụ</label>
                        <select id="employee-role" name="ViTri" required>
                            <option value="QuanLi">Quản lý</option>
                            <option value="NV">Nhân viên</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="employee-startdate">Ngày vào làm</label>
                        <input type="date" id="employee-startdate" name="NgayLam" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="employee-salary">Lương (VNĐ)</label>
                        <input type="number" id="employee-salary" name="Luong" required min="0">
                    </div>

                    <div class="form-group">
                        <label for="employee-status">Trạng thái</label>
                        <select id="employee-status" name="TrangThai" required>
                            <option value="DangLam">Đang hoạt động</option>
                            <option value="DaNghi">Đã nghỉ việc</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn action-btn" id="cancel-btn" onclick="closeForm()">Hủy</button>
                    <button type="submit" class="save-btn action-btn">Lưu</button>
                </div>
            </form>
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
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount);
        }

        function toggleSidebar() {
            const body = document.body;
            body.classList.toggle("sidebar-visible");
        }

        function closeForm() {
            document.getElementById('employee-form-overlay').style.display = 'none';
        }

        function openForm(action, data = {}) {
            document.getElementById('employee-form').reset();
            document.getElementById('form-action-type').value = action;
            document.getElementById('form-title').textContent = action === 'add' ? 'Thêm Nhân viên Mới' : 'Chỉnh sửa Nhân viên';

            const maNVInput = document.getElementById('employee-id');
            const passwordInput = document.getElementById('employee-password');
            const passwordLabel = document.querySelector('#password-group label');

            maNVInput.readOnly = action === 'update';

            if (action === 'update') {
                passwordLabel.textContent = 'Mật khẩu (Để trống nếu không đổi)';
                passwordInput.removeAttribute('required');

                maNVInput.value = data.MaNV;
                document.getElementById('employee-name').value = data.HoTen;
                document.getElementById('employee-dob').value = data.NgaySinh;
                document.getElementById('employee-salary').value = data.Luong;
                document.getElementById('employee-role').value = data.ViTri;
                document.getElementById('employee-phone').value = data.DT;
                document.getElementById('employee-startdate').value = data.NgayLam;
                document.getElementById('employee-status').value = data.TrangThai;
            } else {
                passwordLabel.textContent = 'Mật khẩu (*Bắt buộc)';
                passwordInput.setAttribute('required', 'required');
                maNVInput.readOnly = true;
                maNVInput.value = '(Tự động)';
            }

            document.getElementById('employee-form-overlay').style.display = 'flex';
        }

        function logoutConfirm() {
            if (confirm("Bạn có chắc chắn muốn đăng xuất khỏi hệ thống không?")) {
                const loginPage = "../login_admin.php";
                window.location.replace(loginPage);
                alert("Đã đăng xuất! Chuyển hướng tới trang Đăng nhập...");
            }
        }


        document.getElementById('employee-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const action = document.getElementById('form-action-type').value;
            const formElement = document.getElementById('employee-form');
            const formData = new URLSearchParams(new FormData(formElement));
            formData.append('action', action);

            if (action === 'add') {
                formData.delete('MaNV');
            }

            fetch('admin_nhanvien.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: formData
                })
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            console.error("Lỗi phản hồi Server (Không phải JSON):", text);
                            alert('Lỗi: Lỗi PHP nghiêm trọng trên server. Xem console để biết chi tiết.');
                            throw new TypeError("Server response was not valid JSON. Response text: " + text.substring(0, 100) + "...");
                        });
                    }
                })
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        closeForm();
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Lỗi lưu dữ liệu:', error);
                    alert('Có lỗi xảy ra khi lưu dữ liệu nhân viên. (Lỗi mạng hoặc server không trả về JSON hợp lệ)');
                });
        });

        document.getElementById('employee-grid').addEventListener('click', function(e) {
            const target = e.target;
            const maNV = target.getAttribute('data-id');

            if (!maNV) return;

            if (target.classList.contains("delete-btn")) {
                if (confirm(`Bạn có chắc chắn muốn xóa nhân viên Mã: ${maNV} không?`)) {
                    const formData = new URLSearchParams();
                    formData.append('action', 'delete');
                    formData.append('MaNV', maNV);

                    fetch('admin_nhanvien.php', {
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

            if (target.classList.contains("edit-btn")) {
                const formData = new URLSearchParams();
                formData.append('action', 'get_details');
                formData.append('MaNV', maNV);

                fetch('admin_nhanvien.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            return response.json();
                        } else {
                            throw new TypeError("Server response was not valid JSON");
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            openForm('update', data.data);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi tải chi tiết nhân viên:', error);
                        alert('Lỗi tải chi tiết nhân viên. (Lỗi mạng hoặc server không phản hồi đúng)');
                    });
            }
        });
    </script>
</body>

</html>