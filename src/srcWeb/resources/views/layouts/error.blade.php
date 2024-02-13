@extends('layouts.app')

@section('title', 'エラー')

@section('content')
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

        <div class="button-area mt-3">
            <button type="button" class="btn btn-outline-secondary" onclick="location.href=document.referrer">戻る</button>
        </div>
    </div>
</div>
@endsection
