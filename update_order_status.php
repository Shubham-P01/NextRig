<?php
// filepath: c:\wamp64\www\SSP Project\update_order_status.php
require 'connection.php';

if (isset($_GET['order_id']) && isset($_GET['status'])) {
    $order_id = $_GET['order_id'];
    $new_status = $_GET['status'];

    // Map status to corresponding date column
    $date_columns = [
        'Order Confirmed' => 'order_date',
        'Processing' => 'processing_date',
        'Shipped' => 'shipped_date',
        'Out for Delivery' => 'out_for_delivery_date',
        'Delivered' => 'delivered_date'
    ];

    $date_column = $date_columns[$new_status] ?? null;

    // Prepare SQL
    if ($date_column) {
        $sql = "UPDATE orders 
                SET order_status = ?, $date_column = NOW() 
                WHERE order_id = ?";
    } else {
        // for 'Cancelled' or invalid statuses
        $sql = "UPDATE orders 
                SET order_status = ? 
                WHERE order_id = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_status, $order_id]);

    // Redirect back to admin page
    header("Location: track_admin.php");
    exit();
} else {
    echo "<h2 style='color:red;text-align:center;margin-top:50px;'>Invalid Request!</h2>";
}
?>
