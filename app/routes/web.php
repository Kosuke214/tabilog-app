<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DirectionsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes();

Route::match(['get', 'post'], '/travel-schedule', function () {
    return view('travel-schedule');
})->name('travel-schedule');

Route::get('/directions', function (Illuminate\Http\Request $request) {
    $origin = $request->query('origin');
    $destination = $request->query('destination');
    $mode = $request->query('mode');

    // 経路を取得するための処理を追加する（Google Maps APIなどを使用）

    // 到着予想時間を計算するための処理を追加する

    return view('directions', [
        'origin' => $origin,
        'destination' => $destination,
        'mode' => $mode,
        // 経路や到着予想時間をビューに渡す
    ]);
})->name('directions');

Route::post('/store-schedule', function (Illuminate\Http\Request $request) {
    $data = $request->validate([
        'origin' => 'required',
        'destination' => 'required',
        'mode' => 'required',
        'departure-time' => 'required|date',
        'stay-duration' => 'required|integer',
    ]);

    session($data);

    return redirect()->route('travel-schedule');
})->name('store-schedule');



