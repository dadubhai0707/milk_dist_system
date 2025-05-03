<?php
header("Content-Type: application/json");
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';

if ($path === 'login' && $method === 'POST') {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $contact = isset($input['contact']) ? trim($input['contact']) : null;
    $password = isset($input['password']) ? trim($input['password']) : null;
    $user_type = isset($input['user_type']) ? strtolower(trim($input['user_type'])) : null;

    // Validate input
    if (!$contact || !$password || !$user_type) {
        echo json_encode([
            "status" => "error",
            "message" => "Contact, password, and user_type are required"
        ]);
        exit;
    }

    // Validate user_type
    $valid_user_types = ['customer', 'seller', 'admin'];
    if (!in_array($user_type, $valid_user_types)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid user_type. Must be customer, seller, or admin"
        ]);
        exit;
    }

    try {
        // Determine table and columns based on user_type
        switch ($user_type) {
            case 'customer':
                $table = 'tbl_customer';
                $id_column = 'Customer_id';
                $name_column = 'Name';
                $address_column = 'Address';
                break;
            case 'seller':
                $table = 'tbl_seller';
                $id_column = 'Seller_id';
                $name_column = 'Name';
                $address_column = null;
                break;
            case 'admin':
                $table = 'tbl_admin';
                $id_column = 'Admin_id';
                $name_column = null;
                $address_column = null;
                break;
            default:
                throw new Exception("Invalid user type");
        }

        // Prepare SQL query
        $columns = "$id_column, Password" . ($name_column ? ", $name_column" : "") . ($address_column ? ", $address_column" : "");
        $sql = "SELECT $columns FROM $table WHERE Contact = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $contact);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $stored_password = $row['Password'];
            $user_id = $row[$id_column];
            $user_name = $name_column ? $row[$name_column] : null;
            $user_address = $address_column ? $row[$address_column] : null;

            // Verify password
            $is_valid_password = false;
            if (password_verify($password, $stored_password)) {
                $is_valid_password = true; // Hashed password
            } elseif ($password === $stored_password && $stored_password !== '') {
                $is_valid_password = true; // Plain text password
            }

            if ($is_valid_password) {
                // Generate a simple token (use JWT in production)
                $token = bin2hex(random_bytes(16));
                $response_data = [
                    "user_id" => $user_id,
                    "user_type" => $user_type,
                    "contact" => $contact,
                    "token" => $token
                ];
                if ($user_name) {
                    $response_data["username"] = $user_name;
                }
                if ($user_address) {
                    $response_data["address"] = $user_address;
                }

                echo json_encode([
                    "status" => "success",
                    "message" => "Login successful",
                    "data" => $response_data
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid password"
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "User not found with provided contact"
            ]);
        }

        mysqli_stmt_close($stmt);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Login failed: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => $path === 'login' ? "Invalid request method. Use POST" : "Invalid endpoint"
    ]);
}

mysqli_close($conn);
?>