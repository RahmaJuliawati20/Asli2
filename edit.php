<?php

include "../../config/koneksi.php";
include "../../config/auth.php";

wajib_login();


// ==================================================
// HALAMAN KHUSUS PEMILIK
// ==================================================

if (!punya_role('pemilik')) {

    header(
        "Location: ../../index.php?error=akses_ditolak"
    );

    exit;
}


// ==================================================
// CSRF TOKEN
// ==================================================

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}

$csrf_token =
    $_SESSION['csrf_token'];


// ==================================================
// DATA LOGIN
// ==================================================

$nama_lengkap_login =
    $_SESSION['nama_lengkap'] ?? 'User';

$level_login =
    $_SESSION['level'] ?? '';


// ==================================================
// ID USER
// ==================================================

$id_user = (int) (
    $_GET['id']
    ?? $_POST['id']
    ?? 0
);


if ($id_user <= 0) {

    header(
        "Location: index.php?error=user_tidak_valid"
    );

    exit;
}


// ==================================================
// AMBIL DATA USER
// ==================================================

$query_user = mysqli_prepare(
    $conn,
    "
    SELECT
        id_user,
        username,
        nama_lengkap,
        level
    FROM users
    WHERE id_user = ?
    LIMIT 1
    "
);


if ($query_user === false) {

    die(
        "Query user gagal: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $query_user,
    "i",
    $id_user
);


mysqli_stmt_execute(
    $query_user
);


$result_user =
    mysqli_stmt_get_result(
        $query_user
    );


$data_user =
    mysqli_fetch_assoc(
        $result_user
    );


mysqli_stmt_close(
    $query_user
);


if (!$data_user) {

    header(
        "Location: index.php?error=user_tidak_ditemukan"
    );

    exit;
}


// ==================================================
// DATA FORM
// ==================================================

$username =
    $data_user['username'];

$nama_user =
    $data_user['nama_lengkap'];

$level_user =
    $data_user['level'];


// ==================================================
// PROSES UPDATE
// ==================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token_post =
        $_POST['csrf_token'] ?? '';


    // ==================================================
    // CEK CSRF
    // ==================================================

    if (
        !hash_equals(
            $csrf_token,
            $token_post
        )
    ) {

        header(
            "Location: index.php?error=csrf"
        );

        exit;
    }


    // ==================================================
    // AMBIL INPUT
    // ==================================================

    $username_baru =
        trim(
            (string)
            ($_POST['username'] ?? '')
        );


    $nama_baru =
        trim(
            (string)
            ($_POST['nama_lengkap'] ?? '')
        );


    $level_baru =
        trim(
            (string)
            ($_POST['level'] ?? '')
        );


    $password_baru =
        (string)
        ($_POST['password'] ?? '');


    // ==================================================
    // VALIDASI DASAR
    // ==================================================

    if (
        $username_baru === '' ||
        $nama_baru === '' ||
        $level_baru === ''
    ) {

        header(
            "Location: edit.php?id="
            . $id_user
            . "&error=data_tidak_lengkap"
        );

        exit;
    }


    // ==================================================
    // VALIDASI LEVEL
    // ==================================================

    if (
        $level_baru !== 'admin' &&
        $level_baru !== 'pemilik'
    ) {

        header(
            "Location: edit.php?id="
            . $id_user
            . "&error=level_tidak_valid"
        );

        exit;
    }


    // ==================================================
    // VALIDASI USERNAME
    // ==================================================

    if (
        strlen($username_baru) < 3 ||
        strlen($username_baru) > 50
    ) {

        header(
            "Location: edit.php?id="
            . $id_user
            . "&error=username_tidak_valid"
        );

        exit;
    }


    // ==================================================
    // VALIDASI NAMA
    // ==================================================

    if (
        strlen($nama_baru) < 2 ||
        strlen($nama_baru) > 100
    ) {

        header(
            "Location: edit.php?id="
            . $id_user
            . "&error=nama_tidak_valid"
        );

        exit;
    }


    // ==================================================
    // VALIDASI PASSWORD
    // ==================================================

    if (
        $password_baru !== '' &&
        strlen($password_baru) < 6
    ) {

        header(
            "Location: edit.php?id="
            . $id_user
            . "&error=password_tidak_valid"
        );

        exit;
    }


    // ==================================================
    // CEK USERNAME DUPLIKAT
    // ==================================================

    $cek_username =
        mysqli_prepare(
            $conn,
            "
            SELECT
                id_user
            FROM users
            WHERE username = ?
            AND id_user <> ?
            LIMIT 1
            "
        );


    if ($cek_username === false) {

        header(
            "Location: edit.php?id="
            . $id_user
            . "&error=database"
        );

        exit;
    }


    mysqli_stmt_bind_param(
        $cek_username,
        "si",
        $username_baru,
        $id_user
    );


    mysqli_stmt_execute(
        $cek_username
    );


    $hasil_username =
        mysqli_stmt_get_result(
            $cek_username
        );


    $username_sudah_ada =
        mysqli_num_rows(
            $hasil_username
        ) > 0;


    mysqli_stmt_close(
        $cek_username
    );


    if ($username_sudah_ada) {

        header(
            "Location: edit.php?id="
            . $id_user
            . "&error=username_sudah_digunakan"
        );

        exit;
    }


    // ==================================================
    // CEK PEMILIK TERAKHIR
    // ==================================================

    if (
        $level_user === 'pemilik' &&
        $level_baru === 'admin'
    ) {

        $cek_pemilik =
            mysqli_query(
                $conn,
                "
                SELECT
                    COUNT(*) AS total
                FROM users
                WHERE level = 'pemilik'
                "
            );


        if (!$cek_pemilik) {

            header(
                "Location: edit.php?id="
                . $id_user
                . "&error=database"
            );

            exit;
        }


        $data_pemilik =
            mysqli_fetch_assoc(
                $cek_pemilik
            );


        $jumlah_pemilik =
            (int)
            ($data_pemilik['total'] ?? 0);


        if ($jumlah_pemilik <= 1) {

            header(
                "Location: edit.php?id="
                . $id_user
                . "&error=pemilik_terakhir"
            );

            exit;
        }
    }


    // ==================================================
    // UPDATE USER
    // ==================================================

    if ($password_baru !== '') {

        $password_hash =
            password_hash(
                $password_baru,
                PASSWORD_DEFAULT
            );


        if ($password_hash === false) {

            header(
                "Location: edit.php?id="
                . $id_user
                . "&error=database"
            );

            exit;
        }


        $stmt_update =
            mysqli_prepare(
                $conn,
                "
                UPDATE users
                SET
                    username = ?,
                    password = ?,
                    nama_lengkap = ?,
                    level = ?
                WHERE id_user = ?
                "
            );


        if ($stmt_update === false) {

            header(
                "Location: edit.php?id="
                . $id_user
                . "&error=database"
            );

            exit;
        }


        mysqli_stmt_bind_param(
            $stmt_update,
            "ssssi",
            $username_baru,
            $password_hash,
            $nama_baru,
            $level_baru,
            $id_user
        );


    } else {

        $stmt_update =
            mysqli_prepare(
                $conn,
                "
                UPDATE users
                SET
                    username = ?,
                    nama_lengkap = ?,
                    level = ?
                WHERE id_user = ?
                "
            );


        if ($stmt_update === false) {

            header(
                "Location: edit.php?id="
                . $id_user
                . "&error=database"
            );

            exit;
        }


        mysqli_stmt_bind_param(
            $stmt_update,
            "sssi",
            $username_baru,
            $nama_baru,
            $level_baru,
            $id_user
        );
    }


    // ==================================================
    // EKSEKUSI UPDATE
    // ==================================================

    $berhasil_update =
        mysqli_stmt_execute(
            $stmt_update
        );


    mysqli_stmt_close(
        $stmt_update
    );


    if (!$berhasil_update) {

        header(
            "Location: edit.php?id="
            . $id_user
            . "&error=gagal_update"
        );

        exit;
    }


    // ==================================================
    // UPDATE SESSION
    // ==================================================

    if (
        $id_user ===
        (int)
        ($_SESSION['id_user'] ?? 0)
    ) {

        $_SESSION['username'] =
            $username_baru;

        $_SESSION['nama_lengkap'] =
            $nama_baru;

        $_SESSION['level'] =
            $level_baru;
    }


    // ==================================================
    // REDIRECT
    // ==================================================

    header(
        "Location: index.php?success=user_diubah"
    );

    exit;
}


