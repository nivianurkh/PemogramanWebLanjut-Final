<?php
session_start();
require_once("koneksi.php");

// Error reporting aktif
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ambil nama user dari session
$firstname = htmlspecialchars($_SESSION['firstname'] ?? 'Guest');

// Notifikasi sukses (misal dari checkout atau reservasi)
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

// Total harga keranjang
$total_price = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }
}

// Update keranjang (tambah/kurang)
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
        unset($item); // Hindari reference bug
        header('Location: menu.php');
        exit();
    }

    // Tambah item ke keranjang
    if (isset($_POST['menu_id'], $_POST['menu_name'], $_POST['price'])) {
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
        unset($item);

        if (!$found) {
            $_SESSION['cart'][] = [
                'menu_id' => $menu_id,
                'menu_name' => $menu_name,
                'price' => $price,
                'quantity' => 1
            ];
        }

        header('Location: menu.php');
        exit();
    }
}

// Menampilkan modal jika checkout berhasil
$showModal = false;
$message = "";
if (isset($_SESSION['checkout_success'])) {
    $showModal = true;
    $message = $_SESSION['checkout_success'];
    unset($_SESSION['checkout_success']);
}

// Cek status pembayaran dari URL
$status = $_GET['status'] ?? '';
$order_id = $_GET['id_order'] ?? '';
$notif = '';
if ($status === 'success') {
    $notif = "✅ Payment successful! Thank you for your order! <br><strong>Order ID:</strong> $order_id";
} elseif ($status === 'pending') {
    $notif = "⌛ Payment is being processed... <br><strong>Order ID:</strong> $order_id";
} elseif ($status === 'error') {
    $notif = "❌ Payment failed. Please try again!";
}

