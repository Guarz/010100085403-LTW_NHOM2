<?php
// Tên file: login_admin.php (Đã tái cấu trúc và sửa lỗi)
session_start();

// =========================================================
// CLASS 1: Quản lý Kết nối CSDL
// =========================================================
class Database
{
    private $db;
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "flowershop";

    public function __construct()
    {
        try {
            // Chỉ kết nối khi cần thiết
            $this->db = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
            if ($this->db->connect_error) {
                $_SESSION['system_error'] = "Lỗi hệ thống: Không thể kết nối CSDL.";
                $this->db = null; // Đảm bảo db là null nếu kết nối thất bại
            } else {
                $this->db->set_charset("utf8mb4");
            }
        } catch (Exception $e) {
            $_SESSION['system_error'] = "Lỗi hệ thống: Không thể kết nối CSDL.";
            $this->db = null;
        }
    }

    public function getConnection()
    {
        return $this->db;
    }

    public function closeConnection()
    {
        if ($this->db && $this->db->ping()) {
            $this->db->close();
            $this->db = null;
        }
    }
}

// =========================================================
// CLASS 2: Quản lý Logic Xác thực (Admin Auth)
// =========================================================
class AdminAuth
{
    private $db;

    public function __construct($db_connection)
    {
        $this->db = $db_connection;
    }

    public function register($username, $password, $email, $fullname)
    {
        if (!$this->db) {
            return false;
        }

        $username = trim($username);
        $email = trim($email);
        $fullname = trim($fullname);

        if (empty($username) || empty($password) || empty($email) || empty($fullname)) {
            $_SESSION['register_error'] = "Vui lòng điền đầy đủ các trường.";
            return false;
        }

        try {
            // 1. Kiểm tra username đã tồn tại chưa
            $stmt_check = $this->db->prepare("SELECT username FROM admin WHERE username = ?");
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $_SESSION['register_error'] = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
                $stmt_check->close();
                return false;
            }
            $stmt_check->close();

            // 2. Thực hiện INSERT
            $role = 'admin';
            $sql = "INSERT INTO admin (username, email, password, role, fullname) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new Exception("Lỗi chuẩn bị truy vấn: " . $this->db->error);
            }

            $stmt->bind_param("sssss", $username, $email, $password, $role, $fullname);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $_SESSION['register_success'] = "Đăng ký tài khoản Admin thành công! Vui lòng đăng nhập.";
                return true;
            } else {
                $_SESSION['register_error'] = "Đăng ký thất bại. Vui lòng thử lại.";
                return false;
            }
        } catch (Exception $e) {
            $_SESSION['register_error'] = "Lỗi hệ thống: Đăng ký thất bại. (Lỗi SQL)";
            return false;
        } finally {
            if (isset($stmt)) $stmt->close();
        }
    }

    public function login($username, $password)
    {
        if (!$this->db) {
            $_SESSION['login_error'] = "Lỗi hệ thống: Không thể kết nối CSDL.";
            return false;
        }

        $username = trim($username);

        try {
            // 1. KIỂM TRA TÀI KHOẢN ADMIN
            $sql_admin = "SELECT username, fullname, password, role FROM admin WHERE username = ? LIMIT 1";
            $stmt_admin = $this->db->prepare($sql_admin);
            $stmt_admin->bind_param("s", $username);
            $stmt_admin->execute();
            $result_admin = $stmt_admin->get_result();

            if ($result_admin->num_rows === 1) {
                $user = $result_admin->fetch_assoc();
                if ($password === $user['password']) {
                    // Đăng nhập thành công
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_fullname'] = $user['fullname'];
                    $_SESSION['user_role'] = $user['role'];

                    $stmt_admin->close();
                    return true;
                }
            }

            $stmt_admin->close();

            $_SESSION['login_error'] = "Tên đăng nhập hoặc mật khẩu không đúng. Vui lòng thử lại.";
            return false;
        } catch (Exception $e) {
            $_SESSION['login_error'] = "Lỗi hệ thống trong quá trình đăng nhập.";
            return false;
        }
    }
}

