<?php 
session_start();

// Bersihkan cart dari item yang tidak valid
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        if (!isset($item['menu_id'], $item['menu_name'], $item['price'], $item['quantity'])) {
            unset($_SESSION['cart'][$key]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['menu_name'])) {
        $action = $_POST['action'];
        $menu_name = $_POST['menu_name'];

        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $key => &$item) {
                if ($item['menu_name'] === $menu_name) {
                    if ($action === 'increase') {
                        $item['quantity'] += 1;
                    } elseif ($action === 'decrease') {
                        $item['quantity'] -= 1;
                        if ($item['quantity'] <= 0) {
                            unset($_SESSION['cart'][$key]);
                        }
                    } elseif ($action === 'update' && isset($_POST['quantity'])) {
                        $newQty = (int)$_POST['quantity'];
                        $item['quantity'] = max(1, $newQty); // minimal 1
                    }
                    break;
                }
            }
            unset($item);
        }

        header('Location: cart.php');
        exit();
    }

    header('Location: cart.php');
    exit();
}

// Hitung total harga
$total_price = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['price'], $item['quantity'])) {
            $total_price += $item['price'] * $item['quantity'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Cart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="../../img/hero.PNG" />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../dash/assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="../../dash/assets/css/demo.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../../dash/assets/vendor/css/pages/page-auth.css" />

    <style>
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner" style="max-width: 600px; width: 90%;">
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
                        <h4 class="mb-3 text-center fw-bold">Your Cart 🛒</h4>

                        <?php if (!empty($_SESSION['cart'])): ?>
                            <?php foreach ($_SESSION['cart'] as $item): ?>
                                <div class="row align-items-center mb-3">
                                    <div class="col-6">
                                        <strong><?= htmlspecialchars($item['menu_name']) ?></strong><br>
                                        <small>Rp<?= number_format($item['price']) ?></small>
                                    </div>
                                    <div class="col-3 text-center d-flex align-items-center justify-content-center">
                                        <form method="POST" class="me-1">
                                            <input type="hidden" name="menu_name" value="<?= htmlspecialchars($item['menu_name']) ?>">
                                            <input type="hidden" name="action" value="decrease">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">-</button>
                                        </form>

                                        <form method="POST">
                                            <input type="hidden" name="menu_name" value="<?= htmlspecialchars($item['menu_name']) ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input 
                                                type="number" 
                                                name="quantity" 
                                                value="<?= $item['quantity'] ?>" 
                                                min="1" 
                                                class="form-control form-control-sm text-center" 
                                                style="width: 60px; display: inline-block;" 
                                                onchange="this.form.submit()"
                                            >
                                        </form>

                                        <form method="POST" class="ms-1">
                                            <input type="hidden" name="menu_name" value="<?= htmlspecialchars($item['menu_name']) ?>">
                                            <input type="hidden" name="action" value="increase">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">+</button>
                                        </form>
                                    </div>

                                    <div class="col-3 text-end">
                                        <span>Rp<?= number_format($item['price'] * $item['quantity']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <hr>
                            <div class="d-flex justify-content-between fw-bold mb-3">
                                <span>Total:</span>
                                <span>Rp<?= number_format($total_price) ?></span>
                            </div>

                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <a href="menu.php" class="btn btn-secondary w-100">Back to Menu</a>

                                <form action="checkout.php" method="POST" id="dineinForm" class="w-100">
                                    <input type="hidden" name="from_cart" value="1">
                                    <input type="hidden" name="id_pelanggan" value="123">
                                    <input type="hidden" name="order_type" value="dinein">
                                    <button type="submit" class="btn btn-success w-100">Dine In</button>
                                </form>

                                <a href="checkout_delivery.php" class="btn btn-primary w-100">Delivery</a>
                            </div>
                        <?php else: ?>
                            <p class="text-center mb-3">Your cart is empty.</p>
                            <div class="d-flex justify-content-center">
                                <a href="menu.php" class="btn btn-secondary">Back to Menu</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../dash/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../dash/assets/vendor/libs/popper/popper.js"></script>
    <script src="../../dash/assets/vendor/js/bootstrap.js"></script>
    <script src="../../dash/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../dash/assets/vendor/js/menu.js"></script>
    <script src="../../dash/assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
