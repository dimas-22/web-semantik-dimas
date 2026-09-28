<?php
session_start();
require_once 'koneksi.php';

// Cek apakah sudah login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: login.php");
    exit;
}

// Dashboard ini khusus administrator
if ($_SESSION['role'] !== 'administrator') {
    header("Location: dashboard_prodi.php");
    exit;
}

// Ambil data pengguna yang sedang login
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Administrator';

// =============================
// STATISTIK DATA
// =============================

// Jumlah mahasiswa
$query_mahasiswa = "SELECT COUNT(*) AS total FROM mahasiswa";
$result_mahasiswa = $conn->query($query_mahasiswa);
$total_mahasiswa = $result_mahasiswa->fetch_assoc()['total'];

// Jumlah program studi
$query_prodi = "SELECT COUNT(*) AS total FROM program_studi";
$result_prodi = $conn->query($query_prodi);
$total_prodi = $result_prodi->fetch_assoc()['total'];

// Jumlah fakultas
$query_fakultas = "SELECT COUNT(*) AS total FROM fakultas";
$result_fakultas = $conn->query($query_fakultas);
$total_fakultas = $result_fakultas->fetch_assoc()['total'];

// Jumlah pengguna
$query_pengguna = "SELECT COUNT(*) AS total FROM pengguna";
$result_pengguna = $conn->query($query_pengguna);
$total_pengguna = $result_pengguna->fetch_assoc()['total'];

// =============================
// DATA MAHASISWA TERBARU
// =============================

$query_terbaru = "
    SELECT 
        m.npm,
        m.nama_mahasiswa,
        m.jenis_kelamin,
        p.nama_prodi,
        f.nama_fakultas
    FROM mahasiswa m
    LEFT JOIN program_studi p 
        ON m.kode_prodi = p.kode_prodi
    LEFT JOIN fakultas f 
        ON p.kode_fakultas = f.kode_fakultas
    ORDER BY m.tanggal_masuk DESC
    LIMIT 5
";

