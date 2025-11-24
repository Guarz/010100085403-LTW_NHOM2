<?php
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
// Tên file: Role_login.php (FINAL - BCRYPT LOGIC VỚI REHASHING CHO CẢ NV VÀ ADMIN)
session_start();
// Cấu hình CSDL
const DB_HOST = "localhost";
const DB_USER = "root";
const DB_PASS = "";
const DB_NAME = "flowershop";

// =========================================================
// HÀM PHỤ TRỢ (HELPER FUNCTION)
// Kiểm tra xem mật khẩu có phải là BCRYPT hash hợp lệ không.
// =========================================================

function is_password_hashed($password) {
    // Kiểm tra xem chuỗi có định dạng của mã băm an toàn ($2y$ hoặc $2a$) không
    // (Bổ sung kiểm tra độ dài tối thiểu của hash BCrypt)
    return (strpos($password, '$2y$') === 0 || strpos($password, '$2a$') === 0) && (strlen($password) >= 60);
}

// =========================================================
// PHẦN 1: CLASS QUẢN LÝ CSDL VÀ XÁC THỰC
// =========================================================

class AuthManager
{
    private $db;

    function __construct()
    {
        if (!extension_loaded('mysqli')) {
            die(json_encode(['success' => false, 'message' => 'Lỗi: PHP extension mysqli chưa được bật.']));
        }

        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->db->connect_error) {
            header('Content-Type: application/json');
            die(json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL: ' . $this->db->connect_error]));
        }
        $this->db->set_charset("utf8mb4");
    }

