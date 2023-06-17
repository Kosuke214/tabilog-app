<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class DirectionsController extends Controller
{
    public function showDirections(Request $request)
    {
        $origin = $request->input('origin');
        $destination = $request->input('destination');

        // Google Maps APIへのリクエスト
        $client = new Client();
        $key = env('GOOGLE_MAPS_API_KEY');
        $response = $client->get('https://maps.googleapis.com/maps/api/directions/json', [
            'query' => [
                'origin' => $origin,
                'destination' => $destination,
                'key' => $key,
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        // 結果の表示
        // 例えば、移動時間の取得
        $duration = $data['routes'][0]['legs'][0]['duration']['text'];

        return view('directions', compact('duration', 'origin', 'destination'));
    }
}
