<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_pelanggan'])) {
    echo "You must log in first.";
    exit;
}

$id_pelanggan = $_SESSION['id_pelanggan'];
$limit = 4;

// Delivery Pagination
$page_delivery = isset($_GET['page_delivery']) ? (int)$_GET['page_delivery'] : 1;
$start_delivery = ($page_delivery - 1) * $limit;

$count_delivery = $db->prepare("SELECT COUNT(*) FROM delivery_orders WHERE id_pelanggan = ?");
$count_delivery->execute([$id_pelanggan]);
$total_delivery_rows = $count_delivery->fetchColumn();
$total_delivery_pages = ceil($total_delivery_rows / $limit);

$query_delivery = $db->prepare("SELECT * FROM delivery_orders WHERE id_pelanggan = ? ORDER BY order_date DESC, order_time DESC LIMIT $start_delivery, $limit");
$query_delivery->execute([$id_pelanggan]);
$delivery_orders = $query_delivery->fetchAll();

// Dine-In Pagination
$page_dinein = isset($_GET['page_dinein']) ? (int)$_GET['page_dinein'] : 1;
$start_dinein = ($page_dinein - 1) * $limit;

$count_dinein = $db->prepare("SELECT COUNT(*) FROM orders WHERE id_pelanggan = ? AND order_type = 'dine-in'");
$count_dinein->execute([$id_pelanggan]);
$total_dinein_rows = $count_dinein->fetchColumn();
$total_dinein_pages = ceil($total_dinein_rows / $limit);

$query_dinein = $db->prepare("SELECT * FROM orders WHERE id_pelanggan = ? AND order_type = 'dine-in' ORDER BY created_at DESC LIMIT $start_dinein, $limit");
$query_dinein->execute([$id_pelanggan]);
$dinein_orders = $query_dinein->fetchAll();

