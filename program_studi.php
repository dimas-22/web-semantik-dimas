<?php
session_start();
require_once 'koneksi.php';

// ======================================================
// CEK LOGIN
// ======================================================
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: login.php");
    exit;
}

// Halaman ini khusus administrator
if ($_SESSION['role'] !== 'administrator') {
    header("Location: dashboard_prodi.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Administrator';

$inisial = strtoupper(
    substr($nama_lengkap, 0, 1)
);


// ======================================================
// PROSES TAMBAH PROGRAM STUDI
// ======================================================
if (isset($_POST['tambah'])) {

    $kode_prodi     = trim($_POST['kode_prodi']);
    $nama_prodi     = trim($_POST['nama_prodi']);
    $kode_fakultas  = trim($_POST['kode_fakultas']);

    // Cek kode prodi
    $cek = $conn->prepare("
        SELECT kode_prodi
        FROM program_studi
        WHERE kode_prodi = ?
        LIMIT 1
    ");

    $cek->bind_param("s", $kode_prodi);
    $cek->execute();

    $hasil_cek = $cek->get_result();

    if ($hasil_cek->num_rows > 0) {
        header("Location: program_studi.php?error=kode");
        exit;
    }

    // Simpan data
    $stmt = $conn->prepare("
        INSERT INTO program_studi
        (
            kode_prodi,
            nama_prodi,
            kode_fakultas
        )
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param(
        "sss",
        $kode_prodi,
        $nama_prodi,
        $kode_fakultas
    );

    $stmt->execute();

    header("Location: program_studi.php?success=tambah");
    exit;
}


// ======================================================
// PROSES UPDATE PROGRAM STUDI
// ======================================================
if (isset($_POST['update'])) {

    $kode_prodi     = trim($_POST['kode_prodi']);
    $nama_prodi     = trim($_POST['nama_prodi']);
    $kode_fakultas  = trim($_POST['kode_fakultas']);

    $stmt = $conn->prepare("
        UPDATE program_studi
        SET
            nama_prodi = ?,
            kode_fakultas = ?
        WHERE kode_prodi = ?
    ");

    $stmt->bind_param(
        "sss",
        $nama_prodi,
        $kode_fakultas,
        $kode_prodi
    );

    $stmt->execute();

    header("Location: program_studi.php?success=update");
    exit;
}


// ======================================================
// PROSES HAPUS PROGRAM STUDI
// ======================================================
if (isset($_GET['hapus'])) {

    $kode_prodi = $_GET['hapus'];

    // Cek apakah prodi masih digunakan mahasiswa
    $cek_mahasiswa = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM mahasiswa
        WHERE kode_prodi = ?
    ");

    $cek_mahasiswa->bind_param(
        "s",
        $kode_prodi
    );

    $cek_mahasiswa->execute();

    $hasil_mahasiswa =
        $cek_mahasiswa->get_result()
        ->fetch_assoc();

    if ($hasil_mahasiswa['total'] > 0) {

        header(
            "Location: program_studi.php?error=digunakan"
        );

        exit;
    }


    // Cek apakah prodi masih digunakan pengguna
    $cek_pengguna = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM pengguna
        WHERE kode_prodi = ?
    ");

    $cek_pengguna->bind_param(
        "s",
        $kode_prodi
    );

    $cek_pengguna->execute();

    $hasil_pengguna =
        $cek_pengguna->get_result()
        ->fetch_assoc();

    if ($hasil_pengguna['total'] > 0) {

        header(
            "Location: program_studi.php?error=digunakan_pengguna"
        );

        exit;
    }


    // Hapus
    $stmt = $conn->prepare("
        DELETE FROM program_studi
        WHERE kode_prodi = ?
    ");

    $stmt->bind_param(
        "s",
        $kode_prodi
    );

    $stmt->execute();

    header(
        "Location: program_studi.php?success=hapus"
    );

    exit;
}


// ======================================================
// DATA EDIT
// ======================================================
$data_edit = null;

if (isset($_GET['edit'])) {

    $kode_edit = $_GET['edit'];

    $stmt = $conn->prepare("
        SELECT
            kode_prodi,
            nama_prodi,
            kode_fakultas
        FROM program_studi
        WHERE kode_prodi = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "s",
        $kode_edit
    );

    $stmt->execute();

    $result_edit =
        $stmt->get_result();

    $data_edit =
        $result_edit->fetch_assoc();
}


// ======================================================
// PENCARIAN
// ======================================================
$keyword = '';

if (isset($_GET['cari'])) {
    $keyword = trim($_GET['cari']);
}


// ======================================================
// FILTER FAKULTAS
// ======================================================
$filter_fakultas = '';

if (isset($_GET['fakultas'])) {
    $filter_fakultas =
        trim($_GET['fakultas']);
}


// ======================================================
// QUERY PROGRAM STUDI
// ======================================================
$sql = "
    SELECT
        p.kode_prodi,
        p.nama_prodi,
        p.kode_fakultas,
        f.nama_fakultas,

        (
            SELECT COUNT(*)
            FROM mahasiswa m
            WHERE m.kode_prodi = p.kode_prodi
        ) AS jumlah_mahasiswa

    FROM program_studi p

    LEFT JOIN fakultas f
        ON p.kode_fakultas = f.kode_fakultas

    WHERE 1=1
";

$params = [];
$types = '';


// Pencarian
if ($keyword !== '') {

    $sql .= "
        AND (
            p.kode_prodi LIKE ?
            OR p.nama_prodi LIKE ?
        )
    ";

    $search =
        "%" . $keyword . "%";

    $params[] = $search;
    $params[] = $search;

    $types .= "ss";
}


// Filter fakultas
if ($filter_fakultas !== '') {

    $sql .= "
        AND p.kode_fakultas = ?
    ";

    $params[] =
        $filter_fakultas;

    $types .= "s";
}


$sql .= "
    ORDER BY p.nama_prodi ASC
";


$stmt = $conn->prepare($sql);

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}

$stmt->execute();

$result_prodi =
    $stmt->get_result();


// ======================================================
// DATA FAKULTAS
// ======================================================
$result_fakultas =
    $conn->query("
        SELECT
            kode_fakultas,
            nama_fakultas
        FROM fakultas
        ORDER BY nama_fakultas ASC
    ");


// ======================================================
// STATISTIK
// ======================================================
$result_total =
    $conn->query("
        SELECT COUNT(*) AS total
        FROM program_studi
    ");

$total_prodi =
    $result_total->fetch_assoc()['total'];


$result_total_mahasiswa =
    $conn->query("
        SELECT COUNT(*) AS total
        FROM mahasiswa
    ");

$total_mahasiswa =
    $result_total_mahasiswa
    ->fetch_assoc()['total'];


$result_total_fakultas =
    $conn->query("
        SELECT COUNT(*) AS total
        FROM fakultas
    ");

$total_fakultas =
    $result_total_fakultas
    ->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Program Studi - Universitas Semantik
</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}


body {
    background: #f4f7fb;
    color: #1f2937;
}


/* =====================================================
   SIDEBAR
===================================================== */

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
    background:
        rgba(255,255,255,0.15);
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


/* =====================================================
   MAIN
===================================================== */

.main {
    margin-left: 250px;

    min-height: 100vh;
}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {
    height: 75px;

    background: white;

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 0 30px;

    border-bottom:
        1px solid #e5e7eb;
}


.topbar h1 {
    font-size: 22px;
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


/* =====================================================
   CONTENT
===================================================== */

.content {
    padding: 30px;
}


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}


.page-header h2 {
    font-size: 24px;
}


.page-header p {
    color: #6b7280;

    font-size: 13px;

    margin-top: 5px;
}


/* =====================================================
   STATISTIC CARDS
===================================================== */

.stats {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

    margin-bottom: 25px;
}


.stat-card {
    background: white;

    border-radius: 12px;

    padding: 20px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,0.05);

    display: flex;

    justify-content: space-between;

    align-items: center;
}


.stat-card p {
    color: #6b7280;

    font-size: 13px;

    margin-bottom: 7px;
}


.stat-card h3 {
    font-size: 27px;

    color: #111827;
}


.stat-icon {
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


.orange {
    background: #fff3e0;
}


/* =====================================================
   BUTTON
===================================================== */

.btn {
    display: inline-block;

    border: none;

    cursor: pointer;

    text-decoration: none;

    padding: 10px 16px;

    border-radius: 7px;

    font-size: 13px;
}


.btn-primary {
    background: #0d47a1;

    color: white;
}


.btn-primary:hover {
    background: #083b88;
}


.btn-edit {
    background: #f59e0b;

    color: white;

    padding: 7px 10px;

    font-size: 12px;
}


.btn-delete {
    background: #ef4444;

    color: white;

    padding: 7px 10px;

    font-size: 12px;
}


.btn-cancel {
    background: #e5e7eb;

    color: #374151;
}


/* =====================================================
   FILTER
===================================================== */

.filter-box {
    background: white;

    padding: 20px;

    border-radius: 12px;

    margin-bottom: 20px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,0.05);
}


.filter-form {
    display: grid;

    grid-template-columns:
        2fr 1fr auto auto;

    gap: 10px;
}


.form-control {
    width: 100%;

    padding: 10px 12px;

    border: 1px solid #d1d5db;

    border-radius: 7px;

    outline: none;

    font-size: 13px;

    background: white;
}


.form-control:focus {
    border-color: #0d47a1;
}


/* =====================================================
   TABLE
===================================================== */

.table-container {
    background: white;

    border-radius: 12px;

    overflow: hidden;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,0.05);
}


.table-wrapper {
    overflow-x: auto;
}


table {
    width: 100%;

    border-collapse: collapse;

    min-width: 850px;
}


th {
    background: #f8fafc;

    padding: 14px;

    font-size: 12px;

    text-align: left;

    color: #374151;
}


td {
    padding: 14px;

    border-top:
        1px solid #f0f0f0;

    font-size: 13px;
}


tr:hover {
    background: #fafafa;
}


/* =====================================================
   BADGE
===================================================== */

.badge {
    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: bold;
}


.badge-blue {
    background: #e3f2fd;

    color: #1565c0;
}


/* =====================================================
   ALERT
===================================================== */

.alert {
    padding: 13px 16px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 13px;
}


.alert-success {
    background: #dcfce7;

    color: #166534;
}


.alert-error {
    background: #fee2e2;

    color: #991b1b;
}


/* =====================================================
   MODAL
===================================================== */

.modal {
    display: none;

    position: fixed;

    z-index: 999;

    left: 0;
    top: 0;

    width: 100%;
    height: 100%;

    background:
        rgba(0,0,0,0.5);

    padding: 30px;

    overflow-y: auto;
}


.modal-content {
    background: white;

    max-width: 550px;

    margin: 30px auto;

    border-radius: 12px;

    padding: 25px;
}


.modal-header {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;
}


.modal-header h3 {
    font-size: 19px;
}


.close {
    font-size: 25px;

    cursor: pointer;

    color: #6b7280;

    text-decoration: none;
}


.form-group {
    margin-bottom: 15px;
}


.form-group label {
    display: block;

    font-size: 13px;

    font-weight: bold;

    margin-bottom: 6px;
}


.form-actions {
    display: flex;

    justify-content: flex-end;

    gap: 10px;

    margin-top: 20px;
}


/* =====================================================
   EMPTY
===================================================== */

.empty {
    text-align: center;

    padding: 35px;

    color: #6b7280;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 900px) {

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

    .main {
        margin-left: 70px;
    }

    .stats {
        grid-template-columns:
            1fr;
    }

    .filter-form {
        grid-template-columns:
            1fr;
    }

}


@media (max-width: 600px) {

    .topbar {
        padding: 0 15px;
    }

    .content {
        padding: 15px;
    }

    .page-header {
        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .user-text {
        display: none;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

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


        <a href="dashboard.php">

            <span>▣</span>

            <span>
                Dashboard
            </span>

        </a>


        <a href="mahasiswa.php">

            <span>👨‍🎓</span>

            <span>
                Mahasiswa
            </span>

        </a>


        <a href="program_studi.php"
           class="active">

            <span>📚</span>

            <span>
                Program Studi
            </span>

        </a>


        <a href="fakultas.php">

            <span>🏛</span>

            <span>
                Fakultas
            </span>

        </a>


        <div class="menu-title">
            Pengaturan
        </div>


        <a href="pengguna.php">

            <span>👤</span>

            <span>
                Pengguna
            </span>

        </a>


    </nav>


    <div class="logout">

        <a
            href="logout.php"
            onclick="
                return confirm(
                    'Apakah Anda yakin ingin logout?'
                );
            "
        >

            ⇥ Logout

        </a>

    </div>


</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">


        <h1>
            Program Studi
        </h1>


        <div class="user-info">


            <div class="avatar">

                <?php
                echo htmlspecialchars(
                    $inisial
                );
                ?>

            </div>


            <div class="user-text">

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $nama_lengkap
                    );
                    ?>

                </strong>

                <small>
                    Administrator
                </small>

            </div>


        </div>


    </header>



    <!-- =================================================
         CONTENT
    ================================================= -->

    <section class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">


            <div>

                <h2>
                    Data Program Studi
                </h2>

                <p>
                    Kelola program studi Universitas Semantik.
                </p>

            </div>


            <button
                class="btn btn-primary"
                onclick="bukaModalTambah()"
            >

                + Tambah Program Studi

            </button>


        </div>



        <!-- =================================================
             ALERT
        ================================================= -->

        <?php if (
            isset($_GET['success'])
        ): ?>


            <div class="alert alert-success">


                <?php

                if (
                    $_GET['success']
                    === 'tambah'
                ) {

                    echo
                    "Program studi berhasil ditambahkan.";

                }

                elseif (
                    $_GET['success']
                    === 'update'
                ) {

                    echo
                    "Program studi berhasil diperbarui.";

                }

                elseif (
                    $_GET['success']
                    === 'hapus'
                ) {

                    echo
                    "Program studi berhasil dihapus.";

                }

                ?>


            </div>


        <?php endif; ?>



        <?php if (
            isset($_GET['error'])
        ): ?>


            <div class="alert alert-error">


                <?php

                if (
                    $_GET['error']
                    === 'kode'
                ) {

                    echo
                    "Kode program studi sudah digunakan.";

                }

                elseif (
                    $_GET['error']
                    === 'digunakan'
                ) {

                    echo
                    "Program studi tidak dapat dihapus karena masih memiliki mahasiswa.";

                }

                elseif (
                    $_GET['error']
                    === 'digunakan_pengguna'
                ) {

                    echo
                    "Program studi tidak dapat dihapus karena masih digunakan oleh akun pengguna.";

                }

                else {

                    echo
                    "Terjadi kesalahan.";

                }

                ?>


            </div>


        <?php endif; ?>



        <!-- =================================================
             STATISTICS
        ================================================= -->

        <div class="stats">


            <div class="stat-card">


                <div>

                    <p>
                        Total Program Studi
                    </p>

                    <h3>

                        <?php
                        echo number_format(
                            $total_prodi
                        );
                        ?>

                    </h3>

                </div>


                <div class="stat-icon blue">

                    📚

                </div>


            </div>



            <div class="stat-card">


                <div>

                    <p>
                        Total Fakultas
                    </p>

                    <h3>

                        <?php
                        echo number_format(
                            $total_fakultas
                        );
                        ?>

                    </h3>

                </div>


                <div class="stat-icon orange">

                    🏛

                </div>


            </div>



            <div class="stat-card">


                <div>

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


                <div class="stat-icon green">

                    👨‍🎓

                </div>


            </div>


        </div>



        <!-- =================================================
             FILTER
        ================================================= -->

        <div class="filter-box">


            <form
                method="GET"
                class="filter-form"
            >


                <input
                    type="text"
                    name="cari"
                    class="form-control"

                    placeholder="
                        Cari kode atau nama program studi...
                    "

                    value="<?php
                        echo htmlspecialchars(
                            $keyword
                        );
                    ?>"
                >


                <select
                    name="fakultas"
                    class="form-control"
                >


                    <option value="">

                        Semua Fakultas

                    </option>


                    <?php

                    $fakultas_filter =
                        $conn->query("
                            SELECT
                                kode_fakultas,
                                nama_fakultas
                            FROM fakultas
                            ORDER BY nama_fakultas
                        ");

                    while (
                        $f =
                        $fakultas_filter
                        ->fetch_assoc()
                    ):

                    ?>


                        <option
                            value="<?php
                                echo htmlspecialchars(
                                    $f['kode_fakultas']
                                );
                            ?>"

                            <?php

                            if (
                                $filter_fakultas
                                ===
                                $f['kode_fakultas']
                            ) {

                                echo 'selected';

                            }

                            ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $f['nama_fakultas']
                            );
                            ?>

                        </option>


                    <?php endwhile; ?>


                </select>


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    Cari

                </button>


                <a
                    href="program_studi.php"
                    class="btn btn-cancel"
                >

                    Reset

                </a>


            </form>


        </div>



        <!-- =================================================
             TABLE
        ================================================= -->

        <div class="table-container">


            <div class="table-wrapper">


                <table>


                    <thead>


                        <tr>

                            <th>
                                No
                            </th>

                            <th>
                                Kode Prodi
                            </th>

                            <th>
                                Nama Program Studi
                            </th>

                            <th>
                                Kode Fakultas
                            </th>

                            <th>
                                Fakultas
                            </th>

                            <th>
                                Jumlah Mahasiswa
                            </th>

                            <th>
                                Aksi
                            </th>

                        </tr>


                    </thead>


                    <tbody>


                    <?php

                    if (
                        $result_prodi &&
                        $result_prodi->num_rows > 0
                    ):

                        $no = 1;

                        while (
                            $row =
                            $result_prodi
                            ->fetch_assoc()
                        ):

                    ?>


                        <tr>


                            <td>

                                <?php
                                echo $no++;
                                ?>

                            </td>


                            <td>

                                <span
                                    class="badge badge-blue"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $row['kode_prodi']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['nama_prodi']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['kode_fakultas']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['nama_fakultas']
                                    ?? '-'
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo number_format(
                                    $row[
                                        'jumlah_mahasiswa'
                                    ]
                                );
                                ?>

                                Mahasiswa

                            </td>


                            <td>


                                <a
                                    href="
                                        program_studi.php?edit=<?php
                                            echo urlencode(
                                                $row[
                                                    'kode_prodi'
                                                ]
                                            );
                                        ?>
                                    "
                                    class="btn btn-edit"
                                >

                                    Edit

                                </a>


                                <a
                                    href="
                                        program_studi.php?hapus=<?php
                                            echo urlencode(
                                                $row[
                                                    'kode_prodi'
                                                ]
                                            );
                                        ?>
                                    "
                                    class="btn btn-delete"

                                    onclick="
                                        return confirm(
                                            'Apakah Anda yakin ingin menghapus program studi ini?'
                                        );
                                    "
                                >

                                    Hapus

                                </a>


                            </td>


                        </tr>


                    <?php

                        endwhile;

                    else:

                    ?>


                        <tr>

                            <td
                                colspan="7"
                                class="empty"
                            >

                                Data program studi
                                tidak ditemukan.

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>


    </section>


</main>



<!-- =====================================================
     MODAL TAMBAH
===================================================== -->

<div
    id="modalTambah"
    class="modal"
>


    <div class="modal-content">


        <div class="modal-header">


            <h3>
                Tambah Program Studi
            </h3>


            <span
                class="close"
                onclick="tutupModalTambah()"
            >

                &times;

            </span>


        </div>



        <form
            method="POST"
        >


            <div class="form-group">


                <label>
                    Kode Program Studi
                </label>


                <input
                    type="text"
                    name="kode_prodi"
                    class="form-control"
                    placeholder="Contoh: TI"
                    required
                >


            </div>



            <div class="form-group">


                <label>
                    Nama Program Studi
                </label>


                <input
                    type="text"
                    name="nama_prodi"
                    class="form-control"
                    placeholder="
                        Contoh: Teknik Informatika
                    "
                    required
                >


            </div>



            <div class="form-group">


                <label>
                    Fakultas
                </label>


                <select
                    name="kode_fakultas"
                    class="form-control"
                    required
                >


                    <option value="">

                        Pilih Fakultas

                    </option>


                    <?php

                    $fakultas_form =
                        $conn->query("
                            SELECT
                                kode_fakultas,
                                nama_fakultas
                            FROM fakultas
                            ORDER BY nama_fakultas
                        ");

                    while (
                        $f =
                        $fakultas_form
                        ->fetch_assoc()
                    ):

                    ?>


                        <option
                            value="<?php
                                echo htmlspecialchars(
                                    $f[
                                        'kode_fakultas'
                                    ]
                                );
                            ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $f[
                                    'nama_fakultas'
                                ]
                            );
                            ?>

                        </option>


                    <?php endwhile; ?>


                </select>


            </div>



            <div class="form-actions">


                <button
                    type="button"
                    class="btn btn-cancel"
                    onclick="
                        tutupModalTambah()
                    "
                >

                    Batal

                </button>


                <button
                    type="submit"
                    name="tambah"
                    class="btn btn-primary"
                >

                    Simpan

                </button>


            </div>


        </form>


    </div>

</div>



<!-- =====================================================
     MODAL EDIT
===================================================== -->

<?php if ($data_edit): ?>


<div
    id="modalEdit"
    class="modal"
    style="display:block;"
>


    <div class="modal-content">


        <div class="modal-header">


            <h3>
                Edit Program Studi
            </h3>


            <a
                href="program_studi.php"
                class="close"
            >

                &times;

            </a>


        </div>



        <form
            method="POST"
        >


            <div class="form-group">


                <label>
                    Kode Program Studi
                </label>


                <input
                    type="text"
                    name="kode_prodi"
                    class="form-control"

                    value="<?php
                        echo htmlspecialchars(
                            $data_edit[
                                'kode_prodi'
                            ]
                        );
                    ?>"

                    readonly
                >


            </div>



            <div class="form-group">


                <label>
                    Nama Program Studi
                </label>


                <input
                    type="text"
                    name="nama_prodi"
                    class="form-control"

                    value="<?php
                        echo htmlspecialchars(
                            $data_edit[
                                'nama_prodi'
                            ]
                        );
                    ?>"

                    required
                >


            </div>



            <div class="form-group">


                <label>
                    Fakultas
                </label>


                <select
                    name="kode_fakultas"
                    class="form-control"
                    required
                >


                    <?php

                    $fakultas_edit =
                        $conn->query("
                            SELECT
                                kode_fakultas,
                                nama_fakultas
                            FROM fakultas
                            ORDER BY nama_fakultas
                        ");

                    while (
                        $f =
                        $fakultas_edit
                        ->fetch_assoc()
                    ):

                    ?>


                        <option
                            value="<?php
                                echo htmlspecialchars(
                                    $f[
                                        'kode_fakultas'
                                    ]
                                );
                            ?>"

                            <?php

                            if (
                                $data_edit[
                                    'kode_fakultas'
                                ]
                                ===
                                $f[
                                    'kode_fakultas'
                                ]
                            ) {

                                echo 'selected';

                            }

                            ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $f[
                                    'nama_fakultas'
                                ]
                            );
                            ?>

                        </option>


                    <?php endwhile; ?>


                </select>


            </div>



            <div class="form-actions">


                <a
                    href="program_studi.php"
                    class="btn btn-cancel"
                >

                    Batal

                </a>


                <button
                    type="submit"
                    name="update"
                    class="btn btn-primary"
                >

                    Simpan Perubahan

                </button>


            </div>


        </form>


    </div>

</div>


<?php endif; ?>



<script>

function bukaModalTambah()
{
    document.getElementById(
        "modalTambah"
    ).style.display = "block";
}


function tutupModalTambah()
{
    document.getElementById(
        "modalTambah"
    ).style.display = "none";
}


window.onclick = function(event)
{
    const modal =
        document.getElementById(
            "modalTambah"
        );

    if (event.target === modal) {

        modal.style.display = "none";

    }
}

</script>


</body>

</html>