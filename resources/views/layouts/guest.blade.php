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

        .auth-page .theme-toggle-wrap {
            position: fixed;
            top: 16px;
            right: 20px;
        }
    </style>
</head>

<body class="auth-page">
    <script src="/js/initTheme.js"></script>

    {{-- Dark mode toggle --}}
    <div class="theme-toggle-wrap d-flex align-items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="18" height="18" viewBox="0 0 21 21"
            fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="opacity:.5">
            <circle cx="10.5" cy="10.5" r="4" />
            <path d="M10.5 2v2m0 13v2M2 10.5h2m13 0h2M4.1 4.1l1.4 1.4m9.9 9.9 1.4 1.4M4.1 16.9l1.4-1.4m9.9-9.9 1.4-1.4"
                opacity=".4" />
        </svg>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="toggle-dark" style="cursor:pointer">
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24"
            style="opacity:.5">
            <path fill="currentColor"
                d="m17.75 4.09-2.53 1.94.91 3.06-2.63-1.81-2.63 1.81.91-3.06-2.53-1.94L12.44 4l1.06-3 1.06 3 3.19.09m3.5 6.91-1.64 1.25.59 1.98-1.7-1.17-1.7 1.17.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95 2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14.4-.4.82-.76 1.27-1.08.75-.53 1.93.36 1.85 1.19-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82-2.81 3.14-2.7 7.96.31 10.98 3.02 3.01 7.84 3.12 10.98.31Z" />
        </svg>
    </div>

    {{ $slot }}

    <script src="/js/components/dark.js"></script>
    <script src="/js/app.js"></script>
</body>

</html>
