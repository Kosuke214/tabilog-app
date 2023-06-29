<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;

class TravelScheduleController extends Controller
{
    public function travelSchedule(Request $request)
    {
        $origin = $request->input('origin');
        $destination = $request->input('destination');
        $mode = $request->input('mode');
        $travelDuration = $request->input('travel-duration');
        $stayDuration = $request->input('stay-duration');
        $departureTime = $request->input('departure-time')
            ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $request->input('departure-time'))
            : null;
        $waypoint = $request->input('waypoint');

        $routes = []; // $routes変数を初期化
        $waypoints = []; // $waypoints変数を追加

        // 出発地から経由地までの経路を取得し、配列に追加
        if (!empty($waypoint)) {
            $waypoints = explode("|", $waypoint);
            $previousDuration = 0;
            $previousWaypoint = $origin;

            foreach ($waypoints as $waypoint) {
                // 経由地から目的地までの経路を取得
                $waypointRoutes = $this->getDirectionRoutes($previousWaypoint, $waypoint, $destination, $mode, $stayDuration, $departureTime);

                foreach ($waypointRoutes as $route) {
                    $routes[] = $route;
                    $previousWaypoint = $route['waypoint']; // 経由地を更新
                }
            }
        }

        $routeDetails = [];
        $previousDuration = 0;
        $totalDuration = 0;

        foreach ($routes as $index => $route) {
            $waypointName = $route['waypoint'] ?? null;
            $stayHours = $route['stay_hours'] ?? 0;
            $stayMinutes = $route['stay_minutes'] ?? 0;

            if (isset($route['legs']) && count($route['legs']) > 0) {
                $legDuration = $route['legs'][0]['duration']['value'];
            } else {
                $legDuration = null;
            }

            $previousDuration += $legDuration + ($stayHours * 60) + $stayMinutes;
            $totalDuration += $previousDuration;

            $routeDetails[] = [
                'waypointName' => $waypointName,
                'stayDuration' => $stayHours * 60 + $stayMinutes,
                'legDuration' => $legDuration,
                'totalDuration' => $totalDuration,
            ];
        }

        // ルート詳細情報をセッションに保存
        $request->session()->put('routeDetails', $routeDetails);

        return view('travel-schedule', [
            'origin' => $origin,
            'destination' => $destination,
            'mode' => $mode,
            'travelDuration' => $travelDuration,
            'stayDuration' => $stayDuration,
            'departureTime' => $departureTime,
            'waypoint' => $waypoint,
            'routes' => $routes,
            'routeDetails' => $routeDetails,
        ]);
    }




    public function storeSchedule(Request $request)
    {
        $origin = $request->input('origin');
        $destination = $request->input('destination');
        $waypoint = $request->input('waypoint');
        $departureTime = Carbon::parse($request->input('departure-time'));
        $stayDuration = (int) $request->input('stay-duration');
        $travelDuration = (int) $request->input('travel-duration');
        $mode = $request->input('mode');

        $routeDetails = []; // ルート詳細を格納する空の配列

        $routes = [];
        foreach ($routeDetails as $index => $routeDetail) {
            $routes[] = [
                'waypoint' => $routeDetail['waypointName'], // 'waypoint' ではなく 'waypointName' を使用する
                'stayDuration' => $routeDetail['stayDuration'],
                'legDuration' => $routeDetail['legDuration'],
                'totalDuration' => $routeDetail['totalDuration'],
            ];
        }

        $routeDetails = $request->session()->get('routeDetails', []);

        // ログ出力
        \Log::info('Route Details before saving to session: ' . json_encode($routeDetails));

        // ルート詳細をセッションに保存
        $request->session()->flash('routeDetails', $routeDetails);

        // ルート詳細を表示
        \Log::info('Route Details after saving to session: ' . json_encode($request->session()->get('routeDetails', [])));


        // $routeDetails の後にある処理を実行
        $routes = [];
        foreach ($routeDetails as $index => $routeDetail) {
            $routes[] = [
                'waypoint' => $routeDetail['waypointName'],
                'stayDuration' => $routeDetail['stayDuration'],
                'legDuration' => $routeDetail['legDuration'],
                'totalDuration' => $routeDetail['totalDuration'],
            ];
        }


        // セッションに保存されたデータを確認する
        \Log::info('Route Details after saving to session: ' . json_encode($request->session()->get('routeDetails', [])));

        session([
            'origin' => $origin,
            'destination' => $destination,
            'mode' => $mode,
            'travelDuration' => $travelDuration,
            'stayDuration' => $stayDuration,
            'departure-time' => $departureTime,
            'waypoint' => $waypoint,
        ]);

        return redirect()->route('travel-schedule');
    }

    private function getDirectionRoutes($origin, $waypoint, $destination, $mode, $stayDuration, $departureTime)
    {
        

        // 経路検索のためのAPIリクエストを作成
        $apiKey = config('app.google_maps_api_key');
        $client = new Client();
        $queryParams = [
            'origin' => $origin,
            'destination' => $destination,
            'waypoints' => $waypoint,
            'mode' => $mode,
            'key' => $apiKey,
        ];
        
        if ($departureTime) {
            $queryParams['departure_time'] = $departureTime->getTimestamp();
        }
        
        $response = $client->get('https://maps.googleapis.com/maps/api/directions/json', [
            'query' => $queryParams,
        ]);
        
        // \Log::info('API Response: ' . $response->getBody());
        
        
        $data = json_decode($response->getBody(), true);
        
        $routes = [];

        // 経路ごとに処理
        foreach ($data['routes'] as $route) {
            $legs = $route['legs'];
            $duration = 0;

            foreach ($legs as $leg) {
                $duration += $leg['duration']['value'];
            }

            $stayHours = floor($stayDuration / 60);
            $stayMinutes = $stayDuration % 60;

            $totalDuration = $duration + $stayDuration;

            $routes[] = [
                'legs' => $legs,
                'waypoint' => $waypoint,
                'duration' => $totalDuration,
                'stay_hours' => $stayHours,
                'stay_minutes' => $stayMinutes,
            ];

            // 経由地から目的地までの移動時間を追加
            if (!empty($waypoint)) {
                $routes[] = [
                    'legs' => $legs,
                    'waypoint' => null,
                    'duration' => $duration,
                    'stay_hours' => 0,
                    'stay_minutes' => 0,
                ];
            }
        }
        return $routes;
    }
}