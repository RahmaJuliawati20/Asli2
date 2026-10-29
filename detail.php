<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include "../../config/koneksi.php";
include "../../config/auth.php";

wajib_admin();


// ==================================================
// DATA SESSION
// ==================================================

$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$level = $_SESSION['level'] ?? '';


// ==================================================
// AMBIL ID PEMBELIAN
// ==================================================

$id_pembelian = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


// ==================================================
// VALIDASI ID
// ==================================================

if ($id_pembelian <= 0) {

    header("Location: index.php");

    exit;
}


// ==================================================
// DATA PEMBELIAN
// ==================================================

$pembelian = null;

$sql_pembelian = "
    SELECT
        p.id_pembelian,
        p.tanggal_pembelian,
        p.id_supplier,
        p.total_pembelian,
        s.nama_supplier,
        s.alamat,
        s.no_telepon,
        s.email
    FROM pembelian p
    INNER JOIN supplier s
        ON p.id_supplier = s.id_supplier
    WHERE p.id_pembelian = ?
    LIMIT 1
";

$stmt_pembelian = mysqli_prepare(
    $conn,
    $sql_pembelian
);

if ($stmt_pembelian) {

    mysqli_stmt_bind_param(
        $stmt_pembelian,
        "i",
        $id_pembelian
    );

    mysqli_stmt_execute(
        $stmt_pembelian
    );

    $result_pembelian =
        mysqli_stmt_get_result(
            $stmt_pembelian
        );

    if ($result_pembelian) {

        $pembelian =
            mysqli_fetch_assoc(
                $result_pembelian
            );

        mysqli_free_result(
            $result_pembelian
        );
    }

    mysqli_stmt_close(
        $stmt_pembelian
    );
}


// ==================================================
// JIKA DATA TIDAK DITEMUKAN
// ==================================================

if (!$pembelian) {

    header("Location: index.php");

    exit;
}


// ==================================================
// DATA DETAIL PEMBELIAN
// ==================================================

$detail = [];

$sql_detail = "
    SELECT
        dp.id_detail,
        dp.id_barang,
        dp.jumlah,
        dp.harga_beli,
        dp.subtotal,
        b.nama_barang,
        b.kategori,
        b.satuan
    FROM detail_pembelian dp
    INNER JOIN barang b
        ON dp.id_barang = b.id_barang
    WHERE dp.id_pembelian = ?
    ORDER BY dp.id_detail ASC
";

$stmt_detail = mysqli_prepare(
    $conn,
    $sql_detail
);

if ($stmt_detail) {

    mysqli_stmt_bind_param(
        $stmt_detail,
        "i",
        $id_pembelian
    );

    mysqli_stmt_execute(
        $stmt_detail
    );

    $result_detail =
        mysqli_stmt_get_result(
            $stmt_detail
        );

    if ($result_detail) {

        while (
            $row =
                mysqli_fetch_assoc(
                    $result_detail
                )
        ) {

            $detail[] = $row;
        }

        mysqli_free_result(
            $result_detail
        );
    }

    mysqli_stmt_close(
        $stmt_detail
    );
}


// ==================================================
// HITUNG TOTAL DETAIL
// ==================================================

$total_detail = 0;
$total_item = 0;

foreach ($detail as $item) {

    $total_detail +=
        (float) (
            $item['subtotal'] ?? 0
        );

    $total_item +=
        (int) (
            $item['jumlah'] ?? 0
        );
}


// ==================================================
// FORMAT TANGGAL
// ==================================================

$tanggal_pembelian =
    !empty(
        $pembelian['tanggal_pembelian']
    )
    &&
    strtotime(
        $pembelian['tanggal_pembelian']
    ) !== false
    ?
    date(
        'd-m-Y',
        strtotime(
            $pembelian['tanggal_pembelian']
        )
    )
    :
    '-';


// ==================================================
// NOMOR PEMBELIAN
// ==================================================

$nomor_pembelian =
    'PB' .
    str_pad(
        $id_pembelian,
        4,
        '0',
        STR_PAD_LEFT
    );


