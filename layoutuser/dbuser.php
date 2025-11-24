<?php
class dbuser
{
    public $host = "localhost";
    public $user = "root";
    public $pass = "";
    public $dbname = "flowershop";
    public $conn;

    public function __construct()
    {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

        if ($this->conn->connect_error) {
            die("Kết nối thất bại: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8");
    }
}

class Product
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    function HienThiSP($maLoai = null, $gia = null)
    {
        $sql = "SELECT * FROM sanpham WHERE TrangThai LIKE '%ConHang%'";
        $sql = $this->LocLoaiSP($sql, $maLoai);
        $result = $this->conn->query($sql);
        $products = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }

        return $products;
    }
    private function LocLoaiSP($sql, $maLoai)
    {
        if (!empty($maLoai)) {
            $maLoai = (int)$maLoai;
            $sql .= " AND MaLoai LIKE '$maLoai'";
        }
        return $sql;
    }

    function HienThiLoaiSP()
    {
        $sql = "SELECT TenLoai, MaLoai FROM loaisanpham WHERE TenLoai IS NOT NULL AND TenLoai <> ''";
        $result = $this->conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $maLoai = $row['MaLoai'];
                $tenLoai = htmlspecialchars($row['TenLoai']);


                echo "<a href='san-pham.php?MaLoai=$maLoai' class='filter-btn'>$tenLoai</a> ";
            }
        } else {
            echo "<p>Không có loại sản phẩm</p>";
        }
    }
    function HienThiLoaiSP_Options()
    {
        $sql = "SELECT MaLoai, TenLoai FROM loaisanpham WHERE TenLoai IS NOT NULL AND TenLoai <> ''";

        $result = $this->conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $maLoai = $row['MaLoai'];
                $tenLoai = htmlspecialchars($row['TenLoai']);
                echo "<option value='$maLoai'>$tenLoai</option>";
            }
        }
    }
    function ChiTietSP($maSP)
    {
        $sql = "SELECT * FROM sanpham WHERE MaSP = $maSP LIMIT 1";
        $result = $this->conn->query($sql);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
}

class User
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function loginUser($email, $password)
    {
        $emailEsc = $this->conn->real_escape_string($email);
        $sql = "SELECT * FROM khachhang WHERE Email = '$emailEsc'";
        $result = $this->conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($password === $user['PassKH']) {
                $_SESSION['user_id'] = $user['MaKH'];
                $_SESSION['user_name'] = $user['HoTen'];
                return $user;
            } else {
                return "Sai mật khẩu!";
            }
        } else {
            return "Không tìm thấy tài khoản!";
        }
    }

    public function registerUser($name, $email, $password)
    {
        $nameEsc = $this->conn->real_escape_string($name);
        $emailEsc = $this->conn->real_escape_string($email);
        $passEsc = $this->conn->real_escape_string($password);

        $check = $this->conn->query("SELECT * FROM khachhang WHERE Email = '$emailEsc'");
        if ($check && $check->num_rows > 0) {
            return "Email này đã được đăng ký!";
        }

        $sql = "INSERT INTO khachhang (HoTen, Email, PassKH) VALUES ('$nameEsc', '$emailEsc', '$passEsc')";
        if ($this->conn->query($sql)) {
            return "Đăng ký thành công! Hãy đăng nhập.";
        } else {
            return "Lỗi khi đăng ký: " . $this->conn->error;
        }
    }

    public function resetPassword($email)
    {
        $sql = "SELECT * FROM khachhang WHERE Email = '$email'";
        $result = $this->conn->query($sql);

        if ($result && $result->num_rows > 0) {
            return "Liên kết khôi phục mật khẩu đã được gửi đến email của bạn.";
        } else {
            return "Không tìm thấy tài khoản với email này!";
        }
    }

    public function getUserDetails($maKH)
    {
        $sql = "SELECT HoTen, Email, PassKH, NgaySinh, DT FROM khachhang WHERE MaKH = '$maKH'";
        $result = $this->conn->query($sql);
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = [];
        session_destroy();
        if (isset($_COOKIE["remember_token"])) {
            setcookie("remember_token", "", time() - 3600, "/");
        }
        header("Location: taikhoan.php");
        exit;
    }
    public function updateUser($maKH, $hoTen, $email, $ngaySinh, $DT)
    {
        try {
            $sql = "UPDATE KhachHang 
                    SET HoTen = '$hoTen', 
                        Email = '$email', 
                        NgaySinh = '$ngaySinh', 
                        DT = '$DT' 
                    WHERE MaKH = $maKH";
            return $this->conn->query($sql);
        } catch (Exception $e) {
            return false;
        }
    }
    public function updatePassword($maKH, $newPassword)
    {
        try {
            $sql = "UPDATE KhachHang 
                    SET PassKH = '$newPassword' 
                    WHERE MaKH = $maKH";
            return $this->conn->query($sql);
        } catch (Exception $e) {
            return false;
        }
    }
}

$message = "";
if (session_status() === PHP_SESSION_NONE) session_start();
$db = new dbuser();
$conn = $db->conn;
$user = new User($conn);

