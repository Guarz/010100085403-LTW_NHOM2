<?php
// ===============================
// FILE: show_image.php
// Mục đích: Hiển thị ảnh sản phẩm được lưu trong CSDL (kiểu BLOB)
// ===============================

// --- Kết nối database ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "flowershop";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// Kiểm tra lỗi kết nối
if ($conn->connect_error) {
    http_response_code(500);
    echo "Lỗi kết nối CSDL";
    exit;
}

// --- Kiểm tra tham số MaSP ---
if (!isset($_GET['MaSP'])) {
    http_response_code(400);
    echo "Thiếu tham số MaSP";
    exit;
}

$maSP = $_GET['MaSP'];

// --- Truy vấn ảnh ---
$stmt = $conn->prepare("SELECT Anh FROM sanpham WHERE MaSP = ? LIMIT 1");
$stmt->bind_param("s", $maSP);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $imageData = $row['Anh'];
    if (!empty($imageData)) {

        // --- Xác định loại MIME tự động ---
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);

        // --- Gửi header và hiển thị ảnh ---
        header("Content-Type: $mimeType");
        echo $imageData;
        exit;
    }
}

// --- Nếu không có ảnh, trả về ảnh mặc định ---
$placeholderPath = __DIR__ . "/images/placeholder.jpg";
if (file_exists($placeholderPath)) {
    header("Content-Type: image/jpeg");
    readfile($placeholderPath);
} else {
    http_response_code(404);
    echo "Không có ảnh.";
}

$stmt->close();
$conn->close();
?>
