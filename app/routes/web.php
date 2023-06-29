<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DirectionsController;
use App\Http\Controllers\TravelScheduleController;

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

Route::get('/', function () {
    return view('welcome');
});


Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes();

Route::get('/travel-schedule', [TravelScheduleController::class, 'travelSchedule'])->name('travel-schedule');

Route::get('/travel-schedule/edit', function () {
    return redirect()->route('directions');
})->name('travel-schedule-edit');

Route::get('/directions', [DirectionsController::class, 'showDirectionsForm'])->name('directions');
Route::post('/directions', [DirectionsController::class, 'getDirections'])->name('get-directions');

Route::post('/store-schedule', [TravelScheduleController::class, 'storeSchedule'])->name('store-schedule');


