<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$sessionUser = $_SESSION['digilib_user'] ?? null;
if (is_array($sessionUser) && isset($sessionUser['role'])) {
    digilibRedirectForRole((string) $sessionUser['role']);
}

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!digilibVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');
        $requestedRole = (string) ($_POST['role'] ?? '');

        if ($email === false || $password === '' || !in_array($requestedRole, ['admin', 'anggota'], true)) {
            $errorMessage = 'Email, kata sandi, dan jenis akun harus diisi dengan benar.';
        } else {
            try {
                $pdo = digilibDatabaseConnection();
                $statement = $pdo->prepare(
                    'SELECT user_id, nama_lengkap, email, password_hash, role, status
                     FROM users
                     WHERE email = :email
                     LIMIT 1'
                );
                $statement->execute(['email' => $email]);
                $account = $statement->fetch();

                $roleMatches = $account
                    && ($requestedRole === 'anggota'
                        ? $account['role'] === 'anggota'
                        : in_array($account['role'], ['admin', 'pustakawan'], true));

                if (!$account || !$roleMatches || $account['status'] !== 'aktif' || !password_verify($password, $account['password_hash'])) {
                    $errorMessage = 'Email, kata sandi, atau jenis akun tidak sesuai.';
                } else {
                    if ($account['role'] === 'anggota') {
                        $memberStatement = $pdo->prepare(
                            "SELECT status FROM anggota WHERE user_id = :user_id LIMIT 1"
                        );
                        $memberStatement->execute(['user_id' => $account['user_id']]);
                        $member = $memberStatement->fetch();
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
            } catch (PDOException $exception) {
                error_log('DIGILIB login database error: ' . $exception->getMessage());
                http_response_code(503);
                $errorMessage = 'Database belum dapat dihubungi. Pastikan MySQL Laragon aktif dan konfigurasi database benar.';
            }
        }
    }
}

$selectedRole = (string) ($_POST['role'] ?? 'admin');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0f766e">
  <title>Masuk — DIGILIB SMART LIBRARY</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= digilibEscape(digilibUrl('assets/styles.css')) ?>">