function renderPagination($totalPages, $currentPage, $paramName, $anchor) {
    if ($totalPages <= 1) return;

    $adjacents = 2;
    $paginationHTML = '<nav><ul class="pagination justify-content-center mt-4">';

    if ($currentPage > 1) {
        $paginationHTML .= '<li class="page-item"><a class="page-link" style="color:#b884de;" href="?' . $paramName . '=1#' . $anchor . '">&laquo;</a></li>';
        $paginationHTML .= '<li class="page-item"><a class="page-link" style="color:#b884de;" href="?' . $paramName . '=' . ($currentPage - 1) . '#' . $anchor . '">&lsaquo;</a></li>';
    }

    if ($totalPages <= 7 + ($adjacents * 2)) {
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $paginationHTML .= '<li class="page-item ' . $active . '"><a class="page-link" style="background-color:' . ($active ? '#b884de' : 'transparent') . '; color:' . ($active ? '#fff' : '#b884de') . ';" href="?' . $paramName . '=' . $i . '#' . $anchor . '">' . $i . '</a></li>';
        }
    } else {
        if ($currentPage <= 4) {
            for ($i = 1; $i <= 5; $i++) {
                $active = ($i == $currentPage) ? 'active' : '';
                $paginationHTML .= '<li class="page-item ' . $active . '"><a class="page-link" style="background-color:' . ($active ? '#b884de' : 'transparent') . '; color:' . ($active ? '#fff' : '#b884de') . ';" href="?' . $paramName . '=' . $i . '#' . $anchor . '">' . $i . '</a></li>';
            }
            $paginationHTML .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            $paginationHTML .= '<li class="page-item"><a class="page-link" style="color:#b884de;" href="?' . $paramName . '=' . $totalPages . '#' . $anchor . '">' . $totalPages . '</a></li>';
        } elseif ($currentPage > $totalPages - 4) {
            $paginationHTML .= '<li class="page-item"><a class="page-link" style="color:#b884de;" href="?' . $paramName . '=1#' . $anchor . '">1</a></li>';
            $paginationHTML .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            for ($i = $totalPages - 4; $i <= $totalPages; $i++) {
                $active = ($i == $currentPage) ? 'active' : '';
                $paginationHTML .= '<li class="page-item ' . $active . '"><a class="page-link" style="background-color:' . ($active ? '#b884de' : 'transparent') . '; color:' . ($active ? '#fff' : '#b884de') . ';" href="?' . $paramName . '=' . $i . '#' . $anchor . '">' . $i . '</a></li>';
            }
        } else {
            $paginationHTML .= '<li class="page-item"><a class="page-link" style="color:#b884de;" href="?' . $paramName . '=1#' . $anchor . '">1</a></li>';
            $paginationHTML .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++) {
                $active = ($i == $currentPage) ? 'active' : '';
                $paginationHTML .= '<li class="page-item ' . $active . '"><a class="page-link" style="background-color:' . ($active ? '#b884de' : 'transparent') . '; color:' . ($active ? '#fff' : '#b884de') . ';" href="?' . $paramName . '=' . $i . '#' . $anchor . '">' . $i . '</a></li>';
            }
            $paginationHTML .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            $paginationHTML .= '<li class="page-item"><a class="page-link" style="color:#b884de;" href="?' . $paramName . '=' . $totalPages . '#' . $anchor . '">' . $totalPages . '</a></li>';
        }
    }

    if ($currentPage < $totalPages) {
        $paginationHTML .= '<li class="page-item"><a class="page-link" style="color:#b884de;" href="?' . $paramName . '=' . ($currentPage + 1) . '#' . $anchor . '">&rsaquo;</a></li>';
        $paginationHTML .= '<li class="page-item"><a class="page-link" style="color:#b884de;" href="?' . $paramName . '=' . $totalPages . '#' . $anchor . '">&raquo;</a></li>';
    }

    $paginationHTML .= '</ul></nav>';
    echo $paginationHTML;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Order History | Ocean's Feast</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    body {
      background-color: #f5f5f5;
      font-family: Arial;
      padding-top: 20px;
    }

    .order-card {
      background: #f9f9f9;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
      cursor: pointer;
      border: 1px solid #ddd;
    }

    .order-status {
      background-color: #e0f7e9;
      color: #2e7d32;
      display: inline-block;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: bold;
    }

    .nav-tabs .nav-link.active {
      background-color: #b884de;
      color: #fff;
    }

    .nav-tabs .nav-link {
      color: #b884de;
      font-weight: bold;
    }

    .nav-tabs .nav-link i {
      margin-right: 6px;
    }

    .modal-content p {
      font-size: 15px;
    }

    .btn-back {
      display: inline-block;
      margin-bottom: 15px;
      background-color: #b884de;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      text-decoration: none;
    }

    .btn-back:hover {
      background-color: #a06fcb;
      color: white;
    }
  </style>
</head>
<body>