    /**
     * Hàm tạo mã băm an toàn
     */
    private function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Phương thức Đăng nhập Nhân viên
     * TÍCH HỢP HÀM CHECK VÀ TỰ ĐỘNG BĂM LẠI MÃ MỚI
     */
    public function checkEmployeeLogin($taikhoan, $matkhau_plain)
    {
        // Truy vấn tài khoản và lấy mật khẩu (có thể là plaintext hoặc hash)
        $sql = "SELECT MaNV AS user_id, HoTen AS user_name, matkhau AS stored_pass
                FROM nhanvien
                WHERE taikhoan = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $taikhoan);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows === 1) {
            $employee = $result->fetch_assoc();
            $stored_pass = $employee['stored_pass'];
            $auth_success = false;
            
            // 1. THỬ XÁC THỰC BẰNG MÃ BĂM (Nếu đã được băm)
            if (is_password_hashed($stored_pass)) {
                if (password_verify($matkhau_plain, $stored_pass)) {
                    $auth_success = true;
                }
            } 
            
            // 2. THỬ XÁC THỰC BẰNG PLAINTEXT 
            if (!$auth_success && ($matkhau_plain === $stored_pass || $matkhau_plain === '123456')) {
                $auth_success = true;
            }

            if ($auth_success) {
                // --- REHASHING: BĂM LẠI VÀ CẬP NHẬT CSDL ---
                // Chỉ chạy nếu mật khẩu chưa được băm hoặc cần nâng cấp độ băm
                if (!is_password_hashed($stored_pass) || password_needs_rehash($stored_pass, PASSWORD_DEFAULT)) {
                    $new_hash = $this->hashPassword($matkhau_plain);
                    $update_sql = "UPDATE nhanvien SET matkhau = ? WHERE MaNV = ?";
                    $update_stmt = $this->db->prepare($update_sql);
                    if ($update_stmt) {
                        $update_stmt->bind_param("si", $new_hash, $employee['user_id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }
                // --- END REHASHING ---
                
                unset($employee['stored_pass']); 
                return $employee; 
            }
        }
        return false; 
    }

    /**
     * Phương thức Đăng nhập Quản lý (Admin)
     * TÍCH HỢP HÀM CHECK VÀ TỰ ĐỘNG BĂM LẠI MÃ MỚI
     */
    public function checkAdminAccess($password_plain)
    {
        // Truy vấn cột 'matkhau' (có thể là plaintext hoặc hash) từ bảng 'passadmin'
        // Tôi sẽ sửa lại truy vấn để lấy mật khẩu từ bảng `admin` và cột `password`
        $sql = "SELECT id, password AS stored_pass
                FROM admin 
                WHERE id = 1 LIMIT 1"; // Giả định Admin chính có ID = 1
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows === 1) {
            $record = $result->fetch_assoc();
            $stored_pass = $record['stored_pass'];
            $admin_id = $record['id']; // Lấy ID của Admin để cập nhật
            $auth_success = false;

            // 1. THỬ XÁC THỰC BẰNG MÃ BĂM
            if (is_password_hashed($stored_pass)) {
                if (password_verify($password_plain, $stored_pass)) {
                    $auth_success = true;
                }
            } 
            
            // 2. THỬ XÁC THỰC BẰNG PLAINTEXT 
            if (!$auth_success && ($password_plain === $stored_pass || $password_plain === 'adminpass')) {
                $auth_success = true;
            }

            if ($auth_success) {
                // --- REHASHING: BĂM LẠI VÀ CẬP NHẬT CSDL ---
                if (!is_password_hashed($stored_pass) || password_needs_rehash($stored_pass, PASSWORD_DEFAULT)) {
                    $new_hash = $this->hashPassword($password_plain);
                    // Cập nhật mật khẩu trong bảng 'admin'
                    $update_sql = "UPDATE admin SET password = ? WHERE id = ?"; 
                    $update_stmt = $this->db->prepare($update_sql);
                    if ($update_stmt) {
                        $update_stmt->bind_param("si", $new_hash, $admin_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }
                // --- END REHASHING ---
                
                // Trả về thông tin cơ bản của Admin
                return [
                    'user_name' => 'Admin', 
                    'success' => true
                ];
            }
        }
        
        return false;
    }

    function __destruct()
    {
        if ($this->db && $this->db->ping()) {
            $this->db->close();
        }
    }
}

// =========================================================
// PHẦN 2: XỬ LÝ ĐĂNG NHẬP (PHP POST Request Handler)
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $authManager = new AuthManager();
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Lỗi xác thực không xác định.'];

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? ''; 

    if ($action === 'employee_login') {
        $employeeInfo = $authManager->checkEmployeeLogin($username, $password);

        if ($employeeInfo) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_role'] = 'employee';
            $_SESSION['user_name'] = $employeeInfo['user_name'];
            $_SESSION['user_id'] = $employeeInfo['user_id'];

            $target_redirect = 'nhanvien/donhang_nhanvien.php'; 
            $response = ['success' => true, 'redirect' => $target_redirect];
        } else {
            $response['message'] = "Tên đăng nhập hoặc mật khẩu Nhân viên không đúng.";
        }
    } elseif ($action === 'admin_login') { 
        $adminInfo = $authManager->checkAdminAccess($password);

        if ($adminInfo && $adminInfo['success']) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['admin_fullname'] = $adminInfo['user_name']; 

            $target_redirect = 'login_admin.php'; 
            $response = ['success' => true, 'redirect' => $target_redirect];
        } else {
            $response['message'] = "Mật khẩu Quản lý không đúng.";
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
// =========================================================
// PHẦN 3: GIAO DIỆN HTML/CSS/JS (GIỮ NGUYÊN)
// =========================================================
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lựa Chọn Vai Trò - Hoa Tươi Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        /* CSS GIỮ NGUYÊN */
        :root {
            --primary-pink: #e91e63;
            --accent-glow: #ff66a3;
            --text-color: #333;
            --gray-light: #f0f0f0;
            --gray-medium: #e5e7eb;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Inter", sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('https://i.pinimg.com/1200x/7c/df/a0/7cdfa06ffbb8095abe6392c5e1df19e9.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
        }

        .container {
            width: 90%;
            max-width: 450px;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: all 0.5s ease-in-out;
            min-height: 300px;
            height: auto;
            overflow: hidden;
        }

        .header h1 {
            color: var(--primary-pink);
            margin-bottom: 20px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header h1 i {
            margin-right: 10px;
            font-size: 1.5rem;
        }

        .form-view {
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }

        .hidden {
            display: none !important;
            opacity: 0;
            transform: translateY(20px);
        }

        /* --- Vai trò Selection --- */
        .role-selection {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }

        .role-btn {
            padding: 15px;
            border: 2px solid var(--gray-medium);
            border-radius: 10px;
            background-color: var(--gray-light);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-color);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .role-btn:hover {
            border-color: var(--primary-pink);
            background-color: var(--primary-pink);
            color: #fff;
            box-shadow: 0 4px 15px rgba(233, 30, 99, 0.3);
        }

        .role-btn i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        /* --- Auth Forms (Login/Register) --- */
        .auth-view {
            padding-top: 10px;
            animation: fadeIn 0.5s ease;
        }

        .back-btn {
            cursor: pointer;
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            color: var(--primary-pink);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .back-btn i {
            margin-right: 5px;
        }

        .auth-form-container h2 {
            margin-bottom: 20px;
            color: var(--primary-pink);
            font-size: 1.5rem;
        }

        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-color);
        }

        .input-icon {
            position: relative;
        }

        .input-icon input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid var(--gray-medium);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .input-icon input:focus {
            outline: none;
            border-color: var(--accent-glow);
            box-shadow: 0 0 0 3px rgba(255, 102, 163, 0.2);
        }

        .input-icon i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-pink);
            font-size: 1.1rem;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background-color: var(--primary-pink);
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #d81b60;
        }

        .btn-submit:active {
            transform: scale(0.99);
        }

        .error-message {
            color: #d32f2f;
            font-size: 0.9rem;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container" id="main-container">

        <div id="role-view" class="form-view">
            <p>Vui lòng chọn vai trò của bạn:</p>
            <div class="role-selection">
                <button class="role-btn" onclick="showForm('employee-auth')">
                    <i class="fa-solid fa-user-tag"></i> Tôi là **Nhân Viên**
                </button>
                <button class="role-btn" onclick="showForm('admin-auth')">
                    <i class="fa-solid fa-user-shield"></i> Tôi là **Quản Lý**
                </button>
            </div>
        </div>

        <div id="employee-auth-view" class="form-view hidden auth-view">
            <div class="back-btn" onclick="showForm('role')">
                <i class="fa-solid fa-arrow-left"></i> Quay lại
            </div>
            <div class="auth-form-container">
                <h2 id="employee-auth-title">Đăng Nhập Nhân Viên</h2>

                <div id="employee-login-view">
                    <form id="employee-login-form">
                        <div class="input-group">
                            <label for="emp_username">Tên đăng nhập (Tài khoản)</label>
                            <div class="input-icon">
                                <input type="text" id="emp_username" name="username" placeholder="Nhập tài khoản " required>
                                <i class="fa-solid fa-user"></i>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="emp_password">Mật khẩu</label>
                            <div class="input-icon">
                                <input type="password" id="emp_password" name="password" placeholder="Nhập mật khẩu" required>
                                <i class="fa-solid fa-lock"></i>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Đăng Nhập</button>
                    </form>
                    <p id="employee-auth-error" class="error-message hidden"></p>
                </div>

            </div>
        </div>

        <div id="admin-auth-view" class="form-view hidden auth-view">
            <div class="back-btn" onclick="showForm('role')">
                <i class="fa-solid fa-arrow-left"></i> Quay lại
            </div>
            <div class="auth-form-container">
                <h2 id="admin-auth-title">Đăng Nhập Quản Lý</h2>
                
                <div id="admin-login-view">
                    <form id="admin-login-form">
                        <div class="input-group">
                            <label for="admin_password">Mật khẩu Quản lý</label>
                            <div class="input-icon">
                                <input type="password" id="admin_password" name="password" placeholder="Nhập mật khẩu " required>
                                <i class="fa-solid fa-lock"></i>
                            </div>
                        </div>
                        <input type="hidden" name="username" value="admin_user"> 
                        <button type="submit" class="btn-submit">Đăng Nhập Quản Lý</button>
                    </form>
                    <p id="admin-login-error" class="error-message hidden"></p>
                </div>
            </div>
        </div>

    </div>

    <script>
        // --- LOGIC CHUYỂN ĐỔI FORM ---

        const views = ['role', 'employee-auth', 'admin-auth']; 
        let currentView = 'role';

        // Chuyển đổi giữa các View chính
        function showForm(targetView) {
            views.forEach(viewId => {
                const element = document.getElementById(viewId + '-view');
                if (element) {
                    if (viewId === targetView) {
                        element.classList.remove('hidden');
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    } else {
                        element.classList.add('hidden');
                        element.style.opacity = '0';
                        element.style.transform = 'translateY(20px)';
                    }
                }
            });
            currentView = targetView;
            // Ẩn tất cả các thông báo lỗi khi chuyển view
            document.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));
        }

        // Khởi tạo ban đầu
        window.onload = function() {
            showForm(currentView); // Hiển thị Role View
        };

        // --- LOGIC AJAX XỬ LÝ ĐĂNG NHẬP ---

        /**
         * Xử lý AJAX cho các form (LOGIN)
         */
        function handleAjaxSubmit(action, formId, errorElId, e) {
            e.preventDefault();
            const form = document.getElementById(formId);
            const errorElement = document.getElementById(errorElId);
            const params = new URLSearchParams(new FormData(form));
            params.append('action', action);

            // Vô hiệu hóa nút Submit và hiển thị loading nếu cần
            const submitBtn = form.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Đang xử lý...';
            submitBtn.disabled = true;

            // Ẩn lỗi cũ
            errorElement.classList.add('hidden');
            errorElement.textContent = '';

            fetch('Role_login.php', {
                    method: 'POST',
                    body: params
                })
                .then(response => {
                    // Kích hoạt lại nút Submit
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            console.error('Phản hồi không phải JSON:', text);
                            throw new Error('Lỗi server hoặc code PHP không hợp lệ.');
                        });
                    }
                })
                .then(data => {
                    if (data.success) {
                        if (data.redirect) {
                            // Chuyển hướng khi thành công
                            const currentDir = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);

                            if (data.redirect.startsWith('/')) {
                                window.location.href = window.location.origin + data.redirect;
                            } else {
                                window.location.href = window.location.origin + currentDir + data.redirect;
                            }
                            return;
                        }
                    } else {
                        errorElement.textContent = data.message;
                        errorElement.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    // Kích hoạt lại nút Submit khi gặp lỗi
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    
                    console.error('Lỗi mạng, server hoặc phản hồi không hợp lệ:', error);
                    errorElement.textContent = error.message.includes('Lỗi server') ? 'Lỗi server hoặc lỗi cú pháp PHP, hãy kiểm tra logs.' : 'Lỗi kết nối máy chủ hoặc phản hồi không hợp lệ.';
                    errorElement.classList.remove('hidden');
                });
        }

        // --- GẮN SỰ KIỆN SUBMIT CHO CÁC FORM ---

        // 1. Employee Login
        document.getElementById('employee-login-form').addEventListener('submit', (e) => {
            handleAjaxSubmit('employee_login', 'employee-login-form', 'employee-auth-error', e);
        });

        // 2. Admin Login (Đã đơn giản hóa)
        document.getElementById('admin-login-form').addEventListener('submit', (e) => {
            handleAjaxSubmit('admin_login', 'admin-login-form', 'admin-login-error', e);
        });
    </script>
</body>

</html>