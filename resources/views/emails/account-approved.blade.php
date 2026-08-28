<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Account Approved</title>
    <style>
        :root { color-scheme: light; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #ffffff;
            color: #0a6cb5;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 4px solid #0a6cb5;
        }
        .header img {
            max-width: 200px;
            height: auto;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }
        .content {
            padding: 30px 20px;
        }
        .account-summary {
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .account-summary h2 {
            margin-top: 0;
            font-size: 18px;
            color: #1f2937;
        }
        .account-info {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .account-info:last-child {
            border-bottom: none;
        }
        .account-info .label {
            font-weight: 600;
            color: #4b5563;
            display: block;
            margin-bottom: 2px;
        }
        .account-info .value {
            color: #1f2937;
            display: block;
        }
        .benefits-box {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
        }
        .benefits-box h3 {
            margin-top: 0;
            font-size: 16px;
            color: #1f2937;
        }
        .benefits-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .benefits-box li {
            margin: 8px 0;
            color: #374151;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .button {
            display: inline-block;
            background-color: #0a6cb5;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }

        /* Mobile Responsive Styles */
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .header h1 {
                font-size: 20px;
            }
            .content {
                padding: 20px 15px;
            }
            .account-summary, .benefits-box {
                padding: 15px;
            }
            .button {
                display: block;
                width: 100%;
                box-sizing: border-box;
                text-align: center;
            }
        }

        /* Extra small mobile devices */
        @media only screen and (max-width: 400px) {
            .header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header with Logo -->
        <div class="header">
            <img src="{{ config('app.url') }}/images/logo.png" alt="{{ config('app.name') }}" onerror="this.style.display='none'">
            <h1>Your Account Has Been Approved!</h1>
        </div>

        <!-- Main Content -->
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>Great news! Your account has been approved and is now live. You can start accessing trade pricing, viewing stock levels, and placing orders immediately.</p>

            <!-- Account Summary -->
            <div class="account-summary">
                <h2>Account Details</h2>
                <div class="account-info">
                    <span class="label">Account Name:</span>
                    <span class="value">{{ $account->name }}</span>
                </div>
                <div class="account-info">
                    <span class="label">Account Code:</span>
                    <span class="value">{{ $account->code }}</span>
                </div>
                <div class="account-info">
                    <span class="label">Region:</span>
                    <span class="value">{{ $account->region->name ?? 'N/A' }}</span>
                </div>
                <div class="account-info">
                    <span class="label">Currency:</span>
                    <span class="value">{{ $account->region->currency ?? 'EUR' }}</span>
                </div>
            </div>

            <!-- Benefits -->
            <div class="benefits-box">
                <h3>What You Can Do Now:</h3>
                <ul>
                    <li>Access trade pricing tailored to your account</li>
                    <li>View real-time stock levels</li>
                    <li>Place orders immediately</li>
                    <li>Track your order history</li>
                    <li>Manage multiple delivery addresses</li>
                </ul>
            </div>

            <p>Ready to get started? Log in to your account and explore our full product catalog with your exclusive trade pricing.</p>

            <center>
                <a href="{{ $loginUrl }}" class="button">
                    Log In to Your Account
                </a>
            </center>

            <p>If you have any questions or need assistance, please don't hesitate to contact us at <a href="mailto:info@sound-service.eu">info@sound-service.eu</a></p>

            <p>Thank you for choosing {{ config('app.name') }}!</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ config('app.name') }}</p>
            <p style="margin: 5px 0;">
                This is an automated email. Please do not reply to this message.
            </p>
        </div>
    </div>
</body>
</html>
