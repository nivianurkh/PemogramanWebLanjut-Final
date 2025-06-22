<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_name'])) {
    $menu_name = $_POST['menu_name'];

    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['menu_name'] === $menu_name) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
    }
}

header("Location: cart.php");
exit;
?>
