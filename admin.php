<?php
session_start();
include('config.php');

/* ========= LOGOUT ========= */
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

/* ========= CEK ADMIN ========= */
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$edit = false;
$id = $judul = $penulis = $materi = $file = $kelas = "";
$error = "";

/* ========= TAMBAH / EDIT MATERI ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul   = trim($_POST['judul']);
    $penulis = trim($_POST['penulis']);
    $materi  = trim($_POST['materi']);
    $kelas   = intval($_POST['kelas']);
    $id      = isset($_POST['id']) ? intval($_POST['id']) : null;

    if (!empty($_FILES['file_materi']['name'])) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $filename = time() . "_" . basename($_FILES['file_materi']['name']);
        $target   = $upload_dir . $filename;
        $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed  = ['pdf','doc','docx','txt'];

        if (in_array($ext, $allowed)) {
            move_uploaded_file($_FILES['file_materi']['tmp_name'], $target);
            $file = $target;
        } else {
            $error = "Format file tidak diizinkan!";
        }
    }

    if ($judul && $penulis && $kelas && ($materi || $file) && !$error) {
        if ($id) {
            $stmt = $conn->prepare(
                "UPDATE materi SET judul=?, penulis=?, materi=?, kelas=?, file=? WHERE id=?"
            );
            $stmt->bind_param("ssssis", $judul, $penulis, $materi, $kelas, $file, $id);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO materi (judul, penulis, materi, kelas, file)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssis", $judul, $penulis, $materi, $kelas, $file);
        }
        $stmt->execute();
        header("Location: admin.php");
        exit();
    } elseif (!$error) {
        $error = "Judul, Penulis, Kelas, dan Materi/File wajib diisi!";
    }
}

/* ========= EDIT ========= */
if (isset($_GET['edit'])) {
    $edit = true;
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM materi WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    extract($stmt->get_result()->fetch_assoc());
}

/* ========= HAPUS MATERI ========= */
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM materi WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    header("Location: admin.php");
    exit();
}

/* ========= HAPUS KOMENTAR ========= */
if (isset($_GET['hapus_kom'])) {
    $id = intval($_GET['hapus_kom']);
    $stmt = $conn->prepare("DELETE FROM komentar WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    header("Location: admin.php");
    exit();
}

/* ========= DATA ========= */
$materi_result   = $conn->query("SELECT * FROM materi ORDER BY kelas, judul");
$komentar_result = $conn->query("SELECT * FROM komentar ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin</title>
<style>
body{background:#0f172a;color:white;font-family:Arial;padding:20px;}
h2,h3{color:#38bdf8;}
form,table{background:#1e293b;padding:20px;border-radius:10px;margin-bottom:25px;}
input,textarea,select{width:100%;padding:10px;margin:8px 0;background:#0f172a;color:white;border:1px solid #334155;border-radius:6px;}
button{background:#38bdf8;color:black;padding:10px 20px;border:none;border-radius:6px;font-weight:bold;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #334155;padding:10px;}
a{color:#38bdf8;text-decoration:none;}
.logout{background:#ef4444;color:white;padding:8px 14px;border-radius:6px;font-weight:bold;}
</style>
</head>
<body>

<div style="text-align:right;">
    <a href="?logout=1" class="logout"
       onclick="return confirm('Logout?')">Logout</a>
</div>

<h2>Panel Admin</h2>
<?php if($error): ?><p style="color:red"><?= $error ?></p><?php endif; ?>

<!-- FORM MATERI -->
<form method="post" enctype="multipart/form-data">
<?php if($edit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

<label>Judul</label>
<input type="text" name="judul" value="<?= htmlspecialchars($judul) ?>">

<label>Penulis</label>
<input type="text" name="penulis" value="<?= htmlspecialchars($penulis) ?>">

<label>Kelas</label>
<select name="kelas">
<option value="">-- Pilih Kelas --</option>
<?php for($i=10;$i<=12;$i++): ?>
<option value="<?= $i ?>" <?= $kelas==$i?'selected':'' ?>>Kelas <?= $i ?></option>
<?php endfor; ?>
</select>

<label>Isi Materi</label>
<textarea name="materi"><?= htmlspecialchars($materi) ?></textarea>

<label>Upload File</label>
<input type="file" name="file_materi">

<button type="submit"><?= $edit?'Update':'Tambah' ?></button>
</form>

<!-- DAFTAR MATERI -->
<h3>Daftar Materi</h3>
<table>
<tr><th>Judul</th><th>Kelas</th><th>Aksi</th></tr>
<?php while($m=$materi_result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($m['judul']) ?></td>
<td><?= $m['kelas'] ?></td>
<td>
<a href="?edit=<?= $m['id'] ?>">Edit</a> |
<a href="?hapus=<?= $m['id'] ?>"
   onclick="return confirm('Hapus materi?')">Hapus</a>
</td>
</tr>
<?php endwhile; ?>
</table>

<!-- KOMENTAR USER -->
<h3>Komentar User</h3>
<table>
<tr><th>User</th><th>Komentar</th><th>Tanggal</th><th>Aksi</th></tr>
<?php while($k=$komentar_result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($k['user_name']) ?></td>
<td><?= nl2br(htmlspecialchars($k['komentar'])) ?></td>
<td><?= $k['created_at'] ?></td>
<td>
<a href="?hapus_kom=<?= $k['id'] ?>"
   onclick="return confirm('Hapus komentar?')">Hapus</a>
</td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
