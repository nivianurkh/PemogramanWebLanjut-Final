<?php 
session_start();
require_once 'koneksi.php'; 
require_once 'midtrans_config.php';

if (!isset($_SESSION['id_pelanggan'])) {
    die("You are not logged in, please login first!");
}

$id_pelanggan = $_SESSION['id_pelanggan'];

// DETEKSI apakah ini direct buy
$isDirectBuy = isset($_POST['direct_buy']) && $_POST['direct_buy'] == 1;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

// Ambil item untuk diproses
if ($isDirectBuy) {
    // Ambil dari POST langsung
    $item = [
        'menu_id' => $_POST['menu_id'],
        'menu_name' => $_POST['menu_name'],
        'price' => $_POST['price'],
        'quantity' => $_POST['quantity']
    ];
    $itemsToProcess = [$item];
} else {
    // Dari session cart
    if (empty($_SESSION['cart'])) {
        echo "Keranjang Anda kosong.";
        exit;
    }
    $itemsToProcess = $_SESSION['cart'];
}

try {
    $db->beginTransaction();

    $total = 0;
    foreach ($itemsToProcess as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    $midtrans_order_id = 'ORDER-' . time() . '-' . rand(1000, 9999);

    $stmt = $db->prepare("INSERT INTO orders (id_pelanggan, total_price, midtrans_order_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$id_pelanggan, $total, $midtrans_order_id]);
    $id_order = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO order_items (id_order, id_menu, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($itemsToProcess as $item) {
        $stmt->execute([
            $id_order,
            $item['menu_id'],
            $item['quantity'],
            $item['price']
        ]);
    }

    $db->commit();

    $snapItems = [];
    foreach ($itemsToProcess as $item) {
        $snapItems[] = [
            'id' => $item['menu_id'],
            'price' => $item['price'],
            'quantity' => $item['quantity'],
            'name' => $item['menu_name']
        ];
    }

    $payload = [
        'transaction_details' => [
            'order_id' => $midtrans_order_id,
            'gross_amount' => $total
        ],
        'item_details' => $snapItems,
        'customer_details' => [
            'first_name' => 'Pelanggan',
            'email' => 'pelanggan@example.com'
        ]
    ];

    $snapToken = \Midtrans\Snap::getSnapToken($payload);

    // Kosongkan session cart jika bukan direct buy
    if (!$isDirectBuy) {
        unset($_SESSION['cart']);
    }

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Checkout gagal: " . $e->getMessage();
    exit;
}

// Tentukan source untuk redirect
$redirectSource = $isDirectBuy ? 'buynow' : 'cart';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Payment Process</title>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="ISI_CLIENT_KEY_MU"></script>
</head>
<body>
    <h2>Please wait, redirecting to payment...</h2>

    <script type="text/javascript">
        window.onload = function() {
            snap.pay("<?= $snapToken ?>", {
                onSuccess: function(result) {
                    window.location.href = "menu.php?status=success&id_order=<?= $id_order ?>&source=<?= $redirectSource ?>";
                },
                onPending: function(result) {
                    window.location.href = "menu.php?status=pending&id_order=<?= $id_order ?>&source=<?= $redirectSource ?>";
                },
                onError: function(result) {
                    alert("Payment failed. Please try again!");
                    window.location.href = "menu.php?status=error&id_order=<?= $id_order ?>&source=<?= $redirectSource ?>";
                }
            });
        }
    </script>
</body>
</html>
