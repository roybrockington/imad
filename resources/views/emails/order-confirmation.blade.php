<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $locale === 'de' ? 'Bestellbestätigung' : 'Order Confirmation' }}</title>
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
        .order-summary {
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .order-summary h2 {
            margin-top: 0;
            font-size: 18px;
            color: #1f2937;
        }
        .order-info {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .order-info:last-child {
            border-bottom: none;
        }
        .order-info .label {
            font-weight: 600;
            color: #4b5563;
            display: block;
            margin-bottom: 2px;
        }
        .order-info .value {
            color: #1f2937;
            display: block;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #f3f4f6;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            font-size: 14px;
        }
        .items-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            background-color: #f9fafb;
            font-weight: 600;
            font-size: 16px;
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
        .shipping-address {
            background-color: #f9fafb;
            border-left: 4px solid #0a6cb5;
            padding: 15px;
            margin: 20px 0;
        }
        .shipping-address h3 {
            margin-top: 0;
            font-size: 16px;
            color: #1f2937;
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
            .order-summary, .shipping-address {
                padding: 15px;
            }
            .items-table {
                font-size: 12px;
            }
            .items-table th,
            .items-table td {
                padding: 8px 4px;
            }
            /* Hide Quantity and Price columns on mobile, show only Item and Total */
            .items-table th:nth-child(2),
            .items-table th:nth-child(3),
            .items-table td:nth-child(2),
            .items-table td:nth-child(3) {
                display: none;
            }
            .total-row {
                font-size: 14px;
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
            .items-table {
                font-size: 11px;
            }
            .items-table th,
            .items-table td {
                padding: 6px 3px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header with Logo -->
        <div class="header">
            <img src="{{ config('app.url') }}/images/logo.png" alt="{{ config('app.name') }}" onerror="this.style.display='none'">
            <h1>{{ $locale === 'de' ? 'Vielen Dank für Ihre Bestellung!' : 'Thank You For Your Order!' }}</h1>
        </div>

        <!-- Main Content -->
        <div class="content">
            @if($locale === 'de')
                <p>Hallo {{ $user->name }},</p>
                <p>Vielen Dank für Ihre Bestellung bei {{ config('app.name') }}. Wir haben Ihre Bestellung erhalten und bearbeiten sie bereits.</p>
            @else
                <p>Hello {{ $user->name }},</p>
                <p>Thank you for your order with {{ config('app.name') }}. We have received your order and are processing it now.</p>
            @endif

            <!-- Order Summary -->
            <div class="order-summary">
                <h2>{{ $locale === 'de' ? 'Bestellübersicht' : 'Order Summary' }}</h2>
                <div class="order-info">
                    <span class="label">{{ $locale === 'de' ? 'Bestellnummer:' : 'Order Number:' }}</span>
                    <span class="value">#{{ $order->id }}</span>
                </div>
                <div class="order-info">
                    <span class="label">{{ $locale === 'de' ? 'Bestelldatum:' : 'Order Date:' }}</span>
                    <span class="value">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                </div>
                @if($order->po)
                <div class="order-info">
                    <span class="label">{{ $locale === 'de' ? 'Ihre Bestellnummer:' : 'Your PO Number:' }}</span>
                    <span class="value">{{ $order->po }}</span>
                </div>
                @endif
                @if($order->account)
                <div class="order-info">
                    <span class="label">{{ $locale === 'de' ? 'Firma:' : 'Company:' }}</span>
                    <span class="value">{{ $order->account->name }}</span>
                </div>
                @endif
            </div>

            <!-- Shipping Address -->
            @if($order->address)
            <div class="shipping-address">
                <h3>{{ $locale === 'de' ? 'Lieferadresse' : 'Shipping Address' }}</h3>
                <p style="margin: 5px 0;">
                    @if($order->address->name){{ $order->address->name }}<br>@endif
                    {{ $order->address->address_1 }}<br>
                    @if($order->address->address_2){{ $order->address->address_2 }}<br>@endif
                    {{ $order->address->city }}, {{ $order->address->state }} {{ $order->address->postcode }}<br>
                    {{ $order->address->country }}
                </p>
            </div>
            @endif

            <!-- Order Items -->
            <h2>{{ $locale === 'de' ? 'Bestellte Artikel' : 'Order Items' }}</h2>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>{{ $locale === 'de' ? 'Artikel' : 'Item' }}</th>
                        <th>{{ $locale === 'de' ? 'Menge' : 'Quantity' }}</th>
                        <th>{{ $locale === 'de' ? 'Preis' : 'Price' }}</th>
                        <th>{{ $locale === 'de' ? 'Gesamt' : 'Total' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->product_name }}</strong><br>
                            <small style="color: #6b7280;">{{ $item->product_code }}</small>
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price, 2) }} {{ $item->currency }}</td>
                        <td>{{ number_format($item->price * $item->quantity, 2) }} {{ $item->currency }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">{{ $locale === 'de' ? 'Gesamtsumme:' : 'Total:' }}</td>
                        <td>{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                    </tr>
                </tbody>
            </table>

            @if($order->notes)
            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
                <strong>{{ $locale === 'de' ? 'Ihre Anmerkungen:' : 'Your Notes:' }}</strong>
                <p style="margin: 5px 0 0 0;">{{ $order->notes }}</p>
            </div>
            @endif

            @if($locale === 'de')
                <p>Wir werden Sie informieren, sobald Ihre Bestellung versandt wurde.</p>
                <p>Wenn Sie Fragen zu Ihrer Bestellung haben, kontaktieren Sie uns bitte unter <a href="mailto:info@sound-service.eu">info@sound-service.eu</a></p>
            @else
                <p>We will notify you once your order has been shipped.</p>
                <p>If you have any questions about your order, please contact us at <a href="mailto:info@sound-service.eu">info@sound-service.eu</a></p>
            @endif

            @if($order->account?->term)
            <div class="order-info" style="margin-top: 20px;">
                <span class="label">{{ $locale === 'de' ? 'Zahlungsbedingungen:' : 'Payment Terms:' }}</span>
                <span class="value">{{ $locale === 'de' ? ($order->account->term->name_de ?? $order->account->term->name_en) : $order->account->term->name_en }}</span>
            </div>
            @endif

            <center>
                <a href="{{ config('app.frontend_url') }}/{{ $locale }}/orders/{{ $order->id }}" class="button">
                    {{ $locale === 'de' ? 'Bestellung ansehen' : 'View Order' }}
                </a>
            </center>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ config('app.name') }}</p>
            <p style="margin: 5px 0;">
                @if($locale === 'de')
                    Dies ist eine automatische E-Mail. Bitte antworten Sie nicht auf diese Nachricht.
                @else
                    This is an automated email. Please do not reply to this message.
                @endif
            </p>
        </div>
    </div>
</body>
</html>
