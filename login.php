<?php
session_start();
require_once 'koneksi.php';

$error = '';

/* =========================================================
   JIKA SUDAH LOGIN
========================================================= */

if (isset($_SESSION['id_pengguna'])) {

    if ($_SESSION['role'] === 'administrator') {

        header("Location: dashboard.php");
        exit;

    } elseif ($_SESSION['role'] === 'prodi') {

        header("Location: dashboard_prodi.php");
        exit;

    } elseif ($_SESSION['role'] === 'mahasiswa') {

        header("Location: dashboard_mahasiswa.php");
        exit;
    }
}


/* =========================================================
   PROSES LOGIN
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = "Username dan password wajib diisi.";

    } else {

        /*
         * Mengambil data pengguna.
         *
         * npm ditambahkan karena akun mahasiswa
         * terhubung langsung dengan data mahasiswa.
         */

        $sql = "SELECT
                    id_pengguna,
                    username,
                    password,
                    nama_lengkap,
                    role,
                    kode_prodi,
                    npm,
                    status
                FROM pengguna
                WHERE username = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error = "Terjadi kesalahan pada sistem database.";

        } else {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();


            /* =================================================
               CEK DATA USER
            ================================================= */

            if ($user && $user['status'] === 'aktif') {

                $password_benar = false;


                /* =================================================
                   PASSWORD HASH
                ================================================= */

                if (password_verify(
                    $password,
                    $user['password']
                )) {

                    $password_benar = true;
                }


                /*
                 * Fallback untuk password lama yang masih
                 * berupa teks biasa.
                 *
                 * Jika semua password database sudah memakai
                 * password_hash(), bagian ini bisa dihapus.
                 */

                elseif (
                    hash_equals(
                        $user['password'],
                        $password
                    )
                ) {

                    $password_benar = true;
                }


                /* =================================================
                   LOGIN BERHASIL
                ================================================= */

                if ($password_benar) {

                    /*
                     * Membuat session ID baru untuk keamanan.
                     */

                    session_regenerate_id(true);


                    $_SESSION['id_pengguna'] =
                        $user['id_pengguna'];

                    $_SESSION['username'] =
                        $user['username'];

                    $_SESSION['nama_lengkap'] =
                        $user['nama_lengkap'];

                    $_SESSION['role'] =
                        $user['role'];

                    $_SESSION['kode_prodi'] =
                        $user['kode_prodi'];

                    /*
                     * NPM khusus untuk akun mahasiswa.
                     */

                    $_SESSION['npm'] =
                        $user['npm'];


                    /* =================================================
                       REDIRECT SESUAI ROLE
                    ================================================= */

                    if ($user['role'] === 'administrator') {

                        header(
                            "Location: dashboard.php"
                        );

                        exit;
                    }


                    elseif ($user['role'] === 'prodi') {

                        /*
                         * Pastikan akun Prodi mempunyai
                         * kode program studi.
                         */

                        if (empty($user['kode_prodi'])) {

                            session_destroy();

                            $error =
                                "Akun Prodi belum terhubung dengan Program Studi.";

                        } else {

                            header(
                                "Location: dashboard_prodi.php"
                            );

                            exit;
                        }
                    }


                    elseif ($user['role'] === 'mahasiswa') {

                        /*
                         * Pastikan akun mahasiswa mempunyai
                         * NPM yang terhubung ke tabel mahasiswa.
                         */

                        if (empty($user['npm'])) {

                            session_destroy();

                            $error =
                                "Akun mahasiswa belum terhubung dengan data mahasiswa.";

                        } else {

                            header(
                                "Location: dashboard_mahasiswa.php"
                            );

                            exit;
                        }
                    }


                    else {

                        session_destroy();

                        $error =
                            "Role pengguna tidak dikenali.";
                    }

                } else {

                    $error =
                        "Username atau password salah.";
                }

            } elseif ($user && $user['status'] !== 'aktif') {

                $error =
                    "Akun Anda sedang nonaktif. Silakan hubungi administrator.";

            } else {

                $error =
                    "Username atau password salah.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Login - Universitas Semantik
    </title>


    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            min-height: 100vh;

            font-family:
                'Poppins',
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #0b3b73,
                    #1976ed
                );

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 20px;
        }


        /* =====================================================
           CONTAINER
        ===================================================== */

        .login-container {

            width: 100%;

            max-width: 950px;

            min-height: 560px;

            background: #ffffff;

            border-radius: 20px;

            overflow: hidden;

            display: grid;

            grid-template-columns:
                1fr 1fr;

            box-shadow:
                0 20px 60px
                rgba(0, 0, 0, 0.20);
        }


        /* =====================================================
           BAGIAN KIRI
        ===================================================== */

        .login-left {

            color: white;

            padding: 55px;

            display: flex;

            flex-direction: column;

            justify-content: center;

            background:

                linear-gradient(
                    rgba(8, 62, 124, 0.90),
                    rgba(25, 118, 237, 0.85)
                ),

                url(
                    'https://images.unsplash.com/photo-1564981797816-1043664bf78d?auto=format&fit=crop&w=1000&q=80'
                );

            background-size: cover;

            background-position: center;
        }


        .logo {

            font-size: 55px;

            margin-bottom: 20px;
        }


        .login-left h1 {

            font-size: 35px;

            line-height: 1.2;

            margin-bottom: 15px;
        }


        .login-left p {

            font-size: 14px;

            line-height: 1.8;

            opacity: 0.95;
        }


        /* =====================================================
           BAGIAN KANAN
        ===================================================== */

        .login-right {

            padding: 55px;

            display: flex;

            flex-direction: column;

            justify-content: center;
        }


        .login-right h2 {

            color: #173f70;

            font-size: 30px;

            margin-bottom: 7px;
        }


        .subtitle {

            color: #71829a;

            font-size: 13px;

            margin-bottom: 28px;
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .alert {

            background: #fff0f0;

            border: 1px solid #f0c4c4;

            color: #b42318;

            border-radius: 8px;

            padding: 11px 14px;

            font-size: 13px;

            margin-bottom: 18px;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {

            margin-bottom: 18px;
        }


        .form-group label {

            display: block;

            color: #304b6b;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 7px;
        }


        .form-group input {

            width: 100%;

            height: 48px;

            border: 1px solid #d5dfeb;

            border-radius: 8px;

            padding: 0 14px;

            font-family: inherit;

            font-size: 13px;

            outline: none;

            transition: 0.2s;
        }


        .form-group input:focus {

            border-color: #1976ed;

            box-shadow:
                0 0 0 3px
                rgba(25, 118, 237, 0.10);
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .btn-login {

            width: 100%;

            height: 48px;

            border: none;

            border-radius: 8px;

            background: #1976ed;

            color: white;

            font-family: inherit;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            transition: 0.2s;
        }


        .btn-login:hover {

            background: #0d61c9;

            transform:
                translateY(-1px);
        }


        /* =====================================================
           BACK HOME
        ===================================================== */

        .back-home {

            display: block;

            text-align: center;

            margin-top: 20px;

            color: #58708f;

            font-size: 13px;

            text-decoration: none;
        }


        .back-home:hover {

            color: #1976ed;
        }


        /* =====================================================
           INFO ROLE
        ===================================================== */

        .role-info {

            margin-top: 20px;

            padding: 12px 14px;

            background: #f5f8fc;

            border-radius: 8px;

            color: #667085;

            font-size: 11px;

            line-height: 1.6;

            text-align: center;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 760px) {

            .login-container {

                grid-template-columns: 1fr;

                max-width: 500px;
            }


            .login-left {

                min-height: 260px;

                padding: 35px;
            }


            .login-right {

                padding: 35px;
            }


            .login-left h1 {

                font-size: 28px;
            }


            .logo {

                font-size: 42px;
            }
        }

    </style>

</head>


<body>


<div class="login-container">


    <!-- =====================================================
         LEFT
    ===================================================== -->

    <div class="login-left">

        <div class="logo">
            🎓
        </div>


        <h1>
            Universitas Semantik
        </h1>


        <p>

            Sistem Informasi Data Mahasiswa
            untuk mengelola data mahasiswa,
            program studi, dan fakultas
            secara terintegrasi.

        </p>

    </div>


    <!-- =====================================================
         RIGHT
    ===================================================== -->

    <div class="login-right">


        <h2>
            Selamat Datang
        </h2>


        <p class="subtitle">

            Silakan masuk menggunakan
            akun Anda.

        </p>


        <?php if ($error !== ''): ?>

            <div class="alert">

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
        >


            <!-- USERNAME -->

            <div class="form-group">

                <label for="username">
                    Username
                </label>


                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Masukkan username"
                    value="<?= htmlspecialchars(
                        $_POST['username'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                    autocomplete="username"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>


                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    required
                >

            </div>


            <!-- BUTTON -->

            <button
                type="submit"
                class="btn-login"
            >

                Masuk

            </button>

        </form>


        <div class="role-info">

            Login dapat digunakan oleh
            <strong>Administrator</strong>,
            <strong>Prodi</strong>, dan
            <strong>Mahasiswa</strong>.

        </div>


        <a
            href="index.php"
            class="back-home"
        >

            ← Kembali ke Beranda

        </a>


    </div>

</div>


</body>

</html>