@php
    $d = $cv->data ?? [];
    $p = $d['personal'] ?? [];
    $exps = $d['experiences'] ?? [];
    $edus = $d['education'] ?? [];
    $orgs = $d['organizations'] ?? [];
    $skills = $d['skills'] ?? [];
    $projects = $d['projects'] ?? [];
    $lang = $cv->language ?? 'id';
    $modern = $cv->template === 'modern';

    $bullets = fn($s) => array_values(array_filter(array_map('trim', explode("\n", (string) $s))));
    $hard = array_values(array_filter(array_map('trim', explode(',', $skills['hard'] ?? ''))));
    $soft = array_values(array_filter(array_map('trim', explode(',', $skills['soft'] ?? ''))));
    $displayUrl = fn($u) => preg_replace('#^https?://#i', '', rtrim((string) $u, '/'));
    $hrefUrl = fn($u) => preg_match('#^https?://#i', (string) $u) ? $u : 'https://' . ltrim((string) $u, '/');
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<title>{{ $p['name'] ?? 'CV' }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-size: 11pt; line-height: 1.5; color: #0f172a; -webkit-print-color-adjust: exact; }
    .page { padding: 0; }
    h1 { font-size: 24pt; font-weight: 700; letter-spacing: -0.02em; }
    h2 { font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; padding-bottom: 4px; margin-bottom: 0; }
    .contact { font-size: 10pt; color: #475569; margin-top: 4px; }
    .contact a { color: inherit; text-decoration: none; }
    .summary { font-size: 10pt; color: #334155; margin-top: 12px; }
    .item { margin-top: 12px; }
    .row { display: flex; justify-content: space-between; align-items: baseline; gap: 16px; }
    .title { font-size: 10pt; font-weight: 600; color: #0f172a; }
    .sub { font-size: 10pt; color: #334155; }
    .meta { font-size: 9pt; color: #64748b; }
    .gpa { font-size: 9pt; color: #334155; }
    ul { margin-top: 4px; padding-left: 20px; list-style: disc; }
    li { font-size: 10pt; color: #334155; }
    .skills p { font-size: 10pt; color: #334155; margin-top: 8px; }
    .skills b { font-weight: 600; }
    .tech { font-size: 9pt; color: #64748b; }
    .tech b { font-weight: 600; }
    .section { margin-top: 20px; }
    .section:first-of-type { margin-top: 16px; }
    .sep { color: #cbd5e1; }

    /* modern */
    .modern h1 { color: #0f172a; }
    .modern h2 { border-bottom: 2px solid #1e40af; color: #0f172a; }
    .modern .contact a { color: #1e40af; text-decoration: underline; text-decoration-color: #cbd5e1; text-underline-offset: 2px; }
    .modern { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }

    /* classic */
    .classic { font-family: Georgia, 'Times New Roman', serif; }
    .classic h1 { text-transform: uppercase; letter-spacing: 0.02em; text-align: center; }
    .classic .contact { text-align: center; }
    .classic h2 { border-bottom: 1.5px solid #0f172a; font-size: 11pt; }
    .classic .contact a { text-decoration: underline; text-decoration-color: #cbd5e1; text-underline-offset: 2px; }
</style>
</head>
<body class="{{ $modern ? 'modern' : 'classic' }}">
<div class="page">
    <header>
        <h1>{{ $modern ? ($p['name'] ?? 'Nama Anda') : strtoupper($p['name'] ?? 'Nama Anda') }}</h1>
        <p class="contact">
            @php
                $c = array_filter([$p['email'] ?? null, $p['phone'] ?? null, $p['address'] ?? null]);
            @endphp
            @foreach ($c as $i => $v)
                @if ($i > 0)<span class="sep"> · </span>@endif{{ $v }}
            @endforeach
            @foreach (['linkedin', 'website', 'github'] as $k)
                @if (!empty($p[$k]))
                    <span class="sep"> · </span><a href="{{ $hrefUrl($p[$k]) }}">{{ $displayUrl($p[$k]) }}</a>
                @endif
            @endforeach
            @if (empty($c) && empty($p['linkedin'] ?? null) && empty($p['website'] ?? null) && empty($p['github'] ?? null))
                email · phone · alamat
            @endif
        </p>
    </header>

    @if (!empty($d['summary']))
        <p class="summary">{{ $d['summary'] }}</p>
    @endif

    @if (count($exps))
        <div class="section">
            <h2>Pengalaman Kerja</h2>
            @foreach ($exps as $e)
                <div class="item">
                    <div class="row">
                        <p class="title">{{ $e['position'] ?? 'Posisi' }} · {{ $e['company'] ?? 'Perusahaan' }}@if (!empty($e['employmentType'])) <span class="meta">· {{ $e['employmentType'] }}</span>@endif</p>
                        <p class="meta">{{ $e['startDate'] ?? '' }} - {{ $e['endDate'] ?? '' }}</p>
                    </div>
                    @if (!empty($e['location']))<p class="meta">{{ $e['location'] }}</p>@endif
                    @if (count($bullets($e['description'] ?? '')))
                        <ul>
                            @foreach ($bullets($e['description']) as $b)<li>{{ $b }}</li>@endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if (count($edus))
        <div class="section">
            <h2>Pendidikan</h2>
            @foreach ($edus as $ed)
                <div class="item">
                    <div class="row">
                        <p class="title">{{ $ed['degree'] ?? '' }}</p>
                        <p class="meta">{{ $ed['year'] ?? '' }}</p>
                    </div>
                    <p class="sub">{{ $ed['institution'] ?? '' }}@if (!empty($ed['location'])) <span class="meta">· {{ $ed['location'] }}</span>@endif</p>
                    @if (!empty($ed['gpa']))<p class="gpa">IPK: {{ $ed['gpa'] }}</p>@endif
                    @if (count($bullets($ed['achievements'] ?? '')))
                        <ul>
                            @foreach ($bullets($ed['achievements']) as $b)<li>{{ $b }}</li>@endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if (count($orgs))
        <div class="section">
            <h2>Organisasi</h2>
            @foreach ($orgs as $o)
                <div class="item">
                    <div class="row">
                        <p class="title">{{ $o['role'] ?? 'Peran' }} · {{ $o['organization'] ?? 'Organisasi' }}</p>
                        <p class="meta">{{ $o['period'] ?? '' }}</p>
                    </div>
                    @if (count($bullets($o['description'] ?? '')))
                        <ul>
                            @foreach ($bullets($o['description']) as $b)<li>{{ $b }}</li>@endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if (count($hard) || count($soft))
        <div class="section skills">
            <h2>Keahlian</h2>
            @if (count($hard))<p><b>Hard skills:</b> {{ implode(' · ', $hard) }}</p>@endif
            @if (count($soft))<p><b>Soft skills:</b> {{ implode(' · ', $soft) }}</p>@endif
        </div>
    @endif

    @if (count($projects))
        <div class="section">
            <h2>Proyek</h2>
            @foreach ($projects as $pr)
                <div class="item">
                    <p class="title">{{ $pr['title'] ?? '' }}@if (!empty($pr['role'])) <span class="meta">· {{ $pr['role'] }}</span>@endif</p>
                    @if (!empty($pr['objective']))<p class="sub">{{ $pr['objective'] }}</p>@endif
                    @if (!empty($pr['techStack']))<p class="tech"><b>Tech Stack:</b> {{ $pr['techStack'] }}</p>@endif
                </div>
            @endforeach
        </div>
    @endif

    @if (!empty($d['certificates']))
        <div class="section">
            <h2>{{ $modern ? 'Lainnya' : 'Sertifikasi' }}</h2>
            @if ($modern)
                <p class="sub" style="margin-top:8px"><b>Sertifikat:</b> {{ $d['certificates'] }}</p>
            @else
                <p class="sub" style="margin-top:8px; white-space: pre-line">{{ $d['certificates'] }}</p>
            @endif
        </div>
    @endif

    @if (!empty($d['languages']))
        <div class="section">
            <h2>{{ $modern ? 'Lainnya' : 'Bahasa' }}</h2>
            @if ($modern)
                <p class="sub" style="margin-top:8px"><b>Bahasa:</b> {{ $d['languages'] }}</p>
            @else
                <p class="sub" style="margin-top:8px">{{ $d['languages'] }}</p>
            @endif
        </div>
    @endif
</div>
</body>
</html>
