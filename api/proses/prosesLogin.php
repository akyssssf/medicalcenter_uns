<?php
session_start();
include __DIR__ . '/../server/koneksi.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "POST data: <pre>"; print_r($_POST); echo "</pre>";

$identitas = trim($_POST['identitas'] ?? '');
$password  = $_POST['password'] ?? '';

echo "Identitas: $identitas <br>";
echo "Password length: " . strlen($password) . "<br>";

$stmt = mysqli_prepare($koneksi,
    "SELECT id, nik, nama, password, role FROM users WHERE nik = ? OR no_hp = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "ss", $identitas, $identitas);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

echo "Rows found: " . mysqli_num_rows($result) . "<br>";

if (mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);
    echo "User found: " . $user['nama'] . "<br>";
    echo "Password verify: " . (password_verify($password, $user['password']) ? 'TRUE' : 'FALSE') . "<br>";
} else {
    echo "User NOT found!<br>";
}