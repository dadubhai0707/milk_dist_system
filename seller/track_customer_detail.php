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
$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 3; // Default to 3

switch ($path) {
    case 'distribution_details':
        if ($method === 'GET') {
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
                // Query to get distribution details with full DateTime
                $sql = "SELECT d.Customer_id, c.Name, c.Contact, c.Address, c.Price, d.Quantity, d.DateTime as Distribution_date
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
                    $row['Price'] = $row['Price'] !== null ? (float)$row['Price'] : 0.0;
                    $row['Quantity'] = (float)$row['Quantity'];
                    $distributions[] = $row;
                }
                mysqli_stmt_close($stmt);

                echo json_encode([
                    "status" => "success",
                    "message" => "Distribution details fetched successfully",
                    "data" => $distributions
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch distribution details: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    case 'total_distributed':
        if ($method === 'GET') {
            $date = isset($_GET['date']) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : '';

            // Validate date format
            if (empty($date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Date is required and must be in YYYY-MM-DD format"
                ]);
                exit;
            }

            try {
                // Query to sum quantities
                $sql = "SELECT COALESCE(SUM(Quantity), 0) as total_quantity
                        FROM tbl_milk_delivery
                        WHERE Seller_id = ? AND DATE(DateTime) = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "is", $seller_id, $date);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                echo json_encode([
                    "status" => "success",
                    "message" => "Total milk distributed fetched successfully",
                    "data" => [
                        "seller_id" => $seller_id,
                        "date" => $date,
                        "total_quantity" => (float)$row['total_quantity']
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch total milk distributed: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
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