<?php

include "../../config/koneksi.php";
include "../../config/auth.php";

wajib_admin();


/*
|--------------------------------------------------------------------------
| HANYA BOLEH POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

$csrf_token =
    $_POST['csrf_token'] ?? '';

if (
    !is_string($csrf_token) ||
    !isset($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token']) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $csrf_token
    )
) {

    header(
        "Location: index.php?error=permintaan_tidak_valid"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA FORM
|--------------------------------------------------------------------------
*/

$tanggal =
    trim(
        (string) (
            $_POST['tanggal_pembelian']
            ?? ''
        )
    );


$id_supplier =
    filter_var(
        $_POST['id_supplier'] ?? null,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1
            ]
        ]
    );


$id_barang =
    $_POST['id_barang'] ?? [];


$jumlah =
    $_POST['jumlah'] ?? [];


$harga_beli =
    $_POST['harga_beli'] ?? [];


$subtotal =
    $_POST['subtotal'] ?? [];


/*
|--------------------------------------------------------------------------
| VALIDASI ARRAY
|--------------------------------------------------------------------------
*/

if (
    !is_array($id_barang) ||
    !is_array($jumlah) ||
    !is_array($harga_beli) ||
    !is_array($subtotal)
) {

    header(
        "Location: index.php?error=data_tidak_valid"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDASI TANGGAL
|--------------------------------------------------------------------------
*/

$date = DateTime::createFromFormat(
    'Y-m-d',
    $tanggal
);

if (
    !$date ||
    $date->format('Y-m-d') !== $tanggal
) {

    header(
        "Location: index.php?error=tanggal_tidak_valid"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDASI SUPPLIER
|--------------------------------------------------------------------------
*/

if (
    $id_supplier === false ||
    $id_supplier === null
) {

    header(
        "Location: index.php?error=supplier_tidak_valid"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CEK SUPPLIER ADA
|--------------------------------------------------------------------------
*/

$stmt_supplier = mysqli_prepare(
    $conn,
    "
    SELECT id_supplier
    FROM supplier
    WHERE id_supplier = ?
    LIMIT 1
    "
);

if (!$stmt_supplier) {

    header(
        "Location: index.php?error=gagal_database"
    );

    exit;
}

mysqli_stmt_bind_param(
    $stmt_supplier,
    "i",
    $id_supplier
);

mysqli_stmt_execute(
    $stmt_supplier
);

mysqli_stmt_store_result(
    $stmt_supplier
);

if (
    mysqli_stmt_num_rows(
        $stmt_supplier
    ) !== 1
) {

    mysqli_stmt_close(
        $stmt_supplier
    );

    header(
        "Location: index.php?error=supplier_tidak_ditemukan"
    );

    exit;
}

mysqli_stmt_close(
    $stmt_supplier
);


/*
|--------------------------------------------------------------------------
| SIAPKAN DETAIL BARANG
|--------------------------------------------------------------------------
|
| tambah.php mengirim semua baris barang.
| Barang dengan jumlah 0 harus dilewati.
|
*/

$detail_transaksi = [];

$barang_sudah_dipilih = [];


for (
    $i = 0;
    $i < count($id_barang);
    $i++
) {

    $jumlah_raw =
        $jumlah[$i] ?? '0';


    /*
    |----------------------------------------------------------------------
    | JUMLAH 0 = TIDAK DIPILIH
    |----------------------------------------------------------------------
    */

    if (
        $jumlah_raw === '' ||
        $jumlah_raw === null ||
        $jumlah_raw === '0' ||
        (is_numeric($jumlah_raw) && (int) $jumlah_raw === 0)
    ) {

        continue;
    }


    /*
    |----------------------------------------------------------------------
    | VALIDASI ID BARANG
    |----------------------------------------------------------------------
    */

    $id_barang_item =
        filter_var(
            $id_barang[$i] ?? null,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1
                ]
            ]
        );


    if (
        $id_barang_item === false ||
        $id_barang_item === null
    ) {

        header(
            "Location: index.php?error=barang_tidak_valid"
        );

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | CEGAH BARANG DUPLIKAT
    |----------------------------------------------------------------------
    */

    if (
        isset(
            $barang_sudah_dipilih[
                $id_barang_item
            ]
        )
    ) {

        header(
            "Location: index.php?error=barang_duplikat"
        );

        exit;
    }


    $barang_sudah_dipilih[
        $id_barang_item
    ] = true;


    /*
    |----------------------------------------------------------------------
    | VALIDASI JUMLAH
    |----------------------------------------------------------------------
    */

    if (
        !is_scalar($jumlah_raw) ||
        !ctype_digit(
            (string) $jumlah_raw
        )
    ) {

        header(
            "Location: index.php?error=jumlah_tidak_valid"
        );

        exit;
    }


    $jumlah_item =
        (int) $jumlah_raw;


    if ($jumlah_item <= 0) {

        header(
            "Location: index.php?error=jumlah_tidak_valid"
        );

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | BATAS INT MYSQL
    |----------------------------------------------------------------------
    */

    if (
        $jumlah_item > 2147483647
    ) {

        header(
            "Location: index.php?error=jumlah_terlalu_besar"
        );

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | HARGA DARI FORM TIDAK DIPERCAYA
    |--------------------------------------------------------------------------
    |
    | Harga yang dikirim browser sengaja TIDAK digunakan.
    | Kita ambil ulang harga dari database.
    |
    */

    $stmt_barang = mysqli_prepare(
        $conn,
        "
        SELECT
            id_barang,
            harga_beli,
            stok
        FROM barang
        WHERE id_barang = ?
        LIMIT 1
        "
    );


    if (!$stmt_barang) {

        header(
            "Location: index.php?error=gagal_database"
        );

        exit;
    }


    mysqli_stmt_bind_param(
        $stmt_barang,
        "i",
        $id_barang_item
    );


    mysqli_stmt_execute(
        $stmt_barang
    );


    $result_barang =
        mysqli_stmt_get_result(
            $stmt_barang
        );


    $data_barang =
        mysqli_fetch_assoc(
            $result_barang
        );


    mysqli_stmt_close(
        $stmt_barang
    );


    if (!$data_barang) {

        header(
            "Location: index.php?error=barang_tidak_ditemukan"
        );

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | HARGA ASLI DARI DATABASE
    |----------------------------------------------------------------------
    */

    $harga_item =
        (float) $data_barang['harga_beli'];


    $stok_sekarang =
        (int) $data_barang['stok'];


    /*
    |----------------------------------------------------------------------
    | CEK OVERFLOW STOK
    |----------------------------------------------------------------------
    */

    if (
        $stok_sekarang >
        2147483647 - $jumlah_item
    ) {

        header(
            "Location: index.php?error=stok_terlalu_besar"
        );

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | HITUNG SUBTOTAL DI SERVER
    |----------------------------------------------------------------------
    */

    $subtotal_item =
        $harga_item * $jumlah_item;


    /*
    |----------------------------------------------------------------------
    | SIMPAN DATA DETAIL
    |----------------------------------------------------------------------
    */

    $detail_transaksi[] = [

        'id_barang' =>
            $id_barang_item,

        'jumlah' =>
            $jumlah_item,

        'harga_beli' =>
            $harga_item,

        'subtotal' =>
            $subtotal_item

    ];

}


/*
|--------------------------------------------------------------------------
| MINIMAL SATU BARANG
|--------------------------------------------------------------------------
*/

if (
    count($detail_transaksi) === 0
) {

    header(
        "Location: index.php?error=barang_belum_dipilih"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| HITUNG TOTAL DI SERVER
|--------------------------------------------------------------------------
*/

$total_pembelian = 0;


foreach (
    $detail_transaksi as $detail
) {

    $total_pembelian +=
        $detail['subtotal'];

}


/*
|--------------------------------------------------------------------------
| MULAI TRANSAKSI DATABASE
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction(
    $conn
);


try {


    /*
    |--------------------------------------------------------------------------
    | INSERT PEMBELIAN
    |--------------------------------------------------------------------------
    */

    $stmt_pembelian = mysqli_prepare(
        $conn,
        "
        INSERT INTO pembelian
        (
            tanggal_pembelian,
            id_supplier,
            total_pembelian
        )
        VALUES
        (?, ?, ?)
        "
    );


    if (!$stmt_pembelian) {

        throw new Exception(
            "Gagal menyiapkan transaksi pembelian."
        );

    }


    mysqli_stmt_bind_param(
        $stmt_pembelian,
        "sid",
        $tanggal,
        $id_supplier,
        $total_pembelian
    );


    if (
        !mysqli_stmt_execute(
            $stmt_pembelian
        )
    ) {

        mysqli_stmt_close(
            $stmt_pembelian
        );

        throw new Exception(
            "Gagal menyimpan transaksi pembelian."
        );

    }


    $id_pembelian =
        mysqli_insert_id($conn);


    mysqli_stmt_close(
        $stmt_pembelian
    );


    /*
    |--------------------------------------------------------------------------
    | PREPARE INSERT DETAIL
    |--------------------------------------------------------------------------
    */

    $stmt_detail = mysqli_prepare(
        $conn,
        "
        INSERT INTO detail_pembelian
        (
            id_pembelian,
            id_barang,
            jumlah,
            harga_beli,
            subtotal
        )
        VALUES
        (?, ?, ?, ?, ?)
        "
    );


    if (!$stmt_detail) {

        throw new Exception(
            "Gagal menyiapkan detail pembelian."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PREPARE UPDATE STOK
    |--------------------------------------------------------------------------
    */

    $stmt_stok = mysqli_prepare(
        $conn,
        "
        UPDATE barang
        SET stok = stok + ?
        WHERE id_barang = ?
        "
    );


    if (!$stmt_stok) {

        mysqli_stmt_close(
            $stmt_detail
        );

        throw new Exception(
            "Gagal menyiapkan pembaruan stok."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT DETAIL + UPDATE STOK
    |--------------------------------------------------------------------------
    */

    foreach (
        $detail_transaksi as $detail
    ) {

        $id_barang_item =
            $detail['id_barang'];

        $jumlah_item =
            $detail['jumlah'];

        $harga_item =
            $detail['harga_beli'];

        $subtotal_item =
            $detail['subtotal'];


        /*
        |----------------------------------------------------------------------
        | INSERT DETAIL
        |----------------------------------------------------------------------
        */

        mysqli_stmt_bind_param(
            $stmt_detail,
            "iiidd",
            $id_pembelian,
            $id_barang_item,
            $jumlah_item,
            $harga_item,
            $subtotal_item
        );


        if (
            !mysqli_stmt_execute(
                $stmt_detail
            )
        ) {

            throw new Exception(
                "Gagal menyimpan detail pembelian."
            );

        }


        /*
        |----------------------------------------------------------------------
        | UPDATE STOK
        |----------------------------------------------------------------------
        */

        mysqli_stmt_bind_param(
            $stmt_stok,
            "ii",
            $jumlah_item,
            $id_barang_item
        );


        if (
            !mysqli_stmt_execute(
                $stmt_stok
            )
        ) {

            throw new Exception(
                "Gagal memperbarui stok barang."
            );

        }


        if (
            mysqli_stmt_affected_rows(
                $stmt_stok
            ) !== 1
        ) {

            throw new Exception(
                "Barang tidak ditemukan saat memperbarui stok."
            );

        }

    }


    mysqli_stmt_close(
        $stmt_detail
    );

    mysqli_stmt_close(
        $stmt_stok
    );


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    mysqli_commit(
        $conn
    );


    /*
    |--------------------------------------------------------------------------
    | ROTASI CSRF TOKEN
    |--------------------------------------------------------------------------
    */

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));


    /*
    |--------------------------------------------------------------------------
    | REDIRECT BERHASIL
    |--------------------------------------------------------------------------
    */

    header(
        "Location: index.php?success=1"
    );

    exit;


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    mysqli_rollback(
        $conn
    );


    /*
    |--------------------------------------------------------------------------
    | ERROR GENERIK
    |--------------------------------------------------------------------------
    |
    | Jangan tampilkan detail error database kepada user.
    |
    */

    header(
        "Location: index.php?error=gagal_simpan"
    );

    exit;

}

?>