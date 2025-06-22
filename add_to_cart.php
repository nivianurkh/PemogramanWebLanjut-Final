<?php
session_start();
header('Content-Type: application/json');

// Pastikan method yang dipakai POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Ambil data dari request
$menu_id = $_POST['menu_id'] ?? null;
$menu_name = $_POST['menu_name'] ?? null;
$price = $_POST['price'] ?? null;

// Validasi data wajib ada
if (!$menu_id || !$menu_name || !$price) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

// Inisialisasi cart di session jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Jika item sudah ada di cart, tambah quantity
if (isset($_SESSION['cart'][$menu_id])) {
    $_SESSION['cart'][$menu_id]['quantity'] += 1;
} else {
    // Kalau belum ada, buat item baru dengan quantity 1
    $_SESSION['cart'][$menu_id] = [
        'menu_id' => $menu_id,
        'menu_name' => $menu_name,
        'price' => $price,
        'quantity' => 1
    ];
}

// Hitung total item dalam cart (semua quantity dijumlahkan)
$total_quantity = array_sum(array_column($_SESSION['cart'], 'quantity'));

// Kirim respon JSON sukses
echo json_encode([
    'success' => true,
    'cart_count' => $total_quantity,
    'message' => "$menu_name berhasil ditambahkan ke keranjang!"
]);
