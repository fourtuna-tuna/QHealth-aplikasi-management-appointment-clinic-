<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin QHealth</title>
    <style>
        :root { --ink:#020707; --muted:#5c6767; --line:#dbe7e5; --brand:#0f766e; --brand-accent:#168c8e; --page:#82c8bc; --danger:#b42318; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; font-family:Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif; background:var(--page); color:var(--ink); }
        main { width:min(420px, calc(100vw - 32px)); background:#fff; border:1px solid var(--line); border-radius:8px; padding:28px 26px 26px; box-shadow:0 14px 34px rgba(15,118,110,.13); }
        .brand-mark { display:grid; justify-items:center; gap:8px; margin-bottom:22px; text-align:center; }
        .brand-mark img { display:block; width:118px; height:auto; object-fit:contain; }
        h1 { margin:0; font-size:24px; letter-spacing:0; line-height:1; }
        h1 span { font-size:.52em; vertical-align:top; }
        p { margin:0 0 22px; color:var(--muted); text-align:center; }
        form { display:grid; gap:14px; }
        label { display:grid; gap:6px; color:var(--muted); font-size:13px; }
        input { width:100%; border:1px solid var(--line); border-radius:7px; padding:11px 12px; font:inherit; }
        button { border:0; border-radius:7px; padding:11px 14px; font-weight:700; cursor:pointer; background:var(--brand-accent); color:#fff; }
        .error { margin:0 0 14px; padding:10px 12px; border-radius:7px; background:#fef3f2; color:var(--danger); font-weight:700; }
    </style>
</head>
<body>
    <main>
        <div class="brand-mark">
            <img src="/assets/qhealth-logo.png" alt="QHealth">
            <h1>QHealth<span>+</span> Admin</h1>
        </div>
        <p>Masuk untuk mengelola operasional klinik.</p>
        @if($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
        <form method="post" action="/login">@csrf
            <label>Email atau Username<input type="text" name="login" value="{{ old('login') }}" required autofocus></label>
            <label>Password<input type="password" name="password" required></label>
            <button>Masuk</button>
        </form>
    </main>
</body>
</html>
