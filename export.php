<?php
// export.php — Export all Kopipiro orders to Excel (.xls)
include 'auth.php';
include 'config.php';
require_owner();

// Send headers to force browser download as Excel sheet
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Penjualan_Kopipiro_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

$all_orders = mysqli_query($conn, "SELECT o.*, u.username as cashier_name, c.username as customer_username 
                                   FROM orders o 
                                   LEFT JOIN users u ON o.cashier_id = u.id 
                                   LEFT JOIN users c ON o.customer_id = c.id 
                                   ORDER BY o.id DESC");
?>
<table border="1">
    <thead>
        <tr style="background-color: #333333; color: #ffffff;">
            <th>No</th>
            <th>Order ID</th>
            <th>Date & Time</th>
            <th>Customer Name</th>
            <th>Member Account</th>
            <th>Items Ordered</th>
            <th>Regular Total (Rp)</th>
            <th>Discount (Rp)</th>
            <th>Final Paid (Rp)</th>
            <th>Assigned Cashier</th>
            <th>Order Status</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while ($row = mysqli_fetch_assoc($all_orders)) {
            // Get item summaries
            $it_res = mysqli_query($conn, "SELECT product_name, quantity FROM order_items WHERE order_id = {$row['id']}");
            $item_strs = [];
            while ($it = mysqli_fetch_assoc($it_res)) {
                $item_strs[] = $it['product_name'] . " (x" . $it['quantity'] . ")";
            }
            $items_text = implode(', ', $item_strs);
            $order_code = "#" . str_pad($row['id'], 5, '0', STR_PAD_LEFT);
            $member_acc = !empty($row['customer_username']) ? $row['customer_username'] : 'Guest';
            $cashier_acc = !empty($row['cashier_name']) ? $row['cashier_name'] : 'Unassigned';

            echo "<tr>
                <td>{$no}</td>
                <td>'{$order_code}</td>
                <td>{$row['order_date']}</td>
                <td>{$row['customer_name']}</td>
                <td>{$member_acc}</td>
                <td>{$items_text}</td>
                <td>{$row['total_amount']}</td>
                <td>{$row['discount_amount']}</td>
                <td>{$row['final_amount']}</td>
                <td>{$cashier_acc}</td>
                <td>{$row['status']}</td>
            </tr>";
            $no++;
        }
        ?>
    </tbody>
</table>
