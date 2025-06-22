<?php
require_once dirname(__FILE__) . '/midtrans-php/Midtrans.php';

// Konfigurasi Midtrans
\Midtrans\Config::$serverKey = 'SB-Mid-server-s1oBo3NDiosQdRHxInkxVYdN';
\Midtrans\Config::$isProduction = false; // ganti ke true jika sudah live
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Ambil order ID dari URL
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    echo "Order ID not found";
    exit;
}

// Ambil status transaksi dari Midtrans
try {
    $status = \Midtrans\Transaction::status($order_id);
    echo "<h2>Status Transaksi:</h2>";
    echo "<p><strong>Order ID:</strong> " . $status->order_id . "</p>";
    echo "<p><strong>Status:</strong> " . $status->transaction_status . "</p>";
    echo "<p><strong>Gross Amount:</strong> " . $status->gross_amount . "</p>";
    echo "<p><strong>Waktu Transaksi:</strong> " . $status->transaction_time . "</p>";
} catch (Exception $e) {
    echo "Terjadi kesalahan: " . $e->getMessage();
}
?>
