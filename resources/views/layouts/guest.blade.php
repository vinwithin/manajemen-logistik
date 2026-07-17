<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'HRZ Group') }}</title>

    <link rel="shortcut icon" href="/svg/favicon.svg" type="image/x-icon">

    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/app-dark.css">

    <style>
        body.auth-page {
            background:
                linear-gradient(135deg, rgba(67, 94, 190, .08), transparent 34%),
                linear-gradient(315deg, rgba(16, 185, 129, .10), transparent 32%),
                #eef3f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        body.dark.auth-page {
            background:
                linear-gradient(135deg, rgba(90, 115, 215, .18), transparent 34%),
                linear-gradient(315deg, rgba(16, 185, 129, .12), transparent 32%),
                #141421;
        }

        .auth-card {
            width: 100%;
            max-width: 430px;
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .16);
            border: 1px solid rgba(255, 255, 255, .70);
            overflow: hidden;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(14px);
        }

        body.dark .auth-card {
            background: rgba(30, 30, 45, .92);
            border-color: rgba(255, 255, 255, .08);
            box-shadow: 0 24px 70px rgba(0, 0, 0, .36);
        }

        .auth-card .card-header {
            background: linear-gradient(135deg, #334ca8 0%, #586fce 58%, #20a884 100%);
            padding: 34px 34px 28px;
            border: none;
            text-align: center;
            position: relative;
            isolation: isolate;
        }

        .auth-card .card-header::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, .12), transparent);
            pointer-events: none;
            z-index: -1;
        }

        .auth-card .card-header .brand-logo {
            width: 58px;
            height: 58px;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .28);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18), 0 12px 26px rgba(20, 30, 80, .22);
        }

        .auth-card .card-header h4 {
            color: #fff;
            font-weight: 800;
            margin: 0 0 6px;
            font-size: 1.45rem;
        }

        .auth-card .card-header p {
            color: rgba(255, 255, 255, .82);
            margin: 0;
            font-size: .875rem;
        }

        .auth-card .card-body {
            padding: 30px 34px 26px;
            background: transparent;
        }

        body.dark .auth-card .card-body {
            background: transparent;
        }

        .auth-card .form-label {
            font-size: .8125rem;
            font-weight: 600;
            color: #526070;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }

        body.dark .auth-card .form-label {
            color: #9a9ab0;
        }

        .auth-card .form-control {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            padding: 11px 14px;
            font-size: .9375rem;
            transition: border-color .2s, box-shadow .2s;
            background-color: #f8fafc;
        }

        body.dark .auth-card .form-control {
            background: #2a2a3d;
            border-color: #3a3a55;
            color: #e0e0f0;
        }

        .auth-card .form-control:focus {
            border-color: #435ebe;
            box-shadow: 0 0 0 3px rgba(67, 94, 190, .15);
            background-color: #fff;
        }

        body.dark .auth-card .form-control:focus {
            background: #303047;
        }

        .auth-card .input-group-text {
            border: 1.5px solid #e2e8f0;
            border-right: 0;
            border-radius: 10px 0 0 10px;
            background: #f8fafc;
            color: #7b8794;
            padding: 0 12px;
        }

        body.dark .auth-card .input-group-text {
            background: #2a2a3d;
            border-color: #3a3a55;
            color: #a5adba;
        }

        .auth-card .input-group .form-control {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .auth-card .input-group .form-control:not(:last-child) {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .auth-card .password-toggle {
            border: 1.5px solid #e2e8f0;
            border-left: 0;
            border-radius: 0 10px 10px 0;
            background: #f8fafc;
            color: #687385;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 46px;
        }

        body.dark .auth-card .password-toggle {
            background: #2a2a3d;
            border-color: #3a3a55;
            color: #a5adba;
        }

        .auth-card .btn-auth {
            background: linear-gradient(135deg, #334ca8 0%, #586fce 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            font-size: .9375rem;
            letter-spacing: .02em;
            box-shadow: 0 12px 24px rgba(67, 94, 190, .25);
            transition: opacity .2s, transform .1s, box-shadow .2s;
        }

        .auth-card .btn-auth:hover {
            opacity: .92;
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(67, 94, 190, .30);
        }

        .auth-card .btn-auth:active {
            transform: translateY(0);
        }

        .auth-card .card-footer {
            background: rgba(248, 250, 252, .80);
            border-top: 1px solid #eef2f7;
            padding: 14px 32px;
            text-align: center;
            font-size: .875rem;
        }

        body.dark .auth-card .card-footer {
            background: #1a1a2e;
            border-top-color: #2d2d44;
        }

        .auth-card .card-footer a {
            color: #435ebe;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-card .card-footer a:hover {
            text-decoration: underline;
        }

        .auth-error {
            font-size: .8125rem;
            color: #dc3545;
            margin-top: 4px;
        }

        .auth-card .form-check-input {
            border-color: #cbd5e1;
        }

        .auth-card .form-check-input:checked {
            background-color: #435ebe;
            border-color: #435ebe;
        }

        .auth-link {
            color: #435ebe;
            font-size: .8rem;
            font-weight: 600;
        }

        .auth-link:hover {
            color: #334ca8;
        }

        @media (max-width: 575.98px) {
            body.auth-page {
                padding: 16px;
            }

            .auth-card .card-header {
                padding: 28px 24px 22px;
            }

            .auth-card .card-body {
                padding: 26px 24px 22px;
            }
        }
    </style>
</head>

<body class="auth-page">
    <script src="/js/initTheme.js"></script>

    {{ $slot }}

    <script src="/js/components/dark.js"></script>
    <script src="/js/app.js"></script>
</body>

</html>
