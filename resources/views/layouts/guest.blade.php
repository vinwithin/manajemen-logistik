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
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.dark.auth-page {
            background: #1a1a2e;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .10);
            border: none;
            overflow: hidden;
        }

        .auth-card .card-header {
            background: linear-gradient(135deg, #435ebe 0%, #5a73d7 100%);
            padding: 28px 32px 24px;
            border: none;
            text-align: center;
        }

        .auth-card .card-header .brand-logo {
            width: 52px;
            height: 52px;
            background: rgba(255, 255, 255, .2);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .auth-card .card-header h4 {
            color: #fff;
            font-weight: 700;
            margin: 0 0 4px;
            font-size: 1.3rem;
        }

        .auth-card .card-header p {
            color: rgba(255, 255, 255, .75);
            margin: 0;
            font-size: .875rem;
        }

        .auth-card .card-body {
            padding: 28px 32px 24px;
            background: #fff;
        }

        body.dark .auth-card .card-body {
            background: #1e1e2d;
        }

        .auth-card .form-label {
            font-size: .8125rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }

        body.dark .auth-card .form-label {
            color: #9a9ab0;
        }

        .auth-card .form-control {
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            padding: 10px 14px;
            font-size: .9375rem;
            transition: border-color .2s, box-shadow .2s;
        }

        body.dark .auth-card .form-control {
            background: #2a2a3d;
            border-color: #3a3a55;
            color: #e0e0f0;
        }

        .auth-card .form-control:focus {
            border-color: #435ebe;
            box-shadow: 0 0 0 3px rgba(67, 94, 190, .15);
        }

        .auth-card .btn-auth {
            background: linear-gradient(135deg, #435ebe 0%, #5a73d7 100%);
            border: none;
            border-radius: 8px;
            padding: 11px;
            font-weight: 600;
            font-size: .9375rem;
            letter-spacing: .02em;
            transition: opacity .2s, transform .1s;
        }

        .auth-card .btn-auth:hover {
            opacity: .92;
            transform: translateY(-1px);
        }

        .auth-card .btn-auth:active {
            transform: translateY(0);
        }

        .auth-card .card-footer {
            background: #f8fafc;
            border-top: 1px solid #f0f4f8;
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
    </style>
</head>

<body class="auth-page">
    <script src="/js/initTheme.js"></script>

    {{ $slot }}

    <script src="/js/components/dark.js"></script>
    <script src="/js/app.js"></script>
</body>

</html>
