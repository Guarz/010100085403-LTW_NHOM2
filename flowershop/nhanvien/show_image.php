<?php
include '../connect.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT Anh FROM sanpham WHERE MaSP = $id";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        header("Content-Type: image/jpeg");
        echo $row['Anh'];
    }
}
