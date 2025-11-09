<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'KLEMA - Climate Smart Agriculture')</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- App Styles -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        :root {
            color-scheme: dark;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
            background: radial-gradient(circle at 15% 20%, rgba(59, 130, 246, 0.45), transparent 55%),
                        radial-gradient(circle at 85% 30%, rgba(14, 165, 233, 0.40), transparent 50%),
                        linear-gradient(135deg, #0f172a 0%, #1e293b 45%, #111827 100%);
            color: #e2e8f0;
            overflow: hidden;
        }

        body.auth-scroll {
            overflow-y: auto;
        }

        .auth-alert {
            margin-bottom: 16px;
            padding: 14px 18px;
            border-radius: 14px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .auth-alert--info {
            background: rgba(59, 130, 246, 0.18);
            color: #cbd5f5;
            border-color: rgba(59, 130, 246, 0.35);
        }

        .auth-alert--success {
            background: rgba(34, 197, 94, 0.18);
            color: #bbf7d0;
            border-color: rgba(34, 197, 94, 0.35);
        }

        .auth-alert--error {
            background: rgba(248, 113, 113, 0.2);
            color: #fecaca;
            border-color: rgba(248, 113, 113, 0.35);
        }

        .auth-wrapper {
            position: relative;
            min-height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 18px;
            overflow: hidden;
        }

        body.auth-scroll .auth-wrapper {
            align-items: flex-start;
            padding-top: 80px;
            padding-bottom: 80px;
        }

        body.auth-scroll .auth-card {
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .auth-wrapper::before,
        .auth-wrapper::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            filter: blur(120px);
            opacity: 0.55;
            z-index: 0;
        }

        .auth-wrapper::before {
            width: 480px;
            height: 480px;
            top: -160px;
            left: -140px;
            background: rgba(59, 130, 246, 0.45);
        }

        .auth-wrapper::after {
            width: 420px;
            height: 420px;
            bottom: -180px;
            right: -120px;
            background: rgba(14, 165, 233, 0.35);
        }

        .auth-content {
            position: relative;
            z-index: 1;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-card {
            width: min(440px, 100%);
            background: linear-gradient(160deg, rgba(15, 23, 42, 0.85), rgba(30, 41, 59, 0.78));
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 28px;
            padding: 48px 42px;
            box-shadow: 0 40px 70px rgba(8, 15, 35, 0.45);
            backdrop-filter: blur(22px);
        }

        .auth-card__header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            margin-bottom: 36px;
        }

        .auth-card__badge {
            width: 80px;
            height: 80px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(140deg, rgba(59, 130, 246, 0.25), rgba(37, 99, 235, 0.5));
            border: 1px solid rgba(59, 130, 246, 0.35);
            box-shadow: 0 18px 45px rgba(37, 99, 235, 0.28);
        }

        .auth-card__title {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: #f8fafc;
            text-align: center;
        }

        .auth-card__subtitle {
            margin: 0;
            font-size: 14px;
            color: rgba(226, 232, 240, 0.7);
            text-align: center;
            letter-spacing: 0.02em;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .auth-form__group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .auth-form__label {
            font-size: 14px;
            font-weight: 600;
            color: rgba(226, 232, 240, 0.85);
            letter-spacing: 0.02em;
        }

        .auth-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            border-radius: 16px;
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(148, 163, 184, 0.18);
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-input-wrapper:focus-within {
            border-color: rgba(59, 130, 246, 0.55);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .auth-input-wrapper.auth-input--error {
            border-color: rgba(248, 113, 113, 0.65);
            box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.18);
        }

        .auth-input-wrapper i {
            color: rgba(148, 163, 184, 0.6);
            padding-left: 18px;
            font-size: 15px;
        }

        .auth-input {
            flex: 1 1 auto;
            background: transparent;
            border: none;
            outline: none;
            padding: 16px 20px;
            font-size: 15px;
            color: #f8fafc;
        }

        .auth-input option {
            color: #0f172a;
        }

        .auth-input-wrapper select.auth-input {
            appearance: none;
            cursor: pointer;
            padding-right: 52px;
        }

        .auth-select-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: rgba(148, 163, 184, 0.6);
            font-size: 14px;
        }

        .auth-input::placeholder {
            color: rgba(148, 163, 184, 0.6);
        }

        .auth-input.has-error {
            border-radius: 16px;
            box-shadow: inset 0 0 0 1px rgba(248, 113, 113, 0.5);
        }

        .auth-form__meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
            color: rgba(226, 232, 240, 0.75);
        }

        .auth-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .auth-checkbox input {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: rgba(15, 23, 42, 0.7);
            accent-color: #3b82f6;
        }

        .auth-link {
            color: #60a5fa;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .auth-link:hover {
            color: #93c5fd;
        }

        .auth-button {
            width: 100%;
            padding: 15px 20px;
            border-radius: 16px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            color: #f8fafc;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 25px 45px rgba(37, 99, 235, 0.4);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 30px 60px rgba(37, 99, 235, 0.45);
        }

        .auth-button:focus {
            outline: 2px solid rgba(59, 130, 246, 0.4);
            outline-offset: 3px;
        }

        .auth-feedback {
            font-size: 12px;
            color: #fca5a5;
            margin-top: -8px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 14px;
            color: rgba(226, 232, 240, 0.75);
        }

        .password-toggle {
            background: none;
            border: none;
            color: rgba(148, 163, 184, 0.7);
            cursor: pointer;
            padding: 0 18px;
            font-size: 16px;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: rgba(226, 232, 240, 0.95);
        }

        @media (max-width: 540px) {
            .auth-card {
                padding: 38px 26px;
                border-radius: 22px;
            }

            .auth-card__title {
                font-size: 24px;
            }

            .auth-alert {
            margin-bottom: 16px;
            padding: 14px 18px;
            border-radius: 14px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .auth-alert--info {
            background: rgba(59, 130, 246, 0.18);
            color: #cbd5f5;
            border-color: rgba(59, 130, 246, 0.35);
        }

        .auth-alert--success {
            background: rgba(34, 197, 94, 0.18);
            color: #bbf7d0;
            border-color: rgba(34, 197, 94, 0.35);
        }

        .auth-alert--error {
            background: rgba(248, 113, 113, 0.2);
            color: #fecaca;
            border-color: rgba(248, 113, 113, 0.35);
        }

        .auth-wrapper {
                padding: 48px 14px;
            }
        }

        @media (max-height: 760px) {
            .auth-alert {
            margin-bottom: 16px;
            padding: 14px 18px;
            border-radius: 14px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .auth-alert--info {
            background: rgba(59, 130, 246, 0.18);
            color: #cbd5f5;
            border-color: rgba(59, 130, 246, 0.35);
        }

        .auth-alert--success {
            background: rgba(34, 197, 94, 0.18);
            color: #bbf7d0;
            border-color: rgba(34, 197, 94, 0.35);
        }

        .auth-alert--error {
            background: rgba(248, 113, 113, 0.2);
            color: #fecaca;
            border-color: rgba(248, 113, 113, 0.35);
        }

        .auth-wrapper {
                padding: 42px 18px;
            }

            .auth-card {
                padding: 36px 30px;
                border-radius: 24px;
            }

            .auth-card__header {
                gap: 14px;
                margin-bottom: 30px;
            }

            .auth-card__badge {
                width: 72px;
                height: 72px;
            }

            .auth-card__title {
                font-size: 26px;
            }
        }

        @media (max-height: 640px) {
            .auth-alert {
            margin-bottom: 16px;
            padding: 14px 18px;
            border-radius: 14px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .auth-alert--info {
            background: rgba(59, 130, 246, 0.18);
            color: #cbd5f5;
            border-color: rgba(59, 130, 246, 0.35);
        }

        .auth-alert--success {
            background: rgba(34, 197, 94, 0.18);
            color: #bbf7d0;
            border-color: rgba(34, 197, 94, 0.35);
        }

        .auth-alert--error {
            background: rgba(248, 113, 113, 0.2);
            color: #fecaca;
            border-color: rgba(248, 113, 113, 0.35);
        }

        .auth-wrapper {
                align-items: flex-start;
                padding: 28px 16px 36px;
            }

            .auth-card {
                padding: 28px 24px;
                border-radius: 22px;
            }

            .auth-card__badge {
                width: 60px;
                height: 60px;
                margin-bottom: 4px;
            }

            .auth-card__title {
                font-size: 22px;
                margin-bottom: 2px;
            }

            .auth-card__subtitle {
                font-size: 13px;
                line-height: 1.4;
            }

            .auth-form {
                gap: 18px;
            }

            .auth-form__group {
                gap: 10px;
            }

            .auth-button {
                padding: 12px 16px;
            }

            .auth-footer {
                margin-top: 20px;
                font-size: 13px;
            }

            .auth-input {
                padding: 14px 18px;
                font-size: 14px;
            }

            .auth-input-wrapper i {
                padding-left: 16px;
                font-size: 14px;
            }
        }

        @media (max-height: 580px) {
            .auth-alert {
            margin-bottom: 16px;
            padding: 14px 18px;
            border-radius: 14px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .auth-alert--info {
            background: rgba(59, 130, 246, 0.18);
            color: #cbd5f5;
            border-color: rgba(59, 130, 246, 0.35);
        }

        .auth-alert--success {
            background: rgba(34, 197, 94, 0.18);
            color: #bbf7d0;
            border-color: rgba(34, 197, 94, 0.35);
        }

        .auth-alert--error {
            background: rgba(248, 113, 113, 0.2);
            color: #fecaca;
            border-color: rgba(248, 113, 113, 0.35);
        }

        .auth-wrapper {
                padding: 22px 14px 30px;
            }

            .auth-card {
                padding: 24px 22px;
            }

            .auth-card__header {
                margin-bottom: 24px;
            }

            .auth-card__badge {
                width: 54px;
                height: 54px;
            }

            .auth-card__title {
                font-size: 20px;
            }

            .auth-card__subtitle {
                font-size: 12px;
            }

            .auth-form {
                gap: 16px;
            }

            .auth-form__group {
                gap: 8px;
            }

            .auth-form__label {
                font-size: 13px;
            }

            .auth-input {
                padding: 12px 16px;
            }

            .auth-button {
                padding: 11px 14px;
                font-size: 14px;
            }

            .auth-footer {
                margin-top: 18px;
                font-size: 12px;
            }
        }

        @media (max-height: 520px) {
            .auth-alert {
            margin-bottom: 16px;
            padding: 14px 18px;
            border-radius: 14px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .auth-alert--info {
            background: rgba(59, 130, 246, 0.18);
            color: #cbd5f5;
            border-color: rgba(59, 130, 246, 0.35);
        }

        .auth-alert--success {
            background: rgba(34, 197, 94, 0.18);
            color: #bbf7d0;
            border-color: rgba(34, 197, 94, 0.35);
        }

        .auth-alert--error {
            background: rgba(248, 113, 113, 0.2);
            color: #fecaca;
            border-color: rgba(248, 113, 113, 0.35);
        }

        .auth-wrapper {
                padding: 18px 12px 26px;
            }

            .auth-card {
                padding: 20px 18px;
            }

            .auth-card__header {
                gap: 10px;
                margin-bottom: 20px;
            }

            .auth-form {
                gap: 14px;
            }

            .auth-form__group {
                gap: 6px;
            }

            .auth-input {
                padding: 11px 14px;
                font-size: 13px;
            }

            .password-toggle {
                padding: 0 14px;
                font-size: 14px;
            }

            .auth-input-wrapper select.auth-input {
                padding-right: 44px;
            }

            .auth-button {
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-content">
            @yield('content')
        </div>
    </div>
</body>
</html>