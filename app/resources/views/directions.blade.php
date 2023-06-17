<!DOCTYPE html>
<html>
<head>
    <title>Directions</title>
    <style>
        #map {
            height: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <h1>Directions</h1>
    <div>
        <form action="{{ route('directions') }}" method="GET">
            <label for="origin">出発地：</label>
            <input type="text" name="origin" id="origin" value="<?= isset($origin) ? $origin : '' ?>" required>
            <label for="destination">目的地：</label>
            <input type="text" name="destination" id="destination" value="<?= isset($destination) ? $destination : '' ?>" required>
            <label for="mode">移動手段：</label>
            <select name="mode" id="mode">
                <option value="driving" <?= isset($mode) && $mode == 'driving' ? 'selected' : '' ?>>車</option>
                <option value="walking" <?= isset($mode) && $mode == 'walking' ? 'selected' : '' ?>>徒歩</option>
                <option value="transit" <?= isset($mode) && $mode == 'transit' ? 'selected' : '' ?>>公共交通機関</option>
                <option value="bicycling" <?= isset($mode) && $mode == 'bicycling' ? 'selected' : '' ?>>自転車</option>
            </select>
            <button type="submit">検索</button>
        </form>
    </div>
    <div>
        <?php if (isset($duration)): ?>
            <p>所要時間: <?= $duration ?></p>
        <?php endif; ?>
    </div>
    <div id="map"></div>

    <script>
        function initMap() {
            const directionsService = new google.maps.DirectionsService();
            const directionsRenderer = new google.maps.DirectionsRenderer();
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 7,
                center: { lat: 41.85, lng: -87.65 }, // デフォルトの地図の中心座標
            });
            directionsRenderer.setMap(map);

            const origin = "<?= isset($origin) ? $origin : '' ?>"; // 出発地の座標または住所
            const destination = "<?= isset($destination) ? $destination : '' ?>"; // 目的地の座標または住所
            const mode = "<?= isset($mode) ? $mode : 'driving' ?>"; // 移動手段の選択

            const request = {
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode[mode.toUpperCase()],
            };

            directionsService.route(request, function (response, status) {
                if (status === google.maps.DirectionsStatus.OK) {
                    directionsRenderer.setDirections(response);

                    const route = response.routes[0];
                    const duration = route.legs[0].duration.text;
                    document.querySelector('#duration').textContent = duration;
                }
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= config('app.google_maps_api_key') ?>&callback=initMap" async defer></script>
</body>
</html>




<!-- <!DOCTYPE html>
<html>
<head>
    <title>Directions</title>
    <style>
        #map {
            height: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <h1>Directions</h1>
    <form action="{{ route('directions') }}" method="GET">
    <label for="origin">Origin:</label>
    <input type="text" name="origin" id="origin" value="{{ $origin ?? '' }}" required>

    <label for="destination">Destination:</label>
    <input type="text" name="destination" id="destination" value="{{ $destination ?? '' }}" required>

    <button type="submit">Get Directions</button>
    </form>

    @if (isset($duration))
        <h2>Travel Time</h2>
        <p>Estimated travel time from {{ $origin }} to {{ $destination }}: {{ $duration }}</p>
    @endif
    <div id="map"></div>

    <script>
        function initMap() {
            const directionsService = new google.maps.DirectionsService();
            const directionsRenderer = new google.maps.DirectionsRenderer();
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 7,
                center: { lat: 41.85, lng: -87.65 }, // デフォルトの地図の中心座標
            });
            directionsRenderer.setMap(map);

            const origin = "出発地"; // 出発地の座標または住所
            const destination = "目的地"; // 目的地の座標または住所

            const request = {
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode.DRIVING,
            };

            directionsService.route(request, function (response, status) {
                if (status === google.maps.DirectionsStatus.OK) {
                    directionsRenderer.setDirections(response);
                }
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('app.google_maps_api_key') }}&callback=initMap" async defer></script>
</body>
</html>


<h1>Directions</h1>

<form action="{{ route('directions') }}" method="GET">
    <label for="origin">Origin:</label>
    <input type="text" name="origin" id="origin" value="{{ $origin ?? '' }}" required>

    <label for="destination">Destination:</label>
    <input type="text" name="destination" id="destination" value="{{ $destination ?? '' }}" required>

    <button type="submit">Get Directions</button>
</form>

@if (isset($duration))
    <h2>Travel Time</h2>
    <p>Estimated travel time from {{ $origin }} to {{ $destination }}: {{ $duration }}</p>
@endif -->
