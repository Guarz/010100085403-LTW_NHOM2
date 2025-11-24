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

    if ($_POST['action'] === 'get_sold_details') {
        $result = $dbManager->getSoldProductsDetails();
        $details = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $details[] = [
                    'MaGH' => $row['MaGH'],
                    'NgayDat' => date('d/m/Y', strtotime($row['NgayDat'])),
                    'MaSP' => $row['MaSP'],
                    'TenSP' => $row['TenSP'],
                    'SoLuong' => (int)$row['SoLuong'],
                    'Gia' => formatCurrencyPHP($row['Gia']),
                    'MaKH' => $row['MaKH'],
                    'HoTen' => $row['HoTen']
                ];
            }
        }

        !empty($details)
            ? print json_encode(['success' => true, 'data' => $details])
            : print json_encode(['success' => false, 'message' => 'Không tìm thấy chi tiết sản phẩm đã bán.']);
        exit();
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Role_login.php");
    exit();
}

$generalStats = $dbManager->getGeneralStats(); 
$topSellingProductsResult = $dbManager->getTopSellingProducts();
$topSpendingCustomersResult = $dbManager->getTopSpendingCustomers();

$monthlyRevenueData = $dbManager->getMonthlyRevenueData();

$chartLabels = array_column($monthlyRevenueData, 'label');
$chartData = array_column($monthlyRevenueData, 'revenue');


