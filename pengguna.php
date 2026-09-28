<?php
session_start();
require_once 'koneksi.php';

/* =========================
   CEK LOGIN
========================= */
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: login.php");
    exit;
}

/* =========================
   KHUSUS ADMINISTRATOR
========================= */
if ($_SESSION['role'] !== 'administrator') {
    header("Location: dashboard_prodi.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Administrator';
$inisial = strtoupper(substr($nama_lengkap, 0, 1));

$pesan = '';
$tipe_pesan = '';

/* =========================
   TAMBAH PENGGUNA
========================= */
if (isset($_POST['tambah'])) {

    $username     = trim($_POST['username'] ?? '');
    $nama_lengkap_baru = trim($_POST['nama_lengkap'] ?? '');
    $password     = $_POST['password'] ?? '';
    $role         = $_POST['role'] ?? '';
    $kode_prodi   = trim($_POST['kode_prodi'] ?? '');
    $status       = $_POST['status'] ?? 'aktif';

    if (
        $username === '' ||
        $nama_lengkap_baru === '' ||
        $password === '' ||
        $role === ''
    ) {
        $pesan = "Semua data wajib diisi.";
        $tipe_pesan = "error";

    } elseif (!in_array($role, ['administrator', 'prodi'])) {

        $pesan = "Role pengguna tidak valid.";
        $tipe_pesan = "error";

    } elseif (!in_array($status, ['aktif', 'nonaktif'])) {

        $pesan = "Status pengguna tidak valid.";
        $tipe_pesan = "error";

    } elseif (strlen($password) < 6) {

        $pesan = "Password minimal 6 karakter.";
        $tipe_pesan = "error";

    } elseif ($role === 'prodi' && $kode_prodi === '') {

        $pesan = "Pengguna prodi wajib memilih program studi.";
        $tipe_pesan = "error";

    } else {

        // Administrator tidak terikat program studi
        if ($role === 'administrator') {
            $kode_prodi = null;
        }

        // Cek username
        $cek = $conn->prepare(
            "SELECT id_pengguna
             FROM pengguna
             WHERE username = ?
             LIMIT 1"
        );

        $cek->bind_param("s", $username);
        $cek->execute();

        $hasil_cek = $cek->get_result();

        if ($hasil_cek->num_rows > 0) {

            $pesan = "Username sudah digunakan.";
            $tipe_pesan = "error";

        } else {

            // Jika role prodi, cek kode prodi
            if ($role === 'prodi') {

                $cek_prodi = $conn->prepare(
                    "SELECT kode_prodi
                     FROM program_studi
                     WHERE kode_prodi = ?
                     LIMIT 1"
                );

                $cek_prodi->bind_param("s", $kode_prodi);
                $cek_prodi->execute();

                $hasil_prodi = $cek_prodi->get_result();

                if ($hasil_prodi->num_rows === 0) {

                    $pesan = "Program studi yang dipilih tidak ditemukan.";
                    $tipe_pesan = "error";

                }

                $cek_prodi->close();
            }

            if ($pesan === '') {

                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO pengguna
                    (
                        username,
                        password,
                        nama_lengkap,
                        role,
                        kode_prodi,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssssss",
                    $username,
                    $password_hash,
                    $nama_lengkap_baru,
                    $role,
                    $kode_prodi,
                    $status
                );

                if ($stmt->execute()) {

                    $pesan = "Pengguna berhasil ditambahkan.";
                    $tipe_pesan = "success";

                } else {

                    $pesan = "Gagal menambahkan pengguna.";
                    $tipe_pesan = "error";

                }

                $stmt->close();
            }
        }

        $cek->close();
    }
}


/* =========================
   UPDATE PENGGUNA
========================= */
if (isset($_POST['update'])) {

    $id_pengguna = (int)($_POST['id_pengguna'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $nama_lengkap_baru = trim($_POST['nama_lengkap'] ?? '');
    $role = $_POST['role'] ?? '';
    $kode_prodi = trim($_POST['kode_prodi'] ?? '');
    $status = $_POST['status'] ?? 'aktif';
    $password_baru = $_POST['password'] ?? '';

    if (
        $id_pengguna <= 0 ||
        $username === '' ||
        $nama_lengkap_baru === '' ||
        $role === ''
    ) {

        $pesan = "Data pengguna belum lengkap.";
        $tipe_pesan = "error";

    } elseif (!in_array($role, ['administrator', 'prodi'])) {

        $pesan = "Role tidak valid.";
        $tipe_pesan = "error";

    } elseif (!in_array($status, ['aktif', 'nonaktif'])) {

        $pesan = "Status tidak valid.";
        $tipe_pesan = "error";

    } elseif ($role === 'prodi' && $kode_prodi === '') {

        $pesan = "Pengguna prodi wajib memiliki program studi.";
        $tipe_pesan = "error";

    } else {

        if ($role === 'administrator') {
            $kode_prodi = null;
        }

        /*
         * Cek username milik pengguna lain
         */
        $cek = $conn->prepare(
            "SELECT id_pengguna
             FROM pengguna
             WHERE username = ?
             AND id_pengguna <> ?
             LIMIT 1"
        );

        $cek->bind_param(
            "si",
            $username,
            $id_pengguna
        );

        $cek->execute();

        $hasil_cek = $cek->get_result();

        if ($hasil_cek->num_rows > 0) {

            $pesan = "Username sudah digunakan oleh pengguna lain.";
            $tipe_pesan = "error";

        } else {

            /*
             * Jika password diisi, password ikut diperbarui.
             */
            if ($password_baru !== '') {

                if (strlen($password_baru) < 6) {

                    $pesan = "Password baru minimal 6 karakter.";
                    $tipe_pesan = "error";

                } else {

                    $password_hash = password_hash(
                        $password_baru,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $conn->prepare(
                        "UPDATE pengguna
                         SET username = ?,
                             password = ?,
                             nama_lengkap = ?,
                             role = ?,
                             kode_prodi = ?,
                             status = ?
                         WHERE id_pengguna = ?"
                    );

                    $stmt->bind_param(
                        "ssssssi",
                        $username,
                        $password_hash,
                        $nama_lengkap_baru,
                        $role,
                        $kode_prodi,
                        $status,
                        $id_pengguna
                    );

                    if ($stmt->execute()) {

                        $pesan = "Data pengguna berhasil diperbarui.";
                        $tipe_pesan = "success";

                    } else {

                        $pesan = "Gagal memperbarui pengguna.";
                        $tipe_pesan = "error";

                    }

                    $stmt->close();
                }

            } else {

                $stmt = $conn->prepare(
                    "UPDATE pengguna
                     SET username = ?,
                         nama_lengkap = ?,
                         role = ?,
                         kode_prodi = ?,
                         status = ?
                     WHERE id_pengguna = ?"
                );

                $stmt->bind_param(
                    "sssssi",
                    $username,
                    $nama_lengkap_baru,
                    $role,
                    $kode_prodi,
                    $status,
                    $id_pengguna
                );

                if ($stmt->execute()) {

                    $pesan = "Data pengguna berhasil diperbarui.";
                    $tipe_pesan = "success";

                } else {

                    $pesan = "Gagal memperbarui pengguna.";
                    $tipe_pesan = "error";

                }

                $stmt->close();
            }
        }

        $cek->close();
    }
}


/* =========================
   HAPUS PENGGUNA
========================= */
if (isset($_GET['hapus'])) {

    $id_hapus = (int)$_GET['hapus'];

    /*
     * Administrator tidak boleh menghapus
     * akun yang sedang digunakan.
     */
    if ($id_hapus === (int)$_SESSION['id_pengguna']) {

        $pesan = "Akun yang sedang digunakan tidak dapat dihapus.";
        $tipe_pesan = "error";

    } else {

        $stmt = $conn->prepare(
            "DELETE FROM pengguna
             WHERE id_pengguna = ?"
        );

        $stmt->bind_param("i", $id_hapus);

        if ($stmt->execute()) {

            $pesan = "Pengguna berhasil dihapus.";
            $tipe_pesan = "success";

        } else {

            $pesan = "Gagal menghapus pengguna.";
            $tipe_pesan = "error";

        }

        $stmt->close();
    }
}


/* =========================
   EDIT DATA
========================= */
$data_edit = null;

if (isset($_GET['edit'])) {

    $id_edit = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT
            id_pengguna,
            username,
            nama_lengkap,
            role,
            kode_prodi,
            status
         FROM pengguna
         WHERE id_pengguna = ?
         LIMIT 1"
    );

    $stmt->bind_param("i", $id_edit);
    $stmt->execute();

    $data_edit = $stmt->get_result()->fetch_assoc();

    $stmt->close();
}


/* =========================
   SEARCH & FILTER
========================= */
$keyword = trim($_GET['keyword'] ?? '');
$filter_role = $_GET['role'] ?? '';

$query = "
    SELECT
        p.id_pengguna,
        p.username,
        p.nama_lengkap,
        p.role,
        p.kode_prodi,
        p.status,
        p.created_at,
        ps.nama_prodi
    FROM pengguna p
    LEFT JOIN program_studi ps
        ON p.kode_prodi = ps.kode_prodi
    WHERE 1=1
";

$params = [];
$types = "";

if ($keyword !== '') {

    $query .= "
        AND (
            p.username LIKE ?
            OR p.nama_lengkap LIKE ?
            OR p.kode_prodi LIKE ?
            OR ps.nama_prodi LIKE ?
        )
    ";

    $search = "%" . $keyword . "%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;

    $types .= "ssss";
}

if (
    $filter_role !== '' &&
    in_array($filter_role, ['administrator', 'prodi'])
) {

    $query .= " AND p.role = ? ";

    $params[] = $filter_role;
    $types .= "s";
}

$query .= " ORDER BY p.id_pengguna DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$data_pengguna = $stmt->get_result();


/* =========================
   DATA PROGRAM STUDI
========================= */
$data_prodi = $conn->query(
    "SELECT kode_prodi, nama_prodi
     FROM program_studi
     ORDER BY nama_prodi ASC"
);


/* =========================
   STATISTIK
========================= */

$total_pengguna = 0;
$total_admin = 0;
$total_prodi_user = 0;
$total_aktif = 0;

$stat = $conn->query(
    "SELECT
        COUNT(*) AS total,
        SUM(role = 'administrator') AS admin,
        SUM(role = 'prodi') AS prodi_user,
        SUM(status = 'aktif') AS aktif
     FROM pengguna"
);

if ($stat) {

    $row = $stat->fetch_assoc();

    $total_pengguna = (int)$row['total'];
    $total_admin = (int)$row['admin'];
    $total_prodi_user = (int)$row['prodi_user'];
    $total_aktif = (int)$row['aktif'];
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Pengguna - Universitas Semantik
</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: #f5f7fb;
    color: #1e293b;
}

/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: #0f3d75;
    color: white;
    padding: 25px 15px;
    z-index: 1000;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 12px 30px;
}

.logo-icon {
    width: 45px;
    height: 45px;
    background: white;
    color: #0f3d75;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    font-weight: 800;
}

.logo-text h2 {
    font-size: 17px;
}

.logo-text span {
    font-size: 11px;
    opacity: .75;
}

.menu-title {
    font-size: 11px;
    text-transform: uppercase;
    opacity: .55;
    margin: 10px 12px;
}

.menu {
    list-style: none;
}

.menu li {
    margin-bottom: 5px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: rgba(255,255,255,.78);
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 14px;
    transition: .2s;
}

.menu a:hover,
.menu a.active {
    background: rgba(255,255,255,.13);
    color: white;
}

.menu-icon {
    width: 22px;
    text-align: center;
}

.logout {
    position: absolute;
    left: 15px;
    right: 15px;
    bottom: 20px;
}

.logout a {
    color: #fecaca;
}

/* =========================
   MAIN
========================= */

.main {
    margin-left: 250px;
    min-height: 100vh;
}

/* =========================
   TOPBAR
========================= */

.topbar {
    height: 75px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 35px;
}

.page-title h1 {
    font-size: 22px;
    color: #0f172a;
}

.page-title p {
    font-size: 12px;
    color: #64748b;
    margin-top: 3px;
}

.profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    width: 40px;
    height: 40px;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.profile-info strong {
    display: block;
    font-size: 13px;
}

.profile-info span {
    display: block;
    font-size: 11px;
    color: #64748b;
}

/* =========================
   CONTENT
========================= */

.content {
    padding: 30px 35px;
}

/* =========================
   ALERT
========================= */

.alert {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 13px;
}

.alert.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* =========================
   STATS
========================= */

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stat-left span {
    color: #64748b;
    font-size: 12px;
}

.stat-left h2 {
    margin-top: 5px;
    font-size: 25px;
    color: #0f172a;
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

/* =========================
   TOOLBAR
========================= */

.toolbar {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px 14px 0 0;
    padding: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.filter-area {
    display: flex;
    gap: 8px;
    flex: 1;
    max-width: 650px;
}

.search-form {
    display: flex;
    gap: 8px;
    flex: 1;
}

.search-form input,
.search-form select {
    border: 1px solid #dbe1ea;
    border-radius: 8px;
    padding: 10px 13px;
    outline: none;
    font-size: 13px;
    font-family: inherit;
}

.search-form input {
    flex: 1;
}

.search-form input:focus,
.search-form select:focus {
    border-color: #2563eb;
}

.btn {
    border: none;
    border-radius: 8px;
    padding: 10px 15px;
    cursor: pointer;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
}

.btn-search {
    background: #0f3d75;
    color: white;
}

.btn-add {
    background: #2563eb;
    color: white;
}

.btn-edit {
    background: #fef3c7;
    color: #92400e;
}

.btn-delete {
    background: #fee2e2;
    color: #b91c1c;
}

.btn-secondary {
    background: #f1f5f9;
    color: #334155;
}

/* =========================
   TABLE
========================= */

.table-wrapper {
    background: white;
    border: 1px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 14px 14px;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}

thead {
    background: #f8fafc;
}

th {
    text-align: left;
    padding: 14px 18px;
    font-size: 11px;
    text-transform: uppercase;
    color: #64748b;
    border-bottom: 1px solid #e5e7eb;
}

td {
    padding: 15px 18px;
    border-bottom: 1px solid #eef2f7;
    font-size: 13px;
}

tbody tr:hover {
    background: #f8fafc;
}

.username {
    font-weight: 700;
    color: #2563eb;
}

.role {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.role.admin {
    background: #ede9fe;
    color: #6d28d9;
}

.role.prodi {
    background: #dbeafe;
    color: #1d4ed8;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status.aktif {
    background: #dcfce7;
    color: #166534;
}

.status.nonaktif {
    background: #fee2e2;
    color: #991b1b;
}

.actions {
    display: flex;
    gap: 6px;
}

.empty {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
}

/* =========================
   MODAL
========================= */

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.55);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.show {
    display: flex;
}

.modal-box {
    width: 100%;
    max-width: 550px;
    max-height: 90vh;
    overflow-y: auto;
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 20px 50px rgba(0,0,0,.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h2 {
    font-size: 18px;
}

.close {
    border: none;
    background: #f1f5f9;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 7px;
    color: #334155;
}

.form-group input,
.form-group select {
    width: 100%;
    border: 1px solid #dbe1ea;
    border-radius: 8px;
    padding: 11px 12px;
    outline: none;
    font-family: inherit;
    background: white;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #2563eb;
}

.help {
    display: block;
    font-size: 11px;
    color: #64748b;
    margin-top: 5px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 20px;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 900px) {

    .sidebar {
        width: 220px;
    }

    .main {
        margin-left: 220px;
    }

    .content {
        padding: 25px 20px;
    }

    .toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-area {
        max-width: none;
    }
}

@media (max-width: 700px) {

    .sidebar {
        position: static;
        width: 100%;
        height: auto;
    }

    .logout {
        position: static;
        margin-top: 15px;
    }

    .main {
        margin-left: 0;
    }

    .topbar {
        padding: 15px 20px;
        height: auto;
    }

    .profile-info {
        display: none;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .filter-area {
        flex-direction: column;
    }

    .search-form {
        flex-direction: column;
    }
}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">

        <div class="logo-icon">
            US
        </div>

        <div class="logo-text">

            <h2>Universitas</h2>

            <span>
                Semantik
            </span>

        </div>

    </div>


    <div class="menu-title">
        Menu Utama
    </div>


    <ul class="menu">

        <li>
            <a href="dashboard.php">
                <span class="menu-icon">⌂</span>
                Dashboard
            </a>
        </li>

        <li>
            <a href="mahasiswa.php">
                <span class="menu-icon">👨‍🎓</span>
                Mahasiswa
            </a>
        </li>

        <li>
            <a href="program_studi.php">
                <span class="menu-icon">▣</span>
                Program Studi
            </a>
        </li>

        <li>
            <a href="fakultas.php">
                <span class="menu-icon">🏛</span>
                Fakultas
            </a>
        </li>

        <li>
            <a href="pengguna.php" class="active">
                <span class="menu-icon">👥</span>
                Pengguna
            </a>
        </li>

    </ul>


    <div class="logout">

        <a
            href="logout.php"
            onclick="return confirm('Apakah Anda yakin ingin keluar?');"
        >

            <span class="menu-icon">↪</span>

            Keluar

        </a>

    </div>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">

        <div class="page-title">

            <h1>
                Pengguna
            </h1>

            <p>
                Kelola akun administrator dan program studi
            </p>

        </div>


        <div class="profile">

            <div class="avatar">

                <?= htmlspecialchars($inisial) ?>

            </div>


            <div class="profile-info">

                <strong>
                    <?= htmlspecialchars($nama_lengkap) ?>
                </strong>

                <span>
                    Administrator
                </span>

            </div>

        </div>

    </header>


    <!-- CONTENT -->

    <section class="content">


        <!-- ALERT -->

        <?php if ($pesan !== ''): ?>

            <div class="alert <?= $tipe_pesan ?>">

                <?= htmlspecialchars($pesan) ?>

            </div>

        <?php endif; ?>


        <!-- STATS -->

        <div class="stats">


            <div class="stat-card">

                <div class="stat-left">

                    <span>
                        Total Pengguna
                    </span>

                    <h2>
                        <?= $total_pengguna ?>
                    </h2>

                </div>

                <div class="stat-icon">
                    👥
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-left">

                    <span>
                        Administrator
                    </span>

                    <h2>
                        <?= $total_admin ?>
                    </h2>

                </div>

                <div class="stat-icon">
                    🛡
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-left">

                    <span>
                        Pengguna Prodi
                    </span>

                    <h2>
                        <?= $total_prodi_user ?>
                    </h2>

                </div>

                <div class="stat-icon">
                    🎓
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-left">

                    <span>
                        Pengguna Aktif
                    </span>

                    <h2>
                        <?= $total_aktif ?>
                    </h2>

                </div>

                <div class="stat-icon">
                    ✓
                </div>

            </div>

        </div>


        <!-- TOOLBAR -->

        <div class="toolbar">


            <div class="filter-area">

                <form
                    method="GET"
                    class="search-form"
                >

                    <input
                        type="text"
                        name="keyword"
                        placeholder="Cari username, nama, atau prodi..."
                        value="<?= htmlspecialchars($keyword) ?>"
                    >


                    <select name="role">

                        <option value="">
                            Semua Role
                        </option>

                        <option
                            value="administrator"
                            <?= $filter_role === 'administrator' ? 'selected' : '' ?>
                        >
                            Administrator
                        </option>

                        <option
                            value="prodi"
                            <?= $filter_role === 'prodi' ? 'selected' : '' ?>
                        >
                            Prodi
                        </option>

                    </select>


                    <button
                        type="submit"
                        class="btn btn-search"
                    >
                        🔍 Cari
                    </button>

                </form>

            </div>


            <button
                type="button"
                class="btn btn-add"
                onclick="bukaModalTambah()"
            >
                + Tambah Pengguna
            </button>

        </div>


        <!-- TABLE -->

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            No
                        </th>

                        <th>
                            Username
                        </th>

                        <th>
                            Nama Lengkap
                        </th>

                        <th>
                            Role
                        </th>

                        <th>
                            Program Studi
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($data_pengguna && $data_pengguna->num_rows > 0): ?>

                    <?php
                    $no = 1;

                    while ($row = $data_pengguna->fetch_assoc()):
                    ?>

                        <tr>

                            <td>
                                <?= $no++ ?>
                            </td>


                            <td>

                                <span class="username">
                                    <?= htmlspecialchars($row['username']) ?>
                                </span>

                            </td>


                            <td>

                                <strong>
                                    <?= htmlspecialchars($row['nama_lengkap']) ?>
                                </strong>

                            </td>


                            <td>

                                <?php if ($row['role'] === 'administrator'): ?>

                                    <span class="role admin">
                                        Administrator
                                    </span>

                                <?php else: ?>

                                    <span class="role prodi">
                                        Prodi
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php if ($row['role'] === 'administrator'): ?>

                                    <span style="color:#94a3b8;">
                                        Semua Prodi
                                    </span>

                                <?php else: ?>

                                    <strong>
                                        <?= htmlspecialchars($row['nama_prodi'] ?? '-') ?>
                                    </strong>

                                    <?php if (!empty($row['kode_prodi'])): ?>

                                        <br>

                                        <small style="color:#64748b;">
                                            <?= htmlspecialchars($row['kode_prodi']) ?>
                                        </small>

                                    <?php endif; ?>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span class="status <?= $row['status'] ?>">

                                    <?= ucfirst($row['status']) ?>

                                </span>

                            </td>


                            <td>

                                <div class="actions">

                                    <a
                                        href="?edit=<?= (int)$row['id_pengguna'] ?>"
                                        class="btn btn-edit"
                                    >
                                        ✎ Edit
                                    </a>


                                    <?php if (
                                        (int)$row['id_pengguna']
                                        !==
                                        (int)$_SESSION['id_pengguna']
                                    ): ?>

                                        <a
                                            href="?hapus=<?= (int)$row['id_pengguna'] ?>"
                                            class="btn btn-delete"
                                            onclick="return confirm(
                                                'Apakah Anda yakin ingin menghapus pengguna ini?'
                                            );"
                                        >
                                            🗑 Hapus
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            class="empty"
                        >
                            Data pengguna belum tersedia.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>


<!-- =========================
     MODAL TAMBAH
========================= -->

<div
    class="modal"
    id="modalTambah"
>

    <div class="modal-box">


        <div class="modal-header">

            <h2>
                Tambah Pengguna
            </h2>

            <button
                type="button"
                class="close"
                onclick="tutupModalTambah()"
            >
                ×
            </button>

        </div>


        <form method="POST">


            <div class="form-group">

                <label>
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    maxlength="50"
                    placeholder="Masukkan username"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Nama Lengkap
                </label>

                <input
                    type="text"
                    name="nama_lengkap"
                    maxlength="100"
                    placeholder="Masukkan nama lengkap"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    minlength="6"
                    placeholder="Minimal 6 karakter"
                    required
                >

                <small class="help">
                    Password akan disimpan menggunakan password_hash().
                </small>

            </div>


            <div class="form-group">

                <label>
                    Role
                </label>

                <select
                    name="role"
                    id="roleTambah"
                    onchange="ubahRoleTambah()"
                    required
                >

                    <option value="">
                        Pilih Role
                    </option>

                    <option value="administrator">
                        Administrator
                    </option>

                    <option value="prodi">
                        Prodi
                    </option>

                </select>

            </div>


            <div
                class="form-group"
                id="prodiTambahGroup"
                style="display:none;"
            >

                <label>
                    Program Studi
                </label>

                <select name="kode_prodi">

                    <option value="">
                        Pilih Program Studi
                    </option>

                    <?php if ($data_prodi): ?>

                        <?php while ($prodi = $data_prodi->fetch_assoc()): ?>

                            <option
                                value="<?= htmlspecialchars($prodi['kode_prodi']) ?>"
                            >

                                <?= htmlspecialchars($prodi['kode_prodi']) ?>
                                -
                                <?= htmlspecialchars($prodi['nama_prodi']) ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

                <small class="help">
                    Pengguna Prodi hanya dapat mengelola data pada prodi ini.
                </small>

            </div>


            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <option value="aktif">
                        Aktif
                    </option>

                    <option value="nonaktif">
                        Nonaktif
                    </option>

                </select>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="tutupModalTambah()"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    name="tambah"
                    class="btn btn-add"
                >
                    Simpan Pengguna
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================
     MODAL EDIT
========================= -->

<?php if ($data_edit): ?>

<div
    class="modal show"
    id="modalEdit"
>

    <div class="modal-box">


        <div class="modal-header">

            <h2>
                Edit Pengguna
            </h2>

            <a
                href="pengguna.php"
                class="close"
                style="
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    text-decoration:none;
                    color:#334155;
                "
            >
                ×
            </a>

        </div>


        <form method="POST">


            <input
                type="hidden"
                name="id_pengguna"
                value="<?= (int)$data_edit['id_pengguna'] ?>"
            >


            <div class="form-group">

                <label>
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    maxlength="50"
                    value="<?= htmlspecialchars($data_edit['username']) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Nama Lengkap
                </label>

                <input
                    type="text"
                    name="nama_lengkap"
                    maxlength="100"
                    value="<?= htmlspecialchars($data_edit['nama_lengkap']) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Password Baru
                </label>

                <input
                    type="password"
                    name="password"
                    minlength="6"
                    placeholder="Kosongkan jika tidak ingin mengubah password"
                >

                <small class="help">
                    Kosongkan jika password lama tetap digunakan.
                </small>

            </div>


            <div class="form-group">

                <label>
                    Role
                </label>

                <select
                    name="role"
                    id="roleEdit"
                    onchange="ubahRoleEdit()"
                    required
                >

                    <option
                        value="administrator"
                        <?= $data_edit['role'] === 'administrator'
                            ? 'selected'
                            : '' ?>
                    >
                        Administrator
                    </option>

                    <option
                        value="prodi"
                        <?= $data_edit['role'] === 'prodi'
                            ? 'selected'
                            : '' ?>
                    >
                        Prodi
                    </option>

                </select>

            </div>


            <div
                class="form-group"
                id="prodiEditGroup"
                style="
                    display:
                    <?= $data_edit['role'] === 'prodi'
                        ? 'block'
                        : 'none' ?>;
                "
            >

                <label>
                    Program Studi
                </label>

                <select name="kode_prodi">

                    <option value="">
                        Pilih Program Studi
                    </option>

                    <?php

                    $prodi_edit_result = $conn->query(
                        "SELECT kode_prodi, nama_prodi
                         FROM program_studi
                         ORDER BY nama_prodi ASC"
                    );

                    if ($prodi_edit_result):

                        while (
                            $prodi_edit =
                            $prodi_edit_result->fetch_assoc()
                        ):
                    ?>

                        <option
                            value="<?= htmlspecialchars($prodi_edit['kode_prodi']) ?>"
                            <?= $data_edit['kode_prodi']
                                === $prodi_edit['kode_prodi']
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars($prodi_edit['kode_prodi']) ?>
                            -
                            <?= htmlspecialchars($prodi_edit['nama_prodi']) ?>

                        </option>

                    <?php
                        endwhile;
                    endif;
                    ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <option
                        value="aktif"
                        <?= $data_edit['status'] === 'aktif'
                            ? 'selected'
                            : '' ?>
                    >
                        Aktif
                    </option>

                    <option
                        value="nonaktif"
                        <?= $data_edit['status'] === 'nonaktif'
                            ? 'selected'
                            : '' ?>
                    >
                        Nonaktif
                    </option>

                </select>

            </div>


            <div class="modal-footer">

                <a
                    href="pengguna.php"
                    class="btn btn-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    name="update"
                    class="btn btn-add"
                >
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</div>

<?php endif; ?>


<script>

/* =========================
   MODAL TAMBAH
========================= */

function bukaModalTambah() {

    document
        .getElementById('modalTambah')
        .classList.add('show');

}


function tutupModalTambah() {

    document
        .getElementById('modalTambah')
        .classList.remove('show');

}


/* =========================
   ROLE TAMBAH
========================= */

function ubahRoleTambah() {

    const role =
        document.getElementById('roleTambah').value;

    const group =
        document.getElementById('prodiTambahGroup');

    if (role === 'prodi') {

        group.style.display = 'block';

    } else {

        group.style.display = 'none';

    }

}


/* =========================
   ROLE EDIT
========================= */

function ubahRoleEdit() {

    const role =
        document.getElementById('roleEdit').value;

    const group =
        document.getElementById('prodiEditGroup');

    if (role === 'prodi') {

        group.style.display = 'block';

    } else {

        group.style.display = 'none';

    }

}


/* =========================
   KLIK DI LUAR MODAL
========================= */

document.addEventListener('click', function(event) {

    const modal =
        document.getElementById('modalTambah');

    if (event.target === modal) {

        tutupModalTambah();

    }

});

</script>


</body>

</html>