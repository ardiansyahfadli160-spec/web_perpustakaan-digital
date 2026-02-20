<?php
session_start();

if (!isset($_POST['judul'])) {
    exit();
}

$judul = trim($_POST['judul']);

if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

if (!in_array($judul, $_SESSION['history'])) {
    $_SESSION['history'][] = $judul;
}
?>
