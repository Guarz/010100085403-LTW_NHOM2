<?php
$pass_nv = '123456';
$pass_admin = 'adminpass';

$hash_nv = password_hash($pass_nv, PASSWORD_DEFAULT);
$hash_admin = password_hash($pass_admin, PASSWORD_DEFAULT);

echo "Mã băm Nhân viên ('$pass_nv'): \n<pre>$hash_nv</pre>\n\n";
echo "Mã băm Quản lý ('$pass_admin'): \n<pre>$hash_admin</pre>";
