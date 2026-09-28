<?php

session_start();

require_once 'koneksi.php';


/* =====================================================
   CEK LOGIN
===================================================== */

if (!isset($_SESSION['id_pengguna'])) {

    header("Location: login.php");
    exit;
}


/* =====================================================
   CEK ROLE
===================================================== */

if ($_SESSION['role'] !== 'mahasiswa') {

    header("Location: login.php");
    exit;
}


/* =====================================================
   CEK NPM
===================================================== */

if (empty($_SESSION['npm'])) {

    die("Akun mahasiswa belum terhubung dengan data mahasiswa.");
}


$npm = $_SESSION['npm'];


/* =====================================================
   AMBIL DATA MAHASISWA
===================================================== */

$stmt = $conn->prepare("
    SELECT
        m.npm,
        m.nama_mahasiswa,
        m.jenis_kelamin,
        m.tempat_lahir,
        m.tanggal_lahir,
        m.tanggal_masuk,
        m.alamat,

        p.kode_prodi,
        p.nama_prodi,

        f.kode_fakultas,
        f.nama_fakultas

    FROM mahasiswa m

    INNER JOIN program_studi p
        ON m.kode_prodi = p.kode_prodi

    INNER JOIN fakultas f
        ON p.kode_fakultas = f.kode_fakultas

    WHERE m.npm = ?

    LIMIT 1
");

$stmt->bind_param("s", $npm);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    die("Data mahasiswa tidak ditemukan.");
}


$mhs = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Dashboard Mahasiswa - Universitas Semantik</title>

<link rel="preconnect"
      href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.gstatic.com"
      crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet">


<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Inter, sans-serif;
    background: #f5f7fb;
    color: #172033;
}


/* SIDEBAR */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 250px;
    background: #0f3d75;
    color: white;
    padding: 25px 15px;
}

.logo {
    font-size: 21px;
    font-weight: 800;
    padding: 0 15px 30px;
}

.menu-title {
    font-size: 11px;
    opacity: .6;
    padding: 0 15px 10px;
}

.menu a {
    display: block;
    padding: 12px 15px;
    margin-bottom: 5px;
    border-radius: 9px;
    color: white;
    text-decoration: none;
    font-size: 14px;
}

.menu a:hover,
.menu a.active {
    background: rgba(255,255,255,.15);
}


/* MAIN */

.main {
    margin-left: 250px;
    padding: 30px;
}


.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.topbar h1 {
    margin: 0;
    font-size: 25px;
}

.user {
    font-size: 13px;
    color: #667085;
}


/* WELCOME */

.welcome {
    background: #0f3d75;
    color: white;
    border-radius: 18px;
    padding: 25px;
    margin-bottom: 25px;
}

.welcome h2 {
    margin: 0 0 8px;
}

.welcome p {
    margin: 0;
    opacity: .85;
}


/* PROFILE */

.profile {
    background: white;
    border-radius: 18px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
}

.profile h2 {
    margin-top: 0;
}


.grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}


.item {
    background: #f9fafb;
    border-radius: 12px;
    padding: 15px;
}

.label {
    color: #667085;
    font-size: 12px;
    margin-bottom: 5px;
}

.value {
    font-weight: 600;
    font-size: 14px;
}


.full {
    grid-column: 1 / -1;
}


/* MOBILE */

@media(max-width: 800px) {

    .sidebar {
        position: static;
        width: 100%;
    }

    .main {
        margin-left: 0;
        padding: 15px;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}

</style>

</head>


<body>


<aside class="sidebar">

    <div class="logo">
        🎓 Universitas Semantik
    </div>

    <div class="menu-title">
        Menu Mahasiswa
    </div>

    <nav class="menu">

        <a
            href="dashboard_mahasiswa.php"
            class="active"
        >
            Dashboard
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</aside>


<main class="main">


    <div class="topbar">

        <div>

            <h1>
                Dashboard Mahasiswa
            </h1>

        </div>

        <div class="user">

            👤
            <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>

        </div>

    </div>


    <section class="welcome">

        <h2>
            Selamat Datang,
            <?= htmlspecialchars($mhs['nama_mahasiswa']) ?>
        </h2>

        <p>
            Selamat datang di Sistem Informasi Akademik
            Universitas Semantik.
        </p>

    </section>


    <section class="profile">

        <h2>
            Profil Mahasiswa
        </h2>


        <div class="grid">


            <div class="item">

                <div class="label">
                    NPM
                </div>

                <div class="value">
                    <?= htmlspecialchars($mhs['npm']) ?>
                </div>

            </div>


            <div class="item">

                <div class="label">
                    Nama Mahasiswa
                </div>

                <div class="value">
                    <?= htmlspecialchars($mhs['nama_mahasiswa']) ?>
                </div>

            </div>


            <div class="item">

                <div class="label">
                    Jenis Kelamin
                </div>

                <div class="value">

                    <?php
                    if ($mhs['jenis_kelamin'] === 'L') {
                        echo 'Laki-laki';
                    } else {
                        echo 'Perempuan';
                    }
                    ?>

                </div>

            </div>


            <div class="item">

                <div class="label">
                    Tempat Lahir
                </div>

                <div class="value">
                    <?= htmlspecialchars($mhs['tempat_lahir']) ?>
                </div>

            </div>


            <div class="item">

                <div class="label">
                    Tanggal Lahir
                </div>

                <div class="value">

                    <?= date(
                        'd-m-Y',
                        strtotime($mhs['tanggal_lahir'])
                    ) ?>

                </div>

            </div>


            <div class="item">

                <div class="label">
                    Tanggal Masuk
                </div>

                <div class="value">

                    <?= date(
                        'd-m-Y',
                        strtotime($mhs['tanggal_masuk'])
                    ) ?>

                </div>

            </div>


            <div class="item">

                <div class="label">
                    Fakultas
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $mhs['nama_fakultas']
                    ) ?>

                </div>

            </div>


            <div class="item">

                <div class="label">
                    Program Studi
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $mhs['nama_prodi']
                    ) ?>

                </div>

            </div>


            <div class="item full">

                <div class="label">
                    Alamat
                </div>

                <div class="value">

                    <?= nl2br(
                        htmlspecialchars(
                            $mhs['alamat']
                        )
                    ) ?>

                </div>

            </div>


        </div>

    </section>


</main>

</body>

</html>