// ==================================================
// TOTAL PEMBELIAN
// ==================================================

$total_pembelian =
    (float) (
        $pembelian['total_pembelian']
        ?? $total_detail
    );

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
        Detail Pembelian - Toko Bangunan ASLI 2
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

            color: #111827;
        }


        a {
            text-decoration: none;
        }


        /* ==================================================
           LAYOUT
        ================================================== */

        .layout {
            min-height: 100vh;
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


        .brand-title {

            color: #ffffff;

            font-size: 17px;

            font-weight: 700;

            line-height: 1.2;
        }


        .brand-subtitle {

            margin-top: 3px;

            color: #9ca3af;

            font-size: 12px;
        }


        /* ==================================================
           SIDEBAR USER
        ================================================== */

        .sidebar-user {

            margin:
                15px 12px 8px 12px;

            padding:
                12px 14px;

            background: #1f2937;

            border:
                1px solid #374151;

            border-radius: 10px;
        }


        .user-name {

            color: #ffffff;

            font-size: 14px;

            font-weight: 600;

            line-height: 1.4;

            word-break: break-word;
        }


        .user-role {

            margin-top: 4px;

            color: #9ca3af;

            font-size: 12px;
        }


        /* ==================================================
           MENU
        ================================================== */

        .sidebar nav {

            padding:
                8px 12px 15px;
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
                background .2s ease,
                color .2s ease;
        }


        .menu-item:hover {

            background: #1f2937;

            color: #ffffff;
        }


        .menu-item.active {

            background: #2563eb;

            color: #ffffff;
        }


        .menu-icon {

            width: 24px;

            min-width: 24px;

            height: 24px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            flex:
                0 0 24px;

            font-size: 17px;

            line-height: 1;
        }


        /* ==================================================
           MAIN
        ================================================== */

        .main {

            margin-left: 250px;

            width:
                calc(100% - 250px);

            min-height: 100vh;
        }


        /* ==================================================
           TOPBAR
        ================================================== */

        .topbar {

            height: 86px;

            background: #ffffff;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0 32px;

            position: sticky;

            top: 0;

            z-index: 50;
        }


        .topbar-left h1 {

            font-size: 24px;

            color: #111827;

            margin-bottom: 5px;
        }


        .topbar-left p {

            color: #6b7280;

            font-size: 13px;
        }


        .topbar-user {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .topbar-user-info {

            text-align: right;
        }


        .topbar-user-name {

            color: #111827;

            font-size: 14px;

            font-weight: 600;
        }


        .topbar-user-role {

            margin-top: 2px;

            color: #6b7280;

            font-size: 12px;
        }


        .user-avatar {

            width: 40px;
            height: 40px;

            border-radius: 50%;

            background: #2563eb;

            color: #ffffff;

            display: flex;

            align-items: center;
            justify-content: center;

            font-weight: 700;

            font-size: 15px;
        }


        /* ==================================================
           CONTENT
        ================================================== */

        .content {

            padding:
                30px 32px 40px;
        }


        /* ==================================================
           PAGE HEADER
        ================================================== */

        .page-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 24px;
        }


        .page-title {

            color: #111827;

            font-size: 22px;

            font-weight: 700;

            margin-bottom: 6px;
        }


        .page-description {

            color: #6b7280;

            font-size: 13px;

            line-height: 1.5;
        }


        /* ==================================================
           BUTTON
        ================================================== */

        .button-group {

            display: flex;

            align-items: center;

            gap: 10px;

            flex-wrap: wrap;
        }


        .btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 42px;

            padding:
                0 16px;

            border: none;

            border-radius: 8px;

            font-size: 13px;

            font-weight: 600;

            cursor: pointer;

            transition:
                background .2s ease,
                color .2s ease;
        }


        .btn-primary {

            background: #2563eb;

            color: #ffffff;
        }


        .btn-primary:hover {

            background: #1d4ed8;
        }


        .btn-secondary {

            background: #e5e7eb;

            color: #374151;
        }


        .btn-secondary:hover {

            background: #d1d5db;
        }


        /* ==================================================
           INFO CARD
        ================================================== */

        .info-card {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 14px;

            box-shadow:
                0 4px 15px
                rgba(15, 23, 42, .04);

            margin-bottom: 20px;

            overflow: hidden;
        }


        .info-card-header {

            padding:
                20px 24px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;
        }


        .info-card-header h3 {

            color: #111827;

            font-size: 17px;

            font-weight: 700;

            margin-bottom: 5px;
        }


        .info-card-header p {

            color: #6b7280;

            font-size: 12px;
        }


        .kode-pembelian {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 38px;

            padding:
                0 13px;

            background: #eff6ff;

            color: #1d4ed8;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 700;

            white-space: nowrap;
        }


        /* ==================================================
           INFO GRID
        ================================================== */

        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 0;
        }


        .info-item {

            padding:
                20px 24px;

            border-right:
                1px solid #e5e7eb;
        }


        .info-item:last-child {

            border-right: none;
        }


        .info-label {

            display: block;

            color: #6b7280;

            font-size: 12px;

            margin-bottom: 7px;
        }


        .info-value {

            display: block;

            color: #111827;

            font-size: 14px;

            font-weight: 600;

            line-height: 1.5;
        }


        .info-value.normal {

            font-weight: 500;

            color: #374151;
        }


        /* ==================================================
           DETAIL CARD
        ================================================== */

        .detail-card {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 14px;

            box-shadow:
                0 4px 15px
                rgba(15, 23, 42, .04);

            overflow: hidden;
        }


        .detail-card-header {

            padding:
                20px 24px;

            border-bottom:
                1px solid #e5e7eb;
        }


        .detail-card-header h3 {

            color: #111827;

            font-size: 17px;

            font-weight: 700;

            margin-bottom: 5px;
        }


        .detail-card-header p {

            color: #6b7280;

            font-size: 12px;
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

            min-width: 850px;

            border-collapse: collapse;
        }


        thead th {

            padding:
                14px 18px;

            background: #f9fafb;

            color: #6b7280;

            border-bottom:
                1px solid #e5e7eb;

            font-size: 12px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .35px;

            text-align: left;

            white-space: nowrap;
        }


        tbody td {

            padding:
                15px 18px;

            border-bottom:
                1px solid #f0f0f0;

            color: #374151;

            font-size: 13px;

            vertical-align: middle;
        }


        tbody tr:hover td {

            background: #fafafa;
        }


        tbody tr:last-child td {

            border-bottom: none;
        }


        .number {

            text-align: center;

            width: 60px;
        }


        .nama-barang {

            color: #111827;

            font-weight: 600;
        }


        .kategori {

            color: #6b7280;

            font-size: 12px;
        }


        .jumlah {

            text-align: center;

            font-weight: 600;
        }


        .satuan {

            color: #6b7280;

            font-size: 12px;
        }


        .harga {

            text-align: right;

            white-space: nowrap;
        }


        .subtotal {

            text-align: right;

            color: #111827;

            font-weight: 700;

            white-space: nowrap;
        }


        /* ==================================================
           TOTAL FOOTER
        ================================================== */

        .total-section {

            border-top:
                1px solid #e5e7eb;

            background: #f9fafb;

            padding:
                18px 24px;

            display: flex;

            justify-content: flex-end;
        }


        .total-box {

            min-width: 300px;
        }


        .total-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 30px;

            padding: 6px 0;

            font-size: 13px;
        }


        .total-row-label {

            color: #6b7280;
        }


        .total-row-value {

            color: #374151;

            font-weight: 600;

            text-align: right;
        }


        .total-row-grand {

            margin-top: 8px;

            padding-top: 12px;

            border-top:
                1px solid #d1d5db;
        }


        .total-row-grand
        .total-row-label {

            color: #111827;

            font-weight: 700;

            font-size: 14px;
        }


        .total-row-grand
        .total-row-value {

            color: #2563eb;

            font-size: 18px;

            font-weight: 700;
        }


        /* ==================================================
           EMPTY
        ================================================== */

        .empty-state {

            padding:
                60px 20px;

            text-align: center;
        }


        .empty-icon {

            width: 58px;
            height: 58px;

            margin:
                0 auto 16px;

            border-radius: 14px;

            background: #eff6ff;

            color: #2563eb;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 27px;
        }


        .empty-state h3 {

            color: #111827;

            font-size: 17px;

            margin-bottom: 7px;
        }


        .empty-state p {

            color: #6b7280;

            font-size: 13px;

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
                    calc(100% - 220px);
            }


            .info-grid {

                grid-template-columns: 1fr;
            }


            .info-item {

                border-right: none;

                border-bottom:
                    1px solid #e5e7eb;
            }


            .info-item:last-child {

                border-bottom: none;
            }

        }


        @media (max-width: 700px) {

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

                min-height: 80px;

                height: auto;

                padding:
                    0 20px;
            }


            .topbar-user-info {

                display: none;
            }


            .content {

                padding:
                    24px 20px 30px;
            }


            .page-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .button-group {

                width: 100%;
            }


            .button-group .btn {

                flex: 1;
            }


            .info-card-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .total-section {

                justify-content: stretch;
            }


            .total-box {

                width: 100%;

                min-width: 0;
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

            <div>

                <div class="brand-title">
                    ASLI 2
                </div>

                <div class="brand-subtitle">
                    Toko Bangunan
                </div>

            </div>

        </div>


        <!-- USER -->

        <div class="sidebar-user">

            <div class="user-name">
                <?= e($nama_lengkap); ?>
            </div>

            <div class="user-role">
                <?= e(ucfirst($level)); ?>
            </div>

        </div>


        <!-- MENU -->

        <nav>


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
                href="index.php"
                class="menu-item active"
            >

                <span class="menu-icon">
                    🛒
                </span>

                <span>
                    Pembelian
                </span>

            </a>


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


            <!-- LOGOUT -->

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

        </nav>

    </aside>


    <!-- ==================================================
         MAIN
    ================================================== -->

    <main class="main">


        <!-- ==================================================
             TOPBAR
        ================================================== -->

        <div class="topbar">


            <div class="topbar-left">

                <h1>
                    Detail Pembelian
                </h1>

                <p>
                    Informasi lengkap transaksi pembelian barang.
                </p>

            </div>


            <div class="topbar-user">

                <div class="topbar-user-info">

                    <div class="topbar-user-name">
                        <?= e($nama_lengkap); ?>
                    </div>

                    <div class="topbar-user-role">
                        <?= e(ucfirst($level)); ?>
                    </div>

                </div>


                <div class="user-avatar">

                    <?php

                    $inisial =
                        strtoupper(
                            substr(
                                $nama_lengkap,
                                0,
                                1
                            )
                        );

                    echo e(
                        $inisial !== ''
                            ? $inisial
                            : 'A'
                    );

                    ?>

                </div>

            </div>

        </div>


        <!-- ==================================================
             CONTENT
        ================================================== -->

        <div class="content">


            <!-- ==================================================
                 PAGE HEADER
            ================================================== -->

            <div class="page-header">


                <div>

                    <div class="page-title">
                        Detail Transaksi
                    </div>

                    <div class="page-description">
                        Lihat informasi supplier dan barang
                        pada transaksi pembelian.
                    </div>

                </div>


                <div class="button-group">


                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >

                        ←&nbsp;
                        Kembali

                    </a>


                    <a
                        href="hapus.php?id=<?= $id_pembelian; ?>&csrf_token=<?= e($_SESSION['csrf_token'] ?? ''); ?>"
                        class="btn"
                        style="
                            background:#fef2f2;
                            color:#dc2626;
                        "
                        onclick="
                            return confirm(
                                'Yakin ingin menghapus transaksi pembelian ini? Stok barang juga akan dikurangi kembali.'
                            );
                        "
                    >

                        🗑️&nbsp;
                        Hapus

                    </a>

                </div>

            </div>


            <!-- ==================================================
                 INFORMASI PEMBELIAN
            ================================================== -->

            <div class="info-card">


                <div class="info-card-header">


                    <div>

                        <h3>
                            Informasi Pembelian
                        </h3>

                        <p>
                            Data utama transaksi pembelian.
                        </p>

                    </div>


                    <div class="kode-pembelian">

                        <?= e($nomor_pembelian); ?>

                    </div>

                </div>


                <div class="info-grid">


                    <!-- TANGGAL -->

                    <div class="info-item">

                        <span class="info-label">
                            Tanggal Pembelian
                        </span>

                        <span class="info-value">
                            <?= e($tanggal_pembelian); ?>
                        </span>

                    </div>


                    <!-- SUPPLIER -->

                    <div class="info-item">

                        <span class="info-label">
                            Supplier
                        </span>

                        <span class="info-value">
                            <?= e(
                                $pembelian['nama_supplier']
                                ?? '-'
                            ); ?>
                        </span>

                    </div>


                    <!-- TOTAL -->

                    <div class="info-item">

                        <span class="info-label">
                            Total Pembelian
                        </span>

                        <span class="info-value">

                            Rp
                            <?= number_format(
                                $total_pembelian,
                                0,
                                ',',
                                '.'
                            ); ?>

                        </span>

                    </div>


                </div>

            </div>


            <!-- ==================================================
                 INFORMASI SUPPLIER
            ================================================== -->

            <div class="info-card">


                <div class="info-card-header">

                    <div>

                        <h3>
                            Informasi Supplier
                        </h3>

                        <p>
                            Data supplier yang menyediakan barang.
                        </p>

                    </div>

                </div>


                <div class="info-grid">


                    <!-- NAMA -->

                    <div class="info-item">

                        <span class="info-label">
                            Nama Supplier
                        </span>

                        <span class="info-value">
                            <?= e(
                                $pembelian['nama_supplier']
                                ?? '-'
                            ); ?>
                        </span>

                    </div>


                    <!-- TELEPON -->

                    <div class="info-item">

                        <span class="info-label">
                            No. Telepon
                        </span>

                        <span
                            class="
                                info-value
                                normal
                            "
                        >
                            <?= e(
                                $pembelian['no_telepon']
                                ?? '-'
                            ); ?>
                        </span>

                    </div>


                    <!-- EMAIL -->

                    <div class="info-item">

                        <span class="info-label">
                            Email
                        </span>

                        <span
                            class="
                                info-value
                                normal
                            "
                        >
                            <?= e(
                                $pembelian['email']
                                ?? '-'
                            ); ?>
                        </span>

                    </div>


                    <!-- ALAMAT -->

                    <div
                        class="info-item"
                        style="
                            grid-column:1/-1;
                            border-right:none;
                        "
                    >

                        <span class="info-label">
                            Alamat
                        </span>

                        <span
                            class="
                                info-value
                                normal
                            "
                        >
                            <?= e(
                                $pembelian['alamat']
                                ?? '-'
                            ); ?>
                        </span>

                    </div>


                </div>

            </div>


            <!-- ==================================================
                 DETAIL BARANG
            ================================================== -->

            <div class="detail-card">


                <div class="detail-card-header">

                    <h3>
                        Detail Barang
                    </h3>

                    <p>
                        Daftar barang yang terdapat
                        dalam transaksi pembelian ini.
                    </p>

                </div>


                <?php if (!empty($detail)): ?>


                    <div class="table-wrapper">

                        <table>


                            <thead>

                                <tr>

                                    <th
                                        class="number"
                                    >
                                        No
                                    </th>

                                    <th>
                                        Barang
                                    </th>

                                    <th>
                                        Kategori
                                    </th>

                                    <th>
                                        Jumlah
                                    </th>

                                    <th>
                                        Satuan
                                    </th>

                                    <th
                                        style="
                                            text-align:right;
                                        "
                                    >
                                        Harga Beli
                                    </th>

                                    <th
                                        style="
                                            text-align:right;
                                        "
                                    >
                                        Subtotal
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php

                            $no = 1;

                            foreach (
                                $detail
                                as $item
                            ):

                                $jumlah =
                                    (int) (
                                        $item['jumlah']
                                        ?? 0
                                    );

                                $harga =
                                    (float) (
                                        $item['harga_beli']
                                        ?? 0
                                    );

                                $subtotal =
                                    (float) (
                                        $item['subtotal']
                                        ?? 0
                                    );

                            ?>

                                <tr>


                                    <!-- NO -->

                                    <td
                                        class="number"
                                    >

                                        <?= $no++; ?>

                                    </td>


                                    <!-- BARANG -->

                                    <td>

                                        <div
                                            class="
                                                nama-barang
                                            "
                                        >

                                            <?= e(
                                                $item[
                                                    'nama_barang'
                                                ] ?? '-'
                                            ); ?>

                                        </div>

                                    </td>


                                    <!-- KATEGORI -->

                                    <td>

                                        <span
                                            class="
                                                kategori
                                            "
                                        >

                                            <?= e(
                                                $item[
                                                    'kategori'
                                                ] ?? '-'
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- JUMLAH -->

                                    <td
                                        class="jumlah"
                                    >

                                        <?= number_format(
                                            $jumlah,
                                            0,
                                            ',',
                                            '.'
                                        ); ?>

                                    </td>


                                    <!-- SATUAN -->

                                    <td>

                                        <span
                                            class="satuan"
                                        >

                                            <?= e(
                                                $item[
                                                    'satuan'
                                                ] ?? '-'
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- HARGA -->

                                    <td
                                        class="harga"
                                    >

                                        Rp
                                        <?= number_format(
                                            $harga,
                                            0,
                                            ',',
                                            '.'
                                        ); ?>

                                    </td>


                                    <!-- SUBTOTAL -->

                                    <td
                                        class="subtotal"
                                    >

                                        Rp
                                        <?= number_format(
                                            $subtotal,
                                            0,
                                            ',',
                                            '.'
                                        ); ?>

                                    </td>


                                </tr>

                            <?php endforeach; ?>


                            </tbody>


                        </table>

                    </div>


                    <!-- ==================================================
                         TOTAL
                    ================================================== -->

                    <div class="total-section">


                        <div class="total-box">


                            <div class="total-row">

                                <span
                                    class="total-row-label"
                                >
                                    Jumlah Jenis Barang
                                </span>

                                <span
                                    class="total-row-value"
                                >

                                    <?= number_format(
                                        count($detail),
                                        0,
                                        ',',
                                        '.'
                                    ); ?>

                                    jenis

                                </span>

                            </div>


                            <div class="total-row">

                                <span
                                    class="total-row-label"
                                >
                                    Total Jumlah Barang
                                </span>

                                <span
                                    class="total-row-value"
                                >

                                    <?= number_format(
                                        $total_item,
                                        0,
                                        ',',
                                        '.'
                                    ); ?>

                                    item

                                </span>

                            </div>


                            <div
                                class="
                                    total-row
                                    total-row-grand
                                "
                            >

                                <span
                                    class="total-row-label"
                                >
                                    Total Pembelian
                                </span>

                                <span
                                    class="total-row-value"
                                >

                                    Rp
                                    <?= number_format(
                                        $total_pembelian,
                                        0,
                                        ',',
                                        '.'
                                    ); ?>

                                </span>

                            </div>


                        </div>


                    </div>


                <?php else: ?>


                    <!-- ==================================================
                         EMPTY
                    ================================================== -->

                    <div class="empty-state">


                        <div class="empty-icon">
                            📦
                        </div>


                        <h3>
                            Belum Ada Detail Barang
                        </h3>


                        <p>
                            Transaksi ini belum memiliki
                            detail barang yang tersimpan.
                        </p>


                    </div>


                <?php endif; ?>


            </div>


        </div>

    </main>

</div>

</body>
</html>