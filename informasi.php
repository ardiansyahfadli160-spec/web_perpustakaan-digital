<?php
session_start();
include 'config.php';

// Tangani pencarian
$search = "";
if(isset($_GET['search'])){
    $search = htmlspecialchars($_GET['search']);
    $stmt = $conn->prepare("SELECT * FROM buku WHERE judul LIKE ? OR penulis LIKE ? ORDER BY id DESC");
    if(!$stmt){
        die("Prepare failed: ".$conn->error);
    }
    $likeSearch = "%".$search."%";
    $stmt->bind_param("ss", $likeSearch, $likeSearch);
} else {
    $stmt = $conn->prepare("SELECT * FROM buku ORDER BY id DESC");
    if(!$stmt){
        die("Prepare failed: ".$conn->error);
    }
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Informasi Perpustakaan</title>
<link rel="stylesheet" href="style.css">
<style>
body{font-family:Arial; background:#0b0f24; color:#fff; padding:20px;}
.container{background:#1e253f; padding:30px; border-radius:12px; max-width:900px; margin:0 auto; box-shadow:0 0 15px #000;}
h1{color:#00bfff; margin-bottom:20px;}
.search-box{margin-bottom:20px;}
.search-box input[type=text]{padding:8px; width:70%; border-radius:6px; border:none;}
.search-box input[type=submit]{padding:8px 15px; border:none; border-radius:6px; background:#00bfff; color:#000; cursor:pointer;}
.search-box input[type=submit]:hover{background:#1e90ff;}
table{width:100%; border-collapse:collapse;}
table, th, td{border:1px solid #555;}
th, td{padding:10px; text-align:left;}
th{background:#00bfff; color:#000;}
tr:nth-child(even){background:#2a3150;}
.back-button{display:inline-block; margin-top:20px; padding:10px 15px; background-color:#00bfff; color:#000; text-decoration:none; border-radius:6px;}
.back-button:hover{background-color:#1e90ff;}
</style>
</head>
<body>
<div class="container">
<h1>Informasi Perpustakaan</h1>

<form method="get" class="search-box">
    <input type="text" name="search" placeholder="Cari judul atau penulis..." value="<?= $search ?>">
    <input type="submit" value="Cari">
</form>

<?php if($result->num_rows > 0): ?>
<table>
    <tr>
        <th>No</th>
        <th>Judul</th>
        <th>Penulis</th>
        <th>Tahun</th>
        <th>Aksi</th>
    </tr>
    <?php $no=1; while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($row['judul']) ?></td>
        <td><?= htmlspecialchars($row['penulis']) ?></td>
        <td><?= htmlspecialchars($row['tahun']) ?></td>
        <td>
            <?php if(!empty($row['file']) && file_exists($row['file'])): ?>
                <a href="<?= htmlspecialchars($row['file']) ?>" download style="color:#00bfff;">Download</a>
            <?php else: ?>
                -
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>Tidak ada buku ditemukan.</p>
<?php endif; ?>

<a href="beranda.php" class="back-button">← Kembali ke Beranda</a>
</div>
</body>
</html>
