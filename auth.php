```php
<?php

/*
|--------------------------------------------------------------------------
| PENGATURAN SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| CEK LOGIN
|--------------------------------------------------------------------------
*/

function sudah_login()
{
    return isset($_SESSION['id_user'])
        && is_numeric($_SESSION['id_user'])
        && (int) $_SESSION['id_user'] > 0;
}


/*
|--------------------------------------------------------------------------
| WAJIB LOGIN
|--------------------------------------------------------------------------
*/

function wajib_login()
{
    if (!sudah_login()) {

        header('Location: /asli2/login.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| CEK ROLE
|--------------------------------------------------------------------------
*/

function punya_role($role)
{
    return sudah_login()
        && isset($_SESSION['level'])
        && is_string($_SESSION['level'])
        && $_SESSION['level'] === $role;
}


/*
|--------------------------------------------------------------------------
| WAJIB ADMIN
|--------------------------------------------------------------------------
*/

function wajib_admin()
{
    wajib_login();

    if (!punya_role('admin')) {

        header(
            'Location: /asli2/index.php?error=akses_ditolak'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| WAJIB ADMIN ATAU PEMILIK
|--------------------------------------------------------------------------
*/

function wajib_admin_pemilik()
{
    wajib_login();

    if (
        !isset($_SESSION['level']) ||
        !is_string($_SESSION['level']) ||
        !in_array(
            $_SESSION['level'],
            ['admin', 'pemilik'],
            true
        )
    ) {

        header(
            'Location: /asli2/index.php?error=akses_ditolak'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| ESCAPE OUTPUT HTML
|--------------------------------------------------------------------------
*/

function e($text)
{
    return htmlspecialchars(
        (string) ($text ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

?>
```
