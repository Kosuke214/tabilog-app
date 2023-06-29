<!DOCTYPE html>
<html>
<head>
    <title>Directions</title>
</head>
<body>
    <h1>Directions</h1>

    @if ($directions['status'] === 'OK')
        <h2>移動時間: {{ $directions['routes'][0]['legs'][0]['duration']['text'] }}</h2>
        <h2>距離: {{ $directions['routes'][0]['legs'][0]['distance']['text'] }}</h2>
        <h2>経路:</h2>
        <ol>
            @foreach ($directions['routes'][0]['legs'][0]['steps'] as $step)
                <li>{{ $step['html_instructions'] }}</li>
            @endforeach
        </ol>
    @else
        <p>経路を取得できませんでした。</p>
    @endif
</body>
</html>
