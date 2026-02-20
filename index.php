<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$username = $_SESSION['username'];

// --- PROSES UBAH PASSWORD TANPA MINIMAL PANJANG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_password'])) {
    $password_lama = trim($_POST['password_lama'] ?? '');
    $password_baru = trim($_POST['password_baru'] ?? '');
    $konfirmasi_password = trim($_POST['konfirmasi_password'] ?? '');

    // Ambil password lama dari DB (plain text)
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if (!$row) {
        $error_password = "User tidak ditemukan.";
    } elseif ($password_lama !== trim($row['password'])) {
        $error_password = "Password lama salah.";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error_password = "Konfirmasi password tidak cocok.";
    } else {
        // Update password baru (plain text)
        $stmtUpdate = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmtUpdate->bind_param("ss", $password_baru, $username);
        if ($stmtUpdate->execute()) {
            $success_password = "Password berhasil diubah.";
        } else {
            $error_password = "Terjadi kesalahan saat menyimpan password baru.";
        }
    }
}

// --- PROSES UPLOAD FOTO PROFIL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_profil'])) {
    $file = $_FILES['foto_profil'];

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($file['type'], $allowedTypes) && $file['size'] <= 2 * 1024 * 1024) { // max 2MB
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = $username . '_' . time() . '.' . $ext;
        $uploadDir = 'uploads/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadPath = $uploadDir . $newName;

        // Ambil foto lama agar bisa dihapus (optional)
        $stmtOldFoto = $conn->prepare("SELECT foto_profil FROM users WHERE username = ?");
        $stmtOldFoto->bind_param("s", $username);
        $stmtOldFoto->execute();
        $resOldFoto = $stmtOldFoto->get_result();
        $oldFoto = $resOldFoto->fetch_assoc()['foto_profil'] ?? null;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Update foto di database
            $stmtUpdate = $conn->prepare("UPDATE users SET foto_profil = ? WHERE username = ?");
            $stmtUpdate->bind_param("ss", $newName, $username);
            $stmtUpdate->execute();

            // Hapus file foto lama (kecuali default.png)
            if ($oldFoto && $oldFoto !== 'default.png' && file_exists($uploadDir . $oldFoto)) {
                unlink($uploadDir . $oldFoto);
            }

            // Refresh agar foto terbaru muncul
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $error_foto = "Gagal mengupload foto profil.";
        }
    } else {
        $error_foto = "File harus berupa gambar (jpg/png/gif) dan maksimal 2MB.";
    }
}

// --- AMBIL DATA USER (foto profil) ---
$stmtUser = $conn->prepare("SELECT foto_profil FROM users WHERE username = ?");
$stmtUser->bind_param("s", $username);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$userData = $resultUser->fetch_assoc();
$fotoProfil = $userData['foto_profil'] ?? 'default.png';

// --- PROSES PENCARIAN MATERI ---
$keyword = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';
$result = null;

if (!empty($keyword) || !empty($kelas)) {
    $sql = "SELECT * FROM materi WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($keyword)) {
        $sql .= " AND judul LIKE ?";
        $params[] = '%' . $keyword . '%';
        $types .= "s";
    }
    if (!empty($kelas)) {
        $sql .= " AND kelas = ?";
        $params[] = $kelas;
        $types .= "s";
    }

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
}

// --- HAPUS HISTORY ---
if (isset($_GET['hapus_history_id'])) {
    $hapus_id = $_GET['hapus_history_id'];
    if (isset($_SESSION['history']) && ($key = array_search($hapus_id, $_SESSION['history'])) !== false) {
        unset($_SESSION['history'][$key]);
        $_SESSION['history'] = array_values($_SESSION['history']); // reindex
    }
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); // redirect tanpa query
    exit();
}

