<?php
session_start();
include 'config.php';

/* ================= AMBIL USER VALID (ANTI FK ERROR) ================= */
// Ambil 1 user yang pasti ada
$user_q = $conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
$user   = $user_q->fetch_assoc();

if (!$user) {
    die("Tabel users kosong. Tambahkan user dulu.");
}

$user_id = $user['id'];

/* ================= HAPUS FAVORIT ================= */
if (isset($_GET['hapus'])) {
    $materi_id = intval($_GET['hapus']);

    $stmt = $conn->prepare(
        "DELETE FROM favorit WHERE user_id = ? AND materi_id = ?"
    );
    $stmt->bind_param("ii", $user_id, $materi_id);
    $stmt->execute();

    header("Location: favorit.php");
    exit();
}

/* ================= AMBIL DATA FAVORIT ================= */
$stmt = $conn->prepare("
    SELECT m.id, m.judul
    FROM materi m
    INNER JOIN favorit f ON m.id = f.materi_id
    WHERE f.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Materi Favorit</title>
<style>
body{
    font-family:Arial;
    background:#0b0f24;
    color:#fff;
    padding:20px;
}
.container{
    background:#1e253f;
    padding:30px;
    border-radius:12px;
    max-width:800px;
    margin:auto;
}
h1{color:#00bfff;}
.materi-item{
    display:flex;
    justify-content:space-between;
    padding:8px 0;
    border-bottom:1px solid #334155;
}
a{color:#00bfff;text-decoration:none;}
.hapus{
    background:#ff4d4d;
    padding:4px 8px;
    border-radius:4px;
    color:#fff;
}
.hapus:hover{background:#ff1a1a;}
.back{
    display:inline-block;
    margin-top:20px;
    padding:10px 15px;
    background:#00bfff;
    color:#000;
    border-radius:6px;
}
</style>
</head>
<body>

<div class="container">
<h1>Materi Favorit ⭐</h1>

<?php if ($result->num_rows == 0): ?>
    <p>Belum ada materi favorit.</p>
<?php else: ?>
    <?php while($row = $result->fetch_assoc()): ?>
        <div class="materi-item">
            <a href="materi.php?id=<?= $row['id']; ?>">
                <?= htmlspecialchars($row['judul']); ?>
            </a>
            <a class="hapus"
               href="?hapus=<?= $row['id']; ?>"
               onclick="return confirm('Hapus dari favorit?')">
               ❌
            </a>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<a href="beranda.php" class="back">← Kembali</a>
</div>

</body>
</html>
