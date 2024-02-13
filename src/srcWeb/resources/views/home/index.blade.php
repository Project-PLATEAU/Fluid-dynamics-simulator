@extends('layouts.app')

@section('title', 'ホーム画面')

@section('model-kind-display-area')
<span>{{ App\Commons\Constants::MODEL_KIND_BLANK }}</span>
@endsection

@section('city-model-display-area')
<span>{{ App\Commons\Constants::MODEL_IDENTIFICATE_NAME_DISPLAY_BLANK }}</span>
@endsection

@section('content')
    <div>
        <p>ホーム画面</p>
    </div>
@endsection

