<?php
session_start();
require_once '../DatabaseManager.php';

$dbManager = new DatabaseManager();

$currentAdminName = $_SESSION['user_name'] ?? 'Nhân viên';

if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'delete' && isset($_POST['MaSP'])) {
        $maSP = $_POST['MaSP'];

        $success = $dbManager->deleteSanPham($maSP);

        if ($success) {
            print json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công. (Đã xóa các mục liên quan)']);
        } else {
            print json_encode(['success' => false, 'message' => $errorMsg]);
        }
        exit();
    }

    if (($_POST['action'] === 'update' || $_POST['action'] === 'add') &&
        isset($_POST['MaSP'], $_POST['TenSP'], $_POST['MaLoai'], $_POST['ChiTiet'], $_POST['DonGia'], $_POST['SoLuong'], $_POST['TrangThai'], $_POST['MoTa'], $_POST['MaNV'])
    ) {
        $maSP = $_POST['MaSP'];
        $tenSP = $_POST['TenSP'];
        $maLoai = (int)$_POST['MaLoai'];
        $chiTiet = $_POST['ChiTiet'];
        $donGia = (float)$_POST['DonGia'];
        $soLuong = (int)$_POST['SoLuong'];
        $trangThai = $_POST['TrangThai'];
        $moTa = $_POST['MoTa'];
        $maNV = (int)$_POST['MaNV'];

        $anh = null;
        if (isset($_FILES['Anh']) && $_FILES['Anh']['error'] === 0) {
            $anh = file_get_contents($_FILES['Anh']['tmp_name']);
        }

        $success = false;
        if ($_POST['action'] === 'update') {
            $success = $dbManager->updateSanPham($maSP, $tenSP, $maLoai, $chiTiet, $donGia, $soLuong, $trangThai, $moTa, $maNV, $anh);
            $msg = 'Cập nhật sản phẩm thành công.';
        } else {
            $success = $dbManager->addSanPham($maSP, $tenSP, $maLoai, $chiTiet, $donGia, $soLuong, $trangThai, $moTa, $maNV, $anh);
            $msg = 'Thêm sản phẩm thành công.';
        }

        if ($success) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            echo json_encode(['success' => false, 'message' => $errorMsg]);
        }
        exit();
    }

    if ($_POST['action'] === 'get_details' && isset($_POST['MaSP'])) {
        $maSP = $_POST['MaSP'];
        $product = $dbManager->getSanPhamById($maSP);
        if ($product) {
            $statusValue = ($product['TrangThai'] == 'ConHang' || $product['TrangThai'] == '1') ? 'in' : 'out';
            $product['TrangThai'] = $statusValue;

            echo json_encode(['success' => true, 'data' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
        }
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

$sanPhamResult = $dbManager->getListSanPham();

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Quản lý hàng - Hoa Tươi Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../Chủ/style.css" />
    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
        }

        .status-in {
            background-color: #e6ffed;
            color: #008000;
        }

        .status-out {
            background-color: #ffe6e6;
            color: #cc0000;
        }

        .product-img-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        #product-form-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        .product-form-container {
            background-color: #fefefe;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            max-height: 90vh;
            overflow-y: auto;
        }

        .product-form-container h2 {
            color: #e91e63;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 15px;
        }

        .form-group:only-child {
            flex: none;
            width: 100%;
        }

        .product-form-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .product-form-container input:not([type="file"]),
        .product-form-container select,
        .product-form-container textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form-actions {
            margin-top: 20px;
            text-align: right;
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

        .add-product-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            display: block;
            margin: 20px auto;
        }

        .add-product-button:hover {
            background-color: #0056b3;
        }

        #product-form .form-group:nth-child(odd):not(.form-row *) {
            width: 100%;
        }
    </style>
</head>

