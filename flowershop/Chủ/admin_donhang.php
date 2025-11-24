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

    if ($_POST['action'] === 'delete' && isset($_POST['MaGH'])) {
        $maGH = $_POST['MaGH'];
        $dbManager->deleteDonHang($maGH)
            ? print json_encode(['success' => true, 'message' => 'Xóa đơn hàng thành công.'])
            : print json_encode(['success' => false, 'message' => 'Lỗi: Không thể xóa đơn hàng. Vui lòng kiểm tra ràng buộc CSDL.']);
        exit();
    }

    if ($_POST['action'] === 'update_status' && isset($_POST['MaGH'], $_POST['TrangThaiMoi'])) {
        $maGH = $_POST['MaGH'];
        $trangThaiMoi = $_POST['TrangThaiMoi'];

        $dbManager->updateTrangThai($maGH, $trangThaiMoi)
            ? print json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công.'])
            : print json_encode(['success' => false, 'message' => 'Lỗi: Cập nhật trạng thái thất bại.']);
        exit();
    }

    if ($_POST['action'] === 'view_details' && isset($_POST['MaGH'])) {
        $maGH = $_POST['MaGH'];
        $result = $dbManager->getChiTietDonHang($maGH);
        $details = [];
        $calculatedTotal = 0;

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tongTienSP_raw = (int)$row['SoLuong'] * (float)$row['Gia'];
                $calculatedTotal += $tongTienSP_raw;

                $details[] = [
                    'MaSP'     => htmlspecialchars($row['MaSP']),
                    'TenSP'    => htmlspecialchars($row['TenSP']),
                    'SoLuong'  => (int)$row['SoLuong'],
                    'Gia'      => DatabaseManager::formatCurrencyPHP($row['Gia']),
                    'TongTienSP' => DatabaseManager::formatCurrencyPHP($tongTienSP_raw)
                ];
            }
        }

        if (!empty($details)) {
            print json_encode([
                'success' => true,
                'data' => $details,
                'total_calculated' => DatabaseManager::formatCurrencyPHP($calculatedTotal)
            ]);
        } else {
            print json_encode(['success' => false, 'message' => 'Không tìm thấy chi tiết đơn hàng.']);
        }
        exit();
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Role_login.php");
    exit();
}

