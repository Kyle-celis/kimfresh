<?php 
/* Analytics Live Delivery View */ 

// ✅ CALCULATE DATA AT THE TOP SO HTML CAN USE IT
$labels = [];
$temps = [];

// Group by minute for the initial chart load
$chart_query = "
    SELECT AVG(s.temperature) as temp, DATE_FORMAT(s.reading_timestamp, '%Y-%m-%d %H:%i:00') as minute_group
    FROM sensor_data s 
    JOIN delivery d ON s.delivery_id = d.delivery_id 
    WHERE d.order_id = ? 
    GROUP BY minute_group
    ORDER BY minute_group ASC
";
$stmt = $this->conn->prepare($chart_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
while($r = $res->fetch_assoc()) {
    $val = $r['temp'];
    if (is_numeric($val) && $val > -50 && $val < 100) {
        $labels[] = date('H:i', strtotime($r['minute_group']));
        $temps[] = round((float)$val, 2);
    }
}
$stmt->close();

// Get absolute latest temp
$latest_query = "SELECT s.temperature FROM sensor_data s JOIN delivery d ON s.delivery_id = d.delivery_id WHERE d.order_id = ? ORDER BY s.reading_timestamp DESC LIMIT 1";
$stmt = $this->conn->prepare($latest_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$latest_row = $stmt->get_result()->fetch_assoc();
$latestTemp = $latest_row ? (float)$latest_row['temperature'] : null;
$stmt->close();

$minTemp = !empty($temps) ? min($temps) : 0;
$maxTemp = !empty($temps) ? max($temps) : 0;
$avgTemp = !empty($temps) ? round(array_sum($temps) / count($temps), 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Tracking - Order #<?= $order_id ?></title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    <link rel="stylesheet" href="/php-web/public/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        #map { height: 500px; border-radius: 12px; border: 1px solid #eee; z-index: 1; }
        .card { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px; }
        .stat-box { background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; }
        .stat-value { font-size: 24px; font-weight: bold; color: #C34200; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .chart-summary { display: flex; justify-content: space-between; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 14px; font-weight: 600; color: #555; border: 1px solid #eee; }
        .chart-summary span { color: #C34200; font-weight: bold; }
    </style>
</head>
<body style="background: #f4f6fa; padding: 40px;">
    
    <div style="max-width: 1200px; margin: 0 auto;">
        <a href="/php-web/public/analytics" style="color: #C34200; font-weight: bold; text-decoration: none;">&larr; Back to Analytics</a>
        
        <h1 style="color: #333; margin-top: 20px;">Order #<?= $order_id ?> - Live Tracking</h1>
        
        <!-- Top Stats -->
        <div class="stat-grid">
            <div class="stat-box">
                <div style="font-size: 12px; color: #777; text-transform: uppercase;">Driver</div>
                <div style="font-size: 18px; font-weight: bold; color: #333; margin-top: 5px;"><?= htmlspecialchars($driver_name) ?></div>
            </div>
            <div class="stat-box">
                <div style="font-size: 12px; color: #777; text-transform: uppercase;">Delivery Duration</div>
                <div class="stat-value"><?= $duration ?></div>
            </div>
            <div class="stat-box">
                <div style="font-size: 12px; color: #777; text-transform: uppercase;">Order Status</div>
                <div style="margin-top: 8px;">
                    <span class="status-badge" style="background: #dcfce7; color: #15803d; text-transform: uppercase;">
                        <?= htmlspecialchars($order['order_status']) ?>
                    </span>
                </div>
            </div>
            <!-- ✅ LATEST TEMP BOX -->
            <div class="stat-box">
                <div style="font-size: 12px; color: #777; text-transform: uppercase;">Latest Temp</div>
                <div class="stat-value" id="latestTempBox" style="color: <?= $latestTemp !== null && $latestTemp >= 15 ? '#dc2626' : ($latestTemp !== null && $latestTemp >= 5 ? '#b45309' : '#15803d') ?>;">
                    <?php if ($latestTemp !== null): ?>
                        <?= $latestTemp ?> °C
                    <?php else: ?>
                        --
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Map Container -->
        <div class="card">
            <h3 style="margin-top:0; margin-bottom: 15px;">Live Route & GPS</h3>
            <div id="map"></div>
        </div>

        <!-- Temperature Chart -->
        <div class="card">
            <h3 style="margin-top:0; margin-bottom: 15px;">Cold Chain History</h3>
            <canvas id="tempChart" height="80"></canvas>
            
            <!-- ✅ TEXT SUMMARY BELOW GRAPH (Now with IDs for live update) -->
            <div class="chart-summary" id="chartSummary" style="<?= empty($temps) ? 'display:none;' : '' ?>">
                <div>Minimum: <span id="sum-min"><?= $minTemp ?> °C</span></div>
                <div>Maximum: <span id="sum-max"><?= $maxTemp ?> °C</span></div>
                <div>Average: <span id="sum-avg"><?= $avgTemp ?> °C</span></div>
                <div>Total Readings: <span id="sum-count"><?= count($temps) ?></span></div>
                <div>Status: <span id="sum-status"><?= $maxTemp >= 15 ? 'CRITICAL' : ($maxTemp >= 5 ? 'WARNING' : 'SAFE') ?></span></div>
            </div>
        </div>

    </div>

    <!-- Leaflet Map JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // 1. Initialize Map
        const map = L.map('map').setView([<?= $order['retailer_lat'] ?? 14.5995 ?>, <?= $order['retailer_lng'] ?? 120.9842 ?>], 15);
        
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // 2. Define Icons
        const driverIcon = L.divIcon({
            className: 'custom-icon',
            html: '<div style="background: #C34200; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.3);"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });
        const storeIcon = L.divIcon({
            className: 'custom-icon',
            html: '<div style="background: #15803d; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.3);"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });

        // 3. Variables
        let driverMarker = null;
        let storeMarker = null;
        let routeLine = null;
        const ORS_API_KEY = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjgzMTc3NzM5ZGYxYTQ2NzdhZTI1NTBlNmJkNmQ1ODJmIiwiaCI6Im11cm11cjY0In0=';
        const orderId = <?= $order_id ?>;

        const retailerLat = <?= $order['retailer_lat'] ?? 0 ?>;
        const retailerLng = <?= $order['retailer_lng'] ?? 0 ?>;
        
        let driverLat = <?= $live_gps['lat'] ?? $order['driver_lat'] ?? 0 ?>;
        let driverLng = <?= $live_gps['lng'] ?? $order['driver_lng'] ?? 0 ?>;

        // 4. Plot Initial Markers
        if (retailerLat !== 0) {
            storeMarker = L.marker([retailerLat, retailerLng], {icon: storeIcon}).addTo(map).bindPopup("Retailer Location");
        }
        if (driverLat !== 0) {
            driverMarker = L.marker([driverLat, driverLng], {icon: driverIcon}).addTo(map).bindPopup("Driver Location");
        }

        // 5. Draw Route
        async function fetchRoute(startLat, startLng) {
            if (startLat === 0 || retailerLat === 0) return;
            const url = `https://api.heigit.org/openrouteservice/v2/directions/driving-car?api_key=${ORS_API_KEY}&start=${startLng},${startLat}&end=${retailerLng},${retailerLat}`;
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                const coords = data.features[0].geometry.coordinates;
                const latLngs = coords.map(c => [c[1], c[0]]);
                
                if (routeLine) map.removeLayer(routeLine);
                routeLine = L.polyline(latLngs, { color: '#C34200', weight: 5, opacity: 0.8 }).addTo(map);

                if (!window.hasZoomed) {
                    map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
                    window.hasZoomed = true;
                }
            } catch (error) {
                console.error('Error fetching route:', error);
            }
        }

        if (driverLat !== 0) fetchRoute(driverLat, driverLng);

        // 6. INITIAL CHART SETUP
        const ctx = document.getElementById('tempChart').getContext('2d');
        let myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Temperature (°C)',
                    data: <?= json_encode($temps) ?>,
                    borderColor: '#C34200',
                    backgroundColor: 'rgba(195, 66, 0, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: false,
                        suggestedMin: 0,
                        suggestedMax: 20
                    }
                }
            }
        });

        // 7. LIVE POLLING (Every 5 seconds)
        setInterval(async () => {
            try {
                const res = await fetch(`/php-web/public/api/get_live_report.php?order_id=${orderId}`);
                const data = await res.json();
                
                if (data.success) {
                    // A. Update Map Pin
                    if (data.lat !== null && data.lng !== null) {
                        driverLat = data.lat;
                        driverLng = data.lng;
                        if (driverMarker) {
                            driverMarker.setLatLng([driverLat, driverLng]);
                        } else {
                            driverMarker = L.marker([driverLat, driverLng], {icon: driverIcon}).addTo(map);
                        }
                        fetchRoute(driverLat, driverLng);
                    }
                    
                    // B. Update Latest Temp Box
                    if (data.latest_temp !== null) {
                        const tempBox = document.getElementById('latestTempBox');
                        if (tempBox) {
                            tempBox.innerText = data.latest_temp + ' °C';
                            if (data.latest_temp >= 15) tempBox.style.color = '#dc2626';
                            else if (data.latest_temp >= 5) tempBox.style.color = '#b45309';
                            else tempBox.style.color = '#15803d';
                        }
                    }

                    // C. Update Chart and Summary
                    if (data.temps && data.temps.length > 0) {
                        // Update Chart
                        myChart.data.labels = data.labels;
                        myChart.data.datasets[0].data = data.temps;
                        myChart.update();

                        // Calculate Stats
                        const min = Math.min(...data.temps);
                        const max = Math.max(...data.temps);
                        const sum = data.temps.reduce((a, b) => a + b, 0);
                        const avg = (sum / data.temps.length).toFixed(1);
                        
                        // ✅ Use the LATEST temp for the status badge
                        const currentStatus = data.latest_temp >= 15 ? 'CRITICAL' : (data.latest_temp >= 5 ? 'WARNING' : 'SAFE');
                        
                        // Update Summary Text
                        document.getElementById('chartSummary').style.display = 'flex';
                        document.getElementById('sum-min').innerText = min + ' °C';
                        document.getElementById('sum-max').innerText = max + ' °C';
                        document.getElementById('sum-avg').innerText = avg + ' °C';
                        document.getElementById('sum-count').innerText = data.temps.length + ' readings';
                        
                        const statusEl = document.getElementById('sum-status');
                        statusEl.innerText = currentStatus;
                        statusEl.style.color = currentStatus === 'CRITICAL' ? '#dc2626' : (currentStatus === 'WARNING' ? '#b45309' : '#15803d');
                    }
                }
            } catch (e) {
                console.error("Live update failed:", e);
            }
        }, 5000);
    </script>
</body>
</html>