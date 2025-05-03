<?php
header("Content-Type: application/json");
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Add CORS headers to allow frontend access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';

switch ($path) {
    case 'customers':
        if ($method === 'GET') {
            $sql = "SELECT Customer_id, Name, Contact, Address, Price FROM tbl_customer";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $customers = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $customers[] = $row;
                }
                echo json_encode([
                    "status" => "success",
                    "message" => "Customers fetched successfully",
                    "data" => $customers
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch customers: " . mysqli_error($conn)
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    case 'delivery':
        if ($method === 'POST') {
            // Record milk delivery and update remaining quantity
            $data = json_decode(file_get_contents("php://input"), true);

            // Validate required fields
            if (
                empty($data['seller_id']) ||
                empty($data['customer_id']) ||
                empty($data['quantity']) ||
                empty($data['date'])
            ) {
                echo json_encode([
                    "status" => "error",
                    "message" => "All fields are required: seller_id, customer_id, quantity, date"
                ]);
                exit;
            }

            // Sanitize and validate inputs
            $seller_id = (int)$data['seller_id'];
            $customer_id = (int)$data['customer_id'];
            $quantity = (float)$data['quantity'];
            $date = mysqli_real_escape_string($conn, trim($data['date']));

            // Validate quantity
            if ($quantity <= 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Quantity must be greater than zero"
                ]);
                exit;
            }

            // Validate date format (YYYY-MM-DD)
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }

            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Check if seller exists
                $checkSeller = mysqli_prepare($conn, "SELECT Seller_id FROM tbl_seller WHERE Seller_id = ?");
                mysqli_stmt_bind_param($checkSeller, "i", $seller_id);
                mysqli_stmt_execute($checkSeller);
                mysqli_stmt_store_result($checkSeller);
                if (mysqli_stmt_num_rows($checkSeller) == 0) {
                    mysqli_stmt_close($checkSeller);
                    throw new Exception("Seller not found");
                }
                mysqli_stmt_close($checkSeller);

                // Check if customer exists
                $checkCustomer = mysqli_prepare($conn, "SELECT Customer_id FROM tbl_customer WHERE Customer_id = ?");
                mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                mysqli_stmt_execute($checkCustomer);
                mysqli_stmt_store_result($checkCustomer);
                if (mysqli_stmt_num_rows($checkCustomer) == 0) {
                    mysqli_stmt_close($checkCustomer);
                    throw new Exception("Customer not found");
                }
                mysqli_stmt_close($checkCustomer);

                // Check if seller has an assignment for the given date
                $checkAssignment = mysqli_prepare($conn, "SELECT Assignment_id, Remaining_quantity FROM tbl_milk_assignment WHERE Seller_id = ? AND Date = ?");
                mysqli_stmt_bind_param($checkAssignment, "is", $seller_id, $date);
                mysqli_stmt_execute($checkAssignment);
                $result = mysqli_stmt_get_result($checkAssignment);
                $assignment = mysqli_fetch_assoc($result);
                mysqli_stmt_close($checkAssignment);

                if (!$assignment) {
                    throw new Exception("No milk assignment found for the seller on the specified date");
                }

                // Check if remaining quantity is sufficient
                if ($assignment['Remaining_quantity'] < $quantity) {
                    throw new Exception("Insufficient remaining quantity. Available: " . $assignment['Remaining_quantity']);
                }

                // Insert delivery record
                $insertDelivery = mysqli_prepare($conn, "INSERT INTO tbl_milk_delivery (Seller_id, Customer_id, DateTime, Quantity) VALUES (?, ?, NOW(), ?)");
                mysqli_stmt_bind_param($insertDelivery, "iid", $seller_id, $customer_id, $quantity);
                if (!mysqli_stmt_execute($insertDelivery)) {
                    throw new Exception("Failed to record delivery: " . mysqli_stmt_error($insertDelivery));
                }
                $delivery_id = mysqli_insert_id($conn);
                mysqli_stmt_close($insertDelivery);

                // Update remaining quantity
                $new_remaining_quantity = $assignment['Remaining_quantity'] - $quantity;
                $updateAssignment = mysqli_prepare($conn, "UPDATE tbl_milk_assignment SET Remaining_quantity = ? WHERE Assignment_id = ?");
                mysqli_stmt_bind_param($updateAssignment, "di", $new_remaining_quantity, $assignment['Assignment_id']);
                if (!mysqli_stmt_execute($updateAssignment)) {
                    throw new Exception("Failed to update remaining quantity: " . mysqli_stmt_error($updateAssignment));
                }
                mysqli_stmt_close($updateAssignment);

                // Commit transaction
                mysqli_commit($conn);

                echo json_encode([
                    "status" => "success",
                    "message" => "Delivery recorded successfully",
                    "data" => [
                        "delivery_id" => $delivery_id,
                        "seller_id" => $seller_id,
                        "customer_id" => $customer_id,
                        "quantity" => $quantity,
                        "date" => $date,
                        "remaining_quantity" => $new_remaining_quantity
                    ]
                ]);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("Delivery POST Error: " . $e->getMessage());
                echo json_encode([
                    "status" => "error",
                    "message" => $e->getMessage()
                ]);
            }
        } elseif ($method === 'DELETE') {
            // Delete milk delivery and update remaining quantity
            $data = json_decode(file_get_contents("php://input"), true);

            // Validate required fields
            if (
                empty($data['seller_id']) ||
                empty($data['customer_id']) ||
                empty($data['quantity']) ||
                empty($data['date'])
            ) {
                echo json_encode([
                    "status" => "error",
                    "message" => "All fields are required: seller_id, customer_id, quantity, date"
                ]);
                exit;
            }

            // Sanitize and validate inputs
            $delivery_id = isset($data['delivery_id']) ? (int)$data['delivery_id'] : null;
            $seller_id = (int)$data['seller_id'];
            $customer_id = (int)$data['customer_id'];
            $quantity = (float)$data['quantity'];
            $date = mysqli_real_escape_string($conn, trim($data['date']));

            // Validate quantity
            if ($quantity <= 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Quantity must be greater than zero"
                ]);
                exit;
            }

            // Validate date format (YYYY-MM-DD)
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }

            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Check if seller exists
                $checkSeller = mysqli_prepare($conn, "SELECT Seller_id FROM tbl_seller WHERE Seller_id = ?");
                mysqli_stmt_bind_param($checkSeller, "i", $seller_id);
                mysqli_stmt_execute($checkSeller);
                mysqli_stmt_store_result($checkSeller);
                if (mysqli_stmt_num_rows($checkSeller) == 0) {
                    mysqli_stmt_close($checkSeller);
                    throw new Exception("Seller not found");
                }
                mysqli_stmt_close($checkSeller);

                // Check if customer exists
                $checkCustomer = mysqli_prepare($conn, "SELECT Customer_id FROM tbl_customer WHERE Customer_id = ?");
                mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                mysqli_stmt_execute($checkCustomer);
                mysqli_stmt_store_result($checkCustomer);
                if (mysqli_stmt_num_rows($checkCustomer) == 0) {
                    mysqli_stmt_close($checkCustomer);
                    throw new Exception("Customer not found");
                }
                mysqli_stmt_close($checkCustomer);

                // Check if assignment exists for the date
                $checkAssignment = mysqli_prepare($conn, "SELECT Assignment_id, Remaining_quantity FROM tbl_milk_assignment WHERE Seller_id = ? AND Date = ?");
                mysqli_stmt_bind_param($checkAssignment, "is", $seller_id, $date);
                mysqli_stmt_execute($checkAssignment);
                $result = mysqli_stmt_get_result($checkAssignment);
                $assignment = mysqli_fetch_assoc($result);
                mysqli_stmt_close($checkAssignment);

                if (!$assignment) {
                    throw new Exception("No milk assignment found for the seller on the specified date");
                }

                // Delete delivery record
                if ($delivery_id) {
                    // Delete by Delivery_id
                    $deleteDelivery = mysqli_prepare($conn, "DELETE FROM tbl_milk_delivery WHERE Delivery_id = ?");
                    mysqli_stmt_bind_param($deleteDelivery, "i", $delivery_id);
                } else {
                    // Fallback to matching by seller_id, customer_id, date, and quantity
                    $deleteDelivery = mysqli_prepare($conn, "DELETE FROM tbl_milk_delivery WHERE Seller_id = ? AND Customer_id = ? AND DATE(DateTime) = ? AND Quantity = ? LIMIT 1");
                    mysqli_stmt_bind_param($deleteDelivery, "iisd", $seller_id, $customer_id, $date, $quantity);
                }

                if (!mysqli_stmt_execute($deleteDelivery)) {
                    throw new Exception("Failed to delete delivery: " . mysqli_stmt_error($deleteDelivery));
                }
                $affected_rows = mysqli_stmt_affected_rows($deleteDelivery);
                mysqli_stmt_close($deleteDelivery);

                if ($affected_rows === 0) {
                    throw new Exception("No matching delivery found to delete");
                }

                // Update remaining quantity
                $new_remaining_quantity = $assignment['Remaining_quantity'] + $quantity;
                $updateAssignment = mysqli_prepare($conn, "UPDATE tbl_milk_assignment SET Remaining_quantity = ? WHERE Assignment_id = ?");
                mysqli_stmt_bind_param($updateAssignment, "di", $new_remaining_quantity, $assignment['Assignment_id']);
                if (!mysqli_stmt_execute($updateAssignment)) {
                    throw new Exception("Failed to update remaining quantity: " . mysqli_stmt_error($updateAssignment));
                }
                mysqli_stmt_close($updateAssignment);

                // Commit transaction
                mysqli_commit($conn);

                echo json_encode([
                    "status" => "success",
                    "message" => "Delivery deleted successfully",
                    "data" => [
                        "seller_id" => $seller_id,
                        "customer_id" => $customer_id,
                        "quantity" => $quantity,
                        "date" => $date,
                        "remaining_quantity" => $new_remaining_quantity
                    ]
                ]);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("Delivery DELETE Error: " . $e->getMessage());
                echo json_encode([
                    "status" => "error",
                    "message" => $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    case 'milk_sold':
        if ($method === 'GET') {
            // Fetch total milk sold by seller on a specific date
            $seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
            $date = isset($_GET['date']) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : '';

            // Validate inputs
            if ($seller_id <= 0 || empty($date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "seller_id and date are required"
                ]);
                exit;
            }

            // Validate date format (YYYY-MM-DD)
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }

            try {
                // Query to sum quantities for the seller on the date
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
                    "message" => "Total milk sold fetched successfully",
                    "data" => [
                        "seller_id" => $seller_id,
                        "date" => $date,
                        "total_quantity" => (float)$row['total_quantity']
                    ]
                ]);
            } catch (Exception $e) {
                error_log("Milk Sold GET Error: " . $e->getMessage());
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch total milk sold: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    case 'milk_assignment':
        if ($method === 'GET') {
            // Fetch milk assignment details for seller on a specific date
            $seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
            $date = isset($_GET['date']) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : '';

            // Validate inputs
            if ($seller_id <= 0 || empty($date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "seller_id and date are required"
                ]);
                exit;
            }

            // Validate date format (YYYY-MM-DD)
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }

            try {
                // Query to get assignment details
                $sql = "SELECT Assigned_quantity, Remaining_quantity
                        FROM tbl_milk_assignment
                        WHERE Seller_id = ? AND Date = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "is", $seller_id, $date);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if ($row) {
                    echo json_encode([
                        "status" => "success",
                        "message" => "Milk assignment fetched successfully",
                        "data" => [
                            "seller_id" => $seller_id,
                            "date" => $date,
                            "assigned_quantity" => (float)$row['Assigned_quantity'],
                            "remaining_quantity" => (float)$row['Remaining_quantity']
                        ]
                    ]);
                } else {
                    echo json_encode([
                        "status" => "success",
                        "message" => "No assignment found for the specified date",
                        "data" => [
                            "seller_id" => $seller_id,
                            "date" => $date,
                            "assigned_quantity" => 0,
                            "remaining_quantity" => 0
                        ]
                    ]);
                }
            } catch (Exception $e) {
                error_log("Milk Assignment GET Error: " . $e->getMessage());
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch milk assignment: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    case 'distribution_details':
        if ($method === 'GET') {
            // Fetch milk delivery details for seller on a specific date
            $seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
            $date = isset($_GET['date']) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : '';

            // Validate inputs
            if ($seller_id <= 0 || empty($date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "seller_id and date are required"
                ]);
                exit;
            }

            // Validate date format (YYYY-MM-DD)
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }

            try {
                // Query to get delivery details
                $sql = "SELECT Delivery_id, Seller_id, Customer_id, Quantity, DATE(DateTime) as date
                        FROM tbl_milk_delivery
                        WHERE Seller_id = ? AND DATE(DateTime) = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "is", $seller_id, $date);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $deliveries = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $deliveries[] = [
                        'delivery_id' => (int)$row['Delivery_id'],
                        'seller_id' => (int)$row['Seller_id'],
                        'customer_id' => (int)$row['Customer_id'],
                        'quantity' => (float)$row['Quantity'],
                        'date' => $row['date']
                    ];
                }
                mysqli_stmt_close($stmt);

                echo json_encode([
                    "status" => "success",
                    "message" => "Distribution details fetched successfully",
                    "data" => $deliveries
                ]);
            } catch (Exception $e) {
                error_log("Distribution Details GET Error: " . $e->getMessage());
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

    default:
        echo json_encode([
            "status" => "error",
            "message" => "Invalid endpoint"
        ]);
        break;
}

mysqli_close($conn);
?>