// ==================================================
// PESAN ERROR
// ==================================================

$pesan_error = '';


if (isset($_GET['error'])) {

    switch (
        $_GET['error']
    ) {

        case 'csrf':

            $pesan_error =
                'Permintaan tidak valid. Silakan coba lagi.';

            break;


        case 'data_tidak_lengkap':

            $pesan_error =
                'Semua data wajib diisi.';

            break;


        case 'level_tidak_valid':

            $pesan_error =
                'Level user tidak valid.';

            break;


        case 'username_tidak_valid':

            $pesan_error =
                'Username harus terdiri dari 3 sampai 50 karakter.';

            break;


        case 'nama_tidak_valid':

            $pesan_error =
                'Nama lengkap harus terdiri dari 2 sampai 100 karakter.';

            break;


        case 'password_tidak_valid':

            $pesan_error =
                'Password baru minimal 6 karakter.';

            break;


        case 'username_sudah_digunakan':

            $pesan_error =
                'Username tersebut sudah digunakan oleh user lain.';

            break;


        case 'pemilik_terakhir':

            $pesan_error =
                'Pemilik terakhir tidak dapat diubah menjadi admin.';

            break;


        case 'gagal_update':

            $pesan_error =
                'Data user gagal diperbarui.';

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
        Edit User - Toko Bangunan ASLI 2
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
        input,
        select {
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

            padding:
                25px 20px;

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

            min-width: 48px;

            border-radius: 12px;

            background: #2563eb;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

            font-weight: bold;
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

            font-weight: 700;

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

            min-height: 46px;

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

            flex: 0 0 24px;

            font-size: 17px;

            line-height: 1;
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

            justify-content:
                space-between;

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

            min-height: 42px;

            padding:
                9px 16px;

            border: none;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            font-size: 13px;

            font-weight: 700;

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


        .btn-secondary {

            background: #ffffff;

            color: #374151;

            border:
                1px solid #d1d5db;
        }


        .btn-secondary:hover {

            background: #f3f4f6;
        }


        /* ==================================================
           ALERT
           ================================================== */

        .alert {

            max-width: 850px;

            padding:
                13px 16px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 13px;

            font-weight: 600;
        }


        .alert-error {

            background: #fee2e2;

            color: #991b1b;

            border:
                1px solid #fecaca;
        }


        /* ==================================================
           FORM CARD
           ================================================== */

        .form-card {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 14px;

            box-shadow:
                0 4px 14px
                rgba(
                    0,
                    0,
                    0,
                    0.04
                );

            overflow: hidden;

            max-width: 850px;
        }


        .form-card-header {

            padding:
                20px 22px;

            border-bottom:
                1px solid #e5e7eb;
        }


        .form-card-header h3 {

            font-size: 16px;

            color: #111827;

            margin-bottom: 4px;
        }


        .form-card-header p {

            font-size: 12px;

            color: #6b7280;
        }


        /* ==================================================
           FORM BODY
           ================================================== */

        .form-body {

            padding:
                24px 22px;
        }


        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 18px;
        }


        .form-group {

            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .form-group label {

            font-size: 13px;

            font-weight: 700;

            color: #374151;
        }


        .required {

            color: #ef4444;
        }


        .form-group input,
        .form-group select {

            width: 100%;

            height: 44px;

            padding:
                0 13px;

            border:
                1px solid #d1d5db;

            border-radius: 9px;

            background: #ffffff;

            color: #111827;

            outline: none;

            font-size: 13px;

            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }


        .form-group input:focus,
        .form-group select:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(
                    37,
                    99,
                    235,
                    0.10
                );
        }


        .form-group small {

            font-size: 11px;

            color: #9ca3af;

            line-height: 1.4;
        }


        /* ==================================================
           FORM ACTION
           ================================================== */

        .form-actions {

            margin-top: 24px;

            padding-top: 20px;

            border-top:
                1px solid #e5e7eb;

            display: flex;

            justify-content:
                flex-end;

            gap: 8px;
        }


        /* ==================================================
           INFO BOX
           ================================================== */

        .info-box {

            margin-top: 20px;

            max-width: 850px;

            padding:
                13px 16px;

            border-radius: 10px;

            background: #eff6ff;

            border:
                1px solid #bfdbfe;

            color: #1e40af;

            font-size: 12px;

            line-height: 1.5;
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


            .user-info {

                display: none;
            }


            .page-header {

                flex-direction: column;

                align-items:
                    flex-start;
            }


            .page-header .btn {

                width: 100%;
            }


            .form-grid {

                grid-template-columns: 1fr;
            }


            .form-actions {

                flex-direction: column;
            }


            .form-actions .btn {

                width: 100%;
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
                    $nama_lengkap_login
                ); ?>

            </div>


            <div class="sidebar-user-role">

                <?= e(
                    ucfirst(
                        $level_login
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


            <!-- TAMBAH USER
                 TETAP ACTIVE SAAT EDIT -->

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

            <a
                href="../../logout.php"
                class="
                    menu-item
                    logout-item
                "
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


    </aside>


    <!-- ==================================================
         MAIN
         ================================================== -->

    <main class="main">


        <!-- TOPBAR -->

        <header class="topbar">


            <div class="topbar-title">


                <h1>

                    Edit User

                </h1>


                <p>

                    Ubah data akun pengguna
                    Toko Bangunan ASLI 2.

                </p>


            </div>


            <div class="user-box">


                <div class="user-info">


                    <strong>

                        <?= e(
                            $nama_lengkap_login
                        ); ?>

                    </strong>


                    <span>

                        <?= e(
                            $level_login
                        ); ?>

                    </span>


                </div>


                <div class="user-avatar">

                    <?= strtoupper(
                        substr(
                            $nama_lengkap_login,
                            0,
                            1
                        )
                    ); ?>

                </div>


            </div>


        </header>


        <!-- ==================================================
             CONTENT
             ================================================== -->

        <div class="content">


            <!-- ERROR -->

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


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>


                    <h2>
                        Edit Data Pengguna
                    </h2>


                    <p>
                        Perbarui informasi akun
                        pengguna di bawah ini.
                    </p>


                </div>


                <a
                    href="index.php"
                    class="btn btn-secondary"
                >

                    ← Kembali

                </a>


            </div>


            <!-- FORM CARD -->

            <div class="form-card">


                <div class="form-card-header">


                    <h3>
                        Informasi Pengguna
                    </h3>


                    <p>

                        Kolom bertanda

                        <span
                            style="
                                color:#ef4444;
                            "
                        >
                            *
                        </span>

                        wajib diisi.

                    </p>


                </div>


                <div class="form-body">


                    <form
                        method="POST"
                        action="
                            edit.php?id=
                            <?= $id_user; ?>
                        "
                    >


                        <!-- CSRF -->

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(
                                $csrf_token
                            ); ?>"
                        >


                        <!-- ID USER -->

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $id_user; ?>"
                        >


                        <div class="form-grid">


                            <!-- USERNAME -->

                            <div class="form-group">


                                <label
                                    for="username"
                                >

                                    Username

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    value="<?= e(
                                        $username
                                    ); ?>"
                                    minlength="3"
                                    maxlength="50"
                                    autocomplete="username"
                                    required
                                >


                                <small>

                                    Username digunakan
                                    untuk login ke sistem.

                                </small>


                            </div>


                            <!-- NAMA LENGKAP -->

                            <div class="form-group">


                                <label
                                    for="nama_lengkap"
                                >

                                    Nama Lengkap

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    id="nama_lengkap"
                                    name="nama_lengkap"
                                    value="<?= e(
                                        $nama_user
                                    ); ?>"
                                    minlength="2"
                                    maxlength="100"
                                    autocomplete="name"
                                    required
                                >


                                <small>

                                    Masukkan nama lengkap
                                    pengguna.

                                </small>


                            </div>


                            <!-- LEVEL -->

                            <div class="form-group">


                                <label
                                    for="level"
                                >

                                    Level

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <select
                                    id="level"
                                    name="level"
                                    required
                                >


                                    <option
                                        value="admin"

                                        <?= $level_user === 'admin'
                                            ? 'selected'
                                            : ''; ?>
                                    >

                                        Admin

                                    </option>


                                    <option
                                        value="pemilik"

                                        <?= $level_user === 'pemilik'
                                            ? 'selected'
                                            : ''; ?>
                                    >

                                        Pemilik

                                    </option>


                                </select>


                                <small>

                                    Pemilik memiliki akses
                                    untuk mengelola pengguna.

                                </small>


                            </div>


                            <!-- PASSWORD -->

                            <div class="form-group">


                                <label
                                    for="password"
                                >

                                    Password Baru

                                </label>


                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    minlength="6"
                                    maxlength="255"
                                    autocomplete="new-password"
                                >


                                <small>

                                    Kosongkan jika password
                                    tidak ingin diubah.

                                </small>


                            </div>


                        </div>


                        <!-- ACTION -->

                        <div class="form-actions">


                            <a
                                href="index.php"
                                class="btn btn-secondary"
                            >

                                Batal

                            </a>


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                💾
                                Simpan Perubahan

                            </button>


                        </div>


                    </form>


                </div>


            </div>


            <!-- INFO -->

            <div class="info-box">

                <strong>
                    Catatan:
                </strong>

                Password lama tidak ditampilkan.
                Isi Password Baru hanya apabila
                password akun memang ingin diganti.

            </div>


        </div>


    </main>


</div>


</body>

</html>