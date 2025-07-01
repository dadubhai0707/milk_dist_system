<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Get date and optional seller_id from query parameters, default date to today (July 1, 2025)
$date = isset($_GET['date']) ? $_GET['date'] : '2025-07-01';
$seller_id = isset($_GET['seller_id']) ? $_GET['seller_id'] : null;

try {
    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid date format. Use YYYY-MM-DD."
        ]);
        exit;
    }

    if ($seller_id === null) {
        // Mode 1: Fetch all sellers with their assigned, distributed, and remaining milk for the specific date
        $sql = "SELECT 
                    s.Seller_id,
                    s.Name AS Seller_name,
                    s.Vehicle_no,
                    COALESCE(SUM(a.Assigned_quantity), 0) AS assigned_milk,
                    COALESCE(SUM(d.Quantity), 0) AS distributed_milk
                FROM tbl_seller s
                LEFT JOIN tbl_milk_assignment a ON s.Seller_id = a.Seller_id AND a.Date = ?
                LEFT JOIN tbl_milk_delivery d ON s.Seller_id = d.Seller_id AND DATE(d.DateTime) = ?
                GROUP BY s.Seller_id, s.Name, s.Vehicle_no";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "ss", $date, $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $sellers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $assigned = floatval($row['assigned_milk']);
            $distributed = floatval($row['distributed_milk']);
            $sellers[] = [
                "seller_id" => $row['Seller_id'],
                "seller_name" => $row['Seller_name'],
                "vehicle_no" => $row['Vehicle_no'],
                "assigned_milk" => $assigned,
                "distributed_milk" => $distributed,
                "remaining_milk" => $assigned - $distributed
            ];
        }

        echo json_encode([
            "status" => "success",
            "data" => $sellers
        ]);

        mysqli_stmt_close($stmt);
    } else {
        // Mode 2: Fetch delivery details for a specific seller for the specific date
        if (!is_numeric($seller_id)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid seller_id."
            ]);
            exit;
        }

        // Fetch delivery details
        $sql = "SELECT 
                    d.Delivery_id,
                    d.DateTime,
                    d.Quantity,
                    s.Seller_id,
                    s.Name AS Seller_name,
                    s.Vehicle_no,
                    c.Name AS Customer_name,
                    c.Contact AS Customer_contact,
                    c.Price AS Customer_price,
                    a.Address AS Customer_address
                FROM tbl_milk_delivery d
                JOIN tbl_seller s ON d.Seller_id = s.Seller_id
                JOIN tbl_customer c ON d.Customer_id = c.Customer_id
                JOIN tbl_address a ON c.Address_id = a.Address_id
                WHERE DATE(d.DateTime) = ? AND d.Seller_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare delivery statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "si", $date, $seller_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $deliveries = [];
        $distributed_milk = 0;

        while ($row = mysqli_fetch_assoc($result)) {
            $quantity = floatval($row['Quantity']);
            $distributed_milk += $quantity;

            $deliveries[] = [
                "delivery_id" => $row['Delivery_id'],
                "date_time" => $row['DateTime'],
                "quantity" => $quantity,
                "seller_id" => $row['Seller_id'],
                "seller_name" => $row['Seller_name'],
                "vehicle_no" => $row['Vehicle_no'],
                "customer_name" => $row['Customer_name'],
                "customer_contact" => $row['Customer_contact'],
                "customer_price" => floatval($row['Customer_price']),
                "customer_address" => $row['Customer_address']
            ];
        }

        mysqli_stmt_close($stmt);

        // Fetch assigned milk for the specific date
        $sql_assignment = "SELECT COALESCE(SUM(Assigned_quantity), 0) AS assigned_milk
                           FROM tbl_milk_assignment
                           WHERE Seller_id = ? AND Date = ?";
        $stmt_assignment = mysqli_prepare($conn, $sql_assignment);
        if ($stmt_assignment === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare assignment statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt_assignment, "is", $seller_id, $date);
        mysqli_stmt_execute($stmt_assignment);
        $result_assignment = mysqli_stmt_get_result($stmt_assignment);
        $assignment_row = mysqli_fetch_assoc($result_assignment);
        $assigned_milk = floatval($assignment_row['assigned_milk']);
        mysqli_stmt_close($stmt_assignment);

        // Calculate remaining milk
        $remaining_milk = $assigned_milk - $distributed_milk;

        echo json_encode([
            "status" => "success",
            "data" => $deliveries,
            "seller_totals" => [
                "seller_id" => $seller_id,
                "assigned_milk" => $assigned_milk,
                "distributed_milk" => $distributed_milk,
                "remaining_milk" => $remaining_milk
            ]
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error: " . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>