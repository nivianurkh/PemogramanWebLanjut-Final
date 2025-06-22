<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($_SESSION['cart']);
    http_response_code(200);
    echo 'Cart cleared';
} else {
    http_response_code(405); // Method Not Allowed
    echo 'Method not allowed';
}
