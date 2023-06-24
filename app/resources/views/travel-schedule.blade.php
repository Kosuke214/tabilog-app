@extends('layouts.app')

@section('content')
    @php
      $origin = session('schedule.origin') ?? '';
      $destination = session('schedule.destination') ?? '';
      $travelDuration = session('schedule.duration') ?? 0; // travelDuration を duration に修正
      $stayDuration = session('schedule.stay-duration') ?? 0; // stay-duration を指定
      $estimatedArrivalTime = session('schedule.estimatedArrivalTime') ?? '';
    @endphp

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">旅程スケジュール</div>

                    <div class="card-body">
                    <div>
                        <div>出発時間：{{ (new \DateTime($estimatedArrivalTime))->format('n月j日 G時i分') }}</div>
                        <div>出発地：{{ $origin }}</div>
                    </div>
                    <div>
                        ↓　移動時間：{{ floor($travelDuration / 3600) }}時間 {{ floor(($travelDuration % 3600) / 60) }}分
                    </div>
                    <div>
                        目的地：{{ $destination }}　滞在時間：{{ $stayDuration }}分
                    </div>
                    <div>
                        全行程の終了時間：{{ (new \DateTime($estimatedArrivalTime))->add(new \DateInterval('PT' . ($travelDuration + $stayDuration * 60) . 'S'))->format('n月j日 G時i分') }}
                    </div>
                    <form action="{{ route('directions') }}" method="GET">
                        @csrf <!-- CSRFトークンを追加 -->
                        <div class="form-group">
                            <label for="departure-time">出発時間：</label>
                            <input type="datetime-local" id="departure-time" name="departure-time" class="form-control" value="{{ (new \DateTime($estimatedArrivalTime))->add(new \DateInterval('PT' . ($travelDuration + $stayDuration * 60) . 'S'))->format('Y-m-d\TH:i') }}" required>
                        </div>

                        <button type="submit" class="btn btn-primary">次の行き先を選ぶ</button>
                    </form>
                </div>

                </div>
            </div>
        </div>
    </div>
@endsection
