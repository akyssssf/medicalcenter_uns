<?php
session_start();

if (isset($_GET['set'])) {
    $_SESSION['test'] = 'hello';
    echo "Session SET: " . $_SESSION['test'];
} else {
    echo "Session value: " . ($_SESSION['test'] ?? 'KOSONG');
}
echo "<br>Session ID: " . session_id();
?>