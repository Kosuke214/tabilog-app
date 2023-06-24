@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">経路案内</div>

                    <div class="card-body">
                        <div id="map" style="height: 400px; margin-bottom: 20px;"></div>

                        <form id="directions-form" action="{{ route('storeSchedule') }}" method="POST">
                            @csrf <!-- CSRFトークンを追加 -->

                            <div class="form-group">
                                <label for="origin">出発地：</label>
                                <input type="text" id="origin" name="origin" class="form-control" value="{{ $origin }}" required>
                            </div>

                            <div class="form-group">
                                <label for="destination">目的地：</label>
                                <input type="text" id="destination" name="destination" class="form-control" value="{{ $destination }}" required>
                            </div>

                            <div class="form-group">
                                <label for="mode">移動手段：</label>
                                <select id="mode" name="mode" class="form-control">
                                    <option value="DRIVING">車</option>
                                    <option value="WALKING">徒歩</option>
                                    <option value="BICYCLING">自転車</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="departure-time">出発時間：</label>
                                <input type="datetime-local" id="departure-time" name="departure-time" class="form-control" value="{{ request('departure-time') ?? $departureTime ?? '' }}" required>
                            </div>

                            <div class="form-group">
                                <label for="stay-duration">滞在時間（分）：</label>
                                <input type="number" id="stay-duration" name="stay-duration" class="form-control" value="{{ isset($stayDuration) ? $stayDuration : 0 }}" required>
                            </div>

                            <input type="hidden" name="travelDuration" id="travel-duration"> <!-- travelDuration フィールドを追加 -->

                            <button type="button" id="get-route" class="btn btn-primary">経路を取得</button>
                            <button type="submit" id="apply-schedule" class="btn btn-success" style="display: none;">スケジュールに反映</button>
                        </form>

                        <div id="arrival-time" style="margin-top: 20px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function initMap() {
            const directionsService = new google.maps.DirectionsService();
            const directionsRenderer = new google.maps.DirectionsRenderer();
            const tokyoStation = { lat: 35.681236, lng: 139.767125 }; // 東京駅の座標
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 14,
                center: tokyoStation,
            });
            directionsRenderer.setMap(map);

            const form = document.getElementById("directions-form");
            const getRouteButton = document.getElementById("get-route");
            const applyScheduleButton = document.getElementById("apply-schedule");

            getRouteButton.addEventListener("click", function (event) {
                event.preventDefault();
                calculateAndDisplayRoute(directionsService, directionsRenderer);
            });

            applyScheduleButton.addEventListener("click", function (event) {
                event.preventDefault();
                form.submit();
            });

            function calculateAndDisplayRoute(directionsService, directionsRenderer) {
                const origin = document.getElementById("origin").value;
                const destination = document.getElementById("destination").value;
                const mode = document.getElementById("mode").value;
                const departureTime = new Date(document.getElementById("departure-time").value);
                const stayDuration = parseInt(document.getElementById("stay-duration").value);

                if (isNaN(departureTime.getTime()) || isNaN(stayDuration)) {
                    // 出発時間や滞在時間が無効な場合は処理を中止
                    return;
                }

                const request = {
                    origin: origin,
                    destination: destination,
                    travelMode: google.maps.TravelMode[mode],
                    drivingOptions: {
                        departureTime: departureTime,
                    },
                };

                directionsService.route(request, function (response, status) {
                    if (status === "OK") {
                        // 経路の取得が成功した場合
                        directionsRenderer.setDirections(response);

                        const route = response.routes[0];
                        const legs = route.legs;
                        let totalDuration = 0;

                        for (let i = 0; i < legs.length; i++) {
                            totalDuration += legs[i].duration.value;
                        }

                        const arrivalTime = new Date(departureTime.getTime() + totalDuration * 1000);
                        arrivalTime.setMinutes(arrivalTime.getMinutes() + stayDuration);
                        const arrivalTimeString = arrivalTime.toLocaleTimeString("ja-JP", { hour: "numeric", minute: "numeric" });

                        const arrivalTimeElement = document.getElementById("arrival-time");
                        arrivalTimeElement.innerText = "予想到着時刻：" + arrivalTimeString;

                        applyScheduleButton.style.display = "block";

                        // 移動時間を travelDuration フィールドに設定
                        const travelDurationField = document.getElementById("travel-duration");
                        travelDurationField.value = totalDuration;
                    } else {
                        // 経路の取得が失敗した場合
                        window.alert("経路の取得に失敗しました。ステータス：" + status);
                    }
                });
            }
        }

    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('app.google_maps_api_key') }}&callback=initMap&libraries=places&language=ja" async defer></script>
@endsection
