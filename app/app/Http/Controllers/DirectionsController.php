<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redirect;

class DirectionsController extends Controller
{
    public function showDirections(Request $request)
    {
        $origin = $request->input('origin');
        $destination = $request->input('destination');
        $selectedMode = $request->input('mode'); // 選択された移動手段

        // 出発時間と滞在時間を取得
        $departureTime = $request->input('departure_time');
        $stayDuration = $request->input('stay_duration', 0); // 滞在時間が指定されていない場合はデフォルト値として0を使用

        // Google Maps APIへのリクエスト
        $client = new Client();
        $key = getenv('GOOGLE_MAPS_API_KEY');

        // 移動手段の設定
        $travelModes = ['driving', 'walking'];

        // 結果を格納する配列
        $results = [];

        foreach ($travelModes as $travelMode) {
            $response = $client->get('https://maps.googleapis.com/maps/api/directions/json', [
                'query' => [
                    'origin' => $origin,
                    'destination' => $destination,
                    'key' => $key,
                    'mode' => $travelMode, // 移動手段を指定
                    'language' => 'ja', // 言語を日本語に指定
                    'departure_time' => $departureTime, // 出発時間を指定
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            // 移動時間の取得
            if (isset($data['routes'][0]['legs'][0]['duration']['text'])) {
                $duration = $data['routes'][0]['legs'][0]['duration']['text'];
            } else {
                $duration = 'N/A'; // 移動時間が取得できない場合は 'N/A' とする
            }

            // 経路の取得
            if (isset($data['routes'][0]['overview_polyline']['points'])) {
                $polyline = $data['routes'][0]['overview_polyline']['points'];
            } else {
                $polyline = null; // 経路が取得できない場合は null とする
            }

            // 結果を配列に追加
            $results[$travelMode] = [
                'duration' => $duration,
                'polyline' => $polyline,
            ];
        }

        return view('directions', compact('results', 'origin', 'destination', 'selectedMode', 'departureTime', 'stayDuration'));
    }


    public function storeSchedule(Request $request)
    {
        $request->validate([
            'origin' => 'required',
            'destination' => 'required',
            'travelDuration' => 'required|numeric',
            'stayDuration' => 'required|numeric',
            'estimatedArrivalTime' => 'required|date_format:Y-m-d\TH:i:s.u\Z',
        ]);

        $data = $request->only('origin', 'destination', 'travelDuration', 'stayDuration', 'estimatedArrivalTime');
        $origin = $request->input('origin');
        $data['origin'] = $origin;
        $request->session()->put('schedule', $data);
        
        return redirect()->route('travel-schedule')->with('origin', $origin)->with('schedule', $data);
    }

    public function showTravelSchedule()
    {
        // $waypointの値を設定するロジックを追加
        $waypoint = '経由地の値';

        return view('travel-schedule', compact('waypoint'));
        
    }

}
