<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);  // cek apakah user sudah login
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}
