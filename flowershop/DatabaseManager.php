<?php
class DatabaseManager
{
    public $host = "localhost";
    public $user = "root";
    public $pass = "";
    public $dbname = "flowershop";
    private $db;

    function __construct()
    {
        $this->db = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

        if ($this->db->connect_error) {
            die("Kết nối CSDL thất bại: " . $this->db->connect_error);
        }

        $this->db->set_charset("utf8mb4");
    }
    public function getListGioHang()
    {
        $sql = "SELECT 
                g.MaGH, 
                g.NgayDat, 
                g.TrangThai, 
                g.TongTien, 
                g.MaKH, 
                k.HoTen 
              FROM giohang g
              LEFT JOIN khachhang k ON g.MaKH = k.MaKH
              ORDER BY g.NgayDat DESC";
        return $this->db->query($sql);
    }

    public function getChiTietDonHang($maGH)
    {
        $sql = "SELECT h.MaSP, h.SoLuong, h.Gia, s.TenSP 
          FROM hang h 
          JOIN sanpham s ON h.MaSP = s.MaSP
          WHERE h.MaGH = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $maGH);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    public function updateTrangThai($maGH, $trangThaiMoi)
    {
        $sql = "UPDATE giohang SET TrangThai = ? WHERE MaGH = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("ss", $trangThaiMoi, $maGH);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function deleteDonHang($maGH)
    {
        $this->db->begin_transaction();
        try {
            $sql1 = "DELETE FROM hang WHERE MaGH = ?";
            $stmt1 = $this->db->prepare($sql1);
            if (!$stmt1) throw new Exception("Prepare failed for hang: " . $this->db->error);
            $stmt1->bind_param("s", $maGH);
            $stmt1->execute();
            $stmt1->close();

            $sql2 = "DELETE FROM giohang WHERE MaGH = ?";
            $stmt2 = $this->db->prepare($sql2);
            if (!$stmt2) throw new Exception("Prepare failed for giohang: " . $this->db->error);
            $stmt2->bind_param("s", $maGH);
            $stmt2->execute();
            $stmt2->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
    public function getListKhachHang($search = '')
    {
        $sql = "SELECT MaKH, HoTen, NgaySinh, DT, Email 
                FROM khachhang";

        $params = [];
        $types = '';

        if ($search) {
            $sql .= " WHERE HoTen LIKE ? OR MaKH LIKE ? OR DT LIKE ? OR Email LIKE ?";
            $searchParam = "%" . $search . "%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam];
            $types = 'ssss';
        }

        $sql .= " ORDER BY MaKH ASC";

        if ($search) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result();
        } else {
            return $this->db->query($sql);
        }
    }

    public function getCustomerStats()
    {
        $stats = [];

        $stats['total'] = (int)$this->db->query("SELECT COUNT(MaKH) AS count FROM khachhang")->fetch_assoc()['count'];

        $today = date('Y-m-d');
        $stats['new_today'] = (int)$this->db->query("SELECT COUNT(MaKH) AS count FROM khachhang WHERE NgayDK = '$today'")->fetch_assoc()['count'];
        $sql_potential = "SELECT COUNT(k.MaKH) AS count 
                      FROM khachhang k
                      LEFT JOIN giohang g ON k.MaKH = g.MaKH
                      WHERE g.MaGH IS NULL";

        $stats['potential'] = (int)$this->db->query($sql_potential)->fetch_assoc()['count'];

        return $stats;
    }

    private function deleteGioHangByMaKH($maKH)
    {
        $maGHs = [];
        $result = $this->db->query("SELECT MaGH FROM giohang WHERE MaKH = '{$maKH}'");
        while ($row = $result->fetch_assoc()) {
            $maGHs[] = $row['MaGH'];
        }

        if (empty($maGHs)) return true;

        $this->db->begin_transaction();
        try {
            $placeholders = implode(',', array_fill(0, count($maGHs), '?'));
            $types = str_repeat('s', count($maGHs));

            $sql1 = "DELETE FROM hang WHERE MaGH IN ({$placeholders})";
            $stmt1 = $this->db->prepare($sql1);
            if (!$stmt1) throw new Exception("Prepare failed for hang: " . $this->db->error);
            $stmt1->bind_param($types, ...$maGHs);
            $stmt1->execute();
            $stmt1->close();

            $sql2 = "DELETE FROM giohang WHERE MaKH = ?";
            $stmt2 = $this->db->prepare($sql2);
            if (!$stmt2) throw new Exception("Prepare failed for giohang: " . $this->db->error);
            $stmt2->bind_param("s", $maKH);
            $stmt2->execute();
            $stmt2->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function deleteKhachHang($maKH)
    {
        $this->deleteGioHangByMaKH($maKH);

        $sql = "DELETE FROM khachhang WHERE MaKH = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("s", $maKH);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function getKhachHangById($maKH)
    {
        $sql = "SELECT MaKH, HoTen, NgaySinh, DT, Email FROM khachhang WHERE MaKH = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("s", $maKH);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
    }

    public function updateKhachHang($maKH, $hoTen, $ngaySinh, $dt, $email)
    {
        $sql = "UPDATE khachhang SET HoTen = ?, NgaySinh = ?, DT = ?, Email = ? WHERE MaKH = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("sssss", $hoTen, $ngaySinh, $dt, $email, $maKH);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function addKhachHang($hoTen, $ngaySinh, $dt, $email)
    {
        $sql = "INSERT INTO khachhang (HoTen, NgaySinh, DT, Email) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("ssss", $hoTen, $ngaySinh, $dt, $email);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    public function getOrdersByMaKH($maKH)
    {
        $sql = "SELECT MaGH, NgayDat, TrangThai, TongTien FROM giohang WHERE MaKH = ? ORDER BY NgayDat DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("s", $maKH);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        return $orders;
    }

    public function getChiTietHangByMaGH($maGH)
    {
        $sql = "SELECT MaSP, Gia, SoLuong FROM hang WHERE MaGH = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $maGH);
        $stmt->execute();
        $result = $stmt->get_result();

        $details = [];
        while ($row = $result->fetch_assoc()) {
            $row['ThanhTien'] = $row['SoLuong'] * $row['Gia'];
            $row['Loai'] = "Mã SP: " . $row['MaSP'];
            $details[] = $row;
        }
        $stmt->close();
        return $details;
    }

    public function getNhanVienById($maNV)
    {
        $sql = "SELECT MaNV, HoTen, NgaySinh, DT, ViTri, NgayLam, Luong, TrangThai 
                FROM nhanvien 
                WHERE MaNV = ? 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("i", $maNV);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
    }

    public function deleteNhanVien($maNV)
    {
        $sql = "DELETE FROM nhanvien WHERE MaNV = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("i", $maNV);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function updateNhanVien($maNV, $hoTen, $ngaySinh, $dt, $viTri, $ngayLam, $luong, $matKhau, $trangThai)
    {
        if (!empty($matKhau)) {
            $matKhauHash = password_hash($matKhau, PASSWORD_DEFAULT);
            $sql = "UPDATE nhanvien SET HoTen=?, NgaySinh=?, DT=?, ViTri=?, NgayLam=?, Luong=?, MatKhau=?, TrangThai=? WHERE MaNV=?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sssssds si", $hoTen, $ngaySinh, $dt, $viTri, $ngayLam, $luong, $matKhauHash, $trangThai, $maNV);
        } else {
            $sql = "UPDATE nhanvien SET HoTen=?, NgaySinh=?, DT=?, ViTri=?, NgayLam=?, Luong=?, TrangThai=? WHERE MaNV=?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sssssdsi", $hoTen, $ngaySinh, $dt, $viTri, $ngayLam, $luong, $trangThai, $maNV);
        }

        if (!$stmt) return false;
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function addNhanVien($hoTen, $ngaySinh, $dt, $viTri, $ngayLam, $luong, $matKhau, $trangThai)
    {
        $matKhauHash = password_hash($matKhau, PASSWORD_DEFAULT);

        $sql = "INSERT INTO nhanvien (HoTen, NgaySinh, DT, ViTri, NgayLam, Luong, MatKhau, TrangThai) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("sssssdss", $hoTen, $ngaySinh, $dt, $viTri, $ngayLam, $luong, $matKhauHash, $trangThai);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function getListNhanVien()
    {
        $sql = "SELECT MaNV, HoTen, NgaySinh, DT, ViTri, NgayLam, Luong, TrangThai 
                FROM nhanvien 
                ORDER BY FIELD(ViTri, 'QuanLi') DESC, NgayLam DESC";
        return $this->db->query($sql);
    }
    public function getListTaiKhoanNV()
    {
        $sql = "SELECT MaNV, HoTen, ViTri, taikhoan, matkhau, created_at
                FROM nhanvien
                ORDER BY MaNV ASC";
        return $this->db->query($sql);
    }

    public function getTaiKhoanNVById($maNV)
    {
        $sql = "SELECT MaNV, HoTen, ViTri, taikhoan, matkhau
                FROM nhanvien
                WHERE MaNV = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $maNV);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
    }

    public function deleteTaiKhoanNV($maNV)
    {
        $sql = "DELETE FROM nhanvien WHERE MaNV = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("s", $maNV);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function addTaiKhoanNV($maNV, $taiKhoan, $matKhau)
    {
        $sql = "INSERT INTO nhanvien (MaNV, taikhoan, matkhau) VALUES (?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("sss", $maNV, $taiKhoan, $matKhau);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function updateTaiKhoanNV($maNV, $taiKhoan, $matKhau)
    {
        if (!empty($matKhau)) {
            $sql = "UPDATE nhanvien SET taikhoan = ?, matkhau = ? WHERE MaNV = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("sss", $taiKhoan, $matKhau, $maNV);
        } else {
            $sql = "UPDATE nhanvien SET taikhoan = ? WHERE MaNV = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("ss", $taiKhoan, $maNV);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function getEmployeeActionLog($maNV)
    {
        return [
            ['timestamp' => '2025-10-24 10:30', 'action' => 'Đã sửa thông tin khách hàng KH2.'],
            ['timestamp' => '2025-10-24 09:15', 'action' => 'Đã xóa đơn hàng GH123.'],
            ['timestamp' => '2025-10-23 15:40', 'action' => 'Đã thêm sản phẩm SP99.'],
        ];
    }

    public function getGeneralStats()
    {
        $stats = [];

        $doanhThuResult = $this->db->query("
            SELECT SUM(h.SoLuong * h.Gia) AS total_revenue 
            FROM hang h
            JOIN giohang g ON h.MaGH = g.MaGH
            WHERE g.TrangThai IN ('DaThanhToan', 'DangGiao')
        ");
        $stats['total_revenue'] = (float)($doanhThuResult->fetch_assoc()['total_revenue'] ?? 0);

        $stats['total_orders'] = (int)$this->db->query("SELECT COUNT(MaGH) AS count FROM giohang")->fetch_assoc()['count'];

        $productSoldResult = $this->db->query("SELECT SUM(h.SoLuong) AS total_sold FROM hang h JOIN giohang g ON h.MaGH = g.MaGH WHERE g.TrangThai IN ('DaThanhToan', 'DangGiao')");
        $stats['total_sold_products'] = (int)($productSoldResult->fetch_assoc()['total_sold'] ?? 0);

        $stats['total_customers'] = (int)$this->db->query("SELECT COUNT(MaKH) AS count FROM khachhang")->fetch_assoc()['count'];

        return $stats;
    }

    public function getTopSellingProducts()
    {
        $sql = "SELECT s.TenSP, SUM(h.SoLuong) AS DaBan 
            FROM hang h
            JOIN giohang g ON h.MaGH = g.MaGH
            JOIN sanpham s ON h.MaSP = s.MaSP
            WHERE g.TrangThai IN ('DaThanhToan', 'DangGiao')
            GROUP BY h.MaSP, s.TenSP
            ORDER BY DaBan DESC
            LIMIT 5";
        return $this->db->query($sql);
    }

    public function getTopSpendingCustomers()
    {
        $sql = "SELECT k.HoTen AS TenKhachHang, SUM(g.TongTien) AS TongChiTieu
                FROM giohang g
                JOIN khachhang k ON g.MaKH = k.MaKH
                WHERE g.TrangThai IN ('DaThanhToan', 'DangGiao')
                GROUP BY k.MaKH, k.HoTen
                ORDER BY TongChiTieu DESC
                LIMIT 5";
        return $this->db->query($sql);
    }

    public function getMonthlyRevenueData()
    {
        $sql = "SELECT DATE(g.NgayDat) AS ngay, SUM(h.SoLuong * h.Gia) AS revenue 
            FROM giohang g
            JOIN hang h ON g.MaGH = h.MaGH
            WHERE g.TrangThai IN ('DaThanhToan', 'DangGiao')
            GROUP BY DATE(g.NgayDat)
            ORDER BY ngay DESC";

        $result = $this->db->query($sql);

        $revenueMap = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $revenueMap[$row['ngay']] = (float)$row['revenue'];
            }
        }

        $chartData = [];
        $today = new DateTime();
        for ($i = 29; $i >= 0; $i--) {
            $date = clone $today;
            $date->modify("-{$i} days");
            $formattedDate = $date->format('Y-m-d');
            $label = $date->format('d/m');
            $revenue = $revenueMap[$formattedDate] ?? 0;

            $chartData[] = [
                'label' => $label,
                'revenue' => $revenue
            ];
        }

        return $chartData;
    }

    public function getSoldProductsDetails()
    {
        $sql = "SELECT g.MaGH, g.NgayDat, h.MaSP, s.TenSP AS TenSP, h.SoLuong, h.Gia, k.MaKH, k.HoTen
                FROM hang h
                JOIN giohang g ON h.MaGH = g.MaGH
                JOIN sanpham s ON h.MaSP = s.MaSP
                JOIN khachhang k ON g.MaKH = k.MaKH
                WHERE g.TrangThai IN ('DaThanhToan', 'DangGiao') 
                ORDER BY g.NgayDat DESC";
        return $this->db->query($sql);
    }
    public function getListLoaiSanPham()
    {
        $sql = "SELECT MaLoai, TenLoai FROM loaisanpham ORDER BY MaLoai ASC";
        return $this->db->query($sql);
    }


    public function getListSanPham()
    {
        $sql = "SELECT MaSP, TenSP, MaLoai, Anh, ChiTiet, DonGia, SoLuong, TrangThai, MoTa, MaNV 
                FROM sanpham 
                ORDER BY MaSP ASC";
        return $this->db->query($sql);
    }


    public function getSanPhamById($maSP)
    {
        $sql = "SELECT MaSP, TenSP, MaLoai, Anh, ChiTiet, DonGia, SoLuong, TrangThai, MoTa, MaNV 
                FROM sanpham 
                WHERE MaSP = ? 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $maSP);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $product = $result->fetch_assoc();
        if ($product && $product['Anh']) {
            $product['Anh'] = base64_encode($product['Anh']);
        } else {
            $product['Anh'] = null;
        }
        return $product;
    }

    private function deleteHangByMaSP($maSP)
    {
        $sql = "DELETE FROM hang WHERE MaSP = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("s", $maSP);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function deleteSanPham($maSP)
    {
        $this->deleteHangByMaSP($maSP);

        $sql = "DELETE FROM sanpham WHERE MaSP = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $maSP);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function updateSanPham($maSP, $tenSP, $maLoai, $chiTiet, $donGia, $soLuong, $trangThai, $moTa, $maNV, $anh = null)
    {
        $sql = "UPDATE sanpham 
                SET TenSP = ?, MaLoai = ?, ChiTiet = ?, DonGia = ?, SoLuong = ?, TrangThai = ?, MoTa = ?, MaNV = ?";

        $trangThaiValue = ($trangThai == 'in' || $trangThai == 'Còn hàng') ? 'ConHang' : 'HetHang';
        $donGiaStr = strval($donGia);

        $params = "sississi";
        $bindValues = [$tenSP, $maLoai, $chiTiet, $donGiaStr, $soLuong, $trangThaiValue, $moTa, $maNV];

        if ($anh !== null) {
            $sql .= ", Anh = ?";
            $params .= "b";
            $bindValues[] = &$anh;
        }

        $sql .= " WHERE MaSP = ?";
        $params .= "s";
        $bindValues[] = $maSP;

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param($params, ...$bindValues);

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function addSanPham(
        $maSP,
        $tenSP,
        $maLoai,
        $chiTiet,
        $donGia,
        $soLuong,
        $trangThai,
        $moTa,
        $maNV,
        $anh = null
    ) {
        $sql = "INSERT INTO sanpham (MaSP, TenSP, MaLoai";

        $params = "ssi";
        $bindValues = [&$maSP, &$tenSP, &$maLoai];

        if ($anh !== null) {
            $sql .= ", Anh";
            $params .= "s";
            $bindValues[] = &$anh;
        }

        $sql .= ", ChiTiet, DonGia, SoLuong, TrangThai, MoTa, MaNV)
             VALUES (?, ?, ?";

        if ($anh !== null) {
            $sql .= ", ?";
        }

        $sql .= ", ?, ?, ?, ?, ?, ?)";
        $trangThaiValue = ($trangThai == 'in' || $trangThai == 'Còn hàng')
            ? 'ConHang'
            : 'HetHang';

        $donGiaStr = strval($donGia);
        $params .= "ssissi";
        $bindValues[] = &$chiTiet;
        $bindValues[] = &$donGiaStr;
        $bindValues[] = &$soLuong;
        $bindValues[] = &$trangThaiValue;
        $bindValues[] = &$moTa;
        $bindValues[] = &$maNV;
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param($params, ...$bindValues);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
    public function checkAdminLogin($username, $password)
    {
        $sql = "SELECT id, username, fullname, password FROM admin WHERE username = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if ($password === $admin['password']) return $admin;
        }
        return false;
    }
    public static function formatCurrencyPHP($amount)
    {
        return number_format($amount, 0, ',', '.');
    }
    public static function translateTrangThai($status)
    {
        switch ($status) {
            case 'ChuaThanhToan':
                return '<span class="status-badge status-pending">Chưa TT</span>';
            case 'DaThanhToan':
                return '<span class="status-badge status-success">Đã TT</span>';
            case 'DangGiao':
                return '<span class="status-badge status-shipping">Đang giao</span>';
            case 'DaHuy':
                return '<span class="status-badge status-cancelled">Đã hủy</span>';
            default:
                return $status;
        }
    }
    public static function translateTrangThaiNV($status)
    {
        if ($status === 'DangLam') return 'Đang hoạt động';
        if ($status === 'DaNghi') return 'Đã nghỉ việc';
        return $status;
    }
    public static function translateViTri($role)
    {
        if ($role === 'QuanLi') return 'Quản lý';
        if ($role === 'NV') return 'Nhân viên';
        return $role;
    }
    function stripUnicode($str)
    {
        if (!$str) return false;
        $unicode = array(
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'd' => 'đ',
            'D' => 'Đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ'
        );
        foreach ($unicode as $khongdau => $codau) {
            $arr = explode("|", $codau);
            $str = str_replace($arr, $khongdau, $str);
        }
        return $str;
    }
    function stripSpecial($str)
    {
        $arr = array(",", "$", "!", "?", "&", "'", '"', "+");
        $str = str_replace($arr, "", $str);
        $str = trim($str);
        while (strpos($str, "  ") > 0) $str = str_replace("  ", " ", $str);
        $str = str_replace(" ", "-", $str);
        return $str;
    }
    function changeTitle($str)
    {
        $str = $this->stripUnicode($str);
        $str = $this->stripSpecial($str);
        return strtolower($str);
    }
    function stripUnicode1($str)
    {
        return $str;
    }
    function stripSpecial1($str)
    {
        return $str;
    }
    function __destruct()
    {
        if ($this->db && is_a($this->db, 'mysqli')) {
            $this->db->close();
        }
    }
    public static function translateViTri1($role)
    {
        switch ($role) {
            case 'QuanLi':
                return ['text' => 'Quản lý', 'class' => 'position-manager'];
            case 'NV':
                return ['text' => 'Nhân viên', 'class' => 'position-staff'];
            default:
                return ['text' => $role, 'class' => ''];
        }
    }
}
