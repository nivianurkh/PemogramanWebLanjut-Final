<?php
require_once __DIR__ . '/../../vendor/autoload.php'; // sesuaikan dengan lokasi vendor aslinya

// Konfigurasi Midtrans
\Midtrans\Config::$serverKey = 'SB-Mid-server-s1oBo3NDiosQdRHxInkxVYdN';
\Midtrans\Config::$isProduction = false; // <-- HARUS false untuk sandbox
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;
