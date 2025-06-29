<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $sql = "SELECT Seller_id, Name, Vehicle_no FROM tbl_seller";
    $result = mysqli_query($conn, $sql);

    $sellers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sellers[] = [
            "seller_id" => $row['Seller_id'],
            "name" => $row['Name'],
            "vehicle_no" => $row['Vehicle_no']
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => $sellers
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error: " . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>