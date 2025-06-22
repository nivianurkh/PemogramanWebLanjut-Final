<?php
session_start(); // Pastikan ini ada paling atas

if (isset($_SESSION['success_message'])) {
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '" . $_SESSION['success_message'] . "',
                confirmButtonText: 'OK'
            });
        });
    </script>
    ";
    unset($_SESSION['success_message']);
}

// Total cart
$total_price = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }
}

// Update cart (increase/decrease)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['menu_name'])) {
        foreach ($_SESSION['cart'] as $key => &$item) {
            if ($item['menu_name'] === $_POST['menu_name']) {
                if ($_POST['action'] === 'increase') $item['quantity']++;
                if ($_POST['action'] === 'decrease') {
                    $item['quantity']--;
                    if ($item['quantity'] <= 0) unset($_SESSION['cart'][$key]);
                }
                break;
            }
        }
        unset($item); // untuk menghindari reference bugs
        header('Location: menu.php'); // Refresh untuk update cart
        exit();
    }
}

// Add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_id'], $_POST['menu_name'], $_POST['price'])) {
    $menu_id = $_POST['menu_id'];
    $menu_name = $_POST['menu_name'];
    $price = (float) $_POST['price'];

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['menu_name'] === $menu_name) {
            $item['quantity'] += 1;
            $found = true;
            break;
        }
    }
    unset($item); // penting untuk referensi aman

    if (!$found) {
        $_SESSION['cart'][] = [
            'menu_id' => $menu_id,
            'menu_name' => $menu_name,
            'price' => $price,
            'quantity' => 1
        ];
    }

    header('Location: menu.php'); // Redirect agar form tidak re-submit
    exit();
}


$showModal = false;
$message = "";

if (isset($_SESSION['checkout_success'])) {
    $showModal = true;
    $message = $_SESSION['checkout_success'];
    unset($_SESSION['checkout_success']);
}

// Cek status pembayaran dari URL
$status = isset($_GET['status']) ? $_GET['status'] : '';
$order_id = isset($_GET['id_order']) ? $_GET['id_order'] : '';
$notif = '';

if ($status === 'success') {
    $notif = "✅ Payment successful! Thank you for your order! <br><strong>Order ID:</strong> $order_id";
} elseif ($status === 'pending') {
    $notif = "⌛ Payment is being processed... <br><strong>Order ID:</strong> $order_id";
} elseif ($status === 'error') {
    $notif = "❌ Payment failed. Please try again!";
}

