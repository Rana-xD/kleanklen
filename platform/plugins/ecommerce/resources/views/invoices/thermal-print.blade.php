<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Thermal Invoice - {{ $order->code }}</title>
    
    <!-- Load Khmer fonts from Google Fonts for reliable rendering -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Khmer:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        /* Screen styles - hide everything and show loading */
        @media screen {
            body {
                font-family: 'Noto Sans Khmer', 'DejaVu Sans', sans-serif;
                background: #f0f0f0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            
            .loading-message {
                text-align: center;
                padding: 40px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .loading-message h2 {
                color: #333;
                margin-bottom: 10px;
            }
            
            .loading-message p {
                color: #666;
                margin-bottom: 20px;
            }
            
            .spinner {
                border: 3px solid #f3f3f3;
                border-top: 3px solid #007cba;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                animation: spin 1s linear infinite;
                margin: 0 auto;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .invoice-container {
                display: none;
            }
        }
        
        /* Print styles for thermal printer (3x6 inch) */
        @media print {
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                width: 2.9in;
                max-width: 2.9in;
                font-family: 'Noto Sans Khmer', 'DejaVu Sans', sans-serif;
                font-size: 11px;
                line-height: 1.2;
                color: #000;
                background: #fff;
                padding: 4px;
                margin: 0;
            }
            
            .loading-message {
                display: none !important;
            }
            
            .invoice-container {
                display: block !important;
                width: 100%;
                background: white;
                border: none;
            }
            
            @page {
                size: 2.9in auto;
                margin: 0;
            }
        }
        
        /* Common styles for both screen and print */
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
        
        .invoice-number {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .separator {
            width: 100%;
            height: 2px;
            background: #000;
            margin: 4px 0 2px 0;
            border: none;
        }
        
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
            margin-bottom: 8px;
        }
        
        .customer-info div {
            margin-bottom: 3px;
        }
        
        .items-section {
            margin-bottom: 10px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 8px;
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
        
        .col-barcode {
            width: 20%;
            text-align: center;
            font-size: 8px;
        }
        
        .col-price {
            width: 15%;
            text-align: center;
        }
        
        .col-qty {
            width: 10%;
            text-align: center;
        }
        
        .col-total {
            width: 15%;
            text-align: center;
        }
        
        .total-section {
            text-align: right;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .total-label {
            float: left;
        }
        
        .total-value {
            float: right;
        }
        
        .notes-section {
            margin-bottom: 10px;
        }
        
        .notes-title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 6px;
        }
        
        .notes-content {
            text-align: left;
            font-size: 11px;
            padding: 4px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
        }
        
        .footer .khmer-text {
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 10px;
        }
        
        .thank-you {
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
        
        .khmer-text {
            font-family: 'Noto Sans Khmer', 'DejaVu Sans', sans-serif;
            line-height: 1.4;
            font-weight: 400;
        }
        
        /* Print optimizations */
        @media print {
            .separator {
                background: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .notes-section {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Loading message (only visible on screen, hidden in print) -->
    <div class="loading-message">
        <h2>🖨️ Preparing Invoice</h2>
        <p>Opening print dialog...</p>
        <p style="font-size: 12px; color: #666; margin-top: 10px;">If print dialog doesn't appear, you will be redirected automatically.</p>
        <div class="spinner"></div>
    </div>
    
    <div class="invoice-container">
        <!-- Header Section -->
        <div class="header">
            <div class="logo">
                @if($logo_base64)
                    <img src="{{ $logo_base64 }}" alt="KleanKlen Logo">
                @else
                    <h2>KleanKlen</h2>
                @endif
            </div>
            
            <div class="date-time">
                <strong>Date:</strong> {{ $date }} | <strong>Time:</strong> {{ $order_time }}
            </div>
            
            <div class="invoice-number">
                Invoice #: {{ $invoice_number }}
            </div>
        </div>
        
        <div class="separator"></div>
        
        <!-- Customer Information -->
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
                        <td class="col-price">${{ number_format($product['price'], 2) }}</td>
                        <td class="col-qty">{{ $product['qty'] }}</td>
                        <td class="col-total">${{ number_format($product['total'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="total-section">
            <div class="total-label">TOTAL:</div>
            <div class="total-value">${{ number_format($total_amount, 2) }}</div>
            <div style="clear: both;"></div>
        </div>
        
        <!-- Order Notes (if any) -->
        @if(!empty($notes))
        <div class="notes-section">
            <strong>Notes:</strong><br>
            {{ $notes }}
        </div>
        @endif
        
        <div class="separator"></div>
        
        <!-- Footer Section -->
        <div class="footer">
            <div class="thank-you">
                Thank you for your purchase!
            </div>
            
            <div class="policy khmer-text">
                <div class="policy-item">សូមពិនិត្យមើលទំនិញទាំងអស់ក្នុងរយៈពេល ២៤ ម៉ោង ដើម្បីដាក់ស្នើការប្តូរ ឬសំណង</div>
                <div class="policy-item">ទំនិញដែលបានបើកប្រើ មិនអាចប្តូរបានទេ</div>
            </div>
        </div>
    </div>
    
    <script>
        let printDialogOpened = false;
        let printCompleted = false;
        
        // Auto-print when page loads - skip preview and go directly to print
        window.addEventListener('load', function() {
            // Very short delay to ensure page is fully loaded
            setTimeout(() => {
                window.print();
            }, 100);
            
            // Fallback: if no print events fire within 3 seconds, assume cancellation
            setTimeout(() => {
                if (!printDialogOpened && !printCompleted) {
                    console.log('Print dialog timeout - redirecting to orders');
                    window.location.href = '/admin/ecommerce/orders';
                }
            }, 3000);
        });
        
        // Track when print dialog opens
        window.addEventListener('beforeprint', function() {
            printDialogOpened = true;
            console.log('Print dialog opening...');
        });
        
        // Handle print completion or cancellation
        window.addEventListener('afterprint', function() {
            printCompleted = true;
            console.log('Print dialog closed - afterprint event');
            
            // Redirect back to orders page instead of trying to close window
            setTimeout(() => {
                console.log('Redirecting to orders page...');
                window.location.href = '/admin/ecommerce/orders';
            }, 500);
        });
        
        // Alternative method: detect when window regains focus after print dialog
        let windowBlurred = false;
        
        window.addEventListener('blur', function() {
            if (printDialogOpened) {
                windowBlurred = true;
                console.log('Window blurred - print dialog likely open');
            }
        });
        
        window.addEventListener('focus', function() {
            if (windowBlurred && printDialogOpened && !printCompleted) {
                console.log('Window focused after print dialog - likely canceled');
                // User returned without printing, redirect to orders
                setTimeout(() => {
                    window.location.href = '/admin/ecommerce/orders';
                }, 500);
            }
        });
        
        // Handle page visibility changes (another way to detect cancellation)
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible' && printDialogOpened && !printCompleted) {
                console.log('Page became visible after print dialog - likely canceled');
                setTimeout(() => {
                    window.location.href = '/admin/ecommerce/orders';
                }, 1000);
            }
        });
    </script>
</body>
</html>
