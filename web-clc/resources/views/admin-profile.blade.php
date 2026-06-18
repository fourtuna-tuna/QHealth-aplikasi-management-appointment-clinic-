<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile Admin - QHealth</title>
    <style>
        :root { --brand:#0f766e; --brand-soft:#168c8e; --ink:#020707; --muted:#5c6767; --line:#dbe7e5; --bg:#f6faf9; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif; background:var(--bg); color:var(--ink); }
        .page { min-height:100vh; background:white; }
        .topbar { min-height:88px; background:var(--brand); color:white; display:flex; align-items:center; justify-content:space-between; padding:0 42px; }
        .topbar h1 { margin:0; font-size:20px; letter-spacing:0; }
        .brand { font-weight:900; font-size:19px; }
        .wrap { max-width:820px; margin:0 auto; padding:54px 24px 68px; text-align:center; }
        .avatar { width:156px; height:156px; margin:0 auto 30px; border-radius:50%; background:#020707; display:grid; place-items:center; color:white; font-size:54px; font-weight:900; }
        .card { max-width:570px; margin:0 auto; background:#168c8e; color:white; border-radius:14px; padding:26px 52px 28px; box-shadow:0 12px 24px rgba(15,118,110,.12); }
        .card h2 { margin:0 0 28px; font-size:28px; letter-spacing:0; }
        .row { display:grid; grid-template-columns:120px minmax(0,1fr); gap:18px; align-items:center; padding:14px 4px 8px; border-bottom:1px solid rgba(255,255,255,.7); text-align:left; }
        .label { font-weight:900; }
        .value { text-align:right; font-weight:800; overflow-wrap:anywhere; }
        .actions { margin-top:28px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
        .btn { min-width:190px; min-height:34px; border-radius:7px; display:inline-flex; align-items:center; justify-content:center; padding:8px 14px; background:white; color:var(--brand); text-decoration:none; font-weight:900; }
        .btn.secondary { background:#e9f7f5; }
        @media (max-width:640px) {
            .topbar { padding:0 20px; min-height:74px; }
            .wrap { padding-top:34px; }
            .card { padding:24px 22px; border-radius:10px; }
            .row { grid-template-columns:1fr; gap:5px; }
            .value { text-align:left; }
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="topbar">
            <h1>Profile Admin</h1>
            <div class="brand">QHealth Admin</div>
        </header>
        <main class="wrap">
            <div class="avatar">{{ strtoupper(substr($admin?->name ?: 'A', 0, 1)) }}</div>
            <section class="card">
                <h2>{{ $admin?->name ?: '-' }}</h2>
                <div class="row"><div class="label">Email</div><div class="value">{{ $admin?->email ?: '-' }}</div></div>
                <div class="row"><div class="label">Role</div><div class="value">{{ $admin?->role ?: '-' }}</div></div>
                <div class="actions">
                    <a class="btn" href="/admin">Kembali</a>
                    <a class="btn secondary" href="/admin/settings">Settings</a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
