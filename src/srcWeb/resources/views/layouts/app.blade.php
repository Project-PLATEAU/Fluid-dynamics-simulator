<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>{{ config('app.name', '熱流体シミュレーションシステム') }} - @yield('title')</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/css/bootstrap-icons.min.css') }}">

    {{-- 個別css --}}
    @yield('css')

    <script src="{{ asset('/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('/js/bootstrap.min.js') }}"></script>
</head>

<body>
    <div class="container-fluid">
        <nav class="navbar bg-light">

            <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                <div class="offcanvas-header">
                    <span class="offcanvas-title" id="offcanvasNavbarLabel"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="{{ route('home.index') }}"><i class="bi bi-house-door"></i><span class="ps-2">ホーム</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('simulation_model.index') }}"> <i class="bi bi-calculator"></i><span class="ps-2">シミュレーションモデル<span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('city_model.index') }}"><i class="bi-globe-americas"></i><span class="ps-2">3D都市モデル</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('solver.index') }}"><i class="bi bi-gear"></i><span class="ps-2">熱流体解析ソルバ</span></a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="container-fluid">

                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="d-flex flex-row">
                    @yield('display-info-left-area')
                    <div class="p-2">@yield('model-kind-display-area')</div>
                    <div class="p-2" style="margin-left: 150px;">@yield('city-model-display-area')</div>
                </div>

                <div class="d-flex flex-row text-right">
                    <i class="bi bi-person-fill p-2"></i><span class="p-2">{{ json_decode(Cookie::get(App\Commons\Constants::LOGIN_COOKIE_NAME))->display_name }}</span>
                    <a class="p-2 text-decoration-none" href="{{ route('login.logout') }}"><span class="p-2">Logout</span></a>
                </div>

            </div>
        </nav>

        <div class="mt-3">
             {{-- コンテンツ --}}
            @yield('content')
        </div>

        {{-- 個別js --}}
        @yield('js')

        {{-- モーダル定義エリア --}}
        @yield('modal-area')
    </div>
</body>

</html>
