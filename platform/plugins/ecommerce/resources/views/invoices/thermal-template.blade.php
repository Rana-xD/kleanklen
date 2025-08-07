<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermal Invoice - {{ $order->code }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Khmer:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'KhmerFont';
            src: url('https://fonts.googleapis.com/css2?family=Koh+Santepheap:wght@300;400;700&display=swap');
            font-weight: normal;
            font-style: normal;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            width: 2.9in;
            max-width: 2.9in;
            min-height: 6in;
            font-family: 'DejaVu Sans', 'Arial Unicode MS', sans-serif;
            font-size: 10px;
            line-height: 1.1;
            color: #000;
            background: #fff;
            padding: 4px;
            margin: 0;
        }

        .khmer-text {
            font-family: 'DejaVu Sans', 'Arial Unicode MS', sans-serif;
            line-height: 1.4;
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 8px;
        }

        .logo {
            text-align: center;
            margin-bottom: 8px;
        }
        
        .logo img {
            max-width: 120px;
            max-height: 80px;
            display: block;
            margin: 0 auto;
        }

        .date-time {
            font-size: 11px;
            margin-bottom: 8px;
        }

        .separator {
            width: 100%;
            height: 3px;
            background: #000;
            margin: 4px 0 2px 0;
            border: none;
        }

        /* Customer Information Section */
        .customer-section {
            margin-bottom: 8px;
        }

        .section-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 6px;
            font-size: 12px;
        }

        .customer-info {
            text-align: left;
            font-size: 11px;
        }

        .customer-info div {
            margin-bottom: 3px;
        }

        /* Items Table - Proper table layout matching sample */
        .items-section {
            margin-bottom: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .items-table th {
            font-weight: bold;
            text-align: left;
            border-bottom: 1px solid #333;
            padding: 2px 1px;
            font-size: 9px;
        }

        .items-table td {
            padding: 2px 1px;
            vertical-align: top;
            border-bottom: 1px dotted #ddd;
            font-size: 9px;
        }

        .col-item {
            width: 45%;
            text-align: left;
        }

        .col-price {
            width: 15%;
            text-align: center;
        }

        .col-qty {
            width: 10%;
            text-align: center;
        }

        .col-barcode {
            width: 20%;
            text-align: center;
            font-size: 8px;
        }

        .col-total {
            width: 15%;
            text-align: center;
        }

        /* Total Section */
        .total-section {
            width: 100%;
            font-weight: bold;
            font-size: 12px;
            margin: 8px 0;
        }
        
        .total-label {
            float: left;
        }
        
        .total-value {
            float: right;
        }

        /* Footer Section */
        .footer {
            margin-top: 10px;
        }

        .thank-you {
            text-align: center;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 10px;
        }

        .policy {
            text-align: left;
            font-size: 9px;
            line-height: 1.3;
        }

        .policy-item {
            margin-bottom: 8px;
            padding-left: 8px;
            position: relative;
        }

        .policy-item:before {
            content: "•";
            position: absolute;
            left: 0;
            top: 0;
        }

        /* Print optimizations */
        @media print {
            body {
                width: 78mm;
                max-width: 78mm;
                margin: 0;
                padding: 8px;
            }
            
            .separator {
                background: repeating-linear-gradient(
                    to right,
                    #000 0px,
                    #000 3px,
                    transparent 3px,
                    transparent 6px
                ) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <div class="logo">
            @if($logo_base64)
                <img src="{{ $logo_base64 }}" alt="KleanKlen Logo">
            @else
                <div style="font-weight: bold; font-size: 14px; color: #333;">KleanKlen</div>
            @endif
        </div>
        <div class="date-time">Date: {{ $date }} | Time: {{ $order_time }}</div>
        <div class="date-time">Invoice #: {{ $invoice_number }}</div>
    </div>
    
    <div class="separator"></div>

    <!-- Customer Information Section -->
    <div class="customer-section">
        <div class="section-title">CUSTOMER INFORMATION</div>
        <div class="customer-info">
            <div><strong>Name:</strong> {{ $customer_name }}</div>
            <div><strong>Phone:</strong> {{ $customer_phone }}</div>
            <div><strong>Location:</strong> {{ $customer_location }}</div>
        </div>
    </div>
    
   
    
    <div class="separator"></div>

    <!-- Items Table -->
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-item">Item</th>
                    <th class="col-barcode">Barcode</th>
                    <th class="col-price">Price</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td class="col-item">{{ $product['name'] }}</td>
                    <td class="col-barcode">{{ $product['barcode'] }}</td>
                    <td class="col-price">${{ number_format($product['price'], 0) }}</td>
                    <td class="col-qty">{{ $product['qty'] }}</td>
                    <td class="col-total">${{ number_format($product['total'], 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="separator"></div>

    <!-- Total Section -->
    <div class="total-section">
        <span class="total-label">TOTAL:</span>
        <span class="total-value">${{ number_format($total_amount, 2) }}</span>
        <div style="clear: both;"></div>
    </div>
    
    <div class="separator"></div>

    @if(!empty($notes))
    
    <!-- Notes Section -->
    <div class="customer-section">
        <div class="section-title">NOTES</div>
        <div class="customer-info">
            <div>{{ $notes }}</div>
        </div>
    </div>
    @endif
    <div class="separator"></div>
    <!-- Footer Section -->
    <div class="footer">
        <div class="thank-you">
            Thank you for your purchase!
        </div>
        
        <div class="policy">
            <div class="policy-item">Please check all items within 24 hours for any exchange or refund requests</div>
            <div class="policy-item">Opened or used products cannot be exchanged</div>
        </div>
    </div>
</body>
</html>