require_once("koneksi.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">  
    <link rel="shortcut icon" href="../../img/hero.PNG">    
    <title>Ocean's Feast</title>
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="../../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">


    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../../lib/animate/animate.min.css" rel="stylesheet">
    <link href="../../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../../lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../../css/style.css" rel="stylesheet">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <style>
    .nav-pills .nav-link.active {
      background-color: transparent !important;
      color: inherit !important;
      box-shadow: none !important;
    }

    .nav-pills .nav-link.active i {
      color: #b084e9 !important; /* warna ungu ikon seperti sebelumnya */
    }
  </style>
</head>

<script>
    window.addEventListener('load', function () {
        var spinner = document.getElementById('spinner');
        if (spinner) {
            spinner.classList.remove('show');
        }
    });
</script>

<body>
    <div class="container-xxl bg-white p-0">
        <!-- Spinner Start -->
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->


        <!-- Navbar & Hero Start -->
        <div class="container-xxl position-relative p-0">
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 px-lg-5 py-3 py-lg-0">
                <a href="" class="navbar-brand p-0">
                    <h1 class="text-primary m-0"><i class="fa fa-utensils me-3"></i>Restoran</h1>
                    <!-- <img src="img/logo.png" alt="Logo"> -->
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto py-0 pe-4">
                        <a href="timeline.php" class="nav-item nav-link">Home</a>
                        <a href="about.php" class="nav-item nav-link">About</a>
                        <a href="service.php" class="nav-item nav-link">Service</a>
                        <a href="menu.php" class="nav-item nav-link">Menu</a>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle active" data-bs-toggle="dropdown">Pages</a>
                            <div class="dropdown-menu m-0">
                                <a href="booking.php" class="dropdown-item active">Booking</a>
                                <a href="team.php" class="dropdown-item">Our Team</a>
                                <a href="testimonial.php" class="dropdown-item">Testimonial</a>
                            </div>
                        </div>
                        <a href="contact.php" class="nav-item nav-link">Contact</a>
                    </div>
                    <a href="logout_pelanggan.php" class="btn btn-primary py-2 px-4">Log Out</a>
                </div>
            </nav>

            <div class="container-xxl py-5 bg-dark hero-header mb-5">
                <div class="container text-center my-5 pt-5 pb-4">
                    <h1 class="display-3 text-white mb-3 animated slideInDown">Menu</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center text-uppercase">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Pages</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Menu</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Navbar & Hero End -->
         <?php if (!empty($notif)): ?>
            <div class="alert alert-<?php echo ($status === 'success') ? 'success' : (($status === 'pending') ? 'warning' : 'danger'); ?> alert-dismissible fade show m-3" role="alert">
                <?= $notif ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>


        <!-- Menu Section -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                    <h5 class="section-title ff-secondary text-primary fw-normal">Food Menu</h5>
                    <h1 class="mb-5">Most Popular Items</h1>
                </div>
                <div class="tab-class text-center wow fadeInUp" data-wow-delay="0.1s">
                    <?php
                        $categories = [
                            'Main Course' => 'tab-1',
                            'Appetizer' => 'tab-2',
                            'Dessert' => 'tab-3',
                            'Beverage' => 'tab-4'
                        ];
                        $i = 0;
                    ?>
                    <ul class="nav nav-pills d-inline-flex justify-content-center border-bottom mb-5" id="menuTabs" role="tablist">
                        <?php foreach ($categories as $name => $tabId): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center text-start mx-3 pb-3 <?= $i === 0 ? 'active' : '' ?>" id="<?= $tabId ?>-tab" data-bs-toggle="pill" data-bs-target="#<?= $tabId ?>" type="button" role="tab" aria-controls="<?= $tabId ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                                <i class="fa <?= $name == 'Main Course' ? 'fa-utensils' : ($name == 'Appetizer' ? 'fa-leaf' : ($name == 'Dessert' ? 'fa-ice-cream' : 'fa-glass-martini-alt')) ?> fa-2x text-primary"></i>
                                <div class="ps-3">
                                    <small class="text-body"><?= $name == 'Main Course' ? 'Special' : ($name == 'Appetizer' ? 'Fresh Start' : ($name == 'Dessert' ? 'Lovely' : 'Chill & Sip')) ?></small>
                                    <h6 class="mt-n1 mb-0"><?= $name ?></h6>
                                </div>
                            </button>
                        </li>
                        <?php $i++; endforeach; ?>
                    </ul>

                    <div class="tab-content" id="menuTabContent">
                        <?php
                        $i = 0;
                        foreach ($categories as $categoryName => $tabId):
                        ?>
                            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="<?= $tabId ?>" role="tabpanel" aria-labelledby="<?= $tabId ?>-tab">
                                <div class="row g-4">
                                <?php
                                    $stmt = $db->prepare("SELECT * FROM menu WHERE category = :category");
                                    $stmt->execute(['category' => $categoryName]);

                                    if ($stmt->rowCount() > 0):
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                            $imgPath = htmlspecialchars($row['image']);
                                            $menuName = htmlspecialchars($row['menu_name']);
                                            $price = number_format($row['price'], 2);
                                            $desc = htmlspecialchars($row['description']);
                                ?>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="border rounded p-3 h-100 d-flex flex-column align-items-center">
                                            <img class="img-fluid rounded mb-3" src="/resto/<?= $imgPath ?>" alt="<?= $menuName ?>" style="width: 150px;">
                                            <h5 class="text-center mb-1"><?= $menuName ?></h5>
                                            <p class="text-primary mb-2">Rp<?= $price ?></p>
                                            <small class="fst-italic text-center mb-3"><?= $desc ?></small>
                                            <div class="d-grid gap-2 w-100 mt-auto">
                                                <form method="POST" action="add_to_cart.php" class="add-to-cart-form">

                                                    <input type="hidden" name="menu_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="menu_name" value="<?= $menuName ?>">
                                                    <input type="hidden" name="price" value="<?= $row['price'] ?>">
                                                    <button type="submit" class="btn btn-outline-primary" style="width: 100%; border-radius: 10px; font-size: 14px; font-weight: bold;">+ ADD TO CART</button>
                                                </form>
                                               <form method="POST" action="checkout.php">
                                                <input type="hidden" name="direct_buy" value="1">
                                                <input type="hidden" name="menu_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="menu_name" value="<?= $menuName ?>">
                                                <input type="hidden" name="price" value="<?= $row['price'] ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-primary" style="width: 100%; border-radius: 10px; font-size: 14px; font-weight: bold; background-color: #b084e9; border: none;">
                                                    BUY NOW
                                                </button>
                                            </form>


                                            </div>
                                        </div>
                                    </div>
                                <?php
                                        endwhile;
                                    else:
                                        echo '<p class="text-center">Menu Not Available.</p>';
                                    endif;
                                ?>
                                </div>
                            </div>
                        <?php $i++; endforeach; ?>
                    </div>

                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Bootstrap untuk pesan sukses checkout -->
            <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Checkout Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= htmlspecialchars($message) ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
                </div>
            </div>
            </div>


        <!-- Footer -->
        <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Company</h4>
                        <a class="btn btn-link" href="#">About Us</a>
                        <a class="btn btn-link" href="https://api.whatsapp.com/qr/EXGON2CIPGQMK1?autoload=1&app_absent=0">Contact Us</a>
                        <a class="btn btn-link" href="booking.php">Reservation</a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Contact</h4>
                        <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Bikini Bottom</p>
                        <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+012 345 67890</p>
                        <p class="mb-2"><i class="fa fa-envelope me-3"></i>krustycrab@gmail.com</p>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Opening</h4>
                        <h5 class="text-light fw-normal">Monday - Saturday</h5>
                        <p>09AM - 09PM</p>
                        <h5 class="text-light fw-normal">Sunday</h5>
                        <p>10AM - 08PM</p>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Newsletter</h4>
                        <p>Happy Meal, Happy Day.</p>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="copyright">
                    <div class="row">
                        <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                            &copy; <a class="border-bottom" href="#">Nivia & Anisa</a> 
                            Designed By <a class="border-bottom" href="https://phpcodex.com">PHP Codex</a>
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <div class="footer-menu">
                                <a href="#">Home</a>
                                <a href="#">Cookies</a>
                                <a href="#">Help</a>
                                <a href="#">FQAs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
<!-- Tombol Riwayat Pemesanan -->
<a href="history.php" id="history-icon" class="btn btn-secondary btn-lg-square position-fixed history-btn" 
   style="bottom: 90px; right: 20px; background-color: #B0BEC5; z-index: 1050;">
    <i class="bi bi-clock-history" style="font-size: 28px;"></i>
</a>

<!-- Tombol Keranjang -->
<a href="checkout.php" id="cart-icon" class="btn btn-lg btn-primary btn-lg-square position-fixed" 
   style="bottom: 20px; right: 20px; z-index: 1052;">
    <i class="bi bi-cart" style="font-size: 28px;"></i>
    <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">
        <?= isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0 ?>
    </span>
</a>


    <!-- Bootstrap JS & dependencies -->
    <script src="../../js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <!-- Custom JS for Add to Cart -->

    <script>
    /* =========================================
    1. Handle Redirect dari Checkout (Success/Pending)
        -> Hanya hapus badge & localStorage jika dari cart
    ========================================= */
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const source = urlParams.get('source'); // ambil dari checkout.php

    if ((status === 'success' || status === 'pending') && source === 'cart') {
        localStorage.removeItem('cart');

        const badge = document.getElementById('cart-badge');
        if (badge) {
            badge.textContent = '0';
            badge.style.display = 'none';
        }
    }
    </script>

    <script>
    /* =========================================
    2. Auto-Dismiss Alert setelah 5 detik
    ========================================= */
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.classList.remove('show');
            alert.classList.add('fade');
        }
    }, 5000);
    </script>

    <?php if ($showModal): ?>
    <script>
    /* =========================================
    3. Show Modal Success jika PHP flag true
    ========================================= */
    var myModal = new bootstrap.Modal(document.getElementById('successModal'), {});
    myModal.show();
    </script>
    <?php endif; ?>

    <script>
    /* =========================================
    4. DOM Ready Handler
    ========================================= */
    document.addEventListener('DOMContentLoaded', function () {

        /* -----------------------------------------
        4a. Inisialisasi Badge Keranjang
        ----------------------------------------- */
        const cartBadge = document.getElementById('cart-badge');
        if (parseInt(cartBadge.textContent) > 0) {
            cartBadge.style.display = 'block';
        }

        /* -----------------------------------------
        4b. Toast Notification (Jika ada)
        ----------------------------------------- */
        const toastLiveExample = document.getElementById('liveToast');
        const toastBootstrap = toastLiveExample ? bootstrap.Toast.getOrCreateInstance(toastLiveExample) : null;

        /* -----------------------------------------
        4c. Handler Add to Cart Form
        ----------------------------------------- */
        document.querySelectorAll('.add-to-cart-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('add_to_cart.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        cartBadge.textContent = data.cart_count;
                        cartBadge.style.display = 'block';

                        if (toastBootstrap) {
                            document.querySelector('.toast-body').textContent = data.message;
                            toastBootstrap.show();
                        }
                    } else {
                        console.error('Error:', data.error || 'Unknown error');
                    }
                })
                .catch(console.error);
            });
        });

        /* -----------------------------------------
        4d. Handler Tombol Buy Now
        ----------------------------------------- */
        document.querySelectorAll('.buy-now-btn').forEach(button => {
            button.addEventListener('click', function () {
                const formData = new FormData();
                formData.append('menu_id', this.dataset.menuId);
                formData.append('menu_name', this.dataset.menuName);
                formData.append('price', this.dataset.price);
                formData.append('quantity', 1); // Pastikan ada quantity saat buy now

                fetch('add_to_cart.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Redirect ke checkout
                        window.location.href = 'checkout.php';
                    } else {
                        console.error('Error:', data.error || 'Unknown error');
                    }
                })
                .catch(console.error);
            });
        });

    });
    </script>

</body>

</html>