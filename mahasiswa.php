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


// ======================================================
// PROSES TAMBAH DATA
// ======================================================
if (isset($_POST['tambah'])) {

    $npm            = trim($_POST['npm']);
    $nama           = trim($_POST['nama_mahasiswa']);
    $jenis_kelamin  = $_POST['jenis_kelamin'];
    $tempat_lahir   = trim($_POST['tempat_lahir']);
    $tanggal_lahir  = $_POST['tanggal_lahir'];
    $tanggal_masuk  = $_POST['tanggal_masuk'];
    $alamat         = trim($_POST['alamat']);
    $password       = $_POST['password'];
    $kode_prodi     = $_POST['kode_prodi'];

    // Validasi password
    if (strlen($password) < 6) {

        header("Location: mahasiswa.php?error=password");
        exit;

    }

    // Cek NPM sudah ada
    $cek = $conn->prepare("
        SELECT npm
        FROM mahasiswa
        WHERE npm = ?
        LIMIT 1
    ");

    $cek->bind_param("s", $npm);
    $cek->execute();

    $hasil_cek = $cek->get_result();

    if ($hasil_cek->num_rows > 0) {

        header("Location: mahasiswa.php?error=npm");
        exit;

    }

    // Hash password
    $password_hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $stmt = $conn->prepare("
        INSERT INTO mahasiswa
        (
            npm,
            nama_mahasiswa,
            jenis_kelamin,
            tempat_lahir,
            tanggal_lahir,
            tanggal_masuk,
            alamat,
            password,
            kode_prodi
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssssss",
        $npm,
        $nama,
        $jenis_kelamin,
        $tempat_lahir,
        $tanggal_lahir,
        $tanggal_masuk,
        $alamat,
        $password_hash,
        $kode_prodi
    );

    $stmt->execute();

    header("Location: mahasiswa.php?success=tambah");
    exit;
}


// ======================================================
// PROSES UPDATE DATA
// ======================================================
if (isset($_POST['update'])) {

    $npm            = trim($_POST['npm']);
    $nama           = trim($_POST['nama_mahasiswa']);
    $jenis_kelamin  = $_POST['jenis_kelamin'];
    $tempat_lahir   = trim($_POST['tempat_lahir']);
    $tanggal_lahir  = $_POST['tanggal_lahir'];
    $tanggal_masuk  = $_POST['tanggal_masuk'];
    $alamat         = trim($_POST['alamat']);
    $kode_prodi     = $_POST['kode_prodi'];
    $password       = $_POST['password'];

    if (!empty($password)) {

        if (strlen($password) < 6) {

            header("Location: mahasiswa.php?error=password");
            exit;

        }

        $password_hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare("
            UPDATE mahasiswa
            SET
                nama_mahasiswa = ?,
                jenis_kelamin = ?,
                tempat_lahir = ?,
                tanggal_lahir = ?,
                tanggal_masuk = ?,
                alamat = ?,
                password = ?,
                kode_prodi = ?
            WHERE npm = ?
        ");

        $stmt->bind_param(
            "sssssssss",
            $nama,
            $jenis_kelamin,
            $tempat_lahir,
            $tanggal_lahir,
            $tanggal_masuk,
            $alamat,
            $password_hash,
            $kode_prodi,
            $npm
        );

    } else {

        $stmt = $conn->prepare("
            UPDATE mahasiswa
            SET
                nama_mahasiswa = ?,
                jenis_kelamin = ?,
                tempat_lahir = ?,
                tanggal_lahir = ?,
                tanggal_masuk = ?,
                alamat = ?,
                kode_prodi = ?
            WHERE npm = ?
        ");

        $stmt->bind_param(
            "ssssssss",
            $nama,
            $jenis_kelamin,
            $tempat_lahir,
            $tanggal_lahir,
            $tanggal_masuk,
            $alamat,
            $kode_prodi,
            $npm
        );
    }

    $stmt->execute();

    header("Location: mahasiswa.php?success=update");
    exit;
}


// ======================================================
// PROSES HAPUS DATA
// ======================================================
if (isset($_GET['hapus'])) {

    $npm = $_GET['hapus'];

    $stmt = $conn->prepare("
        DELETE FROM mahasiswa
        WHERE npm = ?
    ");

    $stmt->bind_param("s", $npm);
    $stmt->execute();

    header("Location: mahasiswa.php?success=hapus");
    exit;
}


// ======================================================
// DATA EDIT
// ======================================================
$data_edit = null;

if (isset($_GET['edit'])) {

    $npm_edit = $_GET['edit'];

    $stmt = $conn->prepare("
        SELECT *
        FROM mahasiswa
        WHERE npm = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $npm_edit);
    $stmt->execute();

    $result_edit = $stmt->get_result();

    $data_edit = $result_edit->fetch_assoc();
}


// ======================================================
// PENCARIAN
// ======================================================
$keyword = '';

if (isset($_GET['cari'])) {
    $keyword = trim($_GET['cari']);
}


// ======================================================
// FILTER PRODI
// ======================================================
$filter_prodi = '';

if (isset($_GET['prodi'])) {
    $filter_prodi = trim($_GET['prodi']);
}


// ======================================================
// QUERY MAHASISWA
// ======================================================
$sql = "
    SELECT
        m.npm,
        m.nama_mahasiswa,
        m.jenis_kelamin,
        m.tempat_lahir,
        m.tanggal_lahir,
        m.tanggal_masuk,
        m.alamat,
        m.kode_prodi,
        p.nama_prodi,
        f.nama_fakultas
    FROM mahasiswa m

    LEFT JOIN program_studi p
        ON m.kode_prodi = p.kode_prodi

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
            m.npm LIKE ?
            OR m.nama_mahasiswa LIKE ?
        )
    ";

    $search = "%" . $keyword . "%";

    $params[] = $search;
    $params[] = $search;

    $types .= "ss";
}


// Filter prodi
if ($filter_prodi !== '') {

    $sql .= "
        AND m.kode_prodi = ?
    ";

    $params[] = $filter_prodi;

    $types .= "s";
}


$sql .= "
    ORDER BY m.nama_mahasiswa ASC
";


$stmt = $conn->prepare($sql);

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}

$stmt->execute();

$result_mahasiswa = $stmt->get_result();


// ======================================================
// DATA PROGRAM STUDI
// ======================================================
$result_prodi = $conn->query("
    SELECT
        p.kode_prodi,
        p.nama_prodi,
        f.nama_fakultas
    FROM program_studi p

    LEFT JOIN fakultas f
        ON p.kode_fakultas = f.kode_fakultas

    ORDER BY p.nama_prodi ASC
");


// ======================================================
// JUMLAH MAHASISWA
// ======================================================
$result_total = $conn->query("
    SELECT COUNT(*) AS total
    FROM mahasiswa
");

$total_mahasiswa =
    $result_total->fetch_assoc()['total'];


// ======================================================
// NAMA ADMIN
// ======================================================
$nama_lengkap =
    $_SESSION['nama_lengkap'] ?? 'Administrator';

$inisial =
    strtoupper(
        substr(
            $nama_lengkap,
            0,
            1
        )
    );

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Data Mahasiswa - Universitas Semantik
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

    border-bottom: 1px solid #e5e7eb;
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
   HEADER
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
   CARD TOTAL
===================================================== */

.total-card {
    background: white;

    padding: 20px;

    border-radius: 12px;

    margin-bottom: 20px;

    box-shadow:
        0 2px 10px rgba(0,0,0,0.05);
}

.total-card span {
    color: #6b7280;

    font-size: 13px;
}

.total-card strong {
    display: block;

    font-size: 25px;

    margin-top: 5px;

    color: #0d47a1;
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


/* =====================================================
   FILTER
===================================================== */

.filter-box {
    background: white;

    padding: 20px;

    border-radius: 12px;

    margin-bottom: 20px;

    box-shadow:
        0 2px 10px rgba(0,0,0,0.05);
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
        0 2px 10px rgba(0,0,0,0.05);
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;

    min-width: 1100px;
}

th {
    background: #f8fafc;

    padding: 14px;

    font-size: 12px;

    text-align: left;

    color: #374151;
}

td {
    padding: 13px 14px;

    border-top: 1px solid #f0f0f0;

    font-size: 12px;
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

.badge-l {
    background: #e3f2fd;

    color: #1565c0;
}

.badge-p {
    background: #fce4ec;

    color: #c2185b;
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

    background: rgba(0,0,0,0.5);

    padding: 30px;

    overflow-y: auto;
}

.modal-content {
    background: white;

    max-width: 650px;

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

textarea.form-control {
    resize: vertical;

    min-height: 80px;
}

.form-row {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 15px;
}

.form-actions {
    display: flex;

    justify-content: flex-end;

    gap: 10px;

    margin-top: 20px;
}

.btn-cancel {
    background: #e5e7eb;

    color: #374151;
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

    .filter-form {
        grid-template-columns: 1fr;
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
        align-items: flex-start;

        gap: 15px;

        flex-direction: column;
    }

    .user-text {
        display: none;
    }

    .form-row {
        grid-template-columns: 1fr;
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


        <a href="mahasiswa.php"
           class="active">

            <span>👨‍🎓</span>

            <span>
                Mahasiswa
            </span>

        </a>


        <a href="program_studi.php">

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

        <a href="logout.php"
           onclick="return confirm('Apakah Anda yakin ingin logout?');">

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
        Data Mahasiswa
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



<!-- =====================================================
     CONTENT
===================================================== -->

<section class="content">


<!-- PAGE HEADER -->

<div class="page-header">

    <div>

        <h2>
            Data Mahasiswa
        </h2>

        <p>
            Kelola seluruh data mahasiswa Universitas Semantik.
        </p>

    </div>


    <button
        class="btn btn-primary"
        onclick="bukaModalTambah()">

        + Tambah Mahasiswa

    </button>

</div>



<!-- =====================================================
     ALERT
===================================================== -->

<?php if (isset($_GET['success'])): ?>

    <div class="alert alert-success">

        <?php

        if ($_GET['success'] === 'tambah') {

            echo "Data mahasiswa berhasil ditambahkan.";

        } elseif ($_GET['success'] === 'update') {

            echo "Data mahasiswa berhasil diperbarui.";

        } elseif ($_GET['success'] === 'hapus') {

            echo "Data mahasiswa berhasil dihapus.";

        }

        ?>

    </div>

<?php endif; ?>


<?php if (isset($_GET['error'])): ?>

    <div class="alert alert-error">

        <?php

        if ($_GET['error'] === 'npm') {

            echo "NPM sudah terdaftar.";

        } elseif ($_GET['error'] === 'password') {

            echo "Password minimal 6 karakter.";

        } else {

            echo "Terjadi kesalahan.";

        }

        ?>

    </div>

<?php endif; ?>



<!-- =====================================================
     TOTAL
===================================================== -->

<div class="total-card">

    <span>
        Total Mahasiswa
    </span>

    <strong>
        <?php
        echo number_format(
            $total_mahasiswa
        );
        ?>
    </strong>

</div>



<!-- =====================================================
     FILTER
===================================================== -->

<div class="filter-box">

    <form
        method="GET"
        class="filter-form">


        <input
            type="text"
            name="cari"
            class="form-control"
            placeholder="Cari berdasarkan NPM atau nama..."
            value="<?php
                echo htmlspecialchars($keyword);
            ?>">


        <select
            name="prodi"
            class="form-control">

            <option value="">
                Semua Program Studi
            </option>


            <?php while (
                $prodi =
                $result_prodi->fetch_assoc()
            ): ?>

                <option
                    value="<?php
                        echo htmlspecialchars(
                            $prodi['kode_prodi']
                        );
                    ?>"
                    <?php

                    if (
                        $filter_prodi ===
                        $prodi['kode_prodi']
                    ) {

                        echo 'selected';

                    }

                    ?>
                >

                    <?php
                    echo htmlspecialchars(
                        $prodi['nama_prodi']
                    );
                    ?>

                </option>

            <?php endwhile; ?>

        </select>


        <button
            type="submit"
            class="btn btn-primary">

            Cari

        </button>


        <a
            href="mahasiswa.php"
            class="btn btn-cancel">

            Reset

        </a>


    </form>

</div>



<!-- =====================================================
     TABLE
===================================================== -->

<div class="table-container">

<div class="table-wrapper">

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

    <th>
        Program Studi
    </th>

    <th>
        Fakultas
    </th>

    <th>
        Aksi
    </th>

</tr>

</thead>


<tbody>


<?php

if (
    $result_mahasiswa &&
    $result_mahasiswa->num_rows > 0
):

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
    $row['jenis_kelamin'] === 'L'
):

?>

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


<td>

    <a
        href="mahasiswa.php?edit=<?php
            echo urlencode(
                $row['npm']
            );
        ?>"
        class="btn btn-edit">

        Edit

    </a>


    <a
        href="mahasiswa.php?hapus=<?php
            echo urlencode(
                $row['npm']
            );
        ?>"
        class="btn btn-delete"
        onclick="return confirm(
            'Apakah Anda yakin ingin menghapus mahasiswa ini?'
        );">

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
        colspan="10"
        style="text-align:center; padding:30px;">

        Data mahasiswa tidak ditemukan.

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
    class="modal">


<div class="modal-content">


<div class="modal-header">

    <h3>
        Tambah Mahasiswa
    </h3>

    <span
        class="close"
        onclick="tutupModalTambah()">

        &times;

    </span>

</div>


<form
    method="POST">


<div class="form-row">


<div class="form-group">

    <label>
        NPM
    </label>

    <input
        type="text"
        name="npm"
        class="form-control"
        required>

</div>


<div class="form-group">

    <label>
        Nama Mahasiswa
    </label>

    <input
        type="text"
        name="nama_mahasiswa"
        class="form-control"
        required>

</div>

</div>



<div class="form-row">


<div class="form-group">

    <label>
        Jenis Kelamin
    </label>

    <select
        name="jenis_kelamin"
        class="form-control"
        required>

        <option value="">
            Pilih Jenis Kelamin
        </option>

        <option value="L">
            Laki-laki
        </option>

        <option value="P">
            Perempuan
        </option>

    </select>

</div>


<div class="form-group">

    <label>
        Program Studi
    </label>

    <select
        name="kode_prodi"
        class="form-control"
        required>

        <option value="">
            Pilih Program Studi
        </option>


        <?php

        $prodi_form =
            $conn->query("
                SELECT
                    kode_prodi,
                    nama_prodi
                FROM program_studi
                ORDER BY nama_prodi
            ");

        while (
            $p =
            $prodi_form->fetch_assoc()
        ):

        ?>

            <option
                value="<?php
                    echo htmlspecialchars(
                        $p['kode_prodi']
                    );
                ?>">

                <?php
                echo htmlspecialchars(
                    $p['nama_prodi']
                );
                ?>

            </option>

        <?php endwhile; ?>

    </select>

</div>

</div>



<div class="form-row">


<div class="form-group">

    <label>
        Tempat Lahir
    </label>

    <input
        type="text"
        name="tempat_lahir"
        class="form-control"
        required>

</div>


<div class="form-group">

    <label>
        Tanggal Lahir
    </label>

    <input
        type="date"
        name="tanggal_lahir"
        class="form-control"
        required>

</div>

</div>



<div class="form-row">


<div class="form-group">

    <label>
        Tanggal Masuk
    </label>

    <input
        type="date"
        name="tanggal_masuk"
        class="form-control"
        required>

</div>


<div class="form-group">

    <label>
        Password
    </label>

    <input
        type="password"
        name="password"
        class="form-control"
        minlength="6"
        required>

</div>

</div>



<div class="form-group">

    <label>
        Alamat
    </label>

    <textarea
        name="alamat"
        class="form-control"
        required></textarea>

</div>



<div class="form-actions">

    <button
        type="button"
        class="btn btn-cancel"
        onclick="tutupModalTambah()">

        Batal

    </button>

    <button
        type="submit"
        name="tambah"
        class="btn btn-primary">

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
    style="display:block;">


<div class="modal-content">


<div class="modal-header">

    <h3>
        Edit Mahasiswa
    </h3>

    <a
        href="mahasiswa.php"
        class="close"
        style="text-decoration:none;">

        &times;

    </a>

</div>


<form
    method="POST">


<input
    type="hidden"
    name="npm"
    value="<?php
        echo htmlspecialchars(
            $data_edit['npm']
        );
    ?>">


<div class="form-row">


<div class="form-group">

    <label>
        NPM
    </label>

    <input
        type="text"
        class="form-control"
        value="<?php
            echo htmlspecialchars(
                $data_edit['npm']
            );
        ?>"
        readonly>

</div>


<div class="form-group">

    <label>
        Nama Mahasiswa
    </label>

    <input
        type="text"
        name="nama_mahasiswa"
        class="form-control"
        value="<?php
            echo htmlspecialchars(
                $data_edit['nama_mahasiswa']
            );
        ?>"
        required>

</div>

</div>



<div class="form-row">


<div class="form-group">

    <label>
        Jenis Kelamin
    </label>

    <select
        name="jenis_kelamin"
        class="form-control"
        required>

        <option
            value="L"
            <?php

            if (
                $data_edit['jenis_kelamin']
                === 'L'
            ) {

                echo 'selected';

            }

            ?>>

            Laki-laki

        </option>

        <option
            value="P"
            <?php

            if (
                $data_edit['jenis_kelamin']
                === 'P'
            ) {

                echo 'selected';

            }

            ?>>

            Perempuan

        </option>

    </select>

</div>


<div class="form-group">

    <label>
        Program Studi
    </label>

    <select
        name="kode_prodi"
        class="form-control"
        required>


        <?php

        $prodi_edit =
            $conn->query("
                SELECT
                    kode_prodi,
                    nama_prodi
                FROM program_studi
                ORDER BY nama_prodi
            ");

        while (
            $p =
            $prodi_edit->fetch_assoc()
        ):

        ?>

            <option
                value="<?php
                    echo htmlspecialchars(
                        $p['kode_prodi']
                    );
                ?>"

                <?php

                if (
                    $data_edit['kode_prodi']
                    === $p['kode_prodi']
                ) {

                    echo 'selected';

                }

                ?>>

                <?php
                echo htmlspecialchars(
                    $p['nama_prodi']
                );
                ?>

            </option>

        <?php endwhile; ?>

    </select>

</div>

</div>



<div class="form-row">


<div class="form-group">

    <label>
        Tempat Lahir
    </label>

    <input
        type="text"
        name="tempat_lahir"
        class="form-control"
        value="<?php
            echo htmlspecialchars(
                $data_edit['tempat_lahir']
            );
        ?>"
        required>

</div>


<div class="form-group">

    <label>
        Tanggal Lahir
    </label>

    <input
        type="date"
        name="tanggal_lahir"
        class="form-control"
        value="<?php
            echo htmlspecialchars(
                $data_edit['tanggal_lahir']
            );
        ?>"
        required>

</div>

</div>



<div class="form-row">


<div class="form-group">

    <label>
        Tanggal Masuk
    </label>

    <input
        type="date"
        name="tanggal_masuk"
        class="form-control"
        value="<?php
            echo htmlspecialchars(
                $data_edit['tanggal_masuk']
            );
        ?>"
        required>

</div>


<div class="form-group">

    <label>
        Password Baru
        <small style="color:#6b7280;">
            (kosongkan jika tidak diubah)
        </small>
    </label>

    <input
        type="password"
        name="password"
        class="form-control"
        minlength="6">

</div>

</div>



<div class="form-group">

    <label>
        Alamat
    </label>

    <textarea
        name="alamat"
        class="form-control"
        required><?php

        echo htmlspecialchars(
            $data_edit['alamat']
        );

        ?></textarea>

</div>



<div class="form-actions">

    <a
        href="mahasiswa.php"
        class="btn btn-cancel">

        Batal

    </a>

    <button
        type="submit"
        name="update"
        class="btn btn-primary">

        Simpan Perubahan

    </button>

</div>


</form>

</div>

</div>

<?php endif; ?>



<script>

function bukaModalTambah() {

    document.getElementById(
        "modalTambah"
    ).style.display = "block";

}


function tutupModalTambah() {

    document.getElementById(
        "modalTambah"
    ).style.display = "none";

}


window.onclick = function(event) {

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