// === Reservasi & Email ===
require_once __DIR__ . '/../admin/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../admin/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../admin/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'], $_POST['email'], $_POST['datetime'])) {
    try {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $datetime = DateTime::createFromFormat('m/d/Y h:i A', $_POST['datetime']);
        if (!$datetime) {
            throw new Exception("Format tanggal tidak valid.");
        }
        $reservation_datetime = $datetime->format('Y-m-d H:i:s');
        $number_of_people = (int) $_POST['number_of_people'];
        $special_request = $_POST['message'];
        $price = $number_of_people * 20000;

        $stmt = $db->prepare("INSERT INTO table_reservations 
            (name, email, phone, reservation_datetime, number_of_people, price, special_request, status, payment_status, created_at) 
            VALUES (:name, :email, :phone, :reservation_datetime, :number_of_people, :price, :special_request, 'pending', 'unpaid', NOW())");

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':reservation_datetime' => $reservation_datetime,
            ':number_of_people' => $number_of_people,
            ':price' => $price,
            ':special_request' => $special_request
        ]);

        // Kirim Email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'anisa.indriani@widyatama.ac.id'; // Ganti dengan emailmu
        $mail->Password = 'smczlewnlvhjibid'; // Gunakan app password Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('youremail@gmail.com', 'Resto Reservation'); // Ganti sesuai email pengirim
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Your Reservation Has Been Received';
        $mail->Body = "
            <h3>Hi, $name!</h3>
            <p>Thank you for your reservation. We have received your request and it's now pending approval.</p>
            <h4>Reservation Details:</h4>
            <ul>
                <li><strong>Date & Time:</strong> $reservation_datetime</li>
                <li><strong>No. of People:</strong> $number_of_people</li>
                <li><strong>Special Request:</strong> $special_request</li>
            </ul>
            <p>You will receive a confirmation once your reservation is approved.</p>
            <br>
            <p>Regards, <br>Ocean's Feast Resto Management</p>
        ";

        $mail->send();
        header("Location: timeline.php?message=success_reservation");
        exit;

    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        header("Location: timeline.php?message=error");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">  
    <link rel="shortcut icon" href="../../img/hero.PNG">    
    <title>Ocean's Feast</title>
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="../../img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Libraries Stylesheet -->
    <link href="../../lib/animate/animate.min.css" rel="stylesheet">
    <link href="../../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../../lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../../css/style.css" rel="stylesheet">
</head>
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
                    <h1  style="color: #b884de;"><i class="fa fa-utensils me-3" style="color: #b884de;"></i>Krusty Krab</h1>
                    <!-- <img src="img/logo.png" alt="Logo"> -->
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto py-0 pe-4">
                        <a href="timeline.php" class="nav-item nav-link active">Home</a>
                        <a href="about.php" class="nav-item nav-link">About</a>
                        <a href="service.php" class="nav-item nav-link">Service</a>
                        <a href="menu.php" class="nav-item nav-link">Menu</a>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                            <div class="dropdown-menu m-0">
                                <a href="booking.php" class="dropdown-item">Booking</a>
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
                <div class="container my-5 py-5">
                    <div class="row align-items-center g-5">
                        <div class="col-lg-6 text-center text-lg-start">
                            <h1 class="display-3 text-white animated slideInLeft">
                                <?php
                                $username = htmlspecialchars($_SESSION['firstname'] ?? 'Guest');
                                echo "Hello, {$username}!<br>Enjoy Our<br>Delicious Meal";
                                ?>

                            </h1>
                            <p class="text-white animated slideInLeft mb-4 pb-2">
                                To serve the tastiest seafood with a smile! We’re all about fresh Krabby Patties, friendly service, and a fun underwater vibe. Dive in and enjoy the best of Bikini Bottom!
                            </p>
                            <p class="text-white animated slideInLeft mb-4 pb-2">
                                "Serving happiness, one Krabby Patty at a time!"
                            </p>
                            <a href="booking.php" class="btn btn-primary py-sm-3 px-sm-5 me-3 animated slideInLeft">Book A Table</a>
                        </div>
                        <div class="col-lg-6 text-center text-lg-end overflow-hidden">
                            <img class="img-fluid" src="../../img/hero.png" alt="Hero Image">
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- Navbar & Hero End -->


        <!-- Service Start -->
            <div class="container-xxl py-5">
                <div class="container">
                    <div class="row g-4">
                        <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.1s">
                            <div class="service-item rounded pt-3">
                                <div class="p-4">
                                    <i class="fa fa-3x fa-user-tie text-primary mb-4"></i>
                                    <h5>Master Chef</h5>
                                    <p>Crafted by professional chefs with world-class flavors.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.3s">
                            <div class="service-item rounded pt-3">
                                <div class="p-4">
                                    <i class="fa fa-3x fa-utensils text-primary mb-4"></i>
                                    <h5>Quality Food</h5>
                                    <p>Crafted by professional chefs with world-class flavors.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.5s">
                            <div class="service-item rounded pt-3">
                                <div class="p-4">
                                    <i class="fa fa-3x fa-cart-plus text-primary mb-4"></i>
                                    <h5>Online Order</h5>
                                    <p>Order anytime, anywhere, in just a few clicks.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.7s">
                            <div class="service-item rounded pt-3">
                                <div class="p-4">
                                    <i class="fa fa-3x fa-headset text-primary mb-4"></i>
                                    <h5>24/7 Service</h5>
                                    <p>Fast, reliable service — available 24/7 for you.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Service End -->

        <!-- About Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <div class="row g-3">
                            <div class="col-6 text-start">
                                <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.1s" src="../../img/about-1.jpg">
                            </div>
                            <div class="col-6 text-start">
                                <img class="img-fluid rounded w-75 wow zoomIn" data-wow-delay="0.3s" src="../../img/about-2.jpg" style="margin-top: 25%;">
                            </div>
                            <div class="col-6 text-end">
                                <img class="img-fluid rounded w-75 wow zoomIn" data-wow-delay="0.5s" src="../../img/about-3.jpg">
                            </div>
                            <div class="col-6 text-end">
                                <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.7s" src="../../img/about-4.jpg">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <h5 class="section-title ff-secondary text-start text-primary fw-normal">About Us</h5>
                        <h1 class="mb-4">Welcome to <i class="fa fa-utensils text-primary me-2"></i>Restoran</h1>
                        <p class="mb-4">Welcome to the Krusty Krab, Bikini Bottom's favorite underwater restaurant! Famous for our legendary Krabby Patty, made with a secret recipe, we serve fresh, delicious seafood in a fun and lively atmosphere.</p>
                        <p class="mb-4">Join us for a meal and experience why everyone loves the Krusty Krab! </p>
                        <div class="row g-4 mb-4">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center border-start border-5 border-primary px-3">
                                    <h1 class="flex-shrink-0 display-5 text-primary mb-0" data-toggle="counter-up">15</h1>
                                    <div class="ps-4">
                                        <p class="mb-0">Years of</p>
                                        <h6 class="text-uppercase mb-0">Experience</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center border-start border-5 border-primary px-3">
                                    <h1 class="flex-shrink-0 display-5 text-primary mb-0" data-toggle="counter-up">50</h1>
                                    <div class="ps-4">
                                        <p class="mb-0">Popular</p>
                                        <h6 class="text-uppercase mb-0">Master Chefs</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a class="btn btn-primary py-3 px-5 mt-2" href="">Read More</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- About End -->

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


<!-- Reservation Start -->
        <div class="container-xxl py-5 px-0 wow fadeInUp" data-wow-delay="0.1s">
            <div class="row g-0">
                <div class="col-md-6">
                    <div class="video">
                        <button type="button" class="btn-play" data-bs-toggle="modal" data-src="https://www.youtube.com/embed/DWRcNpR6Kdc" data-bs-target="#videoModal">
                            <span></span>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 bg-dark d-flex align-items-center">
                    <div class="p-5 wow fadeInUp" data-wow-delay="0.2s">
                        <h5 class="section-title ff-secondary text-start text-primary fw-normal">Reservation</h5>
                        <h1 class="text-white mb-4">Book A Table Online</h1>
                        <form action="timeline.php" method="POST">
  <div class="row g-3">
    <div class="col-md-6">
      <div class="form-floating">
        <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
        <label for="name">Your Name</label>
      </div>
    </div>

    <div class="col-md-6">
      <div class="form-floating">
        <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" required>
        <label for="email">Your Email</label>
      </div>
    </div>

    <div class="col-md-6">
      <div class="form-floating">
        <input type="text" class="form-control" id="phone" name="phone"
               placeholder="Your Phone" pattern="^\+?\d{8,15}$"
               title="Enter a valid phone number with digits and optional +" required>
        <label for="phone">Your Phone</label>
      </div>
    </div>

    <div class="col-md-6">
      <div class="form-floating">
        <input type="number" class="form-control" id="number_of_people" name="number_of_people"
               placeholder="Number of People" min="1" max="10" required>
        <label for="number_of_people">No Of People</label>
      </div>
    </div>

    <div class="col-md-12">
      <div class="form-floating date" id="date3" data-target-input="nearest">
        <input type="text" class="form-control datetimepicker-input" id="datetime" name="datetime"
               placeholder="Date & Time" data-target="#date3" data-toggle="datetimepicker" required>
        <label for="datetime">Date & Time</label>
      </div>
    </div>

    <div class="col-12">
      <div class="form-floating">
        <textarea class="form-control" placeholder="Special Request" id="message" name="message"
                  style="height: 100px"></textarea>
        <label for="message">Special Request</label>
      </div>
    </div>

    <div class="col-12">
      <button class="btn btn-primary w-100 py-3" type="submit">Book Now</button>
    </div>
  </div>
</form>




                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content rounded-0">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Youtube Video</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- 16:9 aspect ratio -->
                        <div class="ratio ratio-16x9">
                            <iframe class="embed-responsive-item" src="" id="video" allowfullscreen allowscriptaccess="always"
                                allow="autoplay"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Reservation Start -->


        <!-- Team Start -->
        <div class="container-xxl pt-5 pb-3">
            <div class="container">
                <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                    <h5 class="section-title ff-secondary text-center text-primary fw-normal">Team Members</h5>
                    <h1 class="mb-5">Our Team </h1>
                </div>
                <div class="row g-4">
                    <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                        <div class="team-item text-center rounded overflow-hidden">
                            <div class="rounded-circle overflow-hidden m-4">
                                <img class="img-fluid" src="../../img/team-1.jpg" alt="">
                            </div>
                            <h5 class="mb-0">Mr Krab </h5>
                            <small>Owner</small>
                            <div class="d-flex justify-content-center mt-3">
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                        <div class="team-item text-center rounded overflow-hidden">
                            <div class="rounded-circle overflow-hidden m-4">
                                <img class="img-fluid" src="../../img/team-2.jpg" alt="">
                            </div>
                            <h5 class="mb-0">Squidward</h5>
                            <small>Cashier</small>
                            <div class="d-flex justify-content-center mt-3">
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
                        <div class="team-item text-center rounded overflow-hidden">
                            <div class="rounded-circle overflow-hidden m-4">
                                <img class="img-fluid" src="../../img/team-3.jpg" alt="">
                            </div>
                            <h5 class="mb-0">Spongebob</h5>
                            <small>Chef</small>
                            <div class="d-flex justify-content-center mt-3">
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.7s">
                        <div class="team-item text-center rounded overflow-hidden">
                            <div class="rounded-circle overflow-hidden m-4">
                                <img class="img-fluid" src="../../img/team-4.jpg" alt="">
                            </div>
                            <h5 class="mb-0">Patrick</h5>
                            <small>Intern</small>
                            <div class="d-flex justify-content-center mt-3">
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-square btn-primary mx-1" href=""><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Team End -->


        <!-- Testimonial Start -->
        <div class="container-xxl py-5 wow fadeInUp" data-wow-delay="0.1s">
            <div class="container">
                <div class="text-center">
                    <h5 class="section-title ff-secondary text-center text-primary fw-normal">Testimonial</h5>
                    <h1 class="mb-5">Our Clients Say!!!</h1>
                </div>
                <div class="owl-carousel testimonial-carousel">
                    <div class="testimonial-item bg-transparent border rounded p-4">
                        <i class="fa fa-quote-left fa-2x text-primary mb-3"></i>
                        <p>The food was delicious, especially the Krabby Patty! Service was fast, but the place was a bit crowded. Great for hanging out with friends or family.</p>
                        <div class="d-flex align-items-center">
                            <img class="img-fluid flex-shrink-0 rounded-circle" src="../../img/testimonial-1.jpg" style="width: 50px; height: 50px;">
                            <div class="ps-3">
                                <h5 class="mb-1">Sandals</h5>
                                <small>Seller</small>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-item bg-transparent border rounded p-4">
                        <i class="fa fa-quote-left fa-2x text-primary mb-3"></i>
                        <p>The restaurant has a unique underwater vibe. The food was fresh and the portions were generous. Prices were reasonable for the quality.

                        </p>
                        <div class="d-flex align-items-center">
                            <img class="img-fluid flex-shrink-0 rounded-circle" src="../../img/testimonial-2.jpg" style="width: 50px; height: 50px;">
                            <div class="ps-3">
                                <h5 class="mb-1">Sandy</h5>
                                <small>Science</small>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-item bg-transparent border rounded p-4">
                        <i class="fa fa-quote-left fa-2x text-primary mb-3"></i>
                        <p>The food was good, but the wait time was a bit long. The place was clean, but the drink menu lacked variety.

                        </p>
                        <div class="d-flex align-items-center">
                            <img class="img-fluid flex-shrink-0 rounded-circle" src="../../img/testimonial-3.jpg" style="width: 50px; height: 50px;">
                            <div class="ps-3">
                                <h5 class="mb-1">Not Petterson</h5>
                                <small>Glove Employee </small>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-item bg-transparent border rounded p-4">
                        <i class="fa fa-quote-left fa-2x text-primary mb-3"></i>
                        <p>Such a fun dining experience! The Krusty Crab burger is a must-try. Only downside was the limited parking space. Will definitely come back!</p>
                        <div class="d-flex align-items-center">
                            <img class="img-fluid flex-shrink-0 rounded-circle" src="../../img/testimonial-4.jpg" style="width: 50px; height: 50px;">
                            <div class="ps-3">
                                <h5 class="mb-1">Miss Puff</h5>
                                <small>Teacher</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Testimonial End -->
        
        

        <!-- Footer Start -->
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
							
							<!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. If you'd like to use the template without the footer author’s credit link/attribution link/backlink, you can purchase the Credit Removal License from "https://phpcodex.com/credit-removal". Thank you for your support. ***/-->
							Designed By <a class="border-bottom" href="https://phpcodex.com">php Codex</a><br><br>
                            Distributed By <a class="border-bottom" href="https://themewagon.com" target="_blank">ThemeWagon</a>
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <div class="footer-menu">
                                <a href="https://www.instagram.com/nivianurkh">Instagram Nivia</a>
                                <a href="https://www.instagram.com/heyitsshanin">Instagram Anisa</a>
                                <a href="https://api.whatsapp.com/qr/EXGON2CIPGQMK1?autoload=1&app_absent=0">WhatsApp Nivia</a>
                                <a href="">WhatsApp Anisa</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->

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

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../lib/wow/wow.min.js"></script>
    <script src="../../lib/easing/easing.min.js"></script>
    <script src="../../lib/waypoints/waypoints.min.js"></script>
    <script src="../../lib/counterup/counterup.min.js"></script>
    <script src="../../lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="../../lib/tempusdominus/js/moment.min.js"></script>
    <script src="../../lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="../../lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Template Javascript -->
    <script src="../../js/main.js"></script>
    
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