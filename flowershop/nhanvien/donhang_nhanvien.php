<?php
session_start();
require_once '../DatabaseManager.php';

$dbManager = new DatabaseManager();

$currentUserName = $_SESSION['user_name'] ?? 'Nhân viên';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: Phiên đăng nhập hết hạn.']);
        exit();
    }

    if ($_POST['action'] === 'view_details' && isset($_POST['MaGH'])) {
        $maGH = $_POST['MaGH'];
        $result = $dbManager->getChiTietDonHang($maGH);
        $details = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $details[] = [
                    'MaSP' => htmlspecialchars($row['MaSP']),
                    'TenSP' => htmlspecialchars($row['TenSP']),
                    'SoLuong' => (int)$row['SoLuong'],
                    'Gia' => DatabaseManager::formatCurrencyPHP($row['Gia'])
                ];
            }
        }

        !empty($details)
            ? print json_encode(['success' => true, 'data' => $details])
            : print json_encode(['success' => false, 'message' => 'Không tìm thấy chi tiết đơn hàng.']);
        exit();
    }

    if ($_POST['action'] === 'update_status' && isset($_POST['MaGH'], $_POST['TrangThaiMoi'])) {
        $maGH = $_POST['MaGH'];
        $trangThaiMoi = $_POST['TrangThaiMoi'];

        $validStatus = ['DangGiao', 'DaHuy', 'DaThanhToan', 'ChuaThanhToan'];

        if (!in_array($trangThaiMoi, $validStatus)) {
            print json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ. Chỉ chấp nhận DangGiao, DaHuy, DaThanhToan.']);
            exit();
        }

        $dbManager->updateTrangThai($maGH, $trangThaiMoi)
            ? print json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công.'])
            : print json_encode(['success' => false, 'message' => 'Lỗi: Cập nhật trạng thái thất bại.']);
        exit();
    }
}

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../Role_login.php");
    exit();
}
if ($_SESSION['user_role'] !== 'employee') {
    header("Location: ../chủ/qlsp.php");
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
    <link rel="stylesheet" href="../chủ/style.css" />
    <style>
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

        .add-order-button {
            display: block;
            width: fit-content;
            margin: 20px auto 20px auto;
            background: #e91e63;
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .add-order-button:hover {
            background: #ff66a3;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

    </style>
</head>

<body>
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('orderDetailsModal')">&times;</span>
            <h2>Chi tiết Đơn hàng: <span id="modalMaGH"></span></h2>
            <div id="modal-details" class="modal-details">
            </div>
            <p style="margin-top: 20px; text-align: right; font-weight: bold;">Tổng tiền đơn hàng: <span id="modalTongTien"></span> VNĐ</p>
        </div>
    </div>

    <div id="addOrderModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addOrderModal')">&times;</span>
            <h2>Thêm Đơn hàng Mới</h2>
            <form id="add-order-form">
                <p>Chức năng này sẽ yêu cầu nhập thông tin khách hàng và danh sách sản phẩm. (Chỉ là Placeholder)</p>

                <div style="margin-top: 20px;">
                    <label for="new-ma-kh">Mã Khách hàng:</label>
                    <input type="text" id="new-ma-kh" name="MaKH" required style="padding: 8px; width: 100%;">
                </div>

                <div style="margin-top: 15px;">
                    <label for="new-ma-gh">Mã Giỏ Hàng (Mới):</label>
                    <input type="text" id="new-ma-gh" name="MaGH" required style="padding: 8px; width: 100%;">
                </div>

                <div class="form-actions" style="text-align: right; margin-top: 25px;">
                    <button type="button" class="action-btn" onclick="closeModal('addOrderModal')"
                        style="background-color: #aaa; border-radius: 20px;">Hủy</button>
                    <button type="submit" class="action-btn" style="background-color: #e91e63; border-radius: 20px;">Tiếp tục (Lưu)</button>
                </div>
            </form>
        </div>
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
                <a href="donhang_nhanvien.php" class="active"><i class="fa-solid fa-receipt"></i> Đơn hàng</a>
                <a href="khachhang_nv.php"><i class="fa-solid fa-users"></i> Khách hàng</a>
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
                            echo "<td>" . DatabaseManager::formatCurrencyPHP($row['TongTien']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['MaKH']) . "</td>";
                            echo "<td>
                                    <button class='action-btn view-btn' data-id='{$row['MaGH']}'>Xem</button>
                                    <button class='action-btn status-change-btn' data-id='{$row['MaGH']}' data-current-status='{$row['TrangThai']}'>Cập nhật</button>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr class="empty-row"><td colspan="6" style="text-align:center; color:#888;">Chưa có đơn hàng nào</td><td style="text-align:left;"><button class="action-btn view-btn" disabled>Xem</button><button class="action-btn" disabled>Cập nhật</button></td></tr>';
                    }

                    ?>
                </tbody>
            </table>

            <p style="text-align: center; margin-top: 20px; color: #888;">
                Tổng cộng: <span style="font-weight: bold; color: #e91e63;"><?php echo $totalOrders; ?></span> đơn hàng.
            </p>

            <button class="add-order-button" onclick="openAddOrderForm()">
                + Thêm Đơn hàng
            </button>
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
        const orderDetailsModal = document.getElementById('orderDetailsModal');
        const addOrderModal = document.getElementById('addOrderModal'); 
        const modalDetails = document.getElementById('modal-details');

        function openModal(id) {
            document.getElementById(id).style.display = "flex"; 
        }

        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }

        function openAddOrderForm() {
            document.getElementById('add-order-form').reset();
            openModal('addOrderModal');
        }

        window.onclick = function(event) {
            if (event.target == orderDetailsModal) {
                closeModal('orderDetailsModal');
            }
            if (event.target == addOrderModal) {
                closeModal('addOrderModal');
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

                const formData = new URLSearchParams();
                formData.append('action', 'view_details');
                formData.append('MaGH', maGH);

                fetch('donhang_nhanvien.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        const originalTotal = target.closest('tr').querySelectorAll('td')[3].textContent;
                        document.getElementById('modalTongTien').textContent = originalTotal;

                        if (data.success) {
                            let tableHTML = `
                            <table>
                                <thead>
                                    <tr>
                                        <th>Mã SP</th>
                                        <th>Tên Sản phẩm</th>
                                        <th>SL</th>
                                        <th>Đơn giá</th>
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
                                </tr>
                            `;
                            });
                            tableHTML += `</tbody></table>`;
                            modalDetails.innerHTML = tableHTML;
                        } else {
                            modalDetails.innerHTML = `<p style="color: red; text-align: center;">${data.message}</p>`;
                        }
                        openModal('orderDetailsModal');
                    })
                    .catch(error => {
                        console.error('Lỗi tải chi tiết:', error);
                        alert('Có lỗi xảy ra khi tải chi tiết đơn hàng.');
                    });
            }

            if (target.classList.contains("status-change-btn")) {
                const currentStatus = target.getAttribute('data-current-status');

                let newStatusInput = prompt(`Cập nhật trạng thái cho đơn hàng ${maGH}\n(Ví dụ: DangGiao hoặc DaHuy hoặc DaThanhToan):`, currentStatus);

                if (newStatusInput && newStatusInput !== currentStatus) {
                    const newStatus = newStatusInput.trim();

                    const formData = new URLSearchParams();
                    formData.append('action', 'update_status');
                    formData.append('MaGH', maGH);
                    formData.append('TrangThaiMoi', newStatus);

                    fetch('donhang_nhanvien.php', {
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
        });

        document.getElementById('add-order-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Chức năng tạo đơn hàng mới chưa được triển khai backend chi tiết.');
        });
    </script>
</body>

</html>