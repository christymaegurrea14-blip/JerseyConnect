<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Packing List — {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0B1220;
            background: #F7F9FE;
            margin: 0;
            padding: 32px 24px 64px;
        }
        .sheet {
            max-width: 760px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 1px solid #E4E2DC;
            border-radius: 16px;
            padding: 32px;
        }
        .print-bar {
            max-width: 760px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
        }
        .print-btn {
            background: #2547E0;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
        }
        .print-btn:hover { background: #1B36AF; }
        header.doc {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0B1220;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .brand {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.02em;
        }
        .brand span { color: #2547E0; }
        .doc-title {
            text-align: right;
        }
        .doc-title h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .doc-title p {
            margin: 2px 0 0;
            font-size: 11px;
            color: #6B7280;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        .box {
            border: 1px solid #E4E2DC;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .box h2 {
            margin: 0 0 8px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6B7280;
        }
        .box p { margin: 2px 0; font-size: 13px; }
        .box .big { font-size: 15px; font-weight: 800; }
        .swatches { display: flex; gap: 6px; margin-top: 6px; }
        .swatch {
            width: 18px; height: 18px; border-radius: 999px;
            border: 1px solid rgba(0,0,0,0.15);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        thead th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6B7280;
            border-bottom: 2px solid #0B1220;
            padding: 8px 6px;
        }
        tbody td {
            padding: 9px 6px;
            font-size: 13px;
            border-bottom: 1px solid #E4E2DC;
        }
        .check-col { width: 34px; text-align: center; }
        .checkbox {
            width: 16px; height: 16px;
            border: 1.5px solid #0B1220;
            border-radius: 4px;
            display: inline-block;
        }
        .num-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px; height: 24px;
            border-radius: 999px;
            background: #EEF1FF;
            color: #2547E0;
            font-weight: 800;
            font-size: 11px;
        }
        .empty {
            padding: 24px 0;
            text-align: center;
            color: #6B7280;
            font-size: 13px;
        }
        .summary-line {
            margin-top: 8px;
            font-size: 12px;
            color: #6B7280;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-top: 36px;
        }
        .sig-line {
            border-top: 1.5px solid #0B1220;
            padding-top: 6px;
            font-size: 11px;
            color: #6B7280;
        }
        footer.doc {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #E4E2DC;
            font-size: 10px;
            color: #9CA3AF;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .print-bar { display: none; }
            .sheet { border: none; border-radius: 0; max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button class="print-btn" onclick="window.print()">Print</button>
    </div>

    <div class="sheet">
        <header class="doc">
            <div class="brand">PRINT<span>CODE</span> STUDIO</div>
            <div class="doc-title">
                <h1>Packing List</h1>
                <p>Printed {{ now()->timezone('Asia/Manila')->format('M j, Y g:i A') }}</p>
            </div>
        </header>

        <div class="grid">
            <div class="box">
                <h2>Order</h2>
                <p class="big">{{ $order->order_number }}</p>
                <p>{{ $order->template_name }}</p>
                <p>{{ $order->quantity }} set{{ $order->quantity === 1 ? '' : 's' }} &middot; {{ $order->font_style ?: 'No font style specified' }}</p>
                <div class="swatches">
                    <span class="swatch" style="background: {{ $order->primary_color }}"></span>
                    <span class="swatch" style="background: {{ $order->secondary_color }}"></span>
                    <span class="swatch" style="background: {{ $order->accent_color }}"></span>
                </div>
            </div>
            <div class="box">
                <h2>Deliver To</h2>
                @if($order->address && $order->address->isComplete())
                    <p class="big">{{ $order->address->recipient_name }}</p>
                    <p>{{ $order->address->contact_number }}</p>
                    <p>
                        {{ $order->address->line1 }}{{ $order->address->barangay ? ', ' . $order->address->barangay : '' }},
                        {{ $order->address->city }}, {{ $order->address->province }} {{ $order->address->postal_code }}
                    </p>
                @else
                    <p>Team: {{ $order->team_name }}</p>
                    <p style="color:#9CA3AF;">No delivery address on file yet.</p>
                @endif
            </div>
        </div>

        <div class="box" style="margin-bottom: 8px;">
            <h2>Team Roster — {{ $order->team_name }}</h2>
            @if($players->isEmpty())
                <p class="empty">No roster was submitted for this order.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th class="check-col">✓</th>
                            <th>#</th>
                            <th>Player Name</th>
                            <th>Position</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($players as $player)
                            <tr>
                                <td class="check-col"><span class="checkbox"></span></td>
                                <td><span class="num-badge">{{ $player->number ?? '—' }}</span></td>
                                <td>{{ $player->name }}</td>
                                <td>{{ $player->position ?? '—' }}</td>
                                <td>{{ $player->size ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="summary-line">
                    {{ $players->count() }} player{{ $players->count() === 1 ? '' : 's' }} listed
                    @if($players->count() !== $order->quantity)
                        &mdash; <strong>note: order quantity is {{ $order->quantity }} sets, roster lists {{ $players->count() }}. Double-check before packing.</strong>
                    @endif
                </p>
            @endif
        </div>

        <div class="signatures">
            <div class="sig-line">Packed by &amp; date</div>
            <div class="sig-line">Checked by &amp; date</div>
        </div>

        <footer class="doc">
            <span>jerseyconnect.shop</span>
            <span>{{ $order->order_number }} · {{ $order->team_name }}</span>
        </footer>
    </div>
</body>
</html>
