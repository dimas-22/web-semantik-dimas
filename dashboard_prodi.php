<?php
session_start();
require_once 'koneksi.php';

// ==========================================
// CEK LOGIN
// ==========================================
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: login.php");
    exit;
}

// ==========================================
// CEK ROLE
// ==========================================
if ($_SESSION['role'] !== 'prodi') {
    header("Location: dashboard.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Admin Prodi';
$kode_prodi = $_SESSION['kode_prodi'] ?? '';

if (empty($kode_prodi)) {
    die("Kode program studi pada akun tidak ditemukan.");
}

// ==========================================
// AMBIL DATA PROGRAM STUDI
// ==========================================
$stmt = $conn->prepare("
    SELECT 
        p.kode_prodi,
        p.nama_prodi,
        f.kode_fakultas,
        f.nama_fakultas
    FROM program_studi p
    LEFT JOIN fakultas f
        ON p.kode_fakultas = f.kode_fakultas
    WHERE p.kode_prodi = ?
    LIMIT 1
");

$stmt->bind_param("s", $kode_prodi);
$stmt->execute();

$result_prodi = $stmt->get_result();
$data_prodi = $result_prodi->fetch_assoc();

if (!$data_prodi) {
    die("Data program studi tidak ditemukan.");
}

$nama_prodi = $data_prodi['nama_prodi'];
$nama_fakultas = $data_prodi['nama_fakultas'];
$kode_fakultas = $data_prodi['kode_fakultas'];


// ==========================================
// JUMLAH MAHASISWA PRODI
// ==========================================
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM mahasiswa
    WHERE kode_prodi = ?
");

$stmt->bind_param("s", $kode_prodi);
$stmt->execute();

$result_total = $stmt->get_result();
$total_mahasiswa = $result_total->fetch_assoc()['total'];


// ==========================================
// JUMLAH MAHASISWA LAKI-LAKI
// ==========================================
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM mahasiswa
    WHERE kode_prodi = ?
    AND jenis_kelamin = 'L'
");

$stmt->bind_param("s", $kode_prodi);
$stmt->execute();

$result_laki = $stmt->get_result();
$total_laki = $result_laki->fetch_assoc()['total'];


// ==========================================
// JUMLAH MAHASISWA PEREMPUAN
// ==========================================
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM mahasiswa
    WHERE kode_prodi = ?
    AND jenis_kelamin = 'P'
");

$stmt->bind_param("s", $kode_prodi);
$stmt->execute();

$result_perempuan = $stmt->get_result();
$total_perempuan = $result_perempuan->fetch_assoc()['total'];


// ==========================================
// DATA MAHASISWA TERBARU
// ==========================================
$stmt = $conn->prepare("
    SELECT
        npm,
        nama_mahasiswa,
        jenis_kelamin,
        tempat_lahir,
        tanggal_lahir,
        tanggal_masuk,
        alamat
    FROM mahasiswa
    WHERE kode_prodi = ?
    ORDER BY tanggal_masuk DESC
    LIMIT 10
");

$stmt->bind_param("s", $kode_prodi);
$stmt->execute();

$result_mahasiswa = $stmt->get_result();


// ==========================================
// INISIAL NAMA
// ==========================================
$inisial = strtoupper(substr($nama_lengkap, 0, 1));

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Dashboard Prodi - Universitas Semantik
    </title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f4f7fb;
            color: #1f2937;
        }

        /* =========================================
           SIDEBAR
        ========================================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #0d47a1;
            color: white;
            padding: 25px 15px;
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            width: 55px;
            height: 55px;
            background: white;
            color: #0d47a1;
            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 10px;

            font-size: 23px;
            font-weight: bold;
        }

        .logo h2 {
            font-size: 19px;
        }

        .logo p {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 5px;
        }

        .menu-title {
            font-size: 11px;
            text-transform: uppercase;
            opacity: 0.6;
            margin: 20px 15px 10px;
        }

        .menu a {
            display: block;

            color: white;
            text-decoration: none;

            padding: 13px 15px;
            margin-bottom: 5px;

            border-radius: 8px;

            font-size: 14px;

            transition: 0.3s;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255,0.15);
        }

        .menu a span {
            margin-right: 10px;
        }

        .logout {
            position: absolute;
            bottom: 25px;
            left: 15px;
            right: 15px;
        }

        .logout a {
            display: block;

            text-align: center;

            background: #ef4444;
            color: white;

            padding: 12px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;
        }

        .logout a:hover {
            background: #dc2626;
        }


        /* =========================================
           MAIN
        ========================================= */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }


        /* =========================================
           TOPBAR
        ========================================= */

        .topbar {
            height: 75px;

            background: white;

            display: flex;

            justify-content: space-between;
            align-items: center;

            padding: 0 30px;

            border-bottom: 1px solid #e5e7eb;
        }

        .topbar h1 {
            font-size: 22px;
            color: #111827;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 40px;
            height: 40px;

            background: #e3f2fd;
            color: #0d47a1;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: bold;
        }

        .user-text strong {
            display: block;
            font-size: 14px;
        }

        .user-text small {
            color: #6b7280;
            font-size: 12px;
        }


        /* =========================================
           CONTENT
        ========================================= */

        .content {
            padding: 30px;
        }


        /* =========================================
           WELCOME
        ========================================= */

        .welcome {
            background: linear-gradient(
                135deg,
                #0d47a1,
                #1976d2
            );

            color: white;

            border-radius: 14px;

            padding: 25px 30px;

            margin-bottom: 25px;
        }

        .welcome h2 {
            font-size: 23px;
            margin-bottom: 8px;
        }

        .welcome p {
            opacity: 0.9;
            font-size: 14px;
        }


        /* =========================================
           INFO PRODI
        ========================================= */

        .prodi-info {
            background: white;

            border-radius: 12px;

            padding: 20px;

            margin-bottom: 25px;

            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .prodi-info h3 {
            font-size: 17px;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;
        }

        .info-item {
            background: #f8fafc;

            padding: 15px;

            border-radius: 8px;
        }

        .info-item small {
            display: block;

            color: #6b7280;

            font-size: 11px;

            margin-bottom: 5px;
        }

        .info-item strong {
            font-size: 14px;
        }


        /* =========================================
           STATISTICS
        ========================================= */

        .cards {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

            margin-bottom: 30px;
        }

        .card {
            background: white;

            border-radius: 12px;

            padding: 22px;

            box-shadow:
                0 2px 10px rgba(0,0,0,0.05);

            display: flex;

            justify-content: space-between;
            align-items: center;
        }

        .card-info p {
            color: #6b7280;

            font-size: 13px;

            margin-bottom: 8px;
        }

        .card-info h3 {
            font-size: 28px;

            color: #111827;
        }

        .card-icon {
            width: 50px;
            height: 50px;

            border-radius: 10px;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 22px;
        }

        .blue {
            background: #e3f2fd;
        }

        .green {
            background: #e8f5e9;
        }

        .pink {
            background: #fce4ec;
        }


        /* =========================================
           TABLE
        ========================================= */

        .table-container {
            background: white;

            border-radius: 12px;

            box-shadow:
                0 2px 10px rgba(0,0,0,0.05);

            overflow: hidden;
        }

        .table-header {
            padding: 20px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 17px;
        }

        .btn {
            text-decoration: none;

            background: #0d47a1;

            color: white;

            padding: 9px 15px;

            border-radius: 7px;

            font-size: 13px;
        }

        .btn:hover {
            background: #083b88;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;

            color: #374151;

            font-size: 13px;

            text-align: left;

            padding: 13px 15px;
        }

        td {
            padding: 14px 15px;

            border-top:
                1px solid #f0f0f0;

            font-size: 13px;
        }

        tr:hover {
            background: #fafafa;
        }


        /* =========================================
           BADGE
        ========================================= */

        .badge {
            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;
        }

        .badge-l {
            background: #e3f2fd;
            color: #1565c0;
        }

        .badge-p {
            background: #fce4ec;
            color: #c2185b;
        }

        .empty {
            text-align: center;

            padding: 30px;

            color: #6b7280;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 1000px) {

            .cards {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .info-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }
        }


        @media (max-width: 700px) {

            .sidebar {
                width: 70px;
                padding: 15px 8px;
            }

            .logo h2,
            .logo p,
            .menu-title,
            .menu a span:last-child {
                display: none;
            }

            .logo-icon {
                width: 45px;
                height: 45px;
            }

            .menu a {
                text-align: center;
                padding: 12px 5px;
            }

            .menu a span {
                margin: 0;
            }

            .logout {
                left: 8px;
                right: 8px;
            }

            .logout a {
                font-size: 0;
            }

            .logout a::before {
                content: "⇥";
                font-size: 20px;
            }

            .main {
                margin-left: 70px;
            }

            .topbar {
                padding: 0 15px;
            }

            .content {
                padding: 15px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 850px;
            }

            .user-text {
                display: none;
            }
        }

    </style>

</head>


<body>


<!-- =========================================
     SIDEBAR
========================================= -->

<aside class="sidebar">

    <div class="logo">

        <div class="logo-icon">
            US
        </div>

        <h2>
            Universitas Semantik
        </h2>

        <p>
            Sistem Informasi Akademik
        </p>

    </div>


    <div class="menu-title">
        Menu Utama
    </div>


    <nav class="menu">

        <a href="dashboard_prodi.php"
           class="active">

            <span>▣</span>
            <span>Dashboard</span>

        </a>


        <a href="mahasiswa_prodi.php">

            <span>👨‍🎓</span>
            <span>Mahasiswa</span>

        </a>


        <div class="menu-title">
            Informasi
        </div>


        <a href="profil_prodi.php">

            <span>📚</span>
            <span>Program Studi</span>

        </a>

    </nav>


    <div class="logout">

        <a href="logout.php"
           onclick="return confirm('Apakah Anda yakin ingin logout?');">

            <span>⇥</span>
            Logout

        </a>

    </div>

</aside>



<!-- =========================================
     MAIN
========================================= -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">

        <h1>
            Dashboard Program Studi
        </h1>


        <div class="user-info">

            <div class="avatar">

                <?php
                echo htmlspecialchars($inisial);
                ?>

            </div>


            <div class="user-text">

                <strong>
                    <?php
                    echo htmlspecialchars($nama_lengkap);
                    ?>
                </strong>

                <small>
                    Admin Program Studi
                </small>

            </div>

        </div>

    </header>



    <!-- CONTENT -->

    <section class="content">


        <!-- WELCOME -->

        <div class="welcome">

            <h2>

                Selamat Datang,
                <?php
                echo htmlspecialchars($nama_lengkap);
                ?>!

            </h2>

            <p>

                Dashboard pengelolaan data mahasiswa
                Program Studi
                <?php
                echo htmlspecialchars($nama_prodi);
                ?>.

            </p>

        </div>



        <!-- INFORMASI PRODI -->

        <div class="prodi-info">

            <h3>
                Informasi Program Studi
            </h3>


            <div class="info-grid">


                <div class="info-item">

                    <small>
                        Kode Program Studi
                    </small>

                    <strong>
                        <?php
                        echo htmlspecialchars($kode_prodi);
                        ?>
                    </strong>

                </div>


                <div class="info-item">

                    <small>
                        Nama Program Studi
                    </small>

                    <strong>
                        <?php
                        echo htmlspecialchars($nama_prodi);
                        ?>
                    </strong>

                </div>


                <div class="info-item">

                    <small>
                        Fakultas
                    </small>

                    <strong>
                        <?php
                        echo htmlspecialchars($nama_fakultas);
                        ?>
                    </strong>

                </div>


            </div>

        </div>



        <!-- STATISTICS -->

        <div class="cards">


            <!-- TOTAL MAHASISWA -->

            <div class="card">

                <div class="card-info">

                    <p>
                        Total Mahasiswa
                    </p>

                    <h3>
                        <?php
                        echo number_format(
                            $total_mahasiswa
                        );
                        ?>
                    </h3>

                </div>


                <div class="card-icon blue">
                    👨‍🎓
                </div>

            </div>



            <!-- LAKI-LAKI -->

            <div class="card">

                <div class="card-info">

                    <p>
                        Mahasiswa Laki-laki
                    </p>

                    <h3>
                        <?php
                        echo number_format(
                            $total_laki
                        );
                        ?>
                    </h3>

                </div>


                <div class="card-icon green">
                    👨
                </div>

            </div>



            <!-- PEREMPUAN -->

            <div class="card">

                <div class="card-info">

                    <p>
                        Mahasiswa Perempuan
                    </p>

                    <h3>
                        <?php
                        echo number_format(
                            $total_perempuan
                        );
                        ?>
                    </h3>

                </div>


                <div class="card-icon pink">
                    👩
                </div>

            </div>


        </div>



        <!-- DATA MAHASISWA -->

        <div class="table-container">


            <div class="table-header">

                <h3>
                    Data Mahasiswa Terbaru
                </h3>


                <a href="mahasiswa_prodi.php"
                   class="btn">

                    Kelola Mahasiswa

                </a>

            </div>



            <?php

            if (
                $result_mahasiswa &&
                $result_mahasiswa->num_rows > 0
            ):

            ?>

                <table>

                    <thead>

                        <tr>

                            <th>
                                No
                            </th>

                            <th>
                                NPM
                            </th>

                            <th>
                                Nama Mahasiswa
                            </th>

                            <th>
                                Jenis Kelamin
                            </th>

                            <th>
                                Tempat Lahir
                            </th>

                            <th>
                                Tanggal Lahir
                            </th>

                            <th>
                                Tanggal Masuk
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    $no = 1;

                    while (
                        $row =
                        $result_mahasiswa->fetch_assoc()
                    ):

                    ?>

                        <tr>


                            <td>

                                <?php
                                echo $no++;
                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['npm']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['nama_mahasiswa']
                                );
                                ?>

                            </td>


                            <td>


                                <?php

                                if (
                                    $row['jenis_kelamin']
                                    === 'L'
                                ):

                                ?>

                                    <span
                                        class="badge badge-l">

                                        Laki-laki

                                    </span>

                                <?php

                                else:

                                ?>

                                    <span
                                        class="badge badge-p">

                                        Perempuan

                                    </span>

                                <?php endif; ?>


                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['tempat_lahir']
                                );
                                ?>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    'd-m-Y',
                                    strtotime(
                                        $row['tanggal_lahir']
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    'd-m-Y',
                                    strtotime(
                                        $row['tanggal_masuk']
                                    )
                                );

                                ?>

                            </td>


                        </tr>


                    <?php

                    endwhile;

                    ?>


                    </tbody>

                </table>


            <?php

            else:

            ?>

                <div class="empty">

                    Belum terdapat data mahasiswa
                    pada program studi ini.

                </div>

            <?php endif; ?>


        </div>


    </section>


</main>


</body>

</html>