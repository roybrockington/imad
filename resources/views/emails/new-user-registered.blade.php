<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>New User Registration</title>
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
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-box h2 {
            margin-top: 0;
            font-size: 18px;
            color: #1f2937;
        }
        .info-box p {
            margin: 10px 0;
            color: #4b5563;
        }
        .user-details {
            margin: 15px 0;
        }
        .user-details .detail-row {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .user-details .detail-row:last-child {
            border-bottom: none;
        }
        .user-details .label {
            font-weight: 600;
            color: #374151;
            display: block;
            margin-bottom: 2px;
        }
        .user-details .value {
            color: #1f2937;
            display: block;
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
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .highlight-box {
            background-color: #dbeafe;
            border-left: 4px solid #0a6cb5;
            padding: 15px;
            margin: 20px 0;
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
            .info-box, .highlight-box {
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
            <h1>New User Registration</h1>
        </div>

        <!-- Main Content -->
        <div class="content">
            <p>A new user has registered for B2B access on {{ config('app.name') }}.</p>

            <!-- User Info Box -->
            <div class="info-box">
                <h2>User Details</h2>
                <div class="user-details">
                    <div class="detail-row">
                        <span class="label">Name:</span>
                        <span class="value">{{ $user->name }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Email:</span>
                        <span class="value">{{ $user->email }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Company:</span>
                        <span class="value">{{ $user->company ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Phone:</span>
                        <span class="value">{{ $user->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Registered:</span>
                        <span class="value">{{ $user->created_at->format('d/m/Y H:i:s') }}</span>
                    </div>
                </div>
            </div>

            <div class="highlight-box">
                <strong>Action Required:</strong>
                <p style="margin: 5px 0 0 0;">This user is pending authorisation and cannot see trade pricing until their account is approved and linked to a customer account.</p>
            </div>

            <center>
                <a href="https://sound-service.eu/admin/users" class="button">
                    View User in Admin Panel
                </a>
            </center>

            <p style="margin-top: 30px;">To approve this user:</p>
            <ol style="color: #4b5563; margin: 10px 0;">
                <li>Click the button above to view the user in the admin panel</li>
                <li>Assign them to the appropriate customer account</li>
                <li>Verify their role is set correctly</li>
            </ol>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ config('app.name') }}</p>
            <p style="margin: 5px 0;">This is an automated notification from the user registration system.</p>
        </div>
    </div>
</body>
</html>