if (isset($_POST["login"])) {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $message = $user->loginUser($email, $password);
    if (is_array($message)) {
        header("Location: index.php");
        exit;
    }
} elseif (isset($_POST["register"])) {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $message = $user->registerUser($name, $email, $password);
} elseif (isset($_POST["reset"])) {
    $email = $_POST["email"];
    $message = $user->resetPassword($email);
}
class Cart
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getCartByUser($maKH)
    {
        $sql = "
            SELECT 
                s.TenSP, s.Anh, s.DonGia,
                h.SoLuong, g.MaGH, s.MaSP
            FROM giohang g
            INNER JOIN hang h ON g.MaGH = h.MaGH
            INNER JOIN sanpham s ON h.MaSP = s.MaSP
            WHERE g.MaKH = $maKH AND g.TrangThai = 'ChuaThanhToan'
        ";
        return $this->conn->query($sql);
    }

    public function getOrCreateCart($maKH)
    {
        $sqlCheck = "SELECT MaGH FROM giohang WHERE MaKH = $maKH AND TrangThai = 'ChuaThanhToan' LIMIT 1";
        $result = $this->conn->query($sqlCheck);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['MaGH'];
        }

        $sqlInsert = "INSERT INTO giohang (MaKH, TrangThai, NgayDat, TongTien)
                      VALUES ($maKH, 'ChuaThanhToan', NOW(), 0)";
        $this->conn->query($sqlInsert);
        return $this->conn->insert_id;
    }

    public function addToCart($maKH, $maSP, $soLuong, $gia)
    {
        
        $maGH = $this->getOrCreateCart($maKH);

        $sqlCheck = "SELECT * FROM hang WHERE MaGH = $maGH AND MaSP = '$maSP'";
        $result = $this->conn->query($sqlCheck);

        if ($result && $result->num_rows > 0) {
        $sqlUpdate = "UPDATE hang SET SoLuong = SoLuong + $soLuong WHERE MaGH = $maGH AND MaSP = '$maSP'";
        $updateResult = $this->conn->query($sqlUpdate);

        if (!$updateResult) {
            echo "<h1>LỖI KHI CẬP NHẬT:</h1>";
            echo "<p>Câu lệnh: $sqlUpdate</p>";
            echo "<p>Lỗi: " . $this->conn->error . "</p>";
            die; 
        }
    } else {
        $sqlInsert = "INSERT INTO hang (MaGH, MaSP, SoLuong, Gia) VALUES ($maGH, '$maSP', $soLuong, $gia)";
        $insertResult = $this->conn->query($sqlInsert);

        if (!$insertResult) {
            echo "<h1>LỖI KHI THÊM MỚI:</h1>";
            echo "<p>Câu lệnh: $sqlInsert</p>";
            echo "<p>Lỗi: " . $this->conn->error . "</p>";
            die; 
        }
    }
    $this->updateCartTotal($maGH);
}

    public function updateCartTotal($maGH)
    {
        $sql = "
            UPDATE giohang 
            SET TongTien = (SELECT COALESCE(SUM(Gia * SoLuong), 0) FROM hang WHERE MaGH = $maGH)
            WHERE MaGH = $maGH
        ";
        $this->conn->query($sql);
    }

    public function deleteItem($maKH, $maSP)
    {
        $maSP = $this->conn->real_escape_string($maSP);

        $sqlCart = "SELECT MaGH FROM giohang WHERE MaKH = $maKH AND TrangThai = 'ChuaThanhToan' LIMIT 1";
        $resultCart = $this->conn->query($sqlCart);

        if ($resultCart && $resultCart->num_rows > 0) {
            $cartRow = $resultCart->fetch_assoc();
            $maGH = $cartRow['MaGH'];

            $sqlDelete = "DELETE FROM hang WHERE MaGH = $maGH AND MaSP = '$maSP'";
            $this->conn->query($sqlDelete);

            $this->updateCartTotal($maGH);
            return true;
        }
        return false;
    }

    public function getCartTotal($maKH)
    {
        $sql = "SELECT TongTien FROM giohang WHERE MaKH = $maKH AND TrangThai = 'ChuaThanhToan' LIMIT 1";
        $result = $this->conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['TongTien'];
        }
        return 0;
    }

    public function checkout($maKH)
    {
        $sqlCheck = "SELECT MaGH FROM giohang WHERE MaKH = $maKH AND TrangThai = 'ChuaThanhToan' LIMIT 1";
        $result = $this->conn->query($sqlCheck);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $maGH = $row['MaGH'];

            $this->updateCartTotal($maGH);

            $sqlUpdate = "UPDATE giohang 
                          SET TrangThai = 'DaThanhToan', NgayDat = NOW()
                          WHERE MaGH = $maGH";
            if ($this->conn->query($sqlUpdate)) {
                $sqlNew = "INSERT INTO giohang (MaKH, TrangThai, NgayDat, TongTien)
                           VALUES ($maKH, 'ChuaThanhToan', NOW(), 0)";
                $this->conn->query($sqlNew);
                return true;
            }
        }
        return false;
    }
}
class ChiTietSP{
    function display_image($blob_data, $default_text = 'Ảnh')
    {
      if (!empty($blob_data)) {
        return 'data:image/jpeg;base64,' . base64_encode($blob_data);
      }
      return "https://via.placeholder.com/500x450/fde0ef/333?text=" . urlencode($default_text);
    }
}