<?php
// File: admin/login.php
session_start();
require_once "../config/koneksi.php"; // Gunakan require_once
require_once "session.php"; // <--- TAMBAHKAN BARIS INI

// Jika sudah login, redirect ke index admin
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header('location: index.php');
    exit;
}

$error_message = '';

// Proses login jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loginbtn'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password harus diisi.';
    } else {
        // Gunakan prepared statement untuk keamanan
        $stmt = $con->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // Verifikasi password
                if (password_verify($password, $user['password'])) {
                    // Login sukses
                    session_regenerate_id(true); // Regenerasi ID sesi setelah login
                    $_SESSION['login'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    // $_SESSION['role'] = $user['role']; // Jika ada role
                    unset($_SESSION['csrf_token']); // Reset CSRF token on login
                    generate_csrf_token(); // Generate new token <-- Sekarang fungsi ini dikenal

                    // Redirect ke halaman admin
                    header('Location: index.php');
                    exit;
                } else {
                    // Password salah
                    $error_message = 'Password yang Anda masukkan salah.';
                }
            } else {
                // Username tidak ditemukan
                $error_message = 'Username tidak ditemukan.';
            }
            $stmt->close();
        } else {
            // Error saat menyiapkan statement
            log_error("Login Prepare Statement Error: " . $con->error);
            $error_message = 'Terjadi kesalahan pada sistem. Silakan coba lagi nanti.';
        }
    }
}

mysqli_close($con); // Tutup koneksi
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin | Sedaya Furniture</title>
    <link rel="icon" href="../images/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* CSS login tetap sama */
         body {
            background: linear-gradient(135deg, #6dd5ed, #2193b0);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
            padding: 40px;
            text-align: center;
            width: 100%;
            max-width: 420px;
        }
        .login-container h4 {
            color: #343a40;
            margin-bottom: 30px;
            font-size: 1.8em;
            font-weight: 600;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
            text-align: left;
        }
        .input-group {
             margin-bottom: 1.5rem;
        }
        .input-group .form-control {
             margin-bottom: 0;
        }
        .form-control {
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
             height: calc(1.5em + 1rem + 2px);
        }
         .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .input-group-text {
             background-color: #e9ecef;
             border: 1px solid #ced4da;
             border-right: 0;
             border-radius: 8px 0 0 8px;
             color: #495057;
        }
         .form-control {
             border-left: 0;
              border-radius: 0 8px 8px 0;
         }

        .btn-success {
            background-color: #198754;
            border-color: #198754;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
            width: 100%;
        }
        .btn-success:hover {
            background-color: #157347;
            border-color: #146c43;
        }
        .alert-danger {
            margin-top: 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            text-align: left;
            padding: 10px 15px;
            background-color: #f8d7da;
            border-color: #f5c2c7;
            color: #842029;
        }
         .alert .btn-close {
             padding: 0.75rem 1rem;
         }
        .bi-box-arrow-in-right {
            margin-right: 0.5rem;
        }
        .login-footer {
            margin-top: 25px;
            font-size: 0.85em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h4><i class="bi bi-shield-lock-fill"></i> LOGIN ADMIN</h4>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post" novalidate>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" class="form-control" name="username" id="username" placeholder="Masukkan username" required autocomplete="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                 <div class="input-group">
                     <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Masukkan password" required autocomplete="current-password">
                 </div>
            </div>
            <div>
                <button class="btn btn-success" type="submit" name="loginbtn">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </div>
        </form>
        <p class="login-footer">&copy; <?php echo date("Y"); ?> Sedaya Furniture</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>