<body>
    <div id="product-form-overlay" style="display:none;">
        <div class="product-form-container">
            <h2>Thêm Sản phẩm mới</h2>
            <form id="product-form" enctype="multipart/form-data">
                <input type="hidden" id="form-action-type" value="add">

                <div class="form-group">
                    <label for="product-id">Mã sản phẩm:</label>
                    <input type="text" id="product-id" name="MaSP" required>
                </div>

                <div class="form-group">
                    <label for="product-name">Tên sản phẩm:</label>
                    <input type="text" id="product-name" name="TenSP" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="product-maLoai">Loại sản phẩm:</label>
                        <select id="product-maLoai" name="MaLoai" required>
                            <?php
                            $loaiResult = $dbManager->getListLoaiSanPham();
                            if ($loaiResult) {
                                while ($loai = $loaiResult->fetch_assoc()) {
                                    echo "<option value='{$loai['MaLoai']}'>{$loai['TenLoai']} ({$loai['MaLoai']})</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="product-chiTiet">Chi tiết:</label>
                        <input type="text" id="product-chiTiet" name="ChiTiet" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="product-price">Giá (VNĐ):</label>
                        <input type="number" id="product-price" name="DonGia" required min="0">
                    </div>

                    <div class="form-group">
                        <label for="product-quantity">Số lượng:</label>
                        <input type="number" id="product-quantity" name="SoLuong" required min="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="product-status">Trạng thái:</label>
                        <select id="product-status" name="TrangThai">
                            <option value="in">Còn hàng</option>
                            <option value="out">Hết hàng</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ảnh sản phẩm:</label>
                        <div class="image-upload-area">
                            <input type="file" id="product-image" name="Anh" accept="image/*">
                            <img id="preview-image" alt="Ảnh xem trước" style="display:none; width: 50px; height: 50px; object-fit: cover;">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="product-moTa">Mô tả:</label>
                    <textarea id="product-moTa" name="MoTa" required></textarea>
                </div>

                <div class="form-group">
                    <label for="product-maNV">Nhân viên quản lý:</label>
                    <select id="product-maNV" name="MaNV" required>
                        <?php
                        $nvResult = $dbManager->getListNhanVien();
                        if ($nvResult) {
                            while ($nv = $nvResult->fetch_assoc()) {
                                echo "<option value='{$nv['MaNV']}'>{$nv['HoTen']} ({$nv['MaNV']})</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="action-btn delete-btn" onclick="closeForm()">Hủy</button>
                    <button type="submit" class="action-btn edit-btn">Lưu</button>
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
            <p class="welcome-message"> Xin chào, <span><?php echo htmlspecialchars($currentAdminName); ?></span>!</p>
            <nav>
                <a href="qlsp_nv.php" class="active"><i class="fa-solid fa-leaf"></i> Quản lý Sản phẩm</a>
                <a href="donhang_nhanvien.php"><i class="fa-solid fa-receipt"></i> Đơn hàng</a>
                <a href="khachhang_nv.php"><i class="fa-solid fa-users"></i> Khách hàng</a>
            </nav>
            <button class="sidebar-logout-btn" onclick="logoutConfirm()">
                <i class="fa-solid fa-right-from-bracket"></i>Đăng xuất
            </button>
        </div>

        <div class="main">
            <div class="header">
                <h1>Quản lý Sản phẩm</h1>
            </div>

            <table id="product-table-container">
                <thead>
                    <tr>
                        <th>Mã SP</th>
                        <th>Tên SP</th>
                        <th>Mã Loại</th>
                        <th>Ảnh</th>
                        <th>Chi Tiết</th>
                        <th>Giá (VNĐ)</th>
                        <th>SL Tồn</th>
                        <th>Trạng thái</th>
                        <th>Mô Tả</th>
                        <th>Mã NV</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="product-table">
                    <?php
                    if ($sanPhamResult && $sanPhamResult->num_rows > 0) {
                        while ($row = $sanPhamResult->fetch_assoc()) {
                            $isConHang = ($row['TrangThai'] == 'ConHang' || $row['TrangThai'] == '1');
                            $trangThaiText = $isConHang ? 'Còn hàng' : 'Hết hàng';
                            $trangThaiClass = $isConHang ? 'status-in' : 'status-out';

                            if (!empty($row['Anh'])) {
                                $imgSrc = 'data:image/jpeg;base64,' . base64_encode($row['Anh']);
                            } else {
                                $imgSrc = '../images/no_image.png';
                            }

                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['MaSP']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['TenSP']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['MaLoai']) . "</td>";
                            echo "<td><img src='$imgSrc' alt='Ảnh SP' class='product-img-thumb'></td>";
                            echo "<td>" . htmlspecialchars($row['ChiTiet']) . "</td>";
                            echo "<td>" . DatabaseManager::formatCurrencyPHP($row['DonGia']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['SoLuong']) . "</td>";
                            echo "<td><span class='status-badge $trangThaiClass'>$trangThaiText</span></td>";
                            echo "<td>" . htmlspecialchars($row['MoTa']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['MaNV']) . "</td>";
                            echo "<td>
                        <button class='action-btn edit-btn' data-id='{$row['MaSP']}'>Sửa</button>
                        <button class='action-btn delete-btn' data-id='{$row['MaSP']}'>Xóa</button>
                      </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="11" style="text-align:center; color:#888; padding:20px;">Chưa có sản phẩm nào trong CSDL.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>


            <button class="add-product-button" onclick="addProduct()">+ Thêm Sản phẩm</button>
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
        function closeForm() {
            document.getElementById("product-form-overlay").style.display = "none";
        }

        function addProduct() {
            document.getElementById("product-form").reset();
            document.querySelector(".product-form-container h2").textContent = "Thêm Sản phẩm mới";
            document.getElementById("form-action-type").value = "add";
            document.getElementById("product-id").readOnly = false;

            document.getElementById("preview-image").style.display = "none";
            document.getElementById("product-form-overlay").style.display = "flex";
        }

        document.getElementById("product-image")?.addEventListener("change", function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    imageDataUrl = event.target.result;
                    const preview = document.getElementById("preview-image");
                    preview.src = imageDataUrl;
                    preview.style.display = "block";
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById("product-form").addEventListener("submit", function(e) {
            e.preventDefault();
            const actionType = document.getElementById("form-action-type").value;

            const formData = new FormData(this);
            formData.append('action', actionType);

            fetch('qlsp_nv.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        closeForm();
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Lỗi mạng hoặc server:', error);
                    alert('Có lỗi xảy ra trong quá trình lưu dữ liệu.');
                });
        });

        document.getElementById("product-table").addEventListener("click", function(e) {
            const target = e.target;
            const maSP = target.getAttribute('data-id');

            if (!maSP) return;

            if (target.classList.contains("delete-btn")) {
                if (confirm(`Bạn có chắc chắn muốn xóa sản phẩm Mã: ${maSP} không? (Sẽ xóa cả các mục liên quan trong đơn hàng)`)) {
                    const deleteData = new URLSearchParams();
                    deleteData.append('action', 'delete');
                    deleteData.append('MaSP', maSP);

                    fetch('qlsp_nv.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
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
                            console.error('Lỗi xóa:', error);
                            alert('Lỗi: Lỗi mạng hoặc server không phản hồi đúng định dạng JSON.');
                        });
                }
            }
            if (target.classList.contains("edit-btn")) {
                const fetchDetails = new URLSearchParams();
                fetchDetails.append('action', 'get_details');
                fetchDetails.append('MaSP', maSP);

                fetch('qlsp_nv.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: fetchDetails
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const product = data.data;

                            document.querySelector(".product-form-container h2").textContent = "Cập nhật Sản phẩm";
                            document.getElementById("form-action-type").value = "update";

                            document.getElementById("product-id").value = product.MaSP;
                            document.getElementById("product-id").readOnly = true;
                            document.getElementById("product-name").value = product.TenSP;
                            document.getElementById("product-maLoai").value = product.MaLoai;
                            document.getElementById("product-chiTiet").value = product.ChiTiet;
                            document.getElementById("product-price").value = product.DonGia;
                            document.getElementById("product-quantity").value = product.SoLuong;

                            const statusValue = product.TrangThai;
                            document.getElementById("product-status").value = statusValue;

                            document.getElementById("product-moTa").value = product.MoTa;
                            document.getElementById("product-maNV").value = product.MaNV;

                            const preview = document.getElementById("preview-image");
                            if (product.Anh) {
                                preview.src = 'data:image/jpeg;base64,' + product.Anh;
                                preview.style.display = "block";
                            } else {
                                preview.src = '../images/no_image.png';
                                preview.style.display = "block";
                            }

                            document.getElementById("product-form-overlay").style.display = "flex";
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi tải chi tiết:', error);
                        alert('Có lỗi xảy ra khi tải chi tiết sản phẩm.');
                    });
            }
        });

        function logoutConfirm() {
            if (confirm("Bạn có chắc chắn muốn đăng xuất không?")) {
                window.location.replace("../logout.php");
            }
        }
    </script>
</body>

</html>