// =========================================================
// PHẦN 3: LOGIC CHẠY CHÍNH (MAIN EXECUTION)
// =========================================================

// Lấy tên file hiện tại để đặt vào form action, đảm bảo nó luôn đúng
$current_file = basename(__FILE__);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Khởi tạo và kết nối CSDL
    $dbManager = new Database();
    $db = $dbManager->getConnection(); // Lấy kết nối
    $auth = new AdminAuth($db);

    // 2. Xử lý hành động POST
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'register') {
            $auth->register(
                $_POST['reg-username'],
                $_POST['reg-password'],
                $_POST['reg-email'],
                $_POST['reg-fullname']
            );
        } else if ($action === 'login') {
            if ($auth->login($_POST['username'], $_POST['password'])) {
                $dbManager->closeConnection(); // Đóng kết nối
                header("Location: chủ/qlsp.php");
                exit();
            }
        }
    }

    // 3. Đóng kết nối CSDL
    $dbManager->closeConnection();

    // 4. Chuyển hướng chính nó sau khi xử lý POST
    header("Location: " . $current_file);
    exit();
}

// =========================================================
// PHẦN 4: GIAO DIỆN HIỂN THỊ (HTML/CSS/JS)
// =========================================================

$is_register_success = isset($_SESSION['register_success']);
$is_register_error = isset($_SESSION['register_error']);
$is_system_error = isset($_SESSION['system_error']);

