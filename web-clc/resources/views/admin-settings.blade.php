<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings Admin - QHealth</title>
    <style>
        :root { --brand:#0f766e; --brand-soft:#168c8e; --ink:#020707; --muted:#5c6767; --line:#dbe7e5; --bg:#f6faf9; --danger:#b42318; --ok:#027a48; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif; background:var(--bg); color:var(--ink); }
        .topbar { min-height:88px; background:var(--brand); color:white; display:flex; align-items:center; justify-content:space-between; padding:0 42px; }
        .topbar h1 { margin:0; font-size:20px; letter-spacing:0; }
        .brand { font-weight:900; font-size:19px; }
        .wrap { max-width:1080px; margin:0 auto; padding:30px 24px 64px; }
        .hero { display:flex; align-items:center; justify-content:space-between; gap:20px; margin-bottom:22px; }
        .hero h2 { margin:0 0 6px; font-size:26px; }
        .hero p { margin:0; color:var(--muted); }
        .avatar { width:72px; height:72px; border-radius:50%; background:#020707; color:white; display:grid; place-items:center; font-size:28px; font-weight:900; flex:none; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; align-items:start; }
        .panel { background:white; border:1px solid var(--line); border-radius:8px; padding:20px; box-shadow:0 1px 6px rgba(39,50,68,.06); }
        .panel.wide { grid-column:1 / -1; }
        .panel h3 { margin:0 0 14px; font-size:18px; }
        .field { display:grid; gap:7px; margin-bottom:13px; }
        label { color:#5f6d76; font-size:13px; font-weight:800; }
        input, textarea { width:100%; border:1px solid var(--line); border-radius:4px; padding:10px; background:white; color:var(--ink); font:inherit; }
        textarea { min-height:92px; resize:vertical; }
        .readonly { min-height:39px; display:flex; align-items:center; padding:10px; border:1px solid var(--line); border-radius:4px; background:#fbfdfe; color:#43515a; }
        .actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:16px; }
        .btn { min-height:36px; border:0; border-radius:4px; padding:9px 14px; display:inline-flex; align-items:center; justify-content:center; background:var(--brand-soft); color:white; text-decoration:none; font-weight:900; cursor:pointer; }
        .btn.secondary { background:#edf2f4; color:#43515a; }
        .notice, .error { margin-bottom:18px; padding:12px 14px; border-radius:4px; font-weight:800; }
        .notice { background:#ecfdf3; color:var(--ok); }
        .error { background:#fef3f2; color:var(--danger); }
        @media (max-width:820px) {
            .topbar { padding:0 20px; min-height:74px; }
            .hero { align-items:flex-start; }
            .grid { grid-template-columns:1fr; }
            .panel.wide { grid-column:auto; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <h1>Settings Admin</h1>
        <div class="brand">QHealth Admin</div>
    </header>
    <main class="wrap">
        @if(session('status')) <div class="notice">{{ session('status') }}</div> @endif
        @if($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif

        <div class="hero">
            <div>
                <h2>{{ $admin?->name ?: '-' }}</h2>
                <p>Kelola satu akun admin utama dan identitas klinik.</p>
            </div>
            <div class="avatar">{{ strtoupper(substr($admin?->name ?: 'A', 0, 1)) }}</div>
        </div>

        <div class="grid">
            <section class="panel">
                <h3>Akun Admin Utama</h3>
                <form method="post" action="/admin/settings/account">
                    @csrf
                    <div class="field">
                        <label>Nama Admin</label>
                        <input name="name" value="{{ old('name', $admin?->name) }}" required>
                    </div>
                    <div class="field">
                        <label>Email Admin</label>
                        <input name="email" type="email" value="{{ old('email', $admin?->email) }}" required>
                    </div>
                    <div class="field">
                        <label>Role</label>
                        <div class="readonly">{{ $admin?->role ?: '-' }}</div>
                    </div>
                    <div class="actions">
                        <button class="btn" type="submit">Simpan Akun</button>
                        <a class="btn secondary" href="/admin/profile">Lihat Profile</a>
                    </div>
                </form>
            </section>

            <section class="panel">
                <h3>Ganti Password</h3>
                <form method="post" action="/admin/settings/password">
                    @csrf
                    <div class="field">
                        <label>Password Lama</label>
                        <input name="current_password" type="password" autocomplete="current-password" required>
                    </div>
                    <div class="field">
                        <label>Password Baru</label>
                        <input name="password" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="field">
                        <label>Konfirmasi Password Baru</label>
                        <input name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="actions">
                        <button class="btn" type="submit">Update Password</button>
                    </div>
                </form>
            </section>

            <section class="panel wide">
                <h3>Profil Klinik</h3>
                <form method="post" action="/admin/settings/clinic">
                    @csrf
                    <div class="grid">
                        <div class="field">
                            <label>Nama Klinik</label>
                            <input name="name" value="{{ old('name', $clinic['name'] ?? 'QHealth Clinic') }}" required>
                        </div>
                        <div class="field">
                            <label>Nomor Telepon</label>
                            <input name="phone" value="{{ old('phone', $clinic['phone'] ?? '') }}">
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label>Alamat Klinik</label>
                            <textarea name="address">{{ old('address', $clinic['address'] ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn" type="submit">Simpan Profil Klinik</button>
                        <a class="btn secondary" href="/admin">Kembali ke Panel Admin</a>
                    </div>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