if (isset($_GET['hapus_semua_history'])) {
    unset($_SESSION['history']);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Beranda Materi</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to bottom right, #1f1c2c, #928dab);
            margin: 0;
            padding: 0;
            color: #fff;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            padding: 30px;
        }
        h1 {
            text-align: center;
            color: #00f0ff;
        }
        form {
            text-align: center;
            margin-bottom: 20px;
        }
        input[type="text"], select, input[type="password"] {
            padding: 10px;
            border-radius: 8px;
            border: none;
            margin: 5px;
            width: 40%;
            max-width: 300px;
        }
        input[type="submit"], button {
            padding: 10px 15px;
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 8px;
            cursor: pointer;
        }
        .materi {
            background-color: #2e2e4d;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .materi a {
            color: #00f0ff;
            text-decoration: none;
            font-weight: bold;
        }
        .perpus-info {
            background-color: #2e2e4d;
            padding: 20px;
            border-radius: 10px;
            margin-top: 40px;
        }
        .perpus-info h2 {
            color: #00f0ff;
            margin-bottom: 15px;
        }
        .perpus-info p {
            margin: 5px 0;
        }
        .history-section {
            margin-top: 40px;
        }
        .history-section h2 {
            color: #00f0ff;
            margin-bottom: 15px;
        }
        .history-section .materi {
            background-color: #444;
        }
        .history-section .materi a {
            font-size: 16px;
        }
        .delete-btn {
            color: red;
            font-size: 14px;
            text-decoration: none;
            margin-left: 10px;
        }
        .delete-all {
            color: red;
            font-size: 16px;
            display: block;
            margin-top: 20px;
        }

        /* Profil kanan atas */
        #profilDropdown {
            position: fixed;
            top: 20px;
            right: 30px;
            color: white;
            z-index: 1000;
            user-select: none;
            width: 220px;
        }
        #profilDropdown > div:first-child {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #profilDropdown img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            vertical-align: middle;
        }
        #profilDropdown span.username {
            flex-grow: 1;
            font-weight: bold;
        }
        #dropdownMenu {
            display: none;
            background: #2e2e4d;
            padding: 10px;
            border-radius: 8px;
            margin-top: 5px;
            text-align: left;
            width: 100%;
            box-sizing: border-box;
        }
        #dropdownMenu label {
            cursor: pointer;
            display: block;
            margin-bottom: 10px;
            color: #00f0ff;
        }
        #dropdownMenu input[type="file"] {
            display: none;
        }
        #dropdownMenu a {
            color: #f33;
            text-decoration: none;
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }
        #dropdownMenu a:hover {
            text-decoration: underline;
        }
        #dropdownMenu form {
            margin-bottom: 10px;
        }
        #dropdownMenu input[type="password"] {
            width: 100%;
            margin-bottom: 10px;
            padding: 5px;
            border-radius: 5px;
            border: none;
        }
        #dropdownMenu button {
            width: 100%;
            padding: 7px 0;
            border-radius: 5px;
            border: none;
            background: #007bff;
            color: white;
            cursor: pointer;
        }
        #dropdownMenu p {
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .error-msg {
            color: #f33;
            font-size: 14px;
            margin-top: 5px;
        }
        .success-msg {
            color: #3f3;
            font-size: 14px;
            margin-top: 5px;
        }

        /* Hamburger Menu Kiri Atas */
        #menuHamburger {
            position: fixed;
            top: 20px;
            left: 30px;
            z-index: 1000;
            user-select: none;
        }
        .hamburger-icon {
            font-size: 30px;
            cursor: pointer;
            color: #00f0ff;
        }
        #sideMenu {
            display: none;
            position: fixed;
            top: 60px;
            left: 30px;
            background-color: #2e2e4d;
            border-radius: 8px;
            padding: 10px;
            width: 150px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            flex-direction: column;
        }
        #sideMenu a {
            display: block;
            padding: 8px 0;
            color: #00f0ff;
            text-decoration: none;
            font-weight: bold;
        }
        #sideMenu a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<!-- Menu Hamburger Kiri Atas -->
<div id="menuHamburger">
    <div class="hamburger-icon" onclick="toggleMenu()">&#9776;</div>
    <div id="sideMenu">
        <a href="beranda.php">Beranda</a>
        <a href="favorit.php">Favorit</a>
    </div>
</div>