$show_register_form = $is_register_error && !$is_system_error;

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - Hoa Tươi Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        /* CSS Giữ nguyên */
        /* --- Định nghĩa biến màu --- */
        :root {
            --primary-pink: #e91e63;
            --light-pink: #ffd6e8;
            --accent-glow: #ff66a3;
            --text-color: #333;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text-color);
            background-image: url('https://i.pinimg.com/1200x/7c/df/a0/7cdfa06ffbb8095abe6392c5e1df19e9.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
            z-index: 1;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .container {
            background: white;
            border-radius: 25px;
            box-shadow: var(--card-shadow);
            width: 420px;
            padding: 40px;
            text-align: center;
            transition: all 0.5s ease-in-out;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .logo-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .logo-header i {
            font-size: 45px;
            color: var(--accent-glow);
            text-shadow: 0 0 8px rgba(255, 102, 163, 0.7);
            margin-bottom: 5px;
        }

        h2 {
            color: var(--primary-pink);
            font-size: 26px;
            margin: 0;
        }

        .input-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .input-group label {
            font-weight: 600;
            color: var(--primary-pink);
            display: block;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #ccc;
            font-size: 16px;
            transition: color 0.3s;
        }

        input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus {
            border-color: var(--accent-glow);
            box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.1);
            outline: none;
        }

        input:focus+i {
            color: var(--primary-pink);
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: var(--primary-pink);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
            transition: background 0.3s, transform 0.1s, box-shadow 0.3s;
            margin-top: 10px;
        }

        .btn:hover {
            background: var(--accent-glow);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(255, 102, 163, 0.5);
        }

        .btn:active {
            transform: translateY(1px);
            box-shadow: 0 3px 10px rgba(233, 30, 99, 0.5);
        }

        .switch {
            margin-top: 25px;
            font-size: 14px;
            color: #777;
        }

        .switch a {
            color: var(--primary-pink);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .switch a:hover {
            color: var(--accent-glow);
            text-decoration: underline;
        }

        #register .btn {
            margin-top: 20px;
        }

        /* Xóa #register { display: none; } vì PHP sẽ kiểm soát display inline */

        /* --- CSS cho Thông báo Lỗi/Thành công --- */
        .system-message {
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: absolute;
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            width: 420px;
            z-index: 10;
            text-align: center;
        }

        .error-message {
            background: #e74c3c;
            /* Đỏ cho lỗi */
        }

        .success-message {
            background: #2ecc71;
            /* Xanh lá cho thành công */
        }
    </style>
</head>

<body>
    <?php
    // Hiển thị thông báo (nếu có)
    if (isset($_SESSION['login_error'])) {
        echo '<p class="system-message error-message">' . $_SESSION['login_error'] . '</p>';
        unset($_SESSION['login_error']);
    }
    if (isset($_SESSION['register_error'])) {
        echo '<p class="system-message error-message">' . $_SESSION['register_error'] . '</p>';
        unset($_SESSION['register_error']);
    }
    if (isset($_SESSION['register_success'])) {
        echo '<p class="system-message success-message">' . $_SESSION['register_success'] . '</p>';
        unset($_SESSION['register_success']);
    }
    if (isset($_SESSION['system_error'])) {
        echo '<p class="system-message error-message">' . $_SESSION['system_error'] . '</p>';
        unset($_SESSION['system_error']);
    }
    ?>
    <div class="container" id="login" style="display: <?php echo $show_register_form ? 'none' : 'block'; ?>;">
        <div class="logo-header">
            <i class="fas fa-spa"></i>
            <h2>Đăng nhập Admin</h2>
        </div>

        <form action="<?php echo $current_file; ?>" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="input-group">
                <label for="username">Tên đăng nhập</label>
                <div class="input-icon">
                    <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
            <div class="input-group">
                <label for="password">Mật khẩu</label>
                <div class="input-icon">
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>
            <button type="submit" class="btn">Đăng nhập</button>
        </form>
        <div class="switch">
            Chưa có tài khoản Admin? <a href="#" onclick="showRegister()">Đăng ký ngay</a>
        </div>
    </div>

    <div class="container" id="register" style="display: <?php echo $show_register_form ? 'block' : 'none'; ?>;">
        <div class="logo-header">
            <i class="fas fa-user-plus"></i>
            <h2>Đăng ký Admin</h2>
        </div>
        <form action="<?php echo $current_file; ?>" method="POST">
            <input type="hidden" name="action" value="register">
            <div class="input-group">
                <label for="reg-username">Tên đăng nhập</label>
                <div class="input-icon">
                    <input type="text" id="reg-username" name="reg-username" placeholder="Nhập tên đăng nhập" required>
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
            <div class="input-group">
                <label for="reg-fullname">Họ và Tên</label>
                <div class="input-icon">
                    <input type="text" id="reg-fullname" name="reg-fullname" placeholder="Nhập Họ và Tên" required>
                    <i class="fa-solid fa-address-card"></i>
                </div>
            </div>
            <div class="input-group">
                <label for="reg-email">Email</label>
                <div class="input-icon">
                    <input type="email" id="reg-email" name="reg-email" placeholder="Nhập Email" required>
                    <i class="fa-solid fa-envelope"></i>
                </div>
            </div>
            <div class="input-group">
                <label for="reg-password">Mật khẩu</label>
                <div class="input-icon">
                    <input type="password" id="reg-password" name="reg-password" placeholder="Nhập mật khẩu" required>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>
            <button type="submit" class="btn">Đăng ký</button>
        </form>
        <div class="switch">
            Đã có tài khoản? <a href="#" onclick="showLogin()">Đăng nhập</a>
        </div>
    </div>

    <script>
        function showRegister() {
            document.getElementById('login').style.display = 'none';
            document.getElementById('register').style.display = 'block';
        }

        function showLogin() {
            document.getElementById('register').style.display = 'none';
            document.getElementById('login').style.display = 'block';
        }

        // Sau khi tải trang, nếu có lỗi đăng ký, hiển thị form đăng ký
        if (<?php echo $show_register_form ? 'true' : 'false'; ?>) {
            showRegister();
        } else {
            showLogin();
        }
    </script>
</body>

</html>