$gioHangResult = $dbManager->getListGioHang();
$totalOrders = $gioHangResult ? $gioHangResult->num_rows : 0;

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Quản lý Đơn hàng - Hoa Tươi Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
    <style>
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-success {
            background-color: #d4edda;
            color: #155724;
        }

        .status-shipping {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
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
            width: 80%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            margin: auto;
        }

        .modal-content h2 {
            color: #e91e63;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .modal-details table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .modal-details th,
        .modal-details td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .modal-details th {
            background-color: #f2f2f2;
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
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

        .view-btn {
            background-color: #007bff;
            color: white;
        }

        .view-btn:hover {
            background-color: #0056b3;
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
    </style>
</head>

<body>
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2>Chi tiết Đơn hàng: <span id="modalMaGH"></span></h2>
            <div id="modal-details" class="modal-details">
            </div>
            <p style="margin-top: 20px; text-align: right; font-weight: bold;">Tổng tiền đơn hàng: <span id="modalTongTien"></span> VNĐ</p>
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
                <a href="admin_donhang.php" class="active"><i class="fa-solid fa-receipt"></i> Đơn hàng</a>
                <a href="admin_khachhang.php"><i class="fa-solid fa-users"></i> Khách hàng</a>
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
                <h1>Quản lý Đơn hàng</h1>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Mã Giỏ Hàng</th>
                        <th>Ngày đặt hàng</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền (VNĐ)</th>
                        <th>Mã Khách hàng</th>
                        <th>Họ tên khách hàng</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="order-table">
                    <?php
                    if ($gioHangResult && $gioHangResult->num_rows > 0) {
                        while ($row = $gioHangResult->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['MaGH']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['NgayDat']) . "</td>";
                            echo "<td>" . DatabaseManager::translateTrangThai($row['TrangThai']) . "</td>";
                            echo "<td data-total-from-db='{$row['TongTien']}'>" . DatabaseManager::formatCurrencyPHP($row['TongTien']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['MaKH']) . "</td>";
                            echo "<td>" . ($row['HoTen'] ? htmlspecialchars($row['HoTen']) : '<em style="color:#999;">Không xác định</em>') . "</td>";
                            echo "<td>
                                    <button class='action-btn view-btn' data-id='{$row['MaGH']}'>Xem</button>
                                    <button class='action-btn edit-btn' data-id='{$row['MaGH']}' data-current-status='{$row['TrangThai']}'>Sửa</button>
                                    <button class='action-btn delete-btn' data-id='{$row['MaGH']}'>Xóa</button>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr class="empty-row"><td colspan="7" style="text-align:center; color:#888; font-style:italic;">Chưa có đơn hàng nào</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <p style="text-align: center; margin-top: 20px; color: #888;">
                Tổng cộng: <span style="font-weight: bold; color: #e91e63;"><?php echo $totalOrders; ?></span> đơn hàng.
            </p>
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
        const modal = document.getElementById('orderDetailsModal');
        const modalDetails = document.getElementById('modal-details');
        const modalTongTien = document.getElementById('modalTongTien');

        function openModal() {
            modal.style.display = "flex";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
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

        document.getElementById("order-table").addEventListener("click", function(e) {
            const target = e.target;
            const maGH = target.getAttribute('data-id');

            if (!maGH) return;

            if (target.classList.contains("view-btn")) {
                document.getElementById('modalMaGH').textContent = maGH;
                modalDetails.innerHTML = 'Đang tải chi tiết...';

                const originalTotal = target.closest('tr').querySelectorAll('td')[3].textContent;
                modalTongTien.textContent = originalTotal;

                const formData = new URLSearchParams();
                formData.append('action', 'view_details');
                formData.append('MaGH', maGH);

                fetch('admin_donhang.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let tableHTML = `
                            <table>
                                <thead>
                                    <tr>
                                        <th>Mã SP</th>
                                        <th>Tên Sản phẩm</th>
                                        <th>SL</th>
                                        <th>Đơn giá (VNĐ)</th>
                                        <th>Tổng (VNĐ)</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                            data.data.forEach(item => {
                                tableHTML += `
                                <tr>
                                    <td>${item.MaSP}</td>
                                    <td>${item.TenSP}</td>
                                    <td>${item.SoLuong}</td>
                                    <td>${item.Gia}</td>
                                    <td>${item.TongTienSP}</td> 
                                </tr>
                            `;
                            });
                            tableHTML += `</tbody></table>`;

                            modalDetails.innerHTML = tableHTML;

                            if (data.total_calculated) {
                                modalTongTien.textContent = data.total_calculated;
                            }

                            openModal();
                        } else {
                            modalDetails.innerHTML = `<p style="color: red; text-align: center;">${data.message}</p>`;
                            modalTongTien.textContent = originalTotal;
                            openModal();
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi tải chi tiết:', error);
                        alert('Có lỗi xảy ra khi tải chi tiết đơn hàng.');
                    });
            }

            if (target.classList.contains("edit-btn")) {
                const currentStatus = target.getAttribute('data-current-status');

                let newStatusInput = prompt(`Nhập trạng thái mới cho đơn hàng ${maGH}\n(Các giá trị hợp lệ: ChuaThanhToan, DaThanhToan, DangGiao, DaHuy):`, currentStatus);

                if (newStatusInput && newStatusInput !== currentStatus) {
                    const newStatus = newStatusInput.trim();
                    const formData = new URLSearchParams();
                    formData.append('action', 'update_status');
                    formData.append('MaGH', maGH);
                    formData.append('TrangThaiMoi', newStatus);

                    fetch('admin_donhang.php', {
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
                                window.location.reload();
                            }
                        })
                        .catch(error => {
                            console.error('Lỗi cập nhật:', error);
                            alert('Có lỗi xảy ra khi cập nhật trạng thái.');
                        });
                }
            }

            if (target.classList.contains("delete-btn")) {
                if (confirm(`Bạn có chắc chắn muốn XÓA VĨNH VIỄN đơn hàng Mã: ${maGH} không? (Hành động này sẽ xóa cả chi tiết đơn hàng)`)) {
                    const formData = new URLSearchParams();
                    formData.append('action', 'delete');
                    formData.append('MaGH', maGH);

                    fetch('admin_donhang.php', {
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
                                window.location.reload();
                            }
                        })
                        .catch(error => {
                            console.error('Lỗi xóa:', error);
                            alert('Có lỗi xảy ra trong quá trình xóa đơn hàng.');
                        });
                }
            }
        });
    </script>
</body>

</html>