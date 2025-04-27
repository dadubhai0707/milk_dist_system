<?php
header("Content-Type: application/json");
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';
$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 3;

switch ($path) {
    case 'monthly_consumption':
        if ($method === 'GET') {
            $customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

            if ($customer_id === 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Customer ID is required"
                ]);
                exit;
            }

            try {
                // Current month
                $sql_current = "SELECT COALESCE(SUM(d.Quantity), 0) as total_quantity, c.Price
                               FROM tbl_milk_delivery d
                               LEFT JOIN tbl_customer c ON d.Customer_id = c.Customer_id
                               WHERE d.Customer_id = ? AND YEAR(d.DateTime) = ? AND MONTH(d.DateTime) = ?";
                $stmt_current = mysqli_prepare($conn, $sql_current);
                mysqli_stmt_bind_param($stmt_current, "iii", $customer_id, $year, $month);
                mysqli_stmt_execute($stmt_current);
                $result_current = mysqli_stmt_get_result($stmt_current);
                $row_current = mysqli_fetch_assoc($result_current);
                $current_quantity = (float)$row_current['total_quantity'];
                $price_per_liter = $row_current['Price'] ? (float)$row_current['Price'] : 64.0;
                mysqli_stmt_close($stmt_current);

                // Previous month
                $prev_month = $month - 1;
                $prev_year = $year;
                if ($prev_month === 0) {
                    $prev_month = 12;
                    $prev_year--;
                }
                $sql_prev = "SELECT COALESCE(SUM(d.Quantity), 0) as total_quantity
                             FROM tbl_milk_delivery d
                             WHERE d.Customer_id = ? AND YEAR(d.DateTime) = ? AND MONTH(d.DateTime) = ?";
                $stmt_prev = mysqli_prepare($conn, $sql_prev);
                mysqli_stmt_bind_param($stmt_prev, "iii", $customer_id, $prev_year, $prev_month);
                mysqli_stmt_execute($stmt_prev);
                $result_prev = mysqli_stmt_get_result($stmt_prev);
                $row_prev = mysqli_fetch_assoc($result_prev);
                $prev_quantity = (float)$row_prev['total_quantity'];
                mysqli_stmt_close($stmt_prev);

                // Daily records for current month
                $sql_daily = "SELECT DATE(d.DateTime) as date, d.Quantity
                              FROM tbl_milk_delivery d
                              WHERE d.Customer_id = ? AND YEAR(d.DateTime) = ? AND MONTH(d.DateTime) = ?
                              ORDER BY d.DateTime DESC";
                $stmt_daily = mysqli_prepare($conn, $sql_daily);
                mysqli_stmt_bind_param($stmt_daily, "iii", $customer_id, $year, $month);
                mysqli_stmt_execute($stmt_daily);
                $result_daily = mysqli_stmt_get_result($stmt_daily);
                $daily_records = [];
                while ($row = mysqli_fetch_assoc($result_daily)) {
                    $daily_records[] = [
                        'date' => $row['date'],
                        'quantity' => (float)$row['Quantity']
                    ];
                }
                mysqli_stmt_close($stmt_daily);

                echo json_encode([
                    "status" => "success",
                    "message" => "Monthly consumption fetched successfully",
                    "data" => [
                        "current_month" => [
                            "year" => $year,
                            "month" => $month,
                            "total_quantity" => $current_quantity,
                            "total_price" => $current_quantity * $price_per_liter,
                            "price_per_liter" => $price_per_liter, // Added for clarity
                            "daily_records" => $daily_records
                        ],
                        "previous_month" => [
                            "year" => $prev_year,
                            "month" => $prev_month,
                            "total_quantity" => $prev_quantity,
                            "total_price" => $prev_quantity * $price_per_liter,
                            "price_per_liter" => $price_per_liter // Added for clarity
                        ]
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch monthly consumption: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    // Other cases (distribution_details, total_distributed) remain unchanged
    default:
        echo json_encode([
            "status" => "error",
            "message" => "Invalid endpoint"
        ]);
        break;
}

mysqli_close($conn);
?>