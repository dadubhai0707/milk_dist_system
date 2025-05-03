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
$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : null;

switch ($path) {
    case 'distribution_details':
        if ($method === 'GET') {
            if (!$seller_id) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Seller ID is required"
                ]);
                exit;
            }

            $date = isset($_GET['date']) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : '';

            // Validate date format if provided
            if ($date && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }

            try {
                // Query to get distribution details for the seller
                $sql = "SELECT d.Delivery_id, d.Seller_id, d.Customer_id, c.Name, c.Contact, c.Address, c.Price, d.Quantity, d.DateTime AS Distribution_date
                        FROM tbl_milk_delivery d
                        LEFT JOIN tbl_customer c ON d.Customer_id = c.Customer_id
                        WHERE d.Seller_id = ?";
                if ($date) {
                    $sql .= " AND DATE(d.DateTime) = ?";
                }
                $sql .= " ORDER BY d.DateTime DESC";

                $stmt = mysqli_prepare($conn, $sql);
                if ($date) {
                    mysqli_stmt_bind_param($stmt, "is", $seller_id, $date);
                } else {
                    mysqli_stmt_bind_param($stmt, "i", $seller_id);
                }
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                $distributions = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $row['Quantity'] = (float)$row['Quantity'];
                    $row['Price'] = (float)$row['Price'];
                    $distributions[] = $row;
                }
                mysqli_stmt_close($stmt);

                echo json_encode([
                    "status" => "success",
                    "message" => "Seller distribution details fetched successfully",
                    "data" => $distributions
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch seller distribution details: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method. Use GET"
            ]);
        }
        break;

    case 'total_distributed':
        if ($method === 'GET') {
            if (!$seller_id || !$date) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Seller ID and date are required"
                ]);
                exit;
            }

            // Validate date format
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }

            try {
                // Query to get total distributed quantity for the seller on the given date
                $sql = "SELECT SUM(d.Quantity) AS total_quantity
                        FROM tbl_milk_delivery d
                        WHERE d.Seller_id = ? AND DATE(d.DateTime) = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "is", $seller_id, $date);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                $total_quantity = (float)($row['total_quantity'] ?? 0);
                mysqli_stmt_close($stmt);

                echo json_encode([
                    "status" => "success",
                    "message" => "Total distributed quantity fetched successfully",
                    "data" => [
                        "total_quantity" => $total_quantity
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch total distributed quantity: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method. Use GET"
            ]);
        }
        break;

    case 'customer_distribution_details':
        // Existing code for customer distribution details (unchanged)
        if ($method === 'GET') {
            $customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
            if (!$customer_id) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Customer ID is required"
                ]);
                exit;
            }

            $date = isset($_GET['date']) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : '';

            if ($date && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }

            try {
                $sql = "SELECT d.Delivery_id, d.Seller_id, s.Name AS Seller_name, d.Quantity, d.DateTime AS Distribution_date
                        FROM tbl_milk_delivery d
                        LEFT JOIN tbl_seller s ON d.Seller_id = s.Seller_id
                        WHERE d.Customer_id = ?";
                if ($date) {
                    $sql .= " AND DATE(d.DateTime) = ?";
                }
                $sql .= " ORDER BY d.DateTime DESC";

                $stmt = mysqli_prepare($conn, $sql);
                if ($date) {
                    mysqli_stmt_bind_param($stmt, "is", $customer_id, $date);
                } else {
                    mysqli_stmt_bind_param($stmt, "i", $customer_id);
                }
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                $distributions = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $row['Quantity'] = (float)$row['Quantity'];
                    $distributions[] = $row;
                }
                mysqli_stmt_close($stmt);

                echo json_encode([
                    "status" => "success",
                    "message" => "Customer distribution details fetched successfully",
                    "data" => $distributions
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch customer distribution details: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method. Use GET"
            ]);
        }
        break;

    default:
        echo json_encode([
            "status" => "error",
            "message" => "Invalid endpoint"
        ]);
        break;
}

mysqli_close($conn);
?>