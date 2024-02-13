<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>{{ config('app.name', 'BRIDGE-CFD') }} - ログイン</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/css/bootstrap-icons.min.css') }}">

    <style>
        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
                font-size: 3.5rem;
            }
        }

        html,
        body {
            height: 100%;
        }

        body {
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }

        .form-signin {
            width: 100%;
            max-width: 500px;
            padding: 15px;
            margin: auto;
        }

        .form-signin .checkbox {
            font-weight: 400;
        }

        .form-signin .form-floating:focus-within {
            z-index: 2;
        }

        .form-signin input[type="text"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }

        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>

    {{-- js読み込み --}}
    <script src="{{ asset('/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('/js/bootstrap.min.js') }}"></script>
</head>

<body>
    <div class="d-flex flex-column form-signin">
        <div class="form-signin">
            <form method="POST" id="frmLogin"action="{{ url('/login') }}">
                @csrf
                <h1 class="h3 mb-3 fw-normal text-center">BRIDGE　CFDシミュレータ</h1>

                <div class="form-floating">
                    <input type="text" class="form-control" name="user_id" id="user_id" placeholder="Test User" value="{{ $userAccount->user_id }}">
                    <label for="user_id">User ID</label>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" name="password" id="password" placeholder="Password" value="{{ $userAccount->password }}">
                    <label for="password">Password</label>
                </div>

                <button class="w-100 btn btn-lg btn-outline-secondary" type="submit" id="login">ログイン</button>
            </form>

            <div>
                <hr class="line">
                <p>{{ config('const.ver_app') }}</p>
            </div>
        </div>


        @include('layouts.message_dialog')
    </div>

    <script>
       $(function(){

            @if ($message)
                const msg_type = "{{ $message['type'] }}";
                const code = "{{ $message['code'] }}";
                const msg = "{{ $message['msg'] }}";

                if (msg_type == "E")
                {
                    // エラーメッセージダイアログを表示
                    $("div#messageModal [class='modal-body']").html(
                        '<div class="d-flex flex-row"><img class="ms-2" src="{{ asset('/image/dialog/error.png') }}?ver={{ config('const.ver_image') }}" height="65px" width="65px" alt="warning"><span class="ms-4" id="message"></span></div>');
                    $("div#messageModal [class='modal-footer']").html('<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">OK</button>');
                }

                $("div#messageModal [class='modal-header'] h1#messageModalLabel").html(code);
                $("div#messageModal [class='modal-body'] span#message").html(msg);
                $('#messageModal').modal('show');
            @endif
        });
    </script>

</body>

</html>
