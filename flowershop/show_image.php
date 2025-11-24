<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "flowershop";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    http_response_code(500);
    echo "Lỗi kết nối CSDL";
    exit;
}
if (!isset($_GET['MaSP'])) {
    http_response_code(400);
    echo "Thiếu tham số MaSP";
    exit;
}

$maSP = $_GET['MaSP'];
$stmt = $conn->prepare("SELECT Anh FROM sanpham WHERE MaSP = ? LIMIT 1");
$stmt->bind_param("s", $maSP);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $imageData = $row['Anh'];
    if (!empty($imageData)) {

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $imageData);
        header("Content-Type: $mimeType");
        echo $imageData;
        exit;
    }
}
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
