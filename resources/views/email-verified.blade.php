<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="refresh" content="5;url={{ $loginUrl }}?verified={{ $status === 'success' ? '1' : '0' }}">
    <title>CSS Prep · Email confirmation</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #f5f5f4;
            color: #0a0a0a;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            font-size: 15px;
            line-height: 1.5;
        }

        .card {
            width: 100%;
            max-width: 380px;
            padding: 28px 22px;
            border: 1px solid #dcdcdc;
            border-radius: 14px;
            background: #fff;
            text-align: center;
        }

        .icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #0a0a0a;
            color: #fff;
            font-size: 22px;
            font-weight: 700;
        }

        .icon.invalid {
            background: #fee2e2;
            color: #b42318;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 20px;
        }

        p {
            margin: 0 0 20px;
            color: #5f5f5f;
            font-size: 14px;
            line-height: 1.6;
        }

        .btn {
            display: block;
            padding: 13px;
            border-radius: 10px;
            background: #0a0a0a;
            color: #fff;
            font-weight: 700;
            text-decoration: none;
        }

        .link {
            display: block;
            margin-top: 14px;
            color: #5f5f5f;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <main class="card">
        @if ($status === 'success')
            <div class="icon">✓</div>
            <h1>Email confirmed</h1>
            <p>Your account is ready. Taking you to sign in…</p>
            <a class="btn" href="{{ $loginUrl }}?verified=1">Go to sign in</a>
        @else
            <div class="icon invalid">!</div>
            <h1>This link is no longer valid</h1>
            <p>It may have expired or already been used. Request a new link and try again.</p>
            <a class="btn" href="{{ $resendUrl }}">Send a new link</a>
            <a class="link" href="{{ $loginUrl }}?verified=0">Back to sign in</a>
        @endif
    </main>
</body>

</html>