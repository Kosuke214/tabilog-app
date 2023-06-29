<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class DirectionsController extends Controller
{
    public function showDirectionsForm(Request $request)
    {
        $origin = $request->input('origin');
        $waypoint = $request->input('waypoint');
        $destination = $request->input('destination');
        $mode = $request->input('mode');

        return view('directions', compact('origin', 'waypoint', 'destination', 'mode'));
    }

    public function getDirections(Request $request)
    {
        $request->validate([
            'origin' => 'required',
            'destination' => 'required',
            'mode' => 'required',
            'departure-time' => 'required|date_format:Y-m-d\TH:i',
            'stay-duration' => 'required|integer',
        ]);

        $origin = $request->input('origin');
        $waypoint = $request->input('waypoint');
        $destination = $request->input('destination');
        $mode = $request->input('mode');
        $departureTime = $request->input('departure-time');
        $stayDuration = $request->input('stay-duration');

        $departureTime = Carbon::createFromFormat('Y-m-d\TH:i', $departureTime);

        $travelDuration = $this->calculateTravelDuration($origin, $waypoint, $destination, $mode, $departureTime, $stayDuration);

        session([
            'origin' => $origin,
            'waypoint' => $waypoint,
            'destination' => $destination,
            'mode' => $mode,
            'departure-time' => $departureTime->format('Y-m-d\TH:i'),
            'stay-duration' => $stayDuration,
            'travelDuration' => $travelDuration,
        ]);

        return redirect()->route('travel-schedule');
    }

    private function calculateTravelDuration($origin, $waypoint, $destination, $mode, $departureTime, $stayDuration)
    {
        $client = new Client();
        $apiKey = config('app.google_maps_api_key');

        $waypoints = !empty($waypoint) ? [$waypoint] : [];

        $url = "https://maps.googleapis.com/maps/api/directions/json?origin=$origin&destination=$destination&waypoints=" . implode("|", $waypoints) . "&mode=$mode&key=$apiKey&departure_time=" . $departureTime->getTimestamp();

        $response = $client->get($url);
        $data = json_decode($response->getBody(), true);

        $legs = $data['routes'][0]['legs'];
        $totalDuration = 0;

        foreach ($legs as $leg) {
            $totalDuration += $leg['duration']['value'];
        }

        $totalDuration += $stayDuration * 60;

        return $totalDuration;
    }
}