<div id="profilDropdown">
    <div onclick="toggleDropdown()">
        <img src="uploads/<?= htmlspecialchars($fotoProfil) ?>" alt="Foto Profil">
        <span class="username"><?= htmlspecialchars($username) ?></span>
        <span style="font-weight:bold;">&#9662;</span>
    </div>
    <div id="dropdownMenu">
        <!-- Form Ubah Foto Profil -->
        <form action="" method="POST" enctype="multipart/form-data" style="margin-bottom: 15px;">
            <label>
                Ubah Foto Profil
                <input type="file" name="foto_profil" onchange="this.form.submit()" accept="image/*" />
            </label>
            <?php if (isset($error_foto)): ?>
                <p class="error-msg"><?= htmlspecialchars($error_foto) ?></p>
            <?php endif; ?>
        </form>

        <!-- Form Ubah Password -->
        <form action="" method="POST" style="color:#00f0ff;">
            <input type="hidden" name="ubah_password" value="1">
            <label>Password Lama:</label>
            <input type="password" name="password_lama" required>

            <label>Password Baru:</label>
            <input type="password" name="password_baru" required>

            <label>Konfirmasi Password Baru:</label>
            <input type="password" name="konfirmasi_password" required>

            <button type="submit">Ubah Password</button>
        </form>

        <!-- Pesan error/sukses ubah password -->
        <?php if (isset($error_password)): ?>
            <p class="error-msg"><?= htmlspecialchars($error_password) ?></p>
        <?php elseif (isset($success_password)): ?>
            <p class="success-msg"><?= htmlspecialchars($success_password) ?></p>
        <?php endif; ?>

        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <h1>Daftar Materi Pelajaran</h1>

    <!-- Fitur pencarian -->
    <form method="GET" action="">
        <input type="text" name="cari" placeholder="Cari berdasarkan judul..." value="<?= htmlspecialchars($keyword) ?>">
        <select name="kelas">
            <option value="">Semua Kelas</option>
            <option value="10" <?= $kelas === '10' ? 'selected' : '' ?>>Kelas 10</option>
            <option value="11" <?= $kelas === '11' ? 'selected' : '' ?>>Kelas 11</option>
            <option value="12" <?= $kelas === '12' ? 'selected' : '' ?>>Kelas 12</option>
        </select>
        <input type="submit" value="Cari">
    </form>

    <!-- Hasil pencarian -->
    <?php if (!empty($keyword) || !empty($kelas)): ?>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="materi">
                    <a href="materi.php?id=<?= $row['id'] ?>">
                        <?= htmlspecialchars($row['judul']) ?>
                    </a>
                    <p>Kelas: <?= htmlspecialchars($row['kelas']) ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Tidak ada materi ditemukan.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Silakan gunakan fitur pencarian untuk menemukan materi.</p>
    <?php endif; ?>

    <!-- Menampilkan History dengan Link -->
    <div class="history-section">
        <h2>Riwayat Materi yang Dibaca</h2>
        <?php if (isset($_SESSION['history']) && count($_SESSION['history']) > 0): ?>
            <div class="materi-list">
                <?php
                foreach ($_SESSION['history'] as $history_id):
                    $sql_history = "SELECT id, judul FROM materi WHERE id = ?";
                    $stmt_hist = $conn->prepare($sql_history);
                    $stmt_hist->bind_param("i", $history_id);
                    $stmt_hist->execute();
                    $res_hist = $stmt_hist->get_result();
                    if ($hist_materi = $res_hist->fetch_assoc()):
                ?>
                    <div class="materi">
                        <a href="materi.php?id=<?= $hist_materi['id'] ?>"><?= htmlspecialchars($hist_materi['judul']) ?></a>
                        <a class="delete-btn" href="?hapus_history_id=<?= $hist_materi['id'] ?>" title="Hapus dari history" onclick="return confirm('Hapus materi ini dari history?')">[x]</a>
                    </div>
                <?php
                    endif;
                endforeach;
                ?>
                <a href="?hapus_semua_history=1" class="delete-all" onclick="return confirm('Hapus semua history?')">Hapus Semua Riwayat</a>
            </div>
        <?php else: ?>
            <p>Belum ada materi yang dibaca.</p>
        <?php endif; ?>
    </div>

    <!-- Info Perpustakaan -->
    <div class="perpus-info" id="perpus-info">
        <h2>Info Perpustakaan</h2>
        <p>Lokasi: Perpustakaan SMK WAHIDIN KOTA CIREBON </p>
        <p>Jam buka: Senin - Jumat, 07.00 - 16.00 WIB</p>
        <p>Kontak: (0352) 495-xxx</p>
    </div>
</div>

<script>
    function toggleDropdown() {
        const menu = document.getElementById('dropdownMenu');
        if (menu.style.display === 'block') {
            menu.style.display = 'none';
        } else {
            menu.style.display = 'block';
        }
    }

    // Klik di luar dropdown untuk menutup
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('profilDropdown');
        if (!dropdown.contains(event.target)) {
            document.getElementById('dropdownMenu').style.display = 'none';
        }
    });

    // Hamburger Menu
    function toggleMenu() {
        const menu = document.getElementById('sideMenu');
        if (menu.style.display === 'block') {
            menu.style.display = 'none';
        } else {
            menu.style.display = 'block';
        }
    }

    // Klik di luar menu untuk menutup
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('menuHamburger');
        if (!menu.contains(event.target)) {
            document.getElementById('sideMenu').style.display = 'none';
        }
    });
</script>

</body>
</html>

