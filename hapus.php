<?php

include "../../config/koneksi.php";
include "../../config/auth.php";

wajib_login();

/*
|--------------------------------------------------------------------------
| HANYA PEMILIK YANG BOLEH MENGHAPUS USER
|--------------------------------------------------------------------------
*/
if (!punya_role('pemilik')) {
    header("Location: ../../index.php?error=akses_ditolak");
    exit;
}

/*
|--------------------------------------------------------------------------
| CEK METHOD
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?error=permintaan_tidak_valid");
    exit;
}

/*
|--------------------------------------------------------------------------
| CEK CSRF
|--------------------------------------------------------------------------
*/
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    header("Location: index.php?error=csrf");
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL ID USER
|--------------------------------------------------------------------------
*/
$id_user = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id_user <= 0) {
    header("Location: index.php?error=user_tidak_valid");
    exit;
}

/*
|--------------------------------------------------------------------------
| TIDAK BOLEH MENGHAPUS AKUN YANG SEDANG LOGIN
|--------------------------------------------------------------------------
*/
if ($id_user === (int) $_SESSION['id_user']) {
    header("Location: index.php?error=hapus_diri_sendiri");
    exit;
}

/*
|--------------------------------------------------------------------------
| CEK DATA USER
|--------------------------------------------------------------------------
*/
$stmt_cek = mysqli_prepare(
    $conn,
    "SELECT id_user, username, level FROM users WHERE id_user = ? LIMIT 1"
);

if (!$stmt_cek) {
    header("Location: index.php?error=database");
    exit;
}

mysqli_stmt_bind_param(
    $stmt_cek,
    "i",
    $id_user
);

mysqli_stmt_execute($stmt_cek);

$result_cek = mysqli_stmt_get_result($stmt_cek);
$user = mysqli_fetch_assoc($result_cek);

mysqli_stmt_close($stmt_cek);

if (!$user) {
    header("Location: index.php?error=user_tidak_ditemukan");
    exit;
}

/*
|--------------------------------------------------------------------------
| JANGAN HAPUS PEMILIK TERAKHIR
|--------------------------------------------------------------------------
*/
if ($user['level'] === 'pemilik') {

    $query_jumlah_pemilik = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS jumlah FROM users WHERE level = 'pemilik'"
    );

    if (!$query_jumlah_pemilik) {
        header("Location: index.php?error=database");
        exit;
    }

    $data_jumlah = mysqli_fetch_assoc($query_jumlah_pemilik);

    $jumlah_pemilik = (int) ($data_jumlah['jumlah'] ?? 0);

    if ($jumlah_pemilik <= 1) {
        header("Location: index.php?error=pemilik_terakhir");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| HAPUS USER
|--------------------------------------------------------------------------
*/
$stmt_hapus = mysqli_prepare(
    $conn,
    "DELETE FROM users WHERE id_user = ? LIMIT 1"
);

if (!$stmt_hapus) {
    header("Location: index.php?error=database");
    exit;
}

mysqli_stmt_bind_param(
    $stmt_hapus,
    "i",
    $id_user
);

$berhasil = mysqli_stmt_execute($stmt_hapus);

mysqli_stmt_close($stmt_hapus);

/*
|--------------------------------------------------------------------------
| HASIL
|--------------------------------------------------------------------------
*/
if ($berhasil) {

    // Ganti token CSRF setelah operasi berhasil
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: index.php?success=user_dihapus");
    exit;
}

header("Location: index.php?error=gagal_hapus");
exit;

?>