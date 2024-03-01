<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>{{ config('app.name', '熱流体シミュレーションシステム') }} - エラー</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/css/bootstrap-icons.min.css') }}">
    <script src="{{ asset('/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('/js/bootstrap.min.js') }}"></script>
</head>

<body>
    <div class="container-fluid">
        <div class="mt-3">
            <div class="container d-flex align-items-center justify-content-center pt-5 mt-5">
    <div class="d-flex flex-column">
        <h1>システムエラーが発生しました。</h1>
        <h3 class="mt-3">エラーコード：{{ property_exists($e, 'statusCode') ? $e->getStatusCode() : 500 }}</h3>
        <h3>エラー内容：</h3>
        <h3>{{ $e->getMessage() }}</h3>
        @foreach ($e->getTrace() as $trace)
            @if (isset($trace['file']))
                <span>{{$trace['file'] }}</span>
            @endif
            @if (isset($trace['line']))
                <span>{{$trace['line'] }}</span>
            @endif
            @if (isset($trace['function']))
                <span>{{$trace['function'] }}</span>
            @endif
            @if (isset($trace['class']))
                <span>{{$trace['class'] }}</span>
            @endif
            @if (isset($trace['type']))
                <span>{{$trace['type'] }}</span>
            @endif
        @endforeach
    </div>
</div>
        </div>
    </div>
</body>
