<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$sessionUser = $_SESSION['digilib_user'] ?? null;
if (is_array($sessionUser) && isset($sessionUser['role'])) {
  digilibRedirectForRole((string) $sessionUser['role']);
}

$showLogin = $_SERVER['REQUEST_METHOD'] === 'POST'
    || isset($_GET['login'])
    || isset($_GET['account']);
if (!$showLogin) {
    require __DIR__ . '/includes/landing_page.php';
    exit;
}

$errorMessage = isset($_GET['account']) && $_GET['account'] === 'inactive'
    ? 'Status keanggotaan tidak aktif. Hubungi pustakawan.'
    : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!digilibVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');

        if ($email === false || $password === '') {
          $errorMessage = 'Email dan kata sandi harus diisi dengan benar.';
        } else {
            try {
                $database = digilibDatabaseRead();
                $account = null;
                foreach ($database['users'] as $user) {
                    if (strcasecmp((string) $user['email'], (string) $email) === 0) {
                        $account = $user;
                        break;
                    }
                }

                $supportedRole = $account && in_array($account['role'], ['admin', 'pustakawan', 'anggota'], true);

                if (!$account || !$supportedRole || $account['status'] !== 'aktif' || !password_verify($password, $account['password_hash'])) {
                    $errorMessage = 'Email, kata sandi, atau jenis akun tidak sesuai.';
                } else {
                    if ($account['role'] === 'anggota') {
                        $member = null;
                        foreach ($database['anggota'] as $memberRecord) {
                            if ((int) $memberRecord['user_id'] === (int) $account['user_id']) {
                                $member = $memberRecord;
                                break;
                            }
                        }
                        if (!$member || $member['status'] !== 'aktif') {
                            $errorMessage = 'Status keanggotaan tidak aktif. Hubungi pustakawan.';
                        }
                    }
                    if ($errorMessage === '') {
                        session_regenerate_id(true);
                        $_SESSION['digilib_user'] = [
                            'user_id' => (int) $account['user_id'],
                            'nama_lengkap' => $account['nama_lengkap'],
                            'email' => $account['email'],
                            'role' => $account['role'],
                        ];
                        digilibRedirectForRole((string) $account['role']);
                    }
                }
            } catch (RuntimeException $exception) {
                error_log('DIGILIB login JSON database error: ' . $exception->getMessage());
                http_response_code(503);
                $errorMessage = 'Data perpustakaan belum dapat dibaca. Periksa izin folder database pada hosting.';
            }
        }
    }
}

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0f766e">
  <title>Masuk — DIGILIB SMART LIBRARY</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= digilibEscape(digilibUrl('assets/styles.css?v=' . (string) filemtime(__DIR__ . '/assets/styles.css'))) ?>">
</head>
<body class="bg-sand text-ink antialiased">
  <main class="login-bg login-page">
    <div class="login-card">
      <section class="login-brand-panel text-white">
        <div class="login-brand-identity">
          <span class="login-brand-mark" aria-hidden="true">
            <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8M8 11h8"/></svg>
          </span>
          <span><span class="login-brand-name">DIGILIB</span><span class="login-brand-subtitle">SMART LIBRARY</span></span>
        </div>
        <div class="login-brand-copy">
          <p class="login-brand-eyebrow">YOUR GATEWAY TO KNOWLEDGE</p>
          <h1 class="login-brand-heading">Setiap Buku, Membuka Dunia Baru.</h1>
          <p class="login-brand-description">Temukan inspirasi dari setiap halaman, jelajahi beragam koleksi bacaan, dan bangun pengalaman literasi yang lebih bermakna bersama DIGILIB.</p>
        </div>
      </section>
      <section class="login-form-panel">
        <div class="login-form-heading">
          <a class="login-back-link" href="<?= digilibEscape(digilibUrl('index.php')) ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>Kembali ke beranda</a>
          <p class="mb-2 text-xs font-bold uppercase tracking-[.16em] text-teal-700">Selamat datang kembali</p>
          <h2 class="display-font text-2xl font-extrabold">Masuk ke akun Anda</h2>
          <p class="mt-2 text-sm text-slate-500">Gunakan email dan kata sandi akun yang terdaftar.</p>
        </div>
        <form method="post" action="<?= digilibEscape(digilibUrl('index.php?login=1')) ?>" class="space-y-5">
          <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
          <div><label class="field-label" for="email">Email</label><input class="field" id="email" name="email" type="email" autocomplete="username" placeholder="nama@email.com" value="<?= digilibEscape((string) ($_POST['email'] ?? '')) ?>" required></div>
          <div><label class="field-label" for="password">Kata sandi</label><input class="field" id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required></div>
          <?php if ($errorMessage !== ''): ?>
            <p class="rounded-xl bg-red-50 px-3 py-2 text-sm font-medium text-red-700" role="alert"><?= digilibEscape($errorMessage) ?></p>
          <?php endif; ?>
          <button class="btn btn-primary w-full py-3" type="submit">Masuk ke perpustakaan</button>
        </form>
        <div class="mt-7 border-t border-slate-100 pt-5">
          <div class="mb-3 text-xs font-bold text-slate-500">AKUN USER</div>
          <div class="grid gap-2">
            <button class="rounded-xl border border-slate-200 p-3 text-left hover:border-teal-400 hover:bg-teal-50" type="button" data-demo-user data-demo-email="anggota@digilib.id" data-demo-password="anggota123"><span class="block text-xs font-bold text-slate-700">User</span><span class="mt-1 block text-[11px] text-slate-500">anggota@digilib.id</span></button>
          </div>
        </div>
        <script>
          document.querySelectorAll('[data-demo-user]').forEach((button) => {
            button.addEventListener('click', () => {
              document.getElementById('email').value = button.dataset.demoEmail;
              document.getElementById('password').value = button.dataset.demoPassword;
              document.getElementById('password').focus();
            });
          });
        </script>
      </section>
    </div>
  </main>
</body>
</html>
