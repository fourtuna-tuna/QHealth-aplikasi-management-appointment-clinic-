<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QHealth Admin</title>
    <style>
        :root { color-scheme:light; --ink:#020707; --muted:#5c6767; --line:#dbe7e5; --brand:#0f766e; --brand-dark:#0d6962; --nav:#020707; --blue:#168c8e; --cyan:#2b847d; --red:#d4433e; --orange:#f59e0b; --lime:#82c8bc; --bg:#f6faf9; --soft:#e9f7f5; --warn:#c27018; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif; background:var(--bg); color:var(--ink); }
        button, input, select, textarea { font:inherit; }
        button { border:0; cursor:pointer; }
        .shell { min-height:100vh; display:grid; grid-template-columns:235px 1fr; }
        .shell.sidebar-collapsed { grid-template-columns:0 1fr; }
        aside { background:#fff; border-right:1px solid var(--line); min-height:100vh; position:sticky; top:0; z-index:5; overflow:hidden; transition:transform .18s ease, opacity .18s ease; }
        .sidebar-collapsed aside { transform:translateX(-100%); opacity:0; pointer-events:none; }
        .brand { min-height:58px; display:flex; align-items:center; gap:10px; padding:10px 18px; background:var(--nav); color:white; }
        .brand img { width:34px; height:34px; object-fit:contain; flex:none; }
        .brand h1 { margin:0; font-size:18px; letter-spacing:0; line-height:1; }
        .brand h1 span { font-size:.52em; vertical-align:top; color:white; }
        .brand small { display:block; margin-top:3px; color:#82c8bc; font-size:11px; font-weight:700; }
        nav { padding:16px 12px 24px; display:grid; gap:14px; }
        .nav-group-title { margin:0 0 6px; color:#66737d; font-size:12px; font-weight:800; text-transform:uppercase; }
        .nav-group-note { margin:0 0 8px; color:#9aa4ac; font-size:11px; line-height:1.35; }
        .nav-rule { border-top:1px solid #aeb8bf; margin-top:10px; padding-top:8px; }
        .nav-btn { width:100%; display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:4px; background:transparent; color:#55636d; text-align:left; font-weight:700; }
        .nav-btn.active { background:var(--brand); color:white; box-shadow:0 6px 12px rgba(15,118,110,.2); }
        .nav-btn svg { width:15px; height:15px; flex:none; }
        .logout { padding:0 12px 18px; }
        .layout { display:grid; grid-template-rows:58px 1fr; min-width:0; }
        .topbar { height:58px; background:var(--brand); color:white; display:flex; align-items:center; justify-content:space-between; padding:0 22px; box-shadow:0 2px 8px rgba(39,50,68,.16); }
        .topbar-left { display:flex; align-items:center; gap:14px; }
        .menu-button, .top-icon { width:34px; height:34px; border-radius:4px; display:grid; place-items:center; color:white; background:transparent; text-decoration:none; }
        .top-icon { background:rgba(255,255,255,.15); }
        .avatar { width:34px; height:34px; border-radius:50%; display:grid; place-items:center; background:#fff; color:var(--brand-dark); font-weight:900; text-decoration:none; }
        main { padding:0 0 42px; min-width:0; }
        .crumb { height:64px; display:flex; align-items:center; padding:0 28px; background:white; color:var(--brand); font-size:12px; font-weight:800; border-bottom:1px solid var(--line); text-transform:uppercase; }
        section { display:none; padding:26px 30px; }
        section.active { display:block; }
        .panel { background:white; border:1px solid var(--line); border-radius:4px; padding:22px; box-shadow:0 1px 6px rgba(39,50,68,.06); overflow:auto; }
        .section-head { display:flex; justify-content:space-between; gap:14px; align-items:center; margin-bottom:18px; }
        h2, h3 { margin:0; letter-spacing:0; }
        h2 { font-size:20px; }
        h3 { font-size:17px; }
        .muted, small { color:var(--muted); }
        .toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin:10px 0 16px; }
        .toolbar-left, .toolbar-right, .filters, .row-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        label { color:#5f6d76; font-size:13px; font-weight:700; }
        input, select, textarea { width:100%; border:1px solid var(--line); border-radius:4px; padding:9px 10px; background:white; color:var(--ink); }
        textarea { min-height:84px; resize:vertical; }
        .input-inline { display:flex; align-items:center; gap:7px; }
        .input-inline input, .input-inline select { min-width:155px; }
        .grid { display:grid; grid-template-columns:170px minmax(0,1fr); gap:11px 18px; align-items:center; }
        .grid .wide { grid-column:1 / -1; }
        .grid-actions { grid-column:2; display:flex; gap:8px; }
        .btn { min-height:34px; border-radius:4px; padding:8px 12px; display:inline-flex; align-items:center; justify-content:center; gap:7px; color:white; background:var(--blue); text-decoration:none; font-weight:800; font-size:12px; text-transform:uppercase; }
        .btn svg { width:15px; height:15px; }
        .btn.green { background:var(--brand); }
        .btn.gray { background:#707a7f; }
        .btn.red { background:var(--red); }
        .btn.ghost { background:#edf2f4; color:#43515a; }
        .icon-btn { width:28px; height:28px; padding:0; border-radius:4px; display:inline-grid; place-items:center; color:white; }
        .icon-btn svg { width:15px; height:15px; }
        .icon-blue { background:var(--blue); }
        .icon-cyan { background:var(--cyan); }
        .icon-red { background:var(--red); }
        .stats { display:grid; grid-template-columns:repeat(4, minmax(180px,1fr)); gap:18px; margin-bottom:22px; }
        .stat { min-height:95px; padding:16px 18px; border-radius:4px; color:white; display:flex; justify-content:space-between; gap:12px; overflow:hidden; }
        .stat b { display:block; font-size:26px; margin-bottom:7px; }
        .stat span { font-size:13px; font-weight:800; }
        .stat small { display:block; color:rgba(255,255,255,.76); margin-top:14px; }
        .stat svg { width:34px; height:34px; opacity:.75; margin-top:4px; }
        .stat.blue { background:#168c8e; } .stat.lime { background:#0f766e; } .stat.orange { background:#f59e0b; } .stat.red { background:#d95b5f; }
        .charts { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:18px; }
        .chart-card { background:white; border:1px solid var(--line); border-radius:4px; padding:18px; min-height:260px; }
        .bars { height:190px; display:grid; grid-template-columns:repeat(12,1fr); align-items:end; gap:10px; border-left:1px solid var(--line); border-bottom:1px solid var(--line); padding:10px 8px 0; margin-top:18px; }
        .bar { background:#82c8bc; min-height:3px; border-radius:3px 3px 0 0; }
        table { width:100%; border-collapse:collapse; font-size:13px; min-width:760px; }
        th, td { padding:11px 10px; border-top:1px solid var(--line); text-align:left; vertical-align:middle; }
        th { color:#566670; font-size:12px; font-weight:900; }
        .pill { display:inline-flex; align-items:center; min-height:22px; padding:4px 8px; border-radius:999px; background:var(--soft); color:var(--brand-dark); font-size:11px; font-weight:900; white-space:nowrap; }
        .pill.warn { background:#fff3df; color:var(--warn); }
        .pill.red { background:#ffecec; color:#b42318; }
        .notice, .error { margin:18px 30px 0; padding:12px 14px; border-radius:4px; font-weight:800; }
        .notice { background:#ecfdf3; color:#027a48; }
        .error { background:#fef3f2; color:#b42318; }
        details.edit summary { list-style:none; }
        details.edit summary::-webkit-details-marker { display:none; }
        .details-card { margin-top:10px; padding:14px; border:1px solid var(--line); background:#fbfdfe; border-radius:4px; min-width:440px; }
        .modal { position:fixed; inset:0; display:none; z-index:20; }
        .modal.open { display:block; }
        .modal-backdrop { position:absolute; inset:0; background:rgba(19,27,34,.58); }
        .modal-card { position:relative; width:min(920px, calc(100vw - 28px)); max-height:calc(100vh - 36px); overflow:auto; margin:18px auto; background:white; border-radius:6px; box-shadow:0 24px 60px rgba(0,0,0,.28); }
        .modal-head { min-height:54px; display:flex; justify-content:space-between; align-items:center; padding:0 18px; border-bottom:1px solid var(--line); }
        .modal-body { padding:18px; }
        .close-btn { width:32px; height:32px; background:transparent; color:#a0a9af; display:grid; place-items:center; }
        .required { color:#c0362c; }
        .patient-summary { grid-column:2; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px 20px; padding:12px; background:#fbfdfe; border:1px solid var(--line); border-radius:4px; }
        .autocomplete-wrap { position:relative; }
        .autocomplete-results { position:absolute; inset-inline:0; top:calc(100% + 4px); z-index:10; display:none; max-height:250px; overflow:auto; background:white; border:1px solid var(--line); border-radius:4px; box-shadow:0 10px 20px rgba(39,50,68,.14); }
        .autocomplete-results.open { display:block; }
        .autocomplete-item { width:100%; display:block; padding:10px 12px; background:white; color:var(--ink); text-align:left; border-bottom:1px solid var(--line); }
        .autocomplete-item:last-child { border-bottom:0; }
        .autocomplete-item:hover { background:#f1fbf9; }
        .autocomplete-item strong { display:block; font-size:13px; }
        .autocomplete-item small { display:block; margin-top:3px; }
        .help-text { grid-column:2; margin:-4px 0 2px; color:var(--muted); font-size:12px; line-height:1.45; }
        .selected-patient { grid-column:2; display:grid; grid-template-columns:140px minmax(0,1fr); gap:7px 16px; padding:12px; background:#fbfdfe; border:1px solid var(--line); border-radius:4px; }
        .selected-patient strong { color:#5f6d76; }
        .record-history { margin-top:18px; }
        .record-history h4 { margin:0 0 10px; font-size:15px; }
        .record-history table { min-width:900px; }
        @media (max-width:1000px) { .shell { grid-template-columns:1fr; } aside { position:static; min-height:auto; } .layout { grid-template-rows:auto 1fr; } nav { grid-template-columns:repeat(2,minmax(0,1fr)); } .stats, .charts { grid-template-columns:1fr; } .grid { grid-template-columns:1fr; } .grid-actions, .patient-summary, .selected-patient, .help-text { grid-column:1; } section { padding:18px; } .notice, .error { margin:16px 18px 0; } }
    </style>
</head>
<body>
@php
    $paidAppointments = $paidAppointments ?? $appointments->where('payment_status', 'paid');
    $recordableAppointments = $recordableAppointments ?? collect();
    $registrationAppointments = $registrationAppointments ?? $activeAppointments ?? collect();
    $incomeTotal = $stats['income_total'] ?? 0;
    $incomeMonth = $stats['income_month'] ?? 0;
    $monthlyAppointmentCounts = $monthlyAppointmentCounts ?? array_fill(1, 12, 0);
    $monthlyIncomeTotals = $monthlyIncomeTotals ?? array_fill(1, 12, 0);
    $maxMonthlyAppointments = max(1, max($monthlyAppointmentCounts));
    $maxMonthlyIncome = max(1, max($monthlyIncomeTotals));
    $monthLabels = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
    $statusLabel = ['pending' => 'Menunggu', 'booked' => 'Booking', 'checked_in' => 'Sudah Check-in', 'in_queue' => 'Dalam Antrean', 'in_progress' => 'Proses Pelayanan', 'completed' => 'Selesai', 'paid' => 'Lunas', 'cancelled' => 'Dibatalkan otomatis / Tidak hadir', 'reset' => 'Direset'];
    $icon = [
        'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 13h8V3H3v10Zm0 8h8v-6H3v6Zm10 0h8V11h-8v10Zm0-18v6h8V3h-8Z"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'file' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>',
        'cash' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>',
        'steth' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v5a4 4 0 0 0 8 0V4"/><path d="M12 9a6 6 0 0 0 12 0v-1"/><circle cx="20" cy="8" r="2"/></svg>',
        'edit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
        'trash' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>',
        'eye' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
        'plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
        'menu' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1A2 2 0 1 1 4.2 17l.1-.1A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 1 1 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 1 1 19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.6 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.6 1Z"/></svg>',
    ];
@endphp
    <div class="shell">
        <aside>
            <div class="brand">
                <img src="/assets/qhealth-logo.png" alt="QHealth">
                <div><h1>QHealth<span>+</span></h1><small>admin panel</small></div>
            </div>
            <nav>
                <div>
                    <p class="nav-group-title">Dashboard</p>
                    <p class="nav-group-note">Ringkasan data klinik</p>
                    <button class="nav-btn active" data-target="ringkasan">{!! $icon['dashboard'] !!} Dashboard</button>
                </div>
                <div class="nav-rule">
                    <p class="nav-group-title">Laporan</p>
                    <p class="nav-group-note">Menampilkan data dalam bentuk laporan periode</p>
                    <button class="nav-btn" data-target="kunjungan">{!! $icon['users'] !!} Kunjungan Pasien</button>
                    <button class="nav-btn" data-target="pendapatan">{!! $icon['cash'] !!} Pendapatan</button>
                    <button class="nav-btn" data-target="rekam">{!! $icon['file'] !!} Rekam Medis</button>
                </div>
                <div class="nav-rule">
                    <p class="nav-group-title">Panel Pendaftaran</p>
                    <button class="nav-btn" data-target="pasien">{!! $icon['users'] !!} Pasien</button>
                    <button class="nav-btn" data-target="pendaftaran">{!! $icon['file'] !!} Pendaftaran</button>
                </div>
                <div class="nav-rule">
                    <p class="nav-group-title">Master Data</p>
                    <button class="nav-btn" data-target="layanan">{!! $icon['file'] !!} Layanan</button>
                    <button class="nav-btn" data-target="dokter">{!! $icon['steth'] !!} Dokter</button>
                    <button class="nav-btn" data-target="jadwal">{!! $icon['file'] !!} Jadwal</button>
                </div>
            </nav>
            <form class="logout" method="post" action="/logout">@csrf<button class="btn ghost" type="submit">Logout</button></form>
        </aside>

        <div class="layout">
            <header class="topbar">
                <div class="topbar-left"><button class="menu-button" id="sidebar-toggle" type="button" aria-label="Toggle sidebar">{!! $icon['menu'] !!}</button><strong>Panel Admin</strong></div>
                <div class="toolbar-right"><span>{{ session('admin_name', 'Admin') }}</span><a class="top-icon" href="/admin/settings" title="Settings" aria-label="Settings">{!! $icon['settings'] !!}</a><a class="avatar" href="/admin/profile" title="Profile" aria-label="Profile">{{ strtoupper(substr(session('admin_name', 'A'), 0, 1)) }}</a></div>
            </header>

            <main>
                <div class="crumb" id="breadcrumb">Home / Dashboard</div>
                @if(session('status')) <div class="notice">{{ session('status') }}</div> @endif
                @if($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif

                <section id="ringkasan" class="active" data-title="Dashboard">
                    <div class="stats">
                        <div class="stat blue"><div><b>{{ $stats['patients'] }}</b><span>Total Pasien</span><small>Semua pasien terdaftar</small></div>{!! $icon['users'] !!}</div>
                        <div class="stat lime"><div><b>{{ $stats['doctors'] }}</b><span>Total Dokter</span><small>Dokter aktif</small></div>{!! $icon['steth'] !!}</div>
                        <div class="stat red"><div><b>{{ $stats['appointments_today'] }}</b><span>Appointment Hari Ini</span><small>Status aktif</small></div>{!! $icon['file'] !!}</div>
                        <div class="stat orange"><div><b>Rp {{ number_format($incomeMonth,0,',','.') }}</b><span>Pendapatan Bulan Ini</span><small>{{ now()->translatedFormat('F Y') }}</small></div>{!! $icon['cash'] !!}</div>
                    </div>
                    <div class="charts">
                        <div class="chart-card">
                            <h3>Appointment Per Bulan</h3>
                            <div class="bars">
                                @foreach(range(1, 12) as $month)
                                    @php
                                        $count = $monthlyAppointmentCounts[$month] ?? 0;
                                        $height = max(3, (int) round(($count / $maxMonthlyAppointments) * 170));
                                    @endphp
                                    <div class="bar" title="{{ $monthLabels[$month] }}: {{ $count }} appointment" style="height:{{ $height }}px"></div>
                                @endforeach
                            </div>
                        </div>
                        <div class="chart-card">
                            <h3>Pendapatan Per Bulan</h3>
                            <div class="bars">
                                @foreach(range(1, 12) as $month)
                                    @php
                                        $income = $monthlyIncomeTotals[$month] ?? 0;
                                        $height = max(3, (int) round(($income / $maxMonthlyIncome) * 170));
                                    @endphp
                                    <div class="bar" title="{{ $monthLabels[$month] }}: Rp {{ number_format($income,0,',','.') }}" style="height:{{ $height }}px"></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section id="pasien" data-title="Pasien">
                    <div class="panel">
                        <div class="section-head"><h2>Pasien</h2><button class="btn" type="button" data-open-modal="patient-create">{!! $icon['plus'] !!} Tambah Data</button></div>
                        <div class="toolbar">
                            <div class="toolbar-left"><label class="input-inline">{!! $icon['search'] !!} Pencarian : <input data-table-search="patients-table"></label></div>
                        </div>
                        <table id="patients-table"><thead><tr><th>No</th><th>No RM</th><th>Nama Pasien</th><th>TTL</th><th>Jenis Kelamin</th><th>Kategori</th><th>Tools</th></tr></thead><tbody>
                        @foreach($patients as $patient)
                            @php
                                $medicalRecordNumber = str_pad($patient->id, 6, '0', STR_PAD_LEFT);
                                $patientCategory = $patient->birth_date ? ($patient->birth_date->age < 17 ? 'ANAK-ANAK' : 'DEWASA') : '-';
                            @endphp
                            <tr data-search="{{ strtolower($medicalRecordNumber.' '.$patient->name.' '.$patient->email) }}">
                                <td>{{ $loop->iteration }}</td><td>{{ $medicalRecordNumber }}</td><td>{{ $patient->name ?: '-' }}</td><td>{{ $patient->address ?: '-' }}, {{ $patient->birth_date?->format('d-m-Y') ?: '-' }}</td><td>{{ $patient->gender ?: '-' }}</td><td>{{ $patientCategory }}</td>
                                <td class="row-actions">
                                    <button class="icon-btn icon-blue" title="Edit" type="button" data-open-modal="patient-edit-{{ $patient->id }}">{!! $icon['edit'] !!}</button>
                                    <form method="post" action="/admin/patients/{{ $patient->id }}" onsubmit="return confirm('Hapus data pasien ini?');">@csrf @method('delete')<button class="icon-btn icon-red" title="Hapus" type="submit">{!! $icon['trash'] !!}</button></form>
                                    <button class="icon-btn icon-cyan" title="Detail" type="button" data-open-modal="patient-detail-{{ $patient->id }}">{!! $icon['eye'] !!}</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody></table>
                    </div>
                </section>

                <section id="pendaftaran" data-title="Pendaftaran">
                    <div class="panel">
                        <div class="section-head">
                            <h2>Pendaftaran</h2>
                            <form method="post" action="/admin/queue/reset" onsubmit="return confirm('Reset antrean hari ini? Nomor antrean berikutnya akan mulai dari 1.');">@csrf<button class="btn red" type="submit">Reset Antrean</button></form>
                        </div>
                        <form method="post" action="/admin/offline-appointments" class="grid" style="margin-bottom:20px;">@csrf
                            <label>Cari Pasien <span class="required">*</span></label>
                            <div class="autocomplete-wrap">
                                <input type="search" data-patient-search autocomplete="off" placeholder="Ketik nama, No RM, email, atau No HP" required>
                                <input type="hidden" name="user_id" data-selected-patient-id required>
                                <div class="autocomplete-results" data-patient-results></div>
                            </div>
                            <div class="help-text">Cari pasien berdasarkan nama, No RM, email, atau No HP. Jika pasien belum terdaftar, tambahkan melalui menu Pasien terlebih dahulu.</div>
                            <label>Data Pasien</label>
                            <div class="selected-patient" data-selected-patient-preview>
                                <strong>No RM</strong><span data-preview-field="no_rm">-</span>
                                <strong>Nama</strong><span data-preview-field="name">-</span>
                                <strong>Email</strong><span data-preview-field="email">-</span>
                                <strong>No HP</strong><span data-preview-field="phone">-</span>
                                <strong>Tanggal Lahir</strong><span data-preview-field="birth_date">-</span>
                                <strong>Jenis Kelamin</strong><span data-preview-field="gender">-</span>
                                <strong>Alamat</strong><span data-preview-field="address">-</span>
                            </div>
                            <label>Kategori Layanan <span class="required">*</span></label><select name="doctor_id" data-schedule-filter required>@foreach($activeDoctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->service?->name ?: '-' }} - {{ $doctor->name ?: '-' }}</option>@endforeach</select>
                            <label>Layanan / Jadwal <span class="required">*</span></label><select name="doctor_schedule_id" data-schedule-options required>@foreach($activeSchedules as $schedule)<option value="{{ $schedule->id }}" data-doctor="{{ $schedule->doctor_id }}">{{ $schedule->doctor?->service?->name ?: '-' }} - {{ $schedule->day ?: '-' }} {{ $schedule->start_time ? substr($schedule->start_time,0,5) : '-' }}-{{ $schedule->end_time ? substr($schedule->end_time,0,5) : '-' }}</option>@endforeach</select>
                            <label>Tanggal <span class="required">*</span></label><input name="appointment_date" type="date" value="{{ now()->toDateString() }}" required>
                            <label>Keterangan <span class="required">*</span></label><textarea name="complaint" required></textarea>
                            <div class="grid-actions"><button class="btn" type="submit">Save</button><button class="btn gray" type="reset">Cancel</button></div>
                        </form>
                        <div class="section-head"><h2>Pendaftaran Offline Terbaru</h2></div>
                        <div class="toolbar">
                            <div class="toolbar-left"><label class="input-inline">{!! $icon['search'] !!} Pencarian : <input data-table-search="appointments-table"></label></div>
                        </div>
                        <table id="appointments-table"><thead><tr><th>No Pendaftaran</th><th>No RM</th><th>Nama Pasien</th><th>Kategori</th><th>Layanan</th><th>Dokter</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
                        @forelse($registrationAppointments as $appointment)
                            @php
                                $statusClass = $appointment->status === 'cancelled'
                                    ? 'red'
                                    : (($appointment->status === 'completed' || $appointment->payment_status === 'paid') ? '' : 'warn');
                            @endphp
                            <tr>
                                <td>{{ $appointment->created_at?->format('ymdHis') ?: '-' }}{{ $appointment->id }}</td><td>{{ $appointment->user_id ? str_pad($appointment->user_id, 6, '0', STR_PAD_LEFT) : '-' }}</td><td>{{ $appointment->patient?->name ?: '-' }}</td><td>{{ strtoupper($appointment->doctor?->service?->code ?? $appointment->doctor?->service?->name ?? '-') }}</td><td>{{ $appointment->doctor?->service?->name ?: '-' }}</td><td>{{ $appointment->doctor?->name ?: '-' }}</td><td><span class="pill {{ $statusClass }}">{{ $statusLabel[$appointment->status] ?? $appointment->status ?? '-' }}</span></td>
                                <td><span class="pill warn">Check-in di Kunjungan Pasien</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="muted" style="text-align:center;">Belum ada pendaftaran offline aktif.</td></tr>
                        @endforelse
                        </tbody></table>
                    </div>
                </section>

                <section id="kunjungan" data-title="Laporan Kunjungan">
                    <div class="panel">
                        <div class="toolbar filters"><label class="input-inline">Periode <input type="date" value="{{ now()->startOfMonth()->toDateString() }}"></label><label class="input-inline">s/d <input type="date" value="{{ now()->toDateString() }}"></label><label><select><option>Semua Status</option><option>Belum Membayar</option><option>Proses Pelayanan</option><option>Selesai Pelayanan</option></select></label><button class="btn" type="button">{!! $icon['search'] !!} Search</button></div>
                        <table><thead><tr><th>No</th><th>Tanggal Kunjungan</th><th>No Antrean</th><th>No RM</th><th>Nama Pasien</th><th>Keluhan</th><th>Layanan/Poli</th><th>Dokter</th><th>Status Kunjungan / Check-in</th><th>Aksi</th></tr></thead><tbody>
                        @forelse($visitAppointments as $appointment)
                            @php
                                $visitStatusText = match ($appointment->status) {
                                    'booked', 'pending' => 'Belum Check-in',
                                    'checked_in' => 'Sudah Check-in',
                                    'in_queue' => 'Dalam Antrean',
                                    'in_progress' => 'Sedang Diproses',
                                    'completed', 'paid' => 'Selesai',
                                    'cancelled', 'reset' => 'Dibatalkan',
                                    default => $statusLabel[$appointment->status] ?? $appointment->status ?? '-',
                                };
                                $reportStatusClass = in_array($appointment->status, ['cancelled', 'reset'], true)
                                    ? 'red'
                                    : (in_array($appointment->status, ['checked_in', 'in_queue', 'in_progress'], true) ? '' : 'warn');
                            @endphp
                            <tr><td>{{ $loop->iteration }}</td><td>{{ $appointment->appointment_date?->format('d/m/Y') ?: '-' }}</td><td>{{ $appointment->queue_number ? 'A'.str_pad($appointment->queue_number, 3, '0', STR_PAD_LEFT) : '-' }}</td><td>{{ $appointment->user_id ? str_pad($appointment->user_id, 6, '0', STR_PAD_LEFT) : '-' }}</td><td>{{ $appointment->patient?->name ?: '-' }}</td><td>{{ $appointment->complaint ?: '-' }}</td><td>{{ $appointment->doctor?->service?->name ?: '-' }}</td><td>{{ $appointment->doctor?->name ?: '-' }}</td><td><span class="pill {{ $reportStatusClass }}">{{ $visitStatusText }}</span></td><td class="row-actions">@if(in_array($appointment->status, ['booked', 'pending'], true))<form method="post" action="/admin/appointments/{{ $appointment->id }}/status">@csrf <input type="hidden" name="status" value="checked_in"><button class="btn" type="submit">Check-in</button></form>@else<span class="pill">{{ $visitStatusText }}</span>@endif<details class="edit"><summary class="icon-btn icon-cyan" title="Detail">{!! $icon['eye'] !!}</summary><div class="details-card"><strong>Appointment</strong><p>#{{ $appointment->id }}</p><strong>Keluhan</strong><p>{{ $appointment->complaint ?: '-' }}</p><strong>Status</strong><p>{{ $visitStatusText }}</p></div></details></td></tr>
                        @empty
                            <tr><td colspan="10" class="muted" style="text-align:center;">Pencarian tidak ditemukan</td></tr>
                        @endforelse
                        </tbody></table>
                    </div>
                </section>

                <section id="pendapatan" data-title="Laporan Pendapatan">
                    <div class="panel">
                        <div class="toolbar filters"><label class="input-inline">Periode <input type="date" value="{{ now()->startOfMonth()->toDateString() }}"></label><label class="input-inline">s/d <input type="date" value="{{ now()->toDateString() }}"></label><button class="btn" type="button">{!! $icon['search'] !!} Search</button></div>
                        <table><thead><tr><th>No</th><th>Tgl Pembayaran</th><th>Nama Pasien</th><th>Kategori</th><th>Layanan</th><th>Dokter</th><th>Total</th></tr></thead><tbody>
                        @forelse($paidAppointments as $appointment)
                            <tr><td>{{ $loop->iteration }}</td><td>{{ $appointment->paid_at?->format('d/m/Y') ?: '-' }}</td><td>{{ $appointment->patient?->name ?: '-' }}</td><td>{{ strtoupper($appointment->doctor?->service?->code ?? '-') }}</td><td>{{ $appointment->doctor?->service?->name ?: '-' }}</td><td>{{ $appointment->doctor?->name ?: '-' }}</td><td>Rp {{ number_format($appointment->doctor?->service?->price ?? 0,0,',','.') }}</td></tr>
                        @empty
                            <tr><td colspan="7" class="muted" style="text-align:center;">Pencarian tidak ditemukan</td></tr>
                        @endforelse
                        </tbody></table>
                    </div>
                </section>

                <section id="layanan" data-title="Layanan">
                    <div class="panel">
                        <div class="section-head"><h2>Layanan</h2></div>
                        <form method="post" action="/admin/services" class="grid" style="margin-bottom:18px;">@csrf<label>Nama Poli</label><input name="name" required><label>Kode</label><input name="code" required><label>Durasi</label><input name="duration_minutes" type="number" value="20" required><label>Tarif Klinik</label><input name="price" type="number" value="0" required><label>Deskripsi</label><textarea name="description"></textarea><label>Aktif</label><select name="is_active"><option value="1">Ya</option><option value="0">Tidak</option></select><div class="grid-actions"><button class="btn" type="submit">Simpan</button></div></form>
                        <table><thead><tr><th>Nama Poli</th><th>Tarif Klinik</th><th>Dokter</th><th>Status</th><th>Tools</th></tr></thead><tbody>
                        @foreach($services as $service)
                            <tr><td>{{ $service->name ?: '-' }}<br><small>{{ $service->description ?: '-' }}</small></td><td>Rp {{ number_format($service->price ?? 0,0,',','.') }}</td><td>{{ $service->doctors_count }}</td><td><span class="pill">{{ $service->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="row-actions"><details class="edit"><summary class="icon-btn icon-blue">{!! $icon['edit'] !!}</summary><div class="details-card"><form method="post" action="/admin/services" class="grid">@csrf<input type="hidden" name="id" value="{{ $service->id }}"><label>Nama Poli</label><input name="name" value="{{ $service->name }}" required><label>Kode</label><input name="code" value="{{ $service->code }}" required><label>Durasi</label><input name="duration_minutes" type="number" value="{{ $service->duration_minutes }}" required><label>Tarif</label><input name="price" type="number" value="{{ $service->price }}" required><label>Deskripsi</label><textarea name="description">{{ $service->description }}</textarea><label>Aktif</label><select name="is_active"><option value="1" @selected($service->is_active)>Ya</option><option value="0" @selected(! $service->is_active)>Tidak</option></select><div class="grid-actions"><button class="btn">Update</button></div></form></div></details><form method="post" action="/admin/services/{{ $service->id }}" onsubmit="return confirm('Hapus layanan ini?');">@csrf @method('delete')<button class="icon-btn icon-red">{!! $icon['trash'] !!}</button></form></td></tr>
                        @endforeach
                        </tbody></table>
                    </div>
                </section>

                <section id="dokter" data-title="Dokter">
                    <div class="panel">
                        <form method="post" action="/admin/doctors" class="grid" style="margin-bottom:18px;">@csrf<label>Layanan</label><select name="service_id" required>@foreach($services as $service)<option value="{{ $service->id }}">{{ $service->name }}</option>@endforeach</select><label>Nama</label><input name="name" required><label>Spesialisasi</label><input name="specialization" required><label>No SIP</label><input name="sip_number"><label>Telepon</label><input name="phone"><label>Bio</label><textarea name="bio"></textarea><label>Aktif</label><select name="is_active"><option value="1">Ya</option><option value="0">Tidak</option></select><div class="grid-actions"><button class="btn">Simpan</button></div></form>
                        <table><thead><tr><th>Dokter</th><th>Layanan</th><th>Jadwal</th><th>Status</th><th>Tools</th></tr></thead><tbody>
                        @foreach($doctors as $doctor)
                            <tr><td>{{ $doctor->name ?: '-' }}<br><small>{{ $doctor->specialization ?: '-' }}{{ $doctor->sip_number ? ' / SIP '.$doctor->sip_number : '' }}</small></td><td>{{ $doctor->service?->name ?: '-' }}</td><td>{{ $doctor->schedules->count() }} jadwal</td><td><span class="pill">{{ $doctor->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="row-actions"><details class="edit"><summary class="icon-btn icon-blue">{!! $icon['edit'] !!}</summary><div class="details-card"><form method="post" action="/admin/doctors" class="grid">@csrf<input type="hidden" name="id" value="{{ $doctor->id }}"><label>Layanan</label><select name="service_id">@foreach($services as $service)<option value="{{ $service->id }}" @selected($doctor->service_id === $service->id)>{{ $service->name }}</option>@endforeach</select><label>Nama</label><input name="name" value="{{ $doctor->name }}" required><label>Spesialisasi</label><input name="specialization" value="{{ $doctor->specialization }}" required><label>No SIP</label><input name="sip_number" value="{{ $doctor->sip_number }}"><label>Telepon</label><input name="phone" value="{{ $doctor->phone }}"><label>Bio</label><textarea name="bio">{{ $doctor->bio }}</textarea><label>Aktif</label><select name="is_active"><option value="1" @selected($doctor->is_active)>Ya</option><option value="0" @selected(! $doctor->is_active)>Tidak</option></select><div class="grid-actions"><button class="btn">Update</button></div></form></div></details><form method="post" action="/admin/doctors/{{ $doctor->id }}" onsubmit="return confirm('Hapus dokter ini?');">@csrf @method('delete')<button class="icon-btn icon-red">{!! $icon['trash'] !!}</button></form></td></tr>
                        @endforeach
                        </tbody></table>
                    </div>
                </section>

                <section id="jadwal" data-title="Jadwal">
                    <div class="panel">
                        <form method="post" action="/admin/schedules" class="grid" style="margin-bottom:18px;">@csrf<label>Dokter</label><select name="doctor_id" required>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->name }}</option>@endforeach</select><label>Hari</label><select name="day"><option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option></select><label>Mulai</label><input name="start_time" type="time" value="08:00"><label>Selesai</label><input name="end_time" type="time" value="12:00"><label>Kuota</label><input name="quota" type="number" value="18"><label>Aktif</label><select name="is_active"><option value="1">Ya</option><option value="0">Tidak</option></select><div class="grid-actions"><button class="btn">Simpan</button></div></form>
                        <table><thead><tr><th>Dokter</th><th>Hari</th><th>Jam</th><th>Kuota</th><th>Status</th><th>Tools</th></tr></thead><tbody>
                        @foreach($schedules as $schedule)
                            <tr><td>{{ $schedule->doctor?->name ?: '-' }}</td><td>{{ $schedule->day ?: '-' }}</td><td>{{ $schedule->start_time ? substr($schedule->start_time,0,5) : '-' }} - {{ $schedule->end_time ? substr($schedule->end_time,0,5) : '-' }}</td><td>{{ $schedule->quota ?? '-' }}</td><td><span class="pill">{{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="row-actions"><details class="edit"><summary class="icon-btn icon-blue">{!! $icon['edit'] !!}</summary><div class="details-card"><form method="post" action="/admin/schedules" class="grid">@csrf<input type="hidden" name="id" value="{{ $schedule->id }}"><label>Dokter</label><select name="doctor_id" required>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}" @selected($schedule->doctor_id === $doctor->id)>{{ $doctor->name }}</option>@endforeach</select><label>Hari</label><select name="day">@foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)<option value="{{ $day }}" @selected($schedule->day === $day)>{{ $day }}</option>@endforeach</select><label>Mulai</label><input name="start_time" type="time" value="{{ $schedule->start_time ? substr($schedule->start_time,0,5) : '08:00' }}"><label>Selesai</label><input name="end_time" type="time" value="{{ $schedule->end_time ? substr($schedule->end_time,0,5) : '12:00' }}"><label>Kuota</label><input name="quota" type="number" value="{{ $schedule->quota ?? 18 }}"><label>Aktif</label><select name="is_active"><option value="1" @selected($schedule->is_active)>Ya</option><option value="0" @selected(! $schedule->is_active)>Tidak</option></select><div class="grid-actions"><button class="btn">Update</button></div></form></div></details><form method="post" action="/admin/schedules/{{ $schedule->id }}" onsubmit="return confirm('Hapus jadwal ini?');">@csrf @method('delete')<button class="icon-btn icon-red">{!! $icon['trash'] !!}</button></form></td></tr>
                        @endforeach
                        </tbody></table>
                    </div>
                </section>

                <section id="rekam" data-title="Rekam Medis">
                    <div class="panel">
                        @if($recordableAppointments->isEmpty())
                            <p class="muted" style="margin-top:0;">Tidak ada appointment yang perlu dibuatkan rekam medis.</p>
                        @else
                            <form method="post" action="/admin/records" class="grid" style="margin-bottom:18px;">@csrf<label>Cari Pasien / Appointment</label><input type="search" data-record-appointment-search placeholder="Ketik nama pasien, nomor antrean, dokter, atau layanan"><label>Appointment</label><select name="appointment_id" data-record-appointment-select required>@foreach($recordableAppointments as $appointment)@php $recordOption = '#'.$appointment->id.' - '.($appointment->patient?->name ?: '-').' / '.($appointment->queue_number ? 'A'.str_pad($appointment->queue_number, 3, '0', STR_PAD_LEFT) : '-').' / '.($appointment->doctor?->name ?: '-').' / '.($appointment->doctor?->service?->name ?: '-'); @endphp<option value="{{ $appointment->id }}" data-search="{{ strtolower($recordOption.' '.$appointment->complaint) }}" data-complaint="{{ $appointment->complaint ?: '-' }}">{{ $recordOption }}</option>@endforeach</select><label>Keluhan pasien</label><div class="selected-patient" data-record-complaint style="grid-template-columns:1fr;">{{ $recordableAppointments->first()?->complaint ?: '-' }}</div><label>Tanggal</label><input name="visited_at" type="date" value="{{ now()->toDateString() }}"><label>Diagnosis</label><input name="diagnosis" required><label>Resep</label><textarea name="prescription"></textarea><label>Tindakan</label><textarea name="treatment"></textarea><label>Catatan</label><textarea name="doctor_notes"></textarea><div class="grid-actions"><button class="btn">Simpan</button></div></form>
                        @endif
                        <div class="toolbar">
                            <div class="toolbar-left"><label class="input-inline">{!! $icon['search'] !!} Pencarian : <input data-table-search="records-table"></label></div>
                        </div>
                        <table id="records-table"><thead><tr><th>Tanggal</th><th>Pasien</th><th>Dokter</th><th>Keluhan</th><th>Diagnosis</th><th>Tindakan</th><th>Resep/Obat</th><th>Catatan Dokter</th><th>Status</th></tr></thead><tbody>
                        @forelse($records as $record)
                            <tr><td>{{ $record->visited_at?->format('d/m/Y') ?: '-' }}</td><td>{{ $record->patient?->name ?: '-' }}</td><td>{{ $record->doctor?->name ?: '-' }}</td><td>{{ $record->appointment?->complaint ?: '-' }}</td><td>{{ $record->diagnosis ?: '-' }}</td><td>{{ $record->treatment ?: '-' }}</td><td>{{ $record->prescription ?: '-' }}</td><td>{{ $record->doctor_notes ?: '-' }}</td><td><span class="pill">{{ $statusLabel[$record->appointment?->status] ?? $record->appointment?->status ?? '-' }}</span></td></tr>
                        @empty
                            <tr><td colspan="9" class="muted" style="text-align:center;">Belum ada rekam medis.</td></tr>
                        @endforelse
                        </tbody></table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div class="modal" id="patient-create" aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div><div class="modal-card"><div class="modal-head"><h3>Tambah Data</h3><button class="close-btn" type="button" data-close-modal>&times;</button></div><div class="modal-body">
            <form method="post" action="/admin/patients" class="grid">@csrf
                <label>Nama <span class="required">*</span></label><input name="name" placeholder="Nama" required>
                <label>Jenis Kelamin</label><select name="gender"><option value="">Pilih</option><option>Laki-laki</option><option>Perempuan</option></select>
                <label>Tempat Lahir <span class="required">*</span></label><input name="birth_place" placeholder="Tempat lahir">
                <label>Tanggal Lahir</label><input name="birth_date" type="date">
                <label>No HP</label><input name="phone" placeholder="No hp">
                <label>Email</label><input name="email" type="email" placeholder="Opsional untuk pasien offline">
                <label>Alamat lengkap <span class="required">*</span></label><textarea name="address" placeholder="Alamat lengkap"></textarea>
                <label>Gol. Darah</label><select name="blood_type"><option value="">Tidak Tahu</option><option value="A">A</option><option value="B">B</option><option value="AB">AB</option><option value="O">O</option></select>
                <div class="wide muted"><span class="required">*</span> Harus diisi</div>
                <div class="grid-actions"><button class="btn" type="submit">Save</button><button class="btn gray" type="button" data-close-modal>Cancel</button></div>
            </form>
        </div></div>
    </div>

    @foreach($patients as $patient)
        <div class="modal" id="patient-edit-{{ $patient->id }}" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div><div class="modal-card"><div class="modal-head"><h3>Edit Data Pasien</h3><button class="close-btn" type="button" data-close-modal>&times;</button></div><div class="modal-body">
                <form method="post" action="/admin/patients" class="grid">@csrf<input type="hidden" name="id" value="{{ $patient->id }}"><label>Nama <span class="required">*</span></label><input name="name" value="{{ $patient->name }}" required><label>Email</label><input name="email" type="email" value="{{ str_ends_with($patient->email, '@qhealth.local') ? '' : $patient->email }}"><label>No HP</label><input name="phone" value="{{ $patient->phone }}"><label>Tanggal Lahir</label><input name="birth_date" type="date" value="{{ $patient->birth_date?->toDateString() }}"><label>Jenis Kelamin</label><select name="gender"><option value="">Pilih</option><option @selected($patient->gender === 'Laki-laki')>Laki-laki</option><option @selected($patient->gender === 'Perempuan')>Perempuan</option></select><label>Alamat</label><textarea name="address">{{ $patient->address }}</textarea><label>Gol. Darah</label><select name="blood_type"><option value="" @selected(blank($patient->blood_type))>Tidak Tahu</option><option value="A" @selected($patient->blood_type === 'A')>A</option><option value="B" @selected($patient->blood_type === 'B')>B</option><option value="AB" @selected($patient->blood_type === 'AB')>AB</option><option value="O" @selected($patient->blood_type === 'O')>O</option></select><div class="grid-actions"><button class="btn">Update</button><button class="btn gray" type="button" data-close-modal>Cancel</button></div></form>
            </div></div>
        </div>
        <div class="modal" id="patient-detail-{{ $patient->id }}" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div><div class="modal-card"><div class="modal-head"><h3>Detail Pasien</h3><button class="close-btn" type="button" data-close-modal>&times;</button></div><div class="modal-body">
                <div class="patient-summary"><strong>No RM</strong><span>{{ str_pad($patient->id, 6, '0', STR_PAD_LEFT) }}</span><strong>Nama</strong><span>{{ $patient->name ?: '-' }}</span><strong>No HP</strong><span>{{ $patient->phone ?: '-' }}</span><strong>Kategori</strong><span>{{ $patient->birth_date ? ($patient->birth_date->age < 17 ? 'ANAK-ANAK' : 'DEWASA') : '-' }}</span><strong>Alamat</strong><span>{{ $patient->address ?: '-' }}</span></div>
                <div class="record-history">
                    <h4>Riwayat Rekam Medis</h4>
                    <table><thead><tr><th>Tanggal Kunjungan</th><th>Dokter</th><th>Keluhan</th><th>Diagnosa</th><th>Tindakan</th><th>Resep/Obat</th><th>Catatan Dokter</th><th>Status</th><th>Detail</th></tr></thead><tbody>
                    @forelse($patient->medicalRecords->sortByDesc('visited_at') as $record)
                        <tr><td>{{ $record->visited_at?->format('d/m/Y') ?: '-' }}</td><td>{{ $record->doctor?->name ?: '-' }}</td><td>{{ $record->appointment?->complaint ?: '-' }}</td><td>{{ $record->diagnosis ?: '-' }}</td><td>{{ $record->treatment ?: '-' }}</td><td>{{ $record->prescription ?: '-' }}</td><td>{{ $record->doctor_notes ?: '-' }}</td><td><span class="pill">{{ $statusLabel[$record->appointment?->status] ?? $record->appointment?->status ?? '-' }}</span></td><td><details class="edit"><summary class="icon-btn icon-cyan" title="Detail">{!! $icon['eye'] !!}</summary><div class="details-card"><strong>Appointment</strong><p>#{{ $record->appointment_id ?: '-' }}</p><strong>Layanan</strong><p>{{ $record->doctor?->service?->name ?? $record->appointment?->doctor?->service?->name ?? '-' }}</p><strong>Catatan</strong><p>{{ $record->doctor_notes ?: '-' }}</p></div></details></td></tr>
                    @empty
                        <tr><td colspan="9" class="muted" style="text-align:center;">Belum ada rekam medis untuk pasien ini.</td></tr>
                    @endforelse
                    </tbody></table>
                </div>
            </div></div>
        </div>
    @endforeach

    <script>
        function openTab(id) {
            var target = document.getElementById(id) ? id : 'ringkasan';
            document.querySelectorAll('.nav-btn').forEach(function (item) { item.classList.toggle('active', item.dataset.target === target); });
            document.querySelectorAll('main section').forEach(function (section) { section.classList.toggle('active', section.id === target); });
            var title = document.getElementById(target).dataset.title || 'Dashboard';
            document.getElementById('breadcrumb').textContent = 'Home / ' + title;
            history.replaceState(null, '', '#' + target);
        }
        document.querySelectorAll('.nav-btn').forEach(function (button) { button.addEventListener('click', function () { openTab(button.dataset.target); }); });
        if (location.hash) { openTab(location.hash.slice(1)); }

        document.getElementById('sidebar-toggle')?.addEventListener('click', function () {
            document.querySelector('.shell')?.classList.toggle('sidebar-collapsed');
        });

        document.querySelectorAll('[data-open-modal]').forEach(function (button) {
            button.addEventListener('click', function () { document.getElementById(button.dataset.openModal)?.classList.add('open'); });
        });
        document.querySelectorAll('[data-close-modal]').forEach(function (button) {
            button.addEventListener('click', function () { button.closest('.modal')?.classList.remove('open'); });
        });

        document.querySelectorAll('[data-table-search]').forEach(function (input) {
            input.addEventListener('input', function () {
                var needle = input.value.toLowerCase();
                document.querySelectorAll('#' + input.dataset.tableSearch + ' tbody tr').forEach(function (row) {
                    var haystack = (row.dataset.search || row.textContent).toLowerCase();
                    row.hidden = ! haystack.includes(needle);
                });
            });
        });

        document.querySelectorAll('[data-record-appointment-search]').forEach(function (input) {
            var select = input.form.querySelector('[data-record-appointment-select]');
            var complaint = input.form.querySelector('[data-record-complaint]');

            function syncComplaint() {
                var selected = select.selectedOptions[0];
                if (complaint) {
                    complaint.textContent = selected ? (selected.dataset.complaint || '-') : '-';
                }
            }

            input.addEventListener('input', function () {
                var needle = input.value.toLowerCase();
                var firstVisible = null;

                select.querySelectorAll('option').forEach(function (option) {
                    var visible = ! needle || (option.dataset.search || option.textContent).toLowerCase().includes(needle);
                    option.hidden = ! visible;
                    option.disabled = ! visible;
                    if (visible && ! firstVisible) { firstVisible = option; }
                });

                if (firstVisible) {
                    select.value = firstVisible.value;
                    select.setCustomValidity('');
                } else {
                    select.value = '';
                    select.setCustomValidity('Appointment tidak ditemukan');
                }

                syncComplaint();
            });

            select.addEventListener('change', syncComplaint);
            syncComplaint();
        });

        document.querySelectorAll('[data-schedule-filter]').forEach(function (doctorSelect) {
            var scheduleSelect = doctorSelect.form.querySelector('[data-schedule-options]');
            function syncSchedules() {
                var firstVisible = null;
                scheduleSelect.querySelectorAll('option').forEach(function (option) {
                    var visible = option.dataset.doctor === doctorSelect.value;
                    option.hidden = ! visible; option.disabled = ! visible;
                    if (visible && ! firstVisible) { firstVisible = option; }
                });
                if (firstVisible && scheduleSelect.selectedOptions[0]?.disabled) { scheduleSelect.value = firstVisible.value; }
            }
            doctorSelect.addEventListener('change', syncSchedules); syncSchedules();
        });

        document.querySelectorAll('[data-patient-search]').forEach(function (input) {
            var form = input.form;
            var hidden = form.querySelector('[data-selected-patient-id]');
            var results = form.querySelector('[data-patient-results]');
            var preview = form.querySelector('[data-selected-patient-preview]');
            var selectedLabel = '';
            var timer = null;

            function valueOrDash(value) {
                return value ? value : '-';
            }

            function setPreview(patient) {
                ['no_rm', 'name', 'email', 'phone', 'birth_date', 'gender', 'address'].forEach(function (field) {
                    preview.querySelector('[data-preview-field="' + field + '"]').textContent = patient ? valueOrDash(patient[field]) : '-';
                });
            }

            function closeResults() {
                results.classList.remove('open');
                results.innerHTML = '';
            }

            function resetSelectedPatient() {
                hidden.value = '';
                selectedLabel = '';
                setPreview(null);
            }

            function renderResults(items) {
                results.innerHTML = '';

                if (! items.length) {
                    var empty = document.createElement('div');
                    empty.className = 'autocomplete-item';
                    empty.textContent = 'Pasien tidak ditemukan';
                    results.appendChild(empty);
                    results.classList.add('open');
                    return;
                }

                items.forEach(function (patient) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'autocomplete-item';

                    var title = document.createElement('strong');
                    title.textContent = valueOrDash(patient.no_rm) + ' - ' + valueOrDash(patient.name);
                    var detail = document.createElement('small');
                    detail.textContent = [patient.email, patient.phone].filter(Boolean).join(' / ') || '-';

                    button.appendChild(title);
                    button.appendChild(detail);
                    button.addEventListener('click', function () {
                        hidden.value = patient.id;
                        selectedLabel = patient.no_rm + ' - ' + valueOrDash(patient.name);
                        input.value = selectedLabel;
                        setPreview(patient);
                        closeResults();
                    });
                    results.appendChild(button);
                });

                results.classList.add('open');
            }

            input.addEventListener('input', function () {
                if (input.value !== selectedLabel) {
                    resetSelectedPatient();
                }

                clearTimeout(timer);
                var keyword = input.value.trim();

                if (keyword.length < 2) {
                    closeResults();
                    return;
                }

                timer = setTimeout(function () {
                    fetch('/admin/pasien/search?q=' + encodeURIComponent(keyword), { headers: { 'Accept': 'application/json' } })
                        .then(function (response) { return response.ok ? response.json() : []; })
                        .then(renderResults)
                        .catch(function () { renderResults([]); });
                }, 220);
            });

            form.addEventListener('submit', function (event) {
                if (! hidden.value) {
                    event.preventDefault();
                    input.focus();
                    alert('Pilih pasien dari hasil pencarian terlebih dahulu.');
                }
            });

            form.addEventListener('reset', function () {
                setTimeout(function () {
                    resetSelectedPatient();
                    closeResults();
                });
            });

            document.addEventListener('click', function (event) {
                if (! results.contains(event.target) && event.target !== input) {
                    closeResults();
                }
            });
        });
    </script>
</body>
</html>
