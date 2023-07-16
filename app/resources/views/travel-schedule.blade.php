@extends('layouts.app')

@section('content')
    @php
        $routeDetails = session('routeDetails') ?? [];
        $departureTime = session('departure-time') ? \Carbon\Carbon::parse(session('departure-time')) : null;
        $origin = session('origin') ?? '';
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="container">
        <h2>旅程スケジュール</h2>
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">経路詳細</div>
                    <div class="card-body">
                        @if (count($routeDetails) > 0)
                            @foreach ($routeDetails as $index => $routeDetail)
                                <div>
                                    @if ($index === 0)
                                        <h5>出発日：{{ $routeDetail['departure-time']->format('Y年n月j日') }}</h5>
                                        <div>出発時間: {{ $routeDetail['departure-time']->format('G:i') }}</div>
                                        <div>出発地：{{ $origin }}</div>
                                        @if (isset($routeDetail['legDuration']) && $routeDetail['legDuration'] > 0)
                                            <div>↓ 移動時間：{{ floor($routeDetail['legDuration'] / 60) }}分</div>
                                        @endif
                                    @endif
                                    @if ($index !== count($routeDetails) - 1)
                                        @if (isset($routeDetail['waypointName']))
                                            <div>経由地：{{ $routeDetail['waypointName'] }}（滞在時間：{{ floor($routeDetail['stayDuration'] / 3600) }}時間{{ floor(($routeDetail['stayDuration'] % 3600) / 60) }}分）</div>
                                            @if (isset($routeDetails[$index+1]['legDuration']) && $routeDetails[$index+1]['legDuration'] > 0)
                                                <div>↓ 移動時間：{{ floor($routeDetails[$index+1]['legDuration'] / 60) }}分</div>
                                            @endif

                                        @endif
                                    @else
                                        <div>目的地：{{ $routeDetail['destination'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                            <div>到着予想時刻：{{ $routeDetails[count($routeDetails) - 1]['arrival-time']->format('Y年n月j日 G:i') }}</div>
                        @else
                            <div>経路詳細はありません。</div>
                        @endif

                        <form action="{{ route('travel-schedule-edit') }}" method="GET">
                            @csrf
                            <button type="submit" class="btn btn-primary">スケジュールを編集する</button>
                        </form>
                    </div>

                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">経路案内</div>
                    <div class="card-body">
                        <div id="map" style="height: 400px; margin-bottom: 20px;"></div>

                        <form id="directions-form" action="{{ route('store-schedule') }}" method="POST">
                            @csrf <!-- CSRFトークンを追加 -->

                            <div class="form-group">
                                <label for="departure-time">出発時間：</label>
                                <input type="datetime-local" id="departure-time" name="departure-time" class="form-control" value="{{ $routeDetail['departure-time'] ? $routeDetail['departure-time']->format('Y-m-d\TH:i') : '' }}" required>
                            </div>

                            <div class="form-group">
                                <label for="origin">出発地：</label>
                                <input type="text" id="origin" name="origin" class="form-control" value="{{ $origin ?? '' }}" required>
                            </div>

                            <div class="form-group">
                                <label for="waypoint">経由地：</label>
                                <input type="text" id="waypoint" name="waypoint" class="form-control" value="{{ $waypoint ?? '' }}">
                            </div>

                            <div class="form-group">
                                <label for="stay-duration">滞在時間（分）：</label>
                                <input type="number" id="stay-duration" name="stay-duration" class="form-control" value="{{ $stayDuration ?? 0 }}" required>
                            </div>

                            <div class="form-group">
                                <label for="destination">目的地：</label>
                                <input type="text" id="destination" name="destination" class="form-control" value="{{ $destination ?? '' }}" required>
                            </div>

                            <div class="form-group">
                                <label for="mode">移動手段：</label>
                                <select id="mode" name="mode" class="form-control">
                                    <option value="DRIVING">車</option>
                                    <option value="WALKING">徒歩</option>
                                    <option value="BICYCLING">自転車</option>
                                </select>
                            </div>

                            <input type="hidden" name="travel-duration" id="travel-duration">
                            <!-- 経路詳細のhidden入力 -->
                            @forelse ($routeDetails as $index => $routeDetail)
                                @if(isset($routeDetail['waypoint']) && isset($waypoint))
                                    <input type="hidden" name="routeDetails[{{ $index }}][waypoint]" value="{{ $routeDetail['waypoint'] }}">
                                    <input type="hidden" name="routeDetails[{{ $index }}][stayDuration]" value="{{ $routeDetail['stay_minutes'] }}">
                                    <input type="hidden" name="routeDetails[{{ $index }}][legDuration]" value="{{ $routeDetail['duration'] }}">
                                    <input type="hidden" name="routeDetails[{{ $index }}][totalDuration]" value="{{ $routeDetail['duration'] }}">
                                @endif
                            @empty
                                <!-- No route details -->
                            @endforelse


                            <button type="button" id="get-route" class="btn btn-primary">経路を取得</button>
                            <button type="submit" id="apply-schedule" class="btn btn-success" style="display: none;">スケジュールに反映</button>
                        </form>

                        <div id="arrival-time" style="margin-top: 20px;"></div>
                        <div id="route-summary" style="margin-top: 20px;"></div>


                        <div id="arrival-time" style="margin-top: 20px;"></div>
                        <div id="route-summary" style="margin-top: 20px;"></div>
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
                const waypoint = document.getElementById("waypoint").value;
                const departureTimeInput = document.getElementById("departure-time");
                const departureTime = new Date(departureTimeInput.value);
                const stayDuration = parseInt(document.getElementById("stay-duration").value);
                const destination = document.getElementById("destination").value;
                const mode = document.getElementById("mode").value;

                const request = {
                    origin: origin,
                    destination: destination,
                    waypoints: [
                        {
                            location: waypoint,
                            stopover: true,
                        }
                    ],
                    travelMode: google.maps.TravelMode[mode],
                    drivingOptions: {
                        departureTime: departureTime,
                    },
                };

                directionsService.route(request, function (response, status) {
                    if (status === "OK") {
                        directionsRenderer.setDirections(response);

                        const route = response.routes[0];
                        const legs = route.legs;
                        let totalDuration = 0;

                        const routes = []; // 経路情報を保存する配列

                        for (let i = 0; i < legs.length; i++) {
                            const leg = legs[i];
                            const waypointName = leg.end_address;
                            const legDuration = leg.duration.value;

                            const routeInfo = {
                                waypointName: waypointName,
                                legDuration: legDuration
                            };

                            routes.push(routeInfo);

                            if (i < legs.length - 1) {
                                totalDuration += legDuration; // 経由地間の移動時間を加算
                                totalDuration += stayDuration * 60; // 経由地の滞在時間を加算
                            } else {
                                totalDuration += legDuration; // 最後の目的地までの所要時間のみ加算
                            }
                        }

                        const arrivalTime = new Date(departureTime.getTime() + (totalDuration * 1000));
                        const arrivalTimeString = arrivalTime.toLocaleTimeString("ja-JP", { hour: "numeric", minute: "numeric" });

                        const arrivalTimeElement = document.getElementById("arrival-time");
                        arrivalTimeElement.innerText = "予想到着時刻：" + arrivalTimeString;

                        const routeSummaryElement = document.getElementById("route-summary");
                        routeSummaryElement.innerHTML = "";
                        for (let i = 0; i < routes.length; i++) {
                            const routeInfo = routes[i];
                            const waypointName = routeInfo.waypointName;
                            const legDuration = routeInfo.legDuration;

                            const legInfo = document.createElement("div");
                            legInfo.innerHTML = `経由地${i + 1}: ${waypointName} (${legDuration}秒)`;

                            routeSummaryElement.appendChild(legInfo);
                        }

                        applyScheduleButton.style.display = "block";

                        const travelDurationField = document.getElementById("travel-duration");
                        travelDurationField.value = totalDuration;
                    } else {
                        window.alert("経路の取得に失敗しました。ステータス：" + status);
                    }
                });
            }
        }
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('app.google_maps_api_key') }}&callback=initMap&libraries=places&language=ja" async defer></script>
@endsection