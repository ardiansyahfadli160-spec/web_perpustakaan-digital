<?php
session_start();
include('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (!in_array($role, ['user', 'admin'])) {
        $role = 'user';
    }

    if ($username && $email && $password_raw) {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "Username atau email sudah terdaftar.";
        } else {
            $password_plain = $password_raw;

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                die("Prepare statement gagal: " . $conn->error);
            }

            $stmt->bind_param("ssss", $username, $email, $password_plain, $role);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Registrasi berhasil! Silakan login.";
                header('Location: login.php');
                exit();
            } else {
                $error = "Gagal menyimpan data: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    } else {
        $error = "Semua field wajib diisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registrasi Pengguna</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Orbitron', sans-serif;
            background: radial-gradient(circle, #0a0f2c, #000000 80%);
            color: #fff;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: linear-gradient(145deg, #1f2a6d, #0e1736);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 20px #00aaff, 0 0 40px #001f3f inset;
            width: 100%;
            max-width: 420px;
            border: 1px solid #0077ff;
        }

        h2 {
            text-align: center;
            color: #ffe600;
            text-shadow: 0 0 10px #ffe600;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #cce6ff;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            background: #0b0f26;
            border: 1px solid #00aaff;
            color: #ffffff;
            border-radius: 8px;
            font-size: 1rem;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #ffe600;
            box-shadow: 0 0 10px #ffe600;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #00aaff, #0077ff);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 0 10px #00aaff;
            transition: background 0.3s ease;
        }

        button:hover {
            background: linear-gradient(to right, #ffe600, #ff9900);
            box-shadow: 0 0 15px #ffe600;
        }

        .error, .success {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .error {
            background: #5a0000;
            border-left: 4px solid #ff4444;
            color: #ffaaaa;
        }

        .success {
            background: #004d00;
            border-left: 4px solid #00ff00;
            color: #aaffaa;
        }

        p {
            text-align: center;
            margin-top: 15px;
            color: #ddd;
        }

        p a {
            color: #00aaff;
            text-decoration: none;
        }

        p a:hover {
            text-decoration: underline;
            color: #ffe600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registrasi Pengguna</h2>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

           
            </select>

            <button type="submit">Daftar</button>
        </form>

        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</body>
</html>