<div class="container">
  <a href="menu.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Menu</a>
  <h2 class="text-center mb-4 fw-bold">Order History</h2>

  <!-- Tabs -->
  <ul class="nav nav-tabs justify-content-center mb-4" id="orderTabs" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" id="delivery-tab" data-bs-toggle="tab" data-bs-target="#delivery" type="button" role="tab">
        <i class="fa-solid fa-truck"></i> Delivery
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" id="dinein-tab" data-bs-toggle="tab" data-bs-target="#dinein" type="button" role="tab">
        <i class="fa-solid fa-utensils"></i> Dine-In
      </button>
    </li>
  </ul>

  <div class="tab-content" id="orderTabsContent">
    <!-- DELIVERY TAB -->
    <div class="tab-pane fade show active" id="delivery" role="tabpanel">
      <?php foreach ($delivery_orders as $order): ?>
        <div class="order-card" data-bs-toggle="modal" data-bs-target="#modalDelivery<?= $order['id'] ?>">
          <p><strong>Delivery ID:</strong> <?= $order['id'] ?></p>
          <p><strong>Date:</strong> <?= $order['order_date'] ?> <?= $order['order_time'] ?></p>
          <p><strong>Address:</strong> <?= $order['address'] ?></p>
          <p><strong>Total:</strong> Rp<?= number_format($order['total_price'], 0, ',', '.') ?></p>
          <p><strong>Status:</strong> <span class="order-status"><?= $order['status'] ?: 'Unavailable' ?></span></p>
        </div>

        <!-- Modal Delivery -->
        <div class="modal fade" id="modalDelivery<?= $order['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">
              <div class="modal-header">
                <h5 class="modal-title">Delivery Details #<?= $order['id'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p><strong>Date:</strong> <?= $order['order_date'] ?> <?= $order['order_time'] ?></p>
                <p><strong>Address:</strong> <?= $order['address'] ?></p>
                <p><strong>Total:</strong> Rp<?= number_format($order['total_price'], 0, ',', '.') ?></p>
                <p><strong>Status:</strong> <?= $order['status'] ?: 'Unavailable' ?></p>
                <hr>
                <p><strong>Food Details:</strong></p>
                <ul>
                  <?php
                    $delivery_items = $db->prepare("SELECT m.menu_name, doi.quantity 
                                                    FROM delivery_order_items doi
                                                    JOIN menu m ON m.id = doi.menu_id
                                                    WHERE doi.delivery_id = ?");
                    $delivery_items->execute([$order['id']]);
                    $items = $delivery_items->fetchAll();
                    if ($items):
                      foreach ($items as $item):
                  ?>
                    <li><?= $item['menu_name'] ?> x<?= $item['quantity'] ?></li>
                  <?php endforeach; else: ?>
                    <li>No food data available.</li>
                  <?php endif; ?>
                </ul>

                <hr>
                <a href="tracking_delivery.php?order_id=<?= $order['midtrans_order_id'] ?>" target="_blank" class="btn" style="background-color: #b884de; color: #fff;">
                  <i class="fa-solid fa-truck"></i> Track Your Order
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php renderPagination($total_delivery_pages, $page_delivery, 'page_delivery', 'delivery'); ?>

    </div>

    <!-- DINE-IN TAB -->
    <div class="tab-pane fade" id="dinein" role="tabpanel">
      <?php foreach ($dinein_orders as $order): 
        $id = $order['id_order'];
        $items = $db->prepare("SELECT m.menu_name, oi.quantity 
                               FROM order_items oi 
                               JOIN menu m ON m.id = oi.id_menu 
                               WHERE oi.id_order = ?");
        $items->execute([$id]);
        $menu_items = $items->fetchAll();
      ?>
        <div class="order-card" data-bs-toggle="modal" data-bs-target="#modalDine<?= $id ?>">
          <p><strong>Order ID:</strong> <?= $id ?></p>
          <p><strong>Date:</strong> <?= $order['created_at'] ?></p>
          <p><strong>Total:</strong> Rp<?= number_format($order['total_price'], 0, ',', '.') ?></p>
          <p><strong>Status:</strong> <span class="order-status">Paid</span></p>
        </div>

        <!-- Modal Dine-In -->
        <div class="modal fade" id="modalDine<?= $id ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">
              <div class="modal-header">
                <h5 class="modal-title">Dine-In Details #<?= $id ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p><strong>Date:</strong> <?= $order['created_at'] ?></p>
                <p><strong>Total:</strong> Rp<?= number_format($order['total_price'], 0, ',', '.') ?></p>
                <p><strong>Status:</strong> Paid</p>
                <hr>
                <p><strong>Food Details:</strong></p>
                <ul>
                  <?php foreach ($menu_items as $item): ?>
                    <li><?= $item['menu_name'] ?> x<?= $item['quantity'] ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php renderPagination($total_dinein_pages, $page_dinein, 'page_dinein', 'dinein'); ?>
    </div>
  </div>
</div>

</body> 
</html>
