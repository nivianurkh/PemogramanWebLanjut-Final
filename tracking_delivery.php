<?php
require_once 'koneksi.php';

$midtrans_order_id = $_GET['order_id'] ?? '';

if (empty($midtrans_order_id)) {
    echo "Order ID not found.";
    exit;
}

// Ambil data pesanan dari database
$sql = "SELECT customer_name, address, status, order_date, order_time, latitude, longitude 
        FROM delivery_orders 
        WHERE midtrans_order_id = :midtrans_order_id";
$stmt = $db->prepare($sql);
$stmt->execute(['midtrans_order_id' => $midtrans_order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Order Tracking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .tracking-box {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            max-width: 750px;
            margin: auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
        }
        .status {
            font-weight: bold;
            font-size: 18px;
            margin-top: 15px;
        }
        .status i {
            margin-right: 10px;
        }
        #map {
            height: 400px;
            width: 100%;
            margin-top: 20px;
            border-radius: 12px;
        }
        .back-btn {
            text-decoration: none;
            background-color: #b884de;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            font-weight: bold;
            transition: background-color 0.3s;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background-color: #b884de;
        }
    </style>
    <!-- GANTI DENGAN API KEY ASLIMU -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY"></script>
</head>
<body>
    <div class="tracking-box">
        <a href="history.php" class="back-btn">← Back to History</a>
        <h2>Track Your Order</h2>

        <?php if ($order): ?>
            <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong>Delivery Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
            <p><strong>Order Date:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
            <p><strong>Order Time:</strong> <?= htmlspecialchars($order['order_time']) ?></p>
            
            <!-- Status -->
            <p class="status">
                <?php
                    $status = $order['status'] ?? 'Being Prepared';
                    switch ($status) {
                        case 'pending':
                            echo '<i class="fas fa-hourglass-half" style="color: gray;"></i>Waiting for confirmation...';
                            break;
                        case 'Being Prepared':
                            echo '<i class="fas fa-utensils" style="color: orange;"></i>Your order is being prepared';
                            break;
                        case 'Out for Delivery':
                            echo '<i class="fas fa-motorcycle" style="color: blue;"></i>Your order is on the way!';
                            break;
                        case 'Delivered':
                            echo '<i class="fas fa-check-circle" style="color: green;"></i>Your order has been delivered!';
                            break;
                        default:
                            echo '<i class="fas fa-info-circle" style="color: gray;"></i>Status: ' . htmlspecialchars($status);
                    }
                ?>
            </p>

            <!-- Map ditampilkan hanya saat Out for Delivery -->
            <?php if ($status === 'Out for Delivery'): ?>
                <div id="map"></div>
                <script>
                    function initMap() {
                        const location = {
                            lat: <?= floatval($order['latitude']) ?>,
                            lng: <?= floatval($order['longitude']) ?>
                        };
                        const map = new google.maps.Map(document.getElementById("map"), {
                            zoom: 15,
                            center: location
                        });
                        new google.maps.Marker({
                            position: location,
                            map: map,
                            title: "Delivery Location"
                        });
                    }
                    initMap();
                </script>
            <?php endif; ?>

        <?php else: ?>
            <p style="color:red;">Order not found or invalid ID.</p>
        <?php endif; ?>
    </div>
</body>
</html>
