<?php

include "../../config/koneksi.php";
include "../../config/auth.php";

wajib_login();

/*
|--------------------------------------------------------------------------
| HALAMAN KHUSUS PEMILIK
|--------------------------------------------------------------------------
*/

if (!punya_role('pemilik')) {

    header(
        "Location: ../../index.php?error=akses_ditolak"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}

$csrf_token =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| DATA USER
|--------------------------------------------------------------------------
*/

$query_user = mysqli_query(
    $conn,
    "
    SELECT
        id_user,
        username,
        nama_lengkap,
        level
    FROM users
    ORDER BY id_user DESC
    "
);


if (!$query_user) {

    die(
        "Gagal mengambil data user: "
        . mysqli_error($conn)
    );
}


/*
|--------------------------------------------------------------------------
| DATA LOGIN
|--------------------------------------------------------------------------
*/

$nama_lengkap =
    $_SESSION['nama_lengkap'] ?? 'User';

$level =
    $_SESSION['level'] ?? '';

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
        Tambah User - Toko Bangunan ASLI 2
    </title>


    <style>

        /* ==================================================
           RESET
           ================================================== */

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;
        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f7fb;

            color: #1f2937;
        }


        a {

            text-decoration: none;
        }


        button,
        input {

            font-family: inherit;
        }


        /* ==================================================
           LAYOUT
           ================================================== */

        .layout {

            min-height: 100vh;

            display: flex;
        }


        /* ==================================================
           SIDEBAR
           ================================================== */

        .sidebar {

            width: 250px;

            min-height: 100vh;

            background: #111827;

            border-right:
                1px solid #1f2937;

            position: fixed;

            left: 0;

            top: 0;

            bottom: 0;

            z-index: 100;

            display: flex;

            flex-direction: column;
        }


        /* ==================================================
           BRAND
           ================================================== */

        .brand {

            padding: 25px 20px;

            min-height: 99px;

            border-bottom:
                1px solid #1f2937;

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .brand-logo {

            width: 48px;

            height: 48px;

            border-radius: 12px;

            background: #2563eb;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

            font-weight: bold;

            flex-shrink: 0;
        }


        .brand-text h2 {

            font-size: 17px;

            color: #ffffff;

            margin-bottom: 3px;

            line-height: 1.2;
        }


        .brand-text p {

            font-size: 12px;

            color: #9ca3af;

            line-height: 1.2;
        }


        /* ==================================================
           USER SIDEBAR
           ================================================== */

        .sidebar-user {

            margin:
                15px 12px 5px;

            padding:
                12px 14px;

            background: #1f2937;

            border:
                1px solid #374151;

            border-radius: 10px;
        }


        .sidebar-user-name {

            font-size: 13px;

            font-weight: 700;

            color: #ffffff;

            margin-bottom: 3px;
        }


        .sidebar-user-role {

            font-size: 11px;

            color: #9ca3af;

            text-transform: capitalize;
        }


        /* ==================================================
           MENU
           ================================================== */

        .sidebar-menu {

            padding:
                20px 12px;

            flex: 1;

            overflow-y: auto;
        }


        .menu-title {

            font-size: 11px;

            font-weight: bold;

            color: #6b7280;

            text-transform: uppercase;

            letter-spacing: 0.8px;

            padding:
                0 12px;

            margin-bottom: 10px;
        }


        .menu-item {

            display: flex;

            align-items: center;

            gap: 12px;

            width: 100%;

            padding:
                12px 14px;

            margin-bottom: 5px;

            border-radius: 9px;

            color: #d1d5db;

            background: transparent;

            font-size: 14px;

            font-weight: 500;

            transition:
                background 0.2s,
                color 0.2s;
        }


        .menu-item:hover {

            background: #1f2937;

            color: #ffffff;
        }


        .menu-item.active {

            background: #2563eb;

            color: #ffffff;
        }


        .menu-item.active:hover {

            background: #1d4ed8;

            color: #ffffff;
        }


        .menu-icon {

            width: 24px;

            min-width: 24px;

            height: 24px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            font-size: 17px;

            line-height: 1;
        }


        /* ==================================================
           LOGOUT
           ================================================== */

        .sidebar-bottom {

            padding:
                0 12px 15px;
        }


        .sidebar-bottom .menu-item {

            margin-bottom: 0;
        }


        /* ==================================================
           MAIN
           ================================================== */

        .main {

            margin-left: 250px;

            width:
                calc(
                    100% - 250px
                );

            min-height: 100vh;
        }


        /* ==================================================
           TOPBAR
           ================================================== */

        .topbar {

            height: 75px;

            background: #ffffff;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0 30px;

            position: sticky;

            top: 0;

            z-index: 50;
        }


        .topbar-title h1 {

            font-size: 21px;

            color: #111827;

            margin-bottom: 4px;
        }


        .topbar-title p {

            font-size: 13px;

            color: #6b7280;
        }


        /* ==================================================
           TOPBAR USER
           ================================================== */

        .user-box {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .user-info {

            text-align: right;
        }


        .user-info strong {

            display: block;

            font-size: 14px;

            color: #111827;

            margin-bottom: 2px;
        }


        .user-info span {

            display: block;

            font-size: 12px;

            color: #6b7280;

            text-transform: capitalize;
        }


        .user-avatar {

            width: 42px;

            height: 42px;

            border-radius: 50%;

            background: #dbeafe;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 15px;

            font-weight: bold;
        }


        /* ==================================================
           CONTENT
           ================================================== */

        .content {

            padding: 30px;
        }


        /* ==================================================
           PAGE HEADER
           ================================================== */

        .page-header {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            margin-bottom: 22px;
        }


        .page-header h2 {

            font-size: 22px;

            color: #111827;

            margin-bottom: 5px;
        }


        .page-header p {

            font-size: 13px;

            color: #6b7280;
        }


        /* ==================================================
           BUTTON
           ================================================== */

        .btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            min-height: 40px;

            padding:
                9px 15px;

            border: none;

            border-radius: 8px;

            font-size: 13px;

            font-weight: 600;

            cursor: pointer;

            transition:
                background 0.2s,
                transform 0.2s;
        }


        .btn:hover {

            transform:
                translateY(-1px);
        }


        .btn-primary {

            background: #2563eb;

            color: #ffffff;

            box-shadow:
                0 4px 10px
                rgba(
                    37,
                    99,
                    235,
                    0.18
                );
        }


        .btn-primary:hover {

            background: #1d4ed8;
        }


        .btn-edit {

            background: #f59e0b;

            color: #ffffff;
        }


        .btn-edit:hover {

            background: #d97706;
        }


        .btn-delete {

            background: #ef4444;

            color: #ffffff;
        }


        .btn-delete:hover {

            background: #dc2626;
        }


        /* ==================================================
           ALERT
           ================================================== */

        .alert {

            padding:
                13px 16px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 13px;

            font-weight: 600;
        }


        .alert-success {

            background: #dcfce7;

            color: #166534;

            border:
                1px solid #bbf7d0;
        }


        .alert-error {

            background: #fee2e2;

            color: #991b1b;

            border:
                1px solid #fecaca;
        }


        /* ==================================================
           TABLE CARD
           ================================================== */

        .table-card {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 14px;

            overflow: hidden;

            box-shadow:
                0 4px 14px
                rgba(
                    0,
                    0,
                    0,
                    0.04
                );
        }


        .table-top {

            padding:
                18px 20px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content:
                space-between;
        }


        .table-top h3 {

            font-size: 16px;

            color: #111827;

            margin-bottom: 4px;
        }


        .table-top p {

            font-size: 12px;

            color: #6b7280;
        }


        /* ==================================================
           TABLE
           ================================================== */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse:
                collapse;

            min-width: 750px;
        }


        thead {

            background: #f8fafc;
        }


        th {

            padding:
                13px 16px;

            text-align: left;

            font-size: 12px;

            font-weight: 700;

            color: #475569;

            text-transform: uppercase;

            letter-spacing: 0.3px;

            border-bottom:
                1px solid #e5e7eb;
        }


        td {

            padding:
                14px 16px;

            font-size: 13px;

            color: #374151;

            border-bottom:
                1px solid #f1f5f9;
        }


        tbody tr:hover {

            background: #f8fafc;
        }


        tbody tr:last-child td {

            border-bottom: none;
        }


        .number {

            width: 60px;

            text-align: center;
        }


        .username {

            font-weight: 700;

            color: #111827;
        }


        /* ==================================================
           ROLE BADGE
           ================================================== */

        .role-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                5px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;
        }


        .role-admin {

            background: #dbeafe;

            color: #1d4ed8;
        }


        .role-pemilik {

            background: #fef3c7;

            color: #92400e;
        }


        /* ==================================================
           ACTION
           ================================================== */

        .actions {

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .actions form {

            margin: 0;
        }


        /* ==================================================
           EMPTY
           ================================================== */

        .empty {

            padding:
                50px 20px;

            text-align: center;

            color: #6b7280;
        }


        .empty-icon {

            font-size: 40px;

            margin-bottom: 12px;
        }


        .empty h3 {

            color: #374151;

            font-size: 16px;

            margin-bottom: 5px;
        }


        .empty p {

            font-size: 13px;
        }


        /* ==================================================
           RESPONSIVE
           ================================================== */

        @media (max-width: 900px) {

            .sidebar {

                width: 220px;
            }


            .main {

                margin-left: 220px;

                width:
                    calc(
                        100% - 220px
                    );
            }


            .content {

                padding: 20px;
            }

        }


        @media (max-width: 700px) {

            .layout {

                display: block;
            }


            .sidebar {

                position: relative;

                width: 100%;

                min-height: auto;
            }


            .main {

                margin-left: 0;

                width: 100%;
            }


            .topbar {

                height: auto;

                min-height: 75px;

                padding:
                    15px 20px;
            }


            .content {

                padding: 20px;
            }


            .page-header {

                flex-direction: column;

                align-items:
                    flex-start;
            }


            .page-header .btn {

                width: 100%;
            }


            .user-info {

                display: none;
            }

        }

    </style>

</head>


<body>


<div class="layout">


    <!-- ==================================================
         SIDEBAR
         ================================================== -->

    <aside class="sidebar">


        <!-- BRAND -->

        <div class="brand">


            <div class="brand-logo">

                A2

            </div>


            <div class="brand-text">


                <h2>
                    ASLI 2
                </h2>


                <p>
                    Toko Bangunan
                </p>


            </div>


        </div>


        <!-- USER -->

        <div class="sidebar-user">


            <div class="sidebar-user-name">

                <?= e(
                    $nama_lengkap
                ); ?>

            </div>


            <div class="sidebar-user-role">

                <?= e(
                    ucfirst(
                        $level
                    )
                ); ?>

            </div>


        </div>


        <!-- MENU -->

        <div class="sidebar-menu">


            <div class="menu-title">

                Menu Utama

            </div>


            <!-- DASHBOARD -->

            <a
                href="../../index.php"
                class="menu-item"
            >

                <span class="menu-icon">
                    🏠
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <?php if (punya_role('admin')): ?>


                <!-- DATA BARANG -->

                <a
                    href="../barang/index.php"
                    class="menu-item"
                >

                    <span class="menu-icon">
                        📦
                    </span>

                    <span>
                        Data Barang
                    </span>

                </a>


                <!-- DATA SUPPLIER -->

                <a
                    href="../supplier/index.php"
                    class="menu-item"
                >

                    <span class="menu-icon">
                        🚚
                    </span>

                    <span>
                        Data Supplier
                    </span>

                </a>


                <!-- PEMBELIAN -->

                <a
                    href="../pembelian/index.php"
                    class="menu-item"
                >

                    <span class="menu-icon">
                        🛒
                    </span>

                    <span>
                        Pembelian
                    </span>

                </a>


            <?php endif; ?>


            <!-- PERSEDIAAN -->

            <a
                href="../persediaan/index.php"
                class="menu-item"
            >

                <span class="menu-icon">
                    📊
                </span>

                <span>
                    Persediaan
                </span>

            </a>


            <!-- LAPORAN -->

            <a
                href="../laporan/index.php"
                class="menu-item"
            >

                <span class="menu-icon">
                    📄
                </span>

                <span>
                    Laporan Pembelian
                </span>

            </a>


            <!-- TAMBAH USER -->

            <a
                href="index.php"
                class="menu-item active"
            >

                <span class="menu-icon">
                    👤
                </span>

                <span>
                    Tambah User
                </span>

            </a>


            <!-- LOGOUT -->

            <div class="sidebar-bottom">


                <a
                    href="../../logout.php"
                    class="menu-item"
                    onclick="
                        return confirm(
                            'Apakah Anda yakin ingin keluar?'
                        );
                    "
                >

                    <span class="menu-icon">
                        🚪
                    </span>

                    <span>
                        Logout
                    </span>

                </a>


            </div>


        </div>


    </aside>


    <!-- ==================================================
         MAIN
         ================================================== -->

    <main class="main">


        <!-- TOPBAR -->

        <header class="topbar">


            <div class="topbar-title">


                <h1>
                    Tambah User
                </h1>


                <p>
                    Kelola akun pengguna
                    Toko Bangunan ASLI 2.
                </p>


            </div>


            <div class="user-box">


                <div class="user-info">


                    <strong>
                        <?= e(
                            $nama_lengkap
                        ); ?>
                    </strong>


                    <span>
                        <?= e(
                            $level
                        ); ?>
                    </span>


                </div>


                <div class="user-avatar">


                    <?= strtoupper(
                        substr(
                            $nama_lengkap,
                            0,
                            1
                        )
                    ); ?>


                </div>


            </div>


        </header>


        <!-- CONTENT -->

        <div class="content">


            <!-- ==================================================
                 PESAN SUCCESS
                 ================================================== -->

            <?php if (isset($_GET['success'])): ?>


                <?php if (
                    $_GET['success']
                    === 'user_ditambahkan'
                ): ?>


                    <div
                        class="alert alert-success"
                    >

                        User berhasil
                        ditambahkan.

                    </div>


                <?php elseif (
                    $_GET['success']
                    === 'user_dihapus'
                ): ?>


                    <div
                        class="alert alert-success"
                    >

                        User berhasil
                        dihapus.

                    </div>


                <?php elseif (
                    $_GET['success']
                    === 'user_diubah'
                ): ?>


                    <div
                        class="alert alert-success"
                    >

                        Data user berhasil
                        diubah.

                    </div>


                <?php endif; ?>


            <?php endif; ?>


            <!-- ==================================================
                 PESAN ERROR
                 ================================================== -->

            <?php if (isset($_GET['error'])): ?>


                <?php

                $pesan_error = '';

                switch (
                    $_GET['error']
                ) {

                    case 'akses_ditolak':

                        $pesan_error =
                            'Anda tidak memiliki akses ke halaman ini.';

                        break;


                    case 'csrf':

                        $pesan_error =
                            'Permintaan tidak valid. Silakan coba lagi.';

                        break;


                    case 'permintaan_tidak_valid':

                        $pesan_error =
                            'Permintaan tidak valid.';

                        break;


                    case 'user_tidak_valid':

                        $pesan_error =
                            'Data user tidak valid.';

                        break;


                    case 'user_tidak_ditemukan':

                        $pesan_error =
                            'User tidak ditemukan.';

                        break;


                    case 'hapus_diri_sendiri':

                        $pesan_error =
                            'Akun yang sedang digunakan tidak dapat dihapus.';

                        break;


                    case 'pemilik_terakhir':

                        $pesan_error =
                            'Pemilik terakhir tidak dapat dihapus.';

                        break;


                    case 'gagal_hapus':

                        $pesan_error =
                            'User gagal dihapus.';

                        break;


                    case 'database':

                        $pesan_error =
                            'Terjadi kesalahan pada database.';

                        break;


                    default:

                        $pesan_error =
                            'Terjadi kesalahan.';

                        break;
                }

                ?>


                <?php if (
                    $pesan_error !== ''
                ): ?>


                    <div
                        class="alert alert-error"
                    >

                        <?= e(
                            $pesan_error
                        ); ?>

                    </div>


                <?php endif; ?>


            <?php endif; ?>


            <!-- ==================================================
                 PAGE HEADER
                 ================================================== -->

            <div class="page-header">


                <div>


                    <h2>
                        Daftar Pengguna
                    </h2>


                    <p>
                        Kelola akun admin dan
                        pemilik sistem.
                    </p>


                </div>


                <a
                    href="tambah.php"
                    class="btn btn-primary"
                >

                    👤
                    Tambah User

                </a>


            </div>


            <!-- ==================================================
                 TABLE CARD
                 ================================================== -->

            <div class="table-card">


                <div class="table-top">


                    <div>


                        <h3>
                            Data Pengguna
                        </h3>


                        <p>
                            Daftar akun yang
                            dapat mengakses sistem.
                        </p>


                    </div>


                </div>


                <div class="table-wrapper">


                    <?php if (
                        mysqli_num_rows(
                            $query_user
                        ) > 0
                    ): ?>


                        <table>


                            <thead>


                                <tr>


                                    <th
                                        class="number"
                                    >
                                        No
                                    </th>


                                    <th>
                                        Username
                                    </th>


                                    <th>
                                        Nama Lengkap
                                    </th>


                                    <th>
                                        Level
                                    </th>


                                    <th>
                                        Aksi
                                    </th>


                                </tr>


                            </thead>


                            <tbody>


                                <?php

                                $no = 1;

                                while (
                                    $row =
                                    mysqli_fetch_assoc(
                                        $query_user
                                    )
                                ):

                                    $id_user =
                                        (int)
                                        $row['id_user'];

                                ?>


                                    <tr>


                                        <!-- NO -->

                                        <td
                                            class="number"
                                        >

                                            <?= $no++; ?>

                                        </td>


                                        <!-- USERNAME -->

                                        <td>

                                            <span
                                                class="username"
                                            >

                                                <?= e(
                                                    $row[
                                                        'username'
                                                    ]
                                                ); ?>

                                            </span>

                                        </td>


                                        <!-- NAMA -->

                                        <td>

                                            <?= e(
                                                $row[
                                                    'nama_lengkap'
                                                ]
                                            ); ?>

                                        </td>


                                        <!-- LEVEL -->

                                        <td>


                                            <?php if (
                                                $row['level']
                                                ===
                                                'pemilik'
                                            ): ?>


                                                <span
                                                    class="
                                                        role-badge
                                                        role-pemilik
                                                    "
                                                >

                                                    Pemilik

                                                </span>


                                            <?php else: ?>


                                                <span
                                                    class="
                                                        role-badge
                                                        role-admin
                                                    "
                                                >

                                                    Admin

                                                </span>


                                            <?php endif; ?>


                                        </td>


                                        <!-- AKSI -->

                                        <td>


                                            <div
                                                class="actions"
                                            >


                                                <!-- EDIT -->

                                                <a
                                                    href="
                                                        edit.php?id=
                                                        <?= $id_user; ?>
                                                    "
                                                    class="
                                                        btn
                                                        btn-edit
                                                    "
                                                >

                                                    ✏️
                                                    Edit

                                                </a>


                                                <!-- HAPUS -->

                                                <?php if (
                                                    $id_user
                                                    !==
                                                    (int)
                                                    $_SESSION[
                                                        'id_user'
                                                    ]
                                                ): ?>


                                                    <form
                                                        action="hapus.php"
                                                        method="POST"
                                                        onsubmit="
                                                            return confirm(
                                                                'Yakin ingin menghapus user ini? Data user yang dihapus tidak dapat dikembalikan.'
                                                            );
                                                        "
                                                    >


                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= e(
                                                                $csrf_token
                                                            ); ?>"
                                                        >


                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= $id_user; ?>"
                                                        >


                                                        <button
                                                            type="submit"
                                                            class="
                                                                btn
                                                                btn-delete
                                                            "
                                                        >

                                                            🗑️
                                                            Hapus

                                                        </button>


                                                    </form>


                                                <?php endif; ?>


                                            </div>


                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            </tbody>


                        </table>


                    <?php else: ?>


                        <div class="empty">


                            <div
                                class="empty-icon"
                            >
                                👤
                            </div>


                            <h3>
                                Belum ada pengguna
                            </h3>


                            <p>
                                Silakan tambahkan
                                pengguna baru.
                            </p>


                        </div>


                    <?php endif; ?>


                </div>


            </div>


        </div>


    </main>


</div>


</body>

</html>