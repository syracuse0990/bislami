<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Welcome to {{ $restaurantName }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 0;
            background-color: #eef4f4;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        table { border-collapse: collapse; }

        /* ── Outer wrapper ── */
        .outer {
            width: 100%;
            background: linear-gradient(160deg, #eef4f4 0%, #fdf6ee 100%);
            padding: 48px 16px 56px;
        }
        .inner { max-width: 560px; margin: 0 auto; }

        /* ── Brand header ── */
        .brand-row {
            text-align: center;
            padding-bottom: 28px;
        }
        .brand-pill {
            display: inline-block;
            background: #ffffff;
            border-radius: 20px;
            padding: 12px 28px;
            box-shadow: 0 12px 36px -16px rgba(11,77,89,0.30);
        }
        .brand-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.6px;
            background: linear-gradient(90deg, #0b4d59, #c45f18);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Card ── */
        .card {
            background: #ffffff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow:
                0 2px 4px rgba(11,77,89,0.04),
                0 8px 24px rgba(11,77,89,0.08),
                0 32px 64px -24px rgba(11,77,89,0.22);
        }

        /* ── Hero gradient header ── */
        .card-hero {
            background: linear-gradient(140deg, #0b4d59 0%, #0e6070 55%, #c45f18 100%);
            padding: 36px 40px 32px;
            position: relative;
            overflow: hidden;
        }
        .card-hero::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .card-hero::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -20px;
            width: 160px; height: 160px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        .hero-eyebrow {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: rgba(255,255,255,0.6);
            margin: 0 0 8px;
        }
        .hero-title {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 6px;
            letter-spacing: -0.5px;
            line-height: 1.25;
        }
        .hero-sub {
            font-size: 14px;
            color: rgba(255,255,255,0.72);
            margin: 0;
            line-height: 1.55;
        }
        .role-chip {
            display: inline-block;
            margin-top: 20px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 100px;
            padding: 5px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #ffffff;
            backdrop-filter: blur(4px);
        }

        /* ── Card body ── */
        .card-body { padding: 36px 40px 32px; }

        .section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: #7cb5bb;
            margin: 0 0 14px;
        }

        /* ── Credentials box ── */
        .cred-box {
            background: linear-gradient(135deg, #f4fbfb 0%, #fffaf5 100%);
            border: 1px solid #ddeced;
            border-radius: 18px;
            padding: 0;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .cred-box-header {
            background: linear-gradient(90deg, #0b4d59, #0d6070);
            padding: 10px 22px;
        }
        .cred-box-header span {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: rgba(255,255,255,0.75);
        }
        .cred-row {
            display: flex;
            align-items: center;
            padding: 14px 22px;
            border-bottom: 1px solid #e4f0f0;
        }
        .cred-row:last-of-type { border-bottom: none; }
        .cred-icon {
            width: 32px;
            height: 32px;
            background: #e8f5f5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 14px;
            flex-shrink: 0;
            font-size: 14px;
        }
        .cred-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #8fb8bc;
            display: block;
            margin-bottom: 2px;
        }
        .cred-value {
            font-size: 15px;
            font-weight: 600;
            color: #0f2f35;
            letter-spacing: 0.01em;
        }
        .cred-value.mono {
            font-family: 'Courier New', Consolas, monospace;
            font-size: 16px;
            font-weight: 700;
            color: #0b4d59;
            background: #eaf7f7;
            padding: 2px 8px;
            border-radius: 6px;
        }

        /* ── Tip box ── */
        .tip-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #fffbf3;
            border: 1px solid #f5ddb0;
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 28px;
        }
        .tip-icon {
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .tip-text {
            font-size: 13px;
            color: #7a5a1e;
            line-height: 1.55;
            margin: 0;
        }

        /* ── CTA button ── */
        .cta-wrap { text-align: center; padding: 4px 0 8px; }
        .cta-btn {
            display: inline-block;
            background: linear-gradient(135deg, #0b4d59 0%, #c45f18 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.06em;
            padding: 14px 36px;
            border-radius: 100px;
            box-shadow: 0 12px 32px -12px rgba(11,77,89,0.55);
        }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px solid #e8f0f0;
            margin: 28px 0;
        }

        /* ── Body text ── */
        .body-text {
            font-size: 14px;
            line-height: 1.65;
            color: #4a6770;
            margin: 0 0 20px;
        }
        .body-text strong { color: #1a3035; font-weight: 600; }

        /* ── Footer ── */
        .card-footer {
            background: #f5fafa;
            border-top: 1px solid #e4efef;
            padding: 20px 40px 24px;
            text-align: center;
        }
        .footer-text {
            font-size: 12px;
            color: #94b8bc;
            line-height: 1.6;
            margin: 0;
        }
        .footer-text a { color: #0b4d59; text-decoration: none; }

        /* ── Mobile ── */
        @media only screen and (max-width: 600px) {
            .card-hero, .card-body, .card-footer { padding-left: 24px !important; padding-right: 24px !important; }
            .hero-title { font-size: 22px !important; }
            .outer { padding-top: 28px !important; padding-bottom: 36px !important; }
        }
    </style>
</head>
<body>
<div class="outer">
    <div class="inner">

        <!-- Brand header -->
        <div class="brand-row">
            <div class="brand-pill">
                <span class="brand-name">BisLami</span>
            </div>
        </div>

        <!-- Card -->
        <div class="card">

            <!-- Hero -->
            <div class="card-hero">
                <p class="hero-eyebrow">Team access granted</p>
                <h1 class="hero-title">Welcome aboard,<br>{{ $recipientName }}!</h1>
                <p class="hero-sub">You've been added to <strong style="color:#ffffff;">{{ $restaurantName }}</strong></p>
                <span class="role-chip">{{ $roleLabel }}</span>
            </div>

            <!-- Body -->
            <div class="card-body">

                <p class="body-text">
                    Your account is ready. Use the credentials below to log in and start managing your tasks at
                    <strong>{{ $restaurantName }}</strong>.
                </p>

                <!-- Credentials -->
                <p class="section-label">Your login credentials</p>
                <div class="cred-box">
                    <div class="cred-box-header"><span>Login details</span></div>
                    <div class="cred-row">
                        <div class="cred-icon">✉</div>
                        <div>
                            <span class="cred-label">Email address</span>
                            <span class="cred-value">{{ $loginEmail }}</span>
                        </div>
                    </div>
                    @if ($temporaryPassword)
                    <div class="cred-row">
                        <div class="cred-icon">🔑</div>
                        <div>
                            <span class="cred-label">Temporary password</span>
                            <span class="cred-value mono">{{ $temporaryPassword }}</span>
                        </div>
                    </div>
                    @else
                    <div class="cred-row">
                        <div class="cred-icon">🔑</div>
                        <div>
                            <span class="cred-label">Password</span>
                            <span class="cred-value">Use your existing password</span>
                        </div>
                    </div>
                    @endif
                </div>

                @if ($temporaryPassword)
                <!-- Security tip -->
                <div class="tip-box">
                    <span class="tip-icon">⚠️</span>
                    <p class="tip-text">
                        <strong style="color:#5c3d08;">Security tip:</strong>
                        This is a temporary password. Please change it from your account settings after your first login.
                    </p>
                </div>
                @endif

                <hr class="divider">

                <p class="body-text" style="margin-bottom: 24px;">
                    For questions or support, reach out to your restaurant manager directly.
                </p>

            </div>

            <!-- Footer -->
            <div class="card-footer">
                <p class="footer-text">
                    This email was sent by <strong style="color:#4a8a90;">BisLami</strong> on behalf of {{ $restaurantName }}.<br>
                    If you weren't expecting this, you can safely ignore this message.
                </p>
            </div>

        </div>
        <!-- /Card -->

    </div>
</div>
</body>
</html>
