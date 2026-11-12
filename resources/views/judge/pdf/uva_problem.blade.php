<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $problem->title }}</title>
    <style>
        @page { margin: 24mm 20mm; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 11pt; line-height: 1.5; }
        h1 { border-bottom: 2px solid #172033; font-size: 20pt; margin: 0 0 8px; padding-bottom: 8px; }
        h2 { font-size: 13pt; margin: 20px 0 6px; }
        .limits { color: #465166; font-size: 9pt; margin-bottom: 22px; }
        p { margin: 0 0 9px; }
        pre { background: #f1f4f8; border: 1px solid #d5dce5; padding: 10px; white-space: pre-wrap; }
        code { font-family: DejaVu Sans Mono, monospace; }
        img.diagram { display: block; margin: 12px auto; max-height: 540px; max-width: 100%; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #d5dce5; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h1>{{ $problem->title }}</h1>
    <div class="limits">Time limit: {{ $problem->time_limit }} seconds | Memory limit: {{ $problem->memory_limit }} MB</div>

    <div>{!! $descriptionHtml !!}</div>

    @foreach($diagramImages as $image)
        <img class="diagram" src="{{ $image }}" alt="Problem diagram">
    @endforeach

    @foreach($sampleCases as $index => $sample)
        <h2>Sample Input {{ $index + 1 }}</h2>
        <pre>{{ $sample->input }}</pre>
        <h2>Sample Output {{ $index + 1 }}</h2>
        <pre>{{ $sample->expected_output }}</pre>
    @endforeach
</body>
</html>
