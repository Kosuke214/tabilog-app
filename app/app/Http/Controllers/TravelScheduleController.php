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
          ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $request->input('departure-time'))->format('Y-m-d H:i')
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
                    if ($route['waypoint'] !== null) {
                        $routes[] = [
                            'waypoint' => $route['waypoint'],
                            'stay_hours' => $route['stay_hours'],
                            'stay_minutes' => $route['stay_minutes'],
                            'legs' => $route['legs'],
                            'duration' => $route['duration'],
                        ];
                    } else {
                        $routes[] = [
                            'waypoint' => null,
                            'stay_hours' => 0,
                            'stay_minutes' => 0,
                            'legs' => $route['legs'],
                            'duration' => $route['duration'],
                        ];
                    }

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

        // \Log::info('Route Details: ' . json_encode($routeDetails));

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

        dd($origin, $destination, $mode, $travelDuration, $stayDuration, $departureTime, $waypoint, $routes, $routeDetails);

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

        $routes = [];

        // 出発地から経由地までの経路を取得し、配列に追加
        if (!empty($waypoint)) {
            $waypoints = explode("|", $waypoint);
            $previousWaypoint = $origin;

            foreach ($waypoints as $waypoint) {
                // 経由地から目的地までの経路を取得
                $waypointRoutes = $this->getDirectionRoutes($previousWaypoint, $waypoint, $destination, $mode, $stayDuration, $departureTime);

                foreach ($waypointRoutes as $route) {
                    $routes[] = $route;
                    $previousWaypoint = $route['waypoint']; // 経由地を更新
                }
            }

            // 経由地から目的地までの経路を取得
            $finalRoute = $this->getDirectionRoutes(end($waypoints), $destination, $destination, $mode, $stayDuration, $departureTime);
            if (!empty($finalRoute)) {
                $routes[] = $finalRoute[0];
            }
        }

        $routeDetails = [];
        $totalDuration = 0; // 累計移動時間を初期化
        $arrivalTime = $departureTime->copy(); // 出発時刻を到着予想時刻の初期値とする

        foreach ($routes as $index => $route) {
          $waypointName = $route['waypoint'] ?? null;
          $stayDurationMinutes = $route['stay_hours'] * 60 + $route['stay_minutes'];
          $legDuration = isset($route['legs'][0]['duration']['value']) ? $route['legs'][0]['duration']['value'] : 0;
          $waypointLegDuration = isset($route['legs'][0]['duration']['value']) ? $route['legs'][0]['duration']['value'] : 0;
      
          $stayDurationSeconds = $stayDurationMinutes * 60;
      
          if ($index === 0) {
              $totalDuration = $stayDurationSeconds + $legDuration;
          } else {
              $totalDuration += $stayDurationSeconds + $legDuration;
          }
      
          if ($waypointName !== null) {
              $routeDetails[] = [
                  'waypointName' => $waypointName,
                  'stayDuration' => $stayDurationSeconds,
                  'legDuration' => $legDuration,
                  'totalDuration' => $totalDuration,
                  'departure-time' => $departureTime,
                  'origin' => end($waypoints),
                  'destination' => $destination,
                  'arrival-time' => $arrivalTime->addSeconds($waypointLegDuration),
              ];
          }
        }
        
        // 最後の配列のstayDurationを修正
        if (!empty($routeDetails)) {
            $routeDetails[count($routeDetails) - 1]['stayDuration'] = 0;
        }
        
        // 最後の配列のtotalDurationを修正
        if (!empty($routeDetails)) {
            $routeDetails[count($routeDetails) - 1]['totalDuration'] -= $routeDetails[count($routeDetails) - 1]['legDuration'];
        }
        
        // ルート詳細情報をセッションに保存
        $request->session()->put('routeDetails', $routeDetails);
        
        \Log::info('Route Details: ' . json_encode($routeDetails));
        
        
        
        

        return view('travel-schedule', [
            'origin' => $origin,
            'destination' => $destination,
            'mode' => $mode,
            'travelDuration' => $travelDuration,
            'stayDuration' => $stayDuration,
            'departure-time' => $departureTime,
            'waypoint' => $waypoint,
            'routes' => $routes,
            'routeDetails' => $routeDetails,
            'arrivalTime' => $arrivalTime,
        ]);
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
              'origin' => $origin,
              'destination' => $destination,
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

        // \Log::info('Routes: ' . json_encode($routes));

        return $routes;
    }
}