$result_terbaru = $conn->query($query_terbaru);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Administrator - Universitas Semantik</title>

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

        /* SIDEBAR */
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

        .logo .icon {
            width: 55px;
            height: 55px;
            background: white;
            color: #0d47a1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 25px;
            font-weight: bold;
        }

        .logo h2 {
            font-size: 20px;
        }

        .logo p {
            font-size: 12px;
            margin-top: 5px;
            opacity: 0.8;
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

        /* MAIN */
        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        /* TOPBAR */
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
            justify-content: center;
            align-items: center;
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

        /* CONTENT */
        .content {
            padding: 30px;
        }

        .welcome {
            background: linear-gradient(135deg, #0d47a1, #1976d2);
            color: white;
            border-radius: 14px;
            padding: 25px 30px;
            margin-bottom: 25px;
        }

        .welcome h2 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .welcome p {
            opacity: 0.9;
            font-size: 14px;
        }

        /* STATISTIC CARDS */
        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            justify-content: center;
            align-items: center;
            font-size: 22px;
        }

        .blue {
            background: #e3f2fd;
        }

        .green {
            background: #e8f5e9;
        }

        .orange {
            background: #fff3e0;
        }

        .purple {
            background: #f3e5f5;
        }

        /* TABLE */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
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
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
        }

        tr:hover {
            background: #fafafa;
        }

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

        /* RESPONSIVE */
        @media (max-width: 1000px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
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
            .menu a span:last-child,
            .logout a span {
                display: none;
            }

            .logo .icon {
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

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 700px;
            }

            .user-text {
                display: none;
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="logo">
        <div class="icon">US</div>
        <h2>Universitas Semantik</h2>
        <p>Sistem Informasi Akademik</p>
    </div>

    <div class="menu-title">Menu Utama</div>

    <nav class="menu">

        <a href="dashboard.php" class="active">
            <span>▣</span>
            <span>Dashboard</span>
        </a>

        <a href="mahasiswa.php">
            <span>👨‍🎓</span>
            <span>Mahasiswa</span>
        </a>

        <a href="program_studi.php">
            <span>📚</span>
            <span>Program Studi</span>
        </a>

        <a href="fakultas.php">
            <span>🏛</span>
            <span>Fakultas</span>
        </a>

        <div class="menu-title">Pengaturan</div>

        <a href="pengguna.php">
            <span>👤</span>
            <span>Pengguna</span>
        </a>

    </nav>

    <div class="logout">
        <a href="logout.php"
           onclick="return confirm('Apakah Anda yakin ingin logout?');">
            <span>⇥</span> Logout
        </a>
    </div>

</aside>


<!-- MAIN CONTENT -->
<main class="main">

    <!-- TOPBAR -->
    <header class="topbar">

        <h1>Dashboard</h1>

        <div class="user-info">

            <div class="avatar">
                <?php echo strtoupper(substr($nama_lengkap, 0, 1)); ?>
            </div>

            <div class="user-text">
                <strong>
                    <?php echo htmlspecialchars($nama_lengkap); ?>
                </strong>

                <small>Administrator</small>
            </div>

        </div>

    </header>


    <!-- CONTENT -->
    <section class="content">

        <!-- WELCOME -->
        <div class="welcome">

            <h2>
                Selamat Datang, 
                <?php echo htmlspecialchars($nama_lengkap); ?>!
            </h2>

            <p>
                Selamat datang di Dashboard Administrator
                Sistem Informasi Akademik Universitas Semantik.
            </p>

        </div>


        <!-- STATISTICS -->
        <div class="cards">

            <!-- Mahasiswa -->
            <div class="card">

                <div class="card-info">
                    <p>Total Mahasiswa</p>
                    <h3>
                        <?php echo number_format($total_mahasiswa); ?>
                    </h3>
                </div>

                <div class="card-icon blue">
                    👨‍🎓
                </div>

            </div>


            <!-- Program Studi -->
            <div class="card">

                <div class="card-info">
                    <p>Program Studi</p>
                    <h3>
                        <?php echo number_format($total_prodi); ?>
                    </h3>
                </div>

                <div class="card-icon green">
                    📚
                </div>

            </div>


            <!-- Fakultas -->
            <div class="card">

                <div class="card-info">
                    <p>Fakultas</p>
                    <h3>
                        <?php echo number_format($total_fakultas); ?>
                    </h3>
                </div>

                <div class="card-icon orange">
                    🏛
                </div>

            </div>


            <!-- Pengguna -->
            <div class="card">

                <div class="card-info">
                    <p>Pengguna Sistem</p>
                    <h3>
                        <?php echo number_format($total_pengguna); ?>
                    </h3>
                </div>

                <div class="card-icon purple">
                    👤
                </div>

            </div>

        </div>


        <!-- DATA MAHASISWA TERBARU -->
        <div class="table-container">

            <div class="table-header">

                <h3>Mahasiswa Terbaru</h3>

                <a href="mahasiswa.php" class="btn">
                    Lihat Semua
                </a>

            </div>


            <?php if ($result_terbaru && $result_terbaru->num_rows > 0): ?>

                <table>

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Jenis Kelamin</th>
                            <th>Program Studi</th>
                            <th>Fakultas</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php
                    $no = 1;

                    while ($row = $result_terbaru->fetch_assoc()):
                    ?>

                        <tr>

                            <td>
                                <?php echo $no++; ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($row['npm']); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['nama_mahasiswa']); ?>
                            </td>

                            <td>

                                <?php if ($row['jenis_kelamin'] === 'L'): ?>

                                    <span class="badge badge-l">
                                        Laki-laki
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-p">
                                        Perempuan
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['nama_prodi'] ?? '-'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['nama_fakultas'] ?? '-'
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">
                    Belum terdapat data mahasiswa.
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

</body>
</html>