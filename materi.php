<?php
session_start();
include 'config.php';

/* ================= USER VALID (ANTI FK ERROR) ================= */
// ambil user yang pasti ada di tabel users
$user_q = $conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
$user   = $user_q->fetch_assoc();

if (!$user) {
    die("Tabel users kosong. Tambahkan user terlebih dahulu.");
}

$user_id = (int)$user['id'];

/* ================= CEK ID MATERI ================= */
if (!isset($_GET['id'])) {
    die("ID materi tidak ditemukan.");
}

$id = intval($_GET['id']);

/* ================= AMBIL DATA MATERI ================= */
$stmt = $conn->prepare("SELECT * FROM materi WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Materi tidak ditemukan.");
}

$data = $result->fetch_assoc();

/* ================= HISTORY ================= */
if (!isset($_SESSION['history'])) $_SESSION['history'] = [];
if (!in_array($id, $_SESSION['history'])) $_SESSION['history'][] = $id;

/* ================= FAVORIT ================= */
$is_favorit = false;

$stmt_check = $conn->prepare(
    "SELECT 1 FROM favorit WHERE user_id=? AND materi_id=?"
);
$stmt_check->bind_param("ii", $user_id, $id);
$stmt_check->execute();
$res_check = $stmt_check->get_result();

if ($res_check->num_rows > 0) {
    $is_favorit = true;
}

if (isset($_POST['submit_fav'])) {

    if ($is_favorit) {
        $stmt_del = $conn->prepare(
            "DELETE FROM favorit WHERE user_id=? AND materi_id=?"
        );
        $stmt_del->bind_param("ii", $user_id, $id);
        $stmt_del->execute();

        $fav_msg = "Materi dihapus dari favorit.";
        $is_favorit = false;

    } else {
        $stmt_add = $conn->prepare(
            "INSERT INTO favorit (user_id, materi_id) VALUES (?, ?)"
        );
        $stmt_add->bind_param("ii", $user_id, $id);
        $stmt_add->execute();

        $fav_msg = "Materi ditambahkan ke favorit!";
        $is_favorit = true;
    }
}

/* ================= KOMENTAR ================= */
if (isset($_POST['submit_komentar'])) {
    $user_name = trim($_POST['user_name']);
    $komentar  = trim($_POST['komentar']);

    if ($user_name && $komentar) {
        $stmt_kom = $conn->prepare(
            "INSERT INTO komentar (materi_id, user_name, komentar, created_at)
             VALUES (?, ?, ?, NOW())"
        );
        $stmt_kom->bind_param("iss", $id, $user_name, $komentar);
        $stmt_kom->execute();

        $success_msg = "Komentar berhasil dikirim!";
    } else {
        $error_msg = "Nama dan komentar tidak boleh kosong.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($data['judul']) ?></title>
<style>
body{font-family:Arial;background:#0b0f24;color:#fff;padding:20px}
.container{background:#1e253f;padding:30px;border-radius:12px;max-width:800px;margin:auto}
h1{color:#00bfff}
.fav-button,.back-button{
    padding:10px 15px;
    background:#00bfff;
    color:#000;
    border:none;
    border-radius:6px;
    cursor:pointer
}
.fav-button:hover,.back-button:hover{background:#1e90ff}
input,textarea{width:100%;padding:8px;margin-bottom:10px;border-radius:6px;border:none}
.message{color:lightgreen}
.error{color:#ff6b6b}
</style>
</head>
<body>

<div class="container">
<h1><?= htmlspecialchars($data['judul']) ?></h1>
<p><strong>Kelas:</strong> <?= htmlspecialchars($data['kelas']) ?></p>
<hr>

<?php
if (!empty($data['file']) && file_exists($data['file'])) {
    $ext = strtolower(pathinfo($data['file'], PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        echo '<iframe src="'.htmlspecialchars($data['file']).'" width="100%" height="600"></iframe>';
    } else {
        echo '<a href="'.htmlspecialchars($data['file']).'" download>Unduh File</a>';
    }
} else {
    echo '<div>'.nl2br(htmlspecialchars($data['materi'])).'</div>';
}
?>

<form method="post" style="margin-top:15px">
    <button type="submit" name="submit_fav" class="fav-button">
        <?= $is_favorit ? '★ Hapus dari Favorit' : '☆ Tambah Favorit'; ?>
    </button>
</form>

<?php if (isset($fav_msg)) echo "<p class='message'>$fav_msg</p>"; ?>

<hr>

<h3>Tulis Komentar</h3>
<?php
if (isset($success_msg)) echo "<p class='message'>$success_msg</p>";
if (isset($error_msg)) echo "<p class='error'>$error_msg</p>";
?>
<form method="post">
    <input type="text" name="user_name" placeholder="Nama" required>
    <textarea name="komentar" placeholder="Tulis komentar..." required></textarea>
    <button type="submit" name="submit_komentar" class="fav-button">Kirim</button>
</form>

<br>
<a href="beranda.php" class="back-button">← Kembali</a>
</div>

</body>
</html>
