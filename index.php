<?php
// index.php
// Landing Page - Sistem Informasi Data Mahasiswa
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universitas Semantik - Sistem Informasi Mahasiswa</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #183b68;
            background: #ffffff;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ================= NAVBAR ================= */
        .navbar {
            height: 76px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5.8%;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 23px;
            font-weight: 700;
            color: #0d3b73;
            white-space: nowrap;
        }

        .brand i {
            font-size: 36px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 35px;
            margin-left: auto;
            margin-right: 35px;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 9px;
            height: 76px;
            font-size: 14px;
            color: #274466;
            position: relative;
            transition: .3s;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: #1268d9;
        }

        .nav-menu a.active::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #1774e8;
        }

        .login-btn {
            border: 1px solid #183f70;
            border-radius: 9px;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            gap: 9px;
            color: #183f70;
            font-size: 14px;
            transition: .3s;
        }

        .login-btn:hover {
            background: #183f70;
            color: #fff;
        }

        /* ================= HERO ================= */
        .hero {
            min-height: 460px;
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
            background:
                linear-gradient(90deg, rgba(8,67,132,.97) 0%,
                rgba(12,85,160,.86) 42%,
                rgba(12,85,160,.20) 75%,
                rgba(0,0,0,.05) 100%),
                url('https://images.unsplash.com/photo-1564981797816-1043664bf78d?auto=format&fit=crop&w=1800&q=85')
                center/cover no-repeat;
        }

        .hero-content {
            width: 90%;
            max-width: 1370px;
            margin: auto;
            color: #fff;
            padding: 65px 0;
        }

        .welcome {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .hero h1 {
            max-width: 650px;
            font-size: clamp(42px, 5vw, 64px);
            line-height: 1.08;
            margin-bottom: 20px;
            font-weight: 800;
        }

        .hero p {
            max-width: 580px;
            font-size: 17px;
            line-height: 1.7;
            margin-bottom: 28px;
        }

        .hero-buttons {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 25px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            transition: .3s;
        }

        .btn-primary {
            background: #1976ed;
            color: #fff;
        }

        .btn-primary:hover {
            background: #0c61c9;
            transform: translateY(-2px);
        }

        .btn-outline {
            border: 1px solid rgba(255,255,255,.9);
            color: #fff;
            background: rgba(255,255,255,.05);
        }

        .btn-outline:hover {
            background: #fff;
            color: #174475;
        }

        /* ================= WAVE ================= */
        .wave {
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 55px;
            background: #fff;
            clip-path: ellipse(65% 55% at 50% 100%);
        }

        /* ================= FEATURES ================= */
        .features {
            padding: 42px 6% 55px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .section-title h2 {
            font-size: 31px;
            color: #153f70;
            margin-bottom: 7px;
        }

        .section-title p {
            color: #667a98;
            font-size: 15px;
        }

        .cards {
            max-width: 1370px;
            margin: auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 27px;
        }

        .card {
            border: 1px solid #dbe5f0;
            border-radius: 10px;
            padding: 27px;
            min-height: 245px;
            background: #f8fbff;
            transition: .3s;
        }

        .card:nth-child(2) {
            background: #f4fbf7;
        }

        .card:nth-child(3) {
            background: #faf8ff;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(24,59,104,.10);
        }

        .icon {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 24px;
            margin-bottom: 16px;
            background: #2379e5;
        }

        .card:nth-child(2) .icon {
            background: #14a368;
        }

        .card:nth-child(3) .icon {
            background: #7654d6;
        }

        .card h3 {
            font-size: 20px;
            margin-bottom: 7px;
            color: #183d6c;
        }

        .card p {
            font-size: 14px;
            line-height: 1.7;
            color: #607492;
            margin-bottom: 17px;
        }

        .card-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 16px;
            border-radius: 7px;
            background: #1976ed;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
        }

        .card:nth-child(2) .card-btn {
            background: #11995f;
        }

        .card:nth-child(3) .card-btn {
            background: #7250cf;
        }

        /* ================= FOOTER ================= */
        footer {
            background: #203f67;
            color: #dce7f5;
            padding: 24px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 1050px) {
            .nav-menu {
                gap: 15px;
                margin-right: 15px;
            }

            .brand {
                font-size: 18px;
            }

            .cards {
                grid-template-columns: 1fr;
                max-width: 700px;
            }
        }

        @media (max-width: 800px) {
            .navbar {
                height: auto;
                min-height: 70px;
                padding: 15px 5%;
                flex-wrap: wrap;
                gap: 12px;
            }

            .nav-menu {
                order: 3;
                width: 100%;
                overflow-x: auto;
                margin: 0;
                gap: 25px;
            }

            .nav-menu a {
                height: 42px;
                white-space: nowrap;
            }

            .nav-menu a.active::after {
                height: 3px;
            }

            .hero {
                min-height: 500px;
            }

            .hero-content {
                width: 88%;
            }

            .hero h1 {
                font-size: 43px;
            }

            footer {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }

        @media (max-width: 500px) {
            .brand {
                font-size: 16px;
            }

            .brand i {
                font-size: 29px;
            }

            .login-btn {
                padding: 8px 12px;
            }

            .hero h1 {
                font-size: 36px;
            }

            .hero p {
                font-size: 14px;
            }

            .features {
                padding-left: 5%;
                padding-right: 5%;
            }
        }
    </style>
</head>

<body>

<!-- ================= NAVBAR ================= -->
<header class="navbar">

    <a href="index.php" class="brand">
        <i class="fa-solid fa-graduation-cap"></i>
        <span>Universitas Semantik</span>
    </a>

    <nav class="nav-menu">
        <a href="index.php" class="active">
            <i class="fa-solid fa-house"></i>
            Beranda
        </a>

        <a href="mahasiswa.php">
            <i class="fa-solid fa-users"></i>
            Data Mahasiswa
        </a>

        <a href="program_studi.php">
            <i class="fa-solid fa-book-open"></i>
            Program Studi
        </a>

        <a href="fakultas.php">
            <i class="fa-solid fa-building-columns"></i>
            Fakultas
        </a>
    </nav>

    <a href="login.php" class="login-btn">
        <i class="fa-solid fa-user-lock"></i>
        Login
    </a>

</header>

<!-- ================= HERO ================= -->
<section class="hero">

    <div class="hero-content">

        <div class="welcome">Selamat Datang di</div>

        <h1>
            Sistem Informasi<br>
            Data Mahasiswa
        </h1>

        <p>
            Universitas Semantik adalah platform untuk mengelola
            data mahasiswa, program studi, dan fakultas secara
            terintegrasi dan mudah diakses.
        </p>

        <div class="hero-buttons">
            <a href="login.php" class="btn btn-primary">
                <i class="fa-solid fa-user"></i>
                Login Sekarang
            </a>

            <a href="#fitur" class="btn btn-outline">
                <i class="fa-solid fa-circle-info"></i>
                Tentang Kami
            </a>
        </div>

    </div>

    <div class="wave"></div>

</section>

<!-- ================= FEATURES ================= -->
<section class="features" id="fitur">

    <div class="section-title">
        <h2>Fitur Utama</h2>
        <p>Kelola data dengan lebih mudah dan efisien.</p>
    </div>

    <div class="cards">

        <div class="card">

            <div class="icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <h3>Data Mahasiswa</h3>

            <p>
                Lihat, tambah, ubah, dan hapus data mahasiswa
                dengan mudah.
            </p>

            <a href="mahasiswa.php" class="card-btn">
                <i class="fa-solid fa-arrow-right"></i>
                Kelola Data
            </a>

        </div>

        <div class="card">

            <div class="icon">
                <i class="fa-solid fa-book-open"></i>
            </div>

            <h3>Program Studi</h3>

            <p>
                Kelola informasi program studi yang tersedia
                di universitas.
            </p>

            <a href="program_studi.php" class="card-btn">
                <i class="fa-solid fa-arrow-right"></i>
                Lihat Program Studi
            </a>

        </div>

        <div class="card">

            <div class="icon">
                <i class="fa-solid fa-building-columns"></i>
            </div>

            <h3>Fakultas</h3>

            <p>
                Kelola data fakultas yang ada di
                Universitas Semantik.
            </p>

            <a href="fakultas.php" class="card-btn">
                <i class="fa-solid fa-arrow-right"></i>
                Lihat Fakultas
            </a>

        </div>

    </div>

</section>

<!-- ================= FOOTER ================= -->
<footer>
    <div>
        &copy; <?php echo date('Y'); ?> Universitas Semantik. All rights reserved.
    </div>

    <div>
        Sistem Informasi Akademik
    </div>
</footer>

</body>
</html>
