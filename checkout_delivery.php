<?php
session_start();
require_once 'koneksi.php';
require_once 'midtrans_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // ⬇ Tampilkan form jika belum dikirim
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Checkout - Delivery</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="../../img/hero.PNG" />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../dash/assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="../../dash/assets/css/demo.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/css/pages/page-auth.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>#map { height: 300px; width: 100%; margin-bottom: 1rem; }</style>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner" style="max-width: 1200px; width: 90%;">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <div class="app-brand justify-content-center mb-3">
                            <a href="#" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <img src="../../img/hero.PNG" alt="logo" style="width: 60px;">
                                </span>
                                <span class="app-brand-text demo text-body fw-bolder">Ocean's Feast</span>
                            </a>
                        </div>
                        <h4 class="mb-3 text-center fw-bold">Checkout - Delivery Order</h4>

                        <form action="checkout_delivery.php" method="POST">
                            <div class="mb-2">
                                <label for="customer_name">Full Name</label>
                                <input type="text" name="customer_name" id="customer_name" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label for="phone">No. HP</label>
                                <input type="text" name="phone" id="phone" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label for="address">Address</label>
                                <textarea name="address" id="address" class="form-control" rows="2" required placeholder="Type or select a location from the map"></textarea>
                                <small class="text-muted">You can fill in the address manually or click on the map below.</small>
                            </div>
                            <div id="map"></div>
                            <div class="mb-2">
                                <label for="additional_information">Additional Information</label>
                                <textarea name="additional_information" id="additional_information" class="form-control" rows="2" placeholder="Building name, landmark, etc." required></textarea>
                            </div>
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">
                            <button type="submit" class="btn btn-primary">Order Now</button>
                            <a href="menu.php"><button type="button" class="btn btn-secondary">Back</button></a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([-6.2, 106.8166], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        let marker;
        map.on('click', function(e) {
            const lat = e.latlng.lat, lng = e.latlng.lng;
            marker ? marker.setLatLng(e.latlng) : marker = L.marker(e.latlng).addTo(map);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                .then(res => res.json())
                .then(data => {
                    if (data.display_name) document.getElementById('address').value = data.display_name;
                })
                .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>
<?php
    exit;
}

// ==============================
// POST: Proses setelah submit form
// ==============================

$customer_name = $_POST['customer_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$additional_information = $_POST['additional_information'] ?? '';
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;

if (!empty($_SESSION['cart']) && $customer_name && $phone && $address) {
    try {
        if (!isset($_SESSION['id_pelanggan'])) throw new Exception("ID pelanggan tidak ditemukan.");
        $id_pelanggan = $_SESSION['id_pelanggan'];

        $total_price = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_price += $item['quantity'] * $item['price'];
        }

        $midtrans_order_id = 'DELIVERY-' . time() . '-' . rand(1000, 9999);

        $stmt = $db->prepare("INSERT INTO delivery_orders 
            (id_pelanggan, customer_name, phone, address, latitude, longitude, additional_information, total_price, order_date, order_time, status, midtrans_order_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), CURTIME(), 'pending', ?)");
        $stmt->execute([$id_pelanggan, $customer_name, $phone, $address, $latitude, $longitude, $additional_information, $total_price, $midtrans_order_id]);

        $order_id = $db->lastInsertId();

        $stmtItem = $db->prepare("INSERT INTO delivery_order_items (delivery_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($_SESSION['cart'] as $item) {
            $stmtItem->execute([$order_id, $item['menu_id'], $item['quantity'], $item['price']]);
        }

        $snapItems = [];
        foreach ($_SESSION['cart'] as $item) {
            $snapItems[] = [
                'id' => $item['menu_id'],
                'price' => (int)$item['price'],
                'quantity' => (int)$item['quantity'],
                'name' => $item['menu_name'] ?? 'Menu Item'
            ];
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $midtrans_order_id,
                'gross_amount' => $total_price
            ],
            'item_details' => $snapItems,
            'customer_details' => [
                'first_name' => $customer_name,
                'email' => 'customer@example.com'
            ]
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($payload);

        unset($_SESSION['cart']); // Bersihkan keranjang

    } catch (Exception $e) {
        echo "<script>alert('Gagal checkout: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }
} else {
    echo "<script>alert('Lengkapi data dan pastikan keranjang tidak kosong.'); window.history.back();</script>";
    exit;
}
?>

<!-- Menampilkan Snap Midtrans -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to Payment...</title>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="ISI_CLIENT_KEY_MU"></script>
</head>
<body>
    <h3>Mohon tunggu, membuka halaman pembayaran...</h3>
    <script>
        window.onload = function () {
            snap.pay("<?= $snapToken ?>", {
                onSuccess: function(result) {
                fetch("update_delivery_status.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        order_id: "<?= $midtrans_order_id ?>",
                        status: "Paid"
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = "tracking_delivery.php?order_id=<?= $midtrans_order_id ?>";
                    } else {
                        alert("Gagal update status: " + (data.reason || "unknown error"));
                    }
                });
            },

                onPending: function(result) {
                    window.location.href = "menu.php?status=pending&id_order=<?= $order_id ?>&source=delivery";
                },
                onError: function(result) {
                    alert("Pembayaran gagal. Silakan coba lagi.");
                    window.location.href = "menu.php?status=error&id_order=<?= $order_id ?>&source=delivery";
                }
            });
        };
    </script>
</body>
</html>
