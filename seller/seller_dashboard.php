<?php
header("Content-Type: application/json");
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
    exit;
}

if (empty($_GET['seller_id'])) {
    echo json_encode(["status" => "error", "message" => "Seller ID is required"]);
    exit;
}

$seller_id = intval($_GET['seller_id']);
$today = date('Y-m-d'); // 2025-06-15

try {
    // Fetch seller's name
    $seller_sql = "SELECT Name FROM tbl_seller WHERE Seller_id = ?";
    $seller_stmt = mysqli_prepare($conn, $seller_sql);
    mysqli_stmt_bind_param($seller_stmt, "i", $seller_id);
    mysqli_stmt_execute($seller_stmt);
    $seller_result = mysqli_stmt_get_result($seller_stmt);
    
    if (mysqli_num_rows($seller_result) === 0) {
        echo json_encode(["status" => "error", "message" => "Seller not found"]);
        mysqli_stmt_close($seller_stmt);
        exit;
    }
    
    $seller = mysqli_fetch_assoc($seller_result);
    $seller_name = $seller['Name'];
    mysqli_stmt_close($seller_stmt);

    // Fetch today's assigned milk
    $assign_sql = "SELECT COALESCE(SUM(Assigned_quantity), 0) AS total_assigned
                   FROM tbl_milk_assignment
                   WHERE Seller_id = ? AND Date = ?";
    $assign_stmt = mysqli_prepare($conn, $assign_sql);
    mysqli_stmt_bind_param($assign_stmt, "is", $seller_id, $today);
    mysqli_stmt_execute($assign_stmt);
    $assign_result = mysqli_stmt_get_result($assign_stmt);
    $assign_data = mysqli_fetch_assoc($assign_result);
    $total_assigned = $assign_data['total_assigned'];
    mysqli_stmt_close($assign_stmt);

    // Fetch delivery locations (distinct addresses)
    $delivery_sql = "SELECT DISTINCT a.Address
                     FROM tbl_milk_delivery d
                     JOIN tbl_customer c ON d.Customer_id = c.Customer_id
                     JOIN tbl_address a ON c.Address_id = a.Address_id
                     WHERE d.Seller_id = ? AND DATE(d.DateTime) = ?";
    $delivery_stmt = mysqli_prepare($conn, $delivery_sql);
    mysqli_stmt_bind_param($delivery_stmt, "is", $seller_id, $today);
    mysqli_stmt_execute($delivery_stmt);
    $delivery_result = mysqli_stmt_get_result($delivery_stmt);
    
    $delivery_locations = [];
    while ($row = mysqli_fetch_assoc($delivery_result)) {
        $delivery_locations[] = $row['Address'];
    }
    mysqli_stmt_close($delivery_stmt);

    echo json_encode([
        "status" => "success",
        "data" => [
            "seller_name" => $seller_name,
            "total_assigned" => floatval($total_assigned),
            "delivery_locations" => $delivery_locations
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch data: " . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>