?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Thống kê - Hoa Tươi Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 5px solid #e91e63;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card strong {
            font-size: 30px;
            color: #e91e63;
        }

        .stat-card p {
            color: #888;
            font-size: 14px;
            margin: 0;
        }

        .stat-card .icon-box {
            font-size: 35px;
            color: #ff66a3;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }

        .chart-card,
        .top-list-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }

        .top-list-card table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .top-list-card th,
        .top-list-card td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .top-list-card th {
            background-color: #f8f8f8;
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
            max-width: 800px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
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
            font-size: 14px;
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
    <div id="soldProductsModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('soldProductsModal')">&times;</span>
            <h2>Chi tiết Sản phẩm đã bán</h2>
            <div id="sold-details-content" class="modal-details">
            </div>
        </div>
    </div>

    <button class="hamburger-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
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
                <a href="admin_nhanvien.php"><i class="fa-solid fa-user-tie"></i> Nhân viên</a>
                <a href="admin_thongke.php" class="active"><i class="fa-solid fa-chart-simple"></i> Thống kê</a>
                <a href="admin_taikhoan_nv.php"><i class="fa-solid fa-key"></i> Quản lý TK NV</a>
            </nav>
            </nav>
            <button class="sidebar-logout-btn" onclick="logoutConfirm()">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </button>
        </div>

        <div class="main">
            <div class="header">
                <h1>Tổng quan Báo cáo và Thống kê</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card" onclick="window.location.href='admin_donhang.php'">
                    <div class="info">
                        <p>Doanh thu (VNĐ)</p>
                        <strong><?php echo DatabaseManager::formatCurrencyPHP($generalStats['total_revenue']); ?></strong>
                    </div>
                    <div class="icon-box">
                        <i class="fa-solid fa-sack-dollar" style="color: #4CAF50;"></i>
                    </div>
                </div>
                <div class="stat-card" onclick="window.location.href='admin_donhang.php'">
                    <div class="info">
                        <p>Tổng số Đơn hàng</p>
                        <strong><?php echo $generalStats['total_orders']; ?></strong>
                    </div>
                    <div class="icon-box">
                        <i class="fa-solid fa-receipt" style="color: #007bff;"></i>
                    </div>
                </div>
                <div class="stat-card" onclick="viewSoldProductsDetails()">
                    <div class="info">
                        <p>Sản phẩm đã bán</p>
                        <strong><?php echo $generalStats['total_sold_products']; ?></strong>
                    </div>
                    <div class="icon-box">
                        <i class="fa-solid fa-box-open" style="color: #ffc107;"></i>
                    </div>
                </div>
                <div class="stat-card" onclick="window.location.href='admin_khachhang.php'">
                    <div class="info">
                        <p>Tổng Khách hàng</p>
                        <strong><?php echo $generalStats['total_customers']; ?></strong>
                    </div>
                    <div class="icon-box">
                        <i class="fa-solid fa-users" style="color: #e91e63;"></i>
                    </div>
                </div>
            </div>

            <div class="dashboard-layout">
                <div class="chart-card">
                    <h2>Biểu đồ Doanh thu (30 ngày gần nhất)</h2>
                    <canvas id="revenueChart"></canvas>
                </div>

                <div class="top-list-card">
                    <h2>Top 5 Sản phẩm bán chạy</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tên Sản phẩm</th>
                                <th>Đã bán</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1;
                            if ($topSellingProductsResult):
                                while ($row = $topSellingProductsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($row['TenSP']); ?></td>
                                        <td><?php echo (int)$row['DaBan']; ?></td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="3">Không có dữ liệu bán hàng.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="top-list-card" style="grid-column: 1 / -1;">
                    <h2>Top 5 Khách hàng Chi tiêu nhiều nhất</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tên Khách hàng</th>
                                <th>Tổng chi tiêu (VNĐ)</th>
                                <th>Xem thông tin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1;
                            if ($topSpendingCustomersResult):
                                while ($row = $topSpendingCustomersResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($row['TenKhachHang']); ?></td>
                                        <td><?php echo DatabaseManager::formatCurrencyPHP($row['TongChiTieu']); ?></td>
                                        <td>
                                            <button class="action-btn view-customer-btn"
                                                data-name="<?php echo htmlspecialchars($row['TenKhachHang']); ?>"
                                                style="background-color: #007bff;">Xem</button>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="4">Không có dữ liệu chi tiêu.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
        function formatCurrency(amount) {
            if (typeof amount !== 'number') return amount;
            return amount.toLocaleString('vi-VN');
        }

        function openModal(id) {
            document.getElementById(id).style.display = "flex"; 
        }

        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
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


        function viewSoldProductsDetails() {
            document.getElementById('sold-details-content').innerHTML = 'Đang tải chi tiết...';

            fetch('admin_thongke.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=get_sold_details'
                })
                .then(response => response.json())
                .then(data => {
                    const contentDiv = document.getElementById('sold-details-content');
                    if (data.success) {
                        let tableHTML = `
                        <table>
                            <thead>
                                <tr>
                                    <th>Mã GH</th>
                                    <th>Ngày Đặt</th>
                                    <th>Mã SP</th>
                                    <th>Tên SP</th>
                                    <th>SL</th>
                                    <th>Giá</th>
                                    <th>Mã KH</th>
                                    <th>Tên KH</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                        data.data.forEach(item => {
                            tableHTML += `
                            <tr>
                                <td>${item.MaGH}</td>
                                <td>${item.NgayDat}</td>
                                <td>${item.MaSP}</td>
                                <td>${item.TenSP}</td>
                                <td>${item.SoLuong}</td>
                                <td>${item.Gia}</td>
                                <td>${item.MaKH}</td>
                                <td>${item.HoTen}</td>
                            </tr>
                        `;
                        });
                        tableHTML += `</tbody></table>`;
                        contentDiv.innerHTML = tableHTML;
                    } else {
                        contentDiv.innerHTML = `<p style="color: #e91e63; text-align: center;">${data.message}</p>`;
                    }
                    openModal('soldProductsModal');
                })
                .catch(error => {
                    console.error('Lỗi tải chi tiết sản phẩm đã bán:', error);
                    document.getElementById('sold-details-content').innerHTML = '<p style="color: red;">Lỗi kết nối máy chủ.</p>';
                    openModal('soldProductsModal');
                });
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('view-customer-btn')) {
                const customerName = e.target.getAttribute('data-name');
                alert(`Chuyển hướng đến trang Quản lý Khách hàng để tìm thông tin của: ${customerName}.`);
                window.location.href = `admin_khachhang.php?search=${encodeURIComponent(customerName)}`;
            }
        });

        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: <?php echo json_encode($chartData); ?>,
                    backgroundColor: 'rgba(233, 30, 99, 0.5)',
                    borderColor: '#e91e63',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: '30 ngày gần nhất'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return formatCurrency(value);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';

                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += formatCurrency(context.parsed.y) + ' VNĐ';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>