</head>
<body class="bg-sand text-ink antialiased">
  <main class="login-bg flex min-h-screen items-center justify-center px-4 py-10">
    <div class="grid w-full max-w-5xl overflow-hidden rounded-[28px] bg-white shadow-2xl md:grid-cols-[1fr_1fr]">
      <section class="login-brand-panel hidden flex-col justify-between p-8 text-white sm:p-10 lg:p-12 md:flex">
        <div class="login-brand-content">
          <div class="mb-10 flex items-center gap-3">
            <div class="login-brand-mark grid h-12 w-12 place-items-center rounded-2xl">
              <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8M8 11h8"/></svg>
            </div>
            <div><div class="display-font text-base font-extrabold tracking-wide">DIGILIB</div><div class="text-[10px] font-semibold tracking-[.18em] text-white/70">SMART LIBRARY</div></div>
          </div>
          <p class="mb-3 text-xs font-bold uppercase tracking-[.2em] text-teal-100">Ruang tumbuh bersama</p>
          <h1 class="display-font max-w-sm text-3xl font-extrabold leading-tight lg:text-4xl">Pengetahuan selalu dekat.</h1>
          <p class="mt-4 max-w-sm text-sm leading-7 text-white/75">Kelola koleksi, temukan bacaan, dan dukung pengalaman membaca yang lebih mudah dalam satu perpustakaan digital.</p>
        </div>
        <div class="login-book-art" aria-hidden="true">
          <svg viewBox="0 0 440 250" fill="none" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="222" cy="218" rx="172" ry="17" fill="#092F35" fill-opacity=".22"/>
            <path d="M88 205h264" stroke="#B9EEE0" stroke-opacity=".6" stroke-width="3" stroke-linecap="round"/>
            <path d="M111 203V87c0-8 7-13 15-10l62 25v101l-62-20c-8-3-15 5-15 13Z" fill="#F5D48D"/>
            <path d="M188 102l68-27c8-3 15 2 15 10v116l-68 22c-8 3-15-2-15-10V102Z" fill="#FFF9E9"/>
            <path d="M188 102l-62-25c-8-3-15 2-15 10v116c0-8 7-13 15-10l62 20V102Z" stroke="#FDF3D8" stroke-width="3"/>
            <path d="M196 106l57-22v13l-57 22v-13Z" fill="#C9E9D8"/>
            <path d="M196 132l46-18M196 143l46-18" stroke="#A7CFC0" stroke-width="4" stroke-linecap="round"/>
            <path d="M126 105l48 19M126 119l48 19M126 133l37 15" stroke="#D4A85E" stroke-width="4" stroke-linecap="round"/>
            <path d="M273 202V69c0-7 6-12 13-9l15 6v135l-15-6c-7-3-13 0-13 7Z" fill="#F28D72"/>
            <path d="M301 66l15-6c7-3 13 2 13 9v133c0-7-6-10-13-7l-15 6V66Z" fill="#FFD0A3"/>
            <path d="M282 81l8 3M282 94l8 3M306 80l8-3M306 93l8-3" stroke="#FFF4DD" stroke-width="3" stroke-linecap="round"/>
            <path d="M320 202V99c0-6 5-9 11-7l18 6v103l-18-6c-6-2-11 1-11 7Z" fill="#7BC6B1"/>
            <path d="M349 98l17-5c6-2 11 2 11 8v100c0-6-5-9-11-7l-17 7V98Z" fill="#BDE8D5"/>
            <circle cx="352" cy="52" r="17" fill="#F5D48D" fill-opacity=".95"/>
            <path d="M352 24v-8M380 52h8M324 52h-8M372 32l6-6M332 72l-6 6" stroke="#F5D48D" stroke-width="3" stroke-linecap="round"/>
            <path d="M81 65c8-12 19-18 33-17" stroke="#A4E2CF" stroke-width="3" stroke-linecap="round" stroke-dasharray="2 8"/>
            <path d="M73 57l8 8 8-8" stroke="#A4E2CF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="login-brand-footer flex items-center gap-3">
          <span class="login-brand-footer-icon grid h-9 w-9 place-items-center rounded-xl">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
          </span>
          <div><div class="text-xs font-bold">Satu ruang untuk setiap cerita.</div><div class="mt-1 text-[11px] text-white/65">Perpustakaan digital yang dekat dengan Anda.</div></div>
        </div>
      </section>
      <section class="p-7 sm:p-11">
        <div class="mb-9 flex items-center gap-3 md:hidden">
          <div class="grid h-11 w-11 place-items-center rounded-2xl bg-teal-700 text-white">D</div>
          <div><div class="display-font text-sm font-extrabold">DIGILIB</div><div class="text-xs text-slate-500">SMART LIBRARY</div></div>
        </div>
        <div class="mb-8">
          <p class="mb-2 text-xs font-bold uppercase tracking-[.16em] text-teal-700">Selamat datang kembali</p>
          <h2 class="display-font text-2xl font-extrabold">Masuk ke akun Anda</h2>
          <p class="mt-2 text-sm text-slate-500">Pilih jenis akun yang akan digunakan.</p>
        </div>
        <form method="post" action="<?= digilibEscape(digilibUrl('index.php')) ?>" class="space-y-5">
          <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
          <div>
            <label class="field-label" for="role">Masuk sebagai</label>
            <select class="field" id="role" name="role" required>
              <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Admin / Pustakawan</option>
              <option value="anggota" <?= $selectedRole === 'anggota' ? 'selected' : '' ?>>Anggota</option>
            </select>
          </div>
          <div><label class="field-label" for="email">Email</label><input class="field" id="email" name="email" type="email" autocomplete="username" placeholder="nama@email.com" value="<?= digilibEscape((string) ($_POST['email'] ?? '')) ?>" required></div>
          <div><label class="field-label" for="password">Kata sandi</label><input class="field" id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required></div>
          <?php if ($errorMessage !== ''): ?>
            <p class="rounded-xl bg-red-50 px-3 py-2 text-sm font-medium text-red-700" role="alert"><?= digilibEscape($errorMessage) ?></p>
          <?php endif; ?>
          <button class="btn btn-primary w-full py-3" type="submit">Masuk ke perpustakaan</button>
        </form>
        <div class="mt-7 border-t border-slate-100 pt-5">
          <div class="mb-3 text-xs font-bold text-slate-500">AKUN DEMO</div>
          <div class="grid gap-2 sm:grid-cols-2">
            <button class="rounded-xl border border-slate-200 p-3 text-left hover:border-teal-400 hover:bg-teal-50" type="button" data-demo-role="admin" data-demo-email="admin@digilib.id" data-demo-password="admin123"><span class="block text-xs font-bold text-slate-700">Admin / Pustakawan</span><span class="mt-1 block text-[11px] text-slate-500">admin@digilib.id</span></button>
            <button class="rounded-xl border border-slate-200 p-3 text-left hover:border-teal-400 hover:bg-teal-50" type="button" data-demo-role="anggota" data-demo-email="anggota@digilib.id" data-demo-password="anggota123"><span class="block text-xs font-bold text-slate-700">Anggota</span><span class="mt-1 block text-[11px] text-slate-500">anggota@digilib.id</span></button>
          </div>
        </div>
        <script>
          document.querySelectorAll('[data-demo-role]').forEach((button) => {
            button.addEventListener('click', () => {
              document.getElementById('role').value = button.dataset.demoRole;
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
