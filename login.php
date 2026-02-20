<?php
session_start();
include('config.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {

        $stmt = $conn->prepare(
            "SELECT id, username, password, role 
             FROM users 
             WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // Jika password belum di-hash
            if ($password === $user['password']) {

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Arahkan sesuai ROLE dari database
                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin.php");
                        break;
                    case 'editor':
                        header("Location: editor.php");
                        break;
                    default:
                        header("Location: beranda.php");
                        break;
                }
                exit;

            } else {
                $error = "Password salah!";
            }

        } else {
            $error = "Username tidak ditemukan!";
        }

        $stmt->close();

    } else {
        $error = "Username dan password wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - ML Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Orbitron', sans-serif; }
        body {
            background: linear-gradient(135deg, #0d1b2a, #1a2238);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }
        .container {
            background: rgba(20, 25, 45, 0.95);
            padding: 40px 30px;
            border: 2px solid #4cc9f0;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 0 25px rgba(0,255,255,0.3);
        }
        h2 {
            text-align: center;
            color: #ffdd57;
            margin-bottom: 30px;
            text-shadow: 0 0 10px #ffc300;
        }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 0.95em; }
        input {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            background: #1f2b3a;
            border: 2px solid #3a506b;
            border-radius: 10px;
            color: #fff;
            font-size: 1em;
        }
        input:focus {
            border-color: #4cc9f0;
            outline: none;
            box-shadow: 0 0 10px #4cc9f0;
        }
        button {
            margin-top: 25px;
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #4361ee, #4cc9f0);
            border: none;
            color: white;
            font-size: 1.1em;
            font-weight: bold;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 0 10px #4cc9f0;
            transition: 0.3s;
        }
        button:hover {
            background: linear-gradient(90deg, #4cc9f0, #4361ee);
            box-shadow: 0 0 20px #4cc9f0;
        }
        .error {
            margin-top: 15px;
            background: #ef233c;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            font-size: 0.9em;
        }
        .register {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9em;
        }
        .register a {
            color: #00f5d4;
            text-decoration: none;
        }
        .register a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>Login Akun</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Masuk</button>
    </form>

    <div class="register">
        Belum punya akun? <a href="registrasi.php">Daftar sekarang</a>
    </div>
</div>
</body>
</html>
