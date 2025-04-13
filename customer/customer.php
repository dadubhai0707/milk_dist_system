<?php
header("Content-Type: application/json");
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // READ customers
        $sql = "SELECT * FROM tbl_Customer";
        $result = mysqli_query($conn, $sql);

        $customers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $customers[] = $row;
        }
        echo json_encode($customers);
        break;

    case 'POST':
        // CREATE customer
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate required fields
        if (
            empty($data['Name']) ||
            empty($data['Contact']) ||
            empty($data['Password']) ||
            empty($data['Address']) ||
            empty($data['Price']) ||
            empty($data['Date'])
        ) {
            echo json_encode([
                "status" => "error",
                "message" => "All fields are required"
            ]);
            exit;
        }

        // Sanitize and validate inputs
        $name = mysqli_real_escape_string($conn, trim($data['Name']));
        $contact = mysqli_real_escape_string($conn, trim($data['Contact']));
        $password = password_hash(trim($data['Password']), PASSWORD_BCRYPT);
        $address = mysqli_real_escape_string($conn, trim($data['Address']));
        $price = floatval($data['Price']);
        $date = mysqli_real_escape_string($conn, trim($data['Date']));

        // Validate phone number (basic check for 10 digits)
        if (!preg_match("/^[0-9]{10}$/", $contact)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid phone number"
            ]);
            exit;
        }

        // Validate price
        if ($price <= 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Price must be a positive number"
            ]);
            exit;
        }

        // Validate date format (YYYY-MM-DD)
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid date format"
            ]);
            exit;
        }

        // Check if phone already exists
        $checkQuery = "SELECT Customer_id FROM tbl_Customer WHERE Contact = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "s", $contact);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Phone number already exists"
            ]);
            mysqli_stmt_close($checkStmt);
            exit;
        }
        mysqli_stmt_close($checkStmt);

        // Prepare and execute insert query
        $sql = "INSERT INTO tbl_Customer (Name, Contact, Password, Address, Price, Date) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "ssssds", $name, $contact, $password, $address, $price, $date);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "success",
                "message" => "Customer added successfully",
                "customer_id" => mysqli_insert_id($conn)
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to add customer: " . mysqli_stmt_error($stmt)
            ]);
        }

        mysqli_stmt_close($stmt);
        break;

    case 'PUT':
        // UPDATE customer
        $data = json_decode(file_get_contents("php://input"), true);

        $id = $data['Customer_id'];
        $name = mysqli_real_escape_string($conn, $data['Name']);
        $contact = mysqli_real_escape_string($conn, $data['Contact']);
        $address = mysqli_real_escape_string($conn, $data['Address']);
        $price = floatval($data['Price']);
        $date = mysqli_real_escape_string($conn, $data['Date']);

        $sql = "UPDATE tbl_Customer SET 
                Name='$name', Contact='$contact', Address='$address', 
                Price='$price', Date='$date' 
                WHERE Customer_id=$id";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success", "message" => "Customer updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;

    case 'DELETE':
        // DELETE customer
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['Customer_id'];

        $sql = "DELETE FROM tbl_Customer WHERE Customer_id=$id";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success", "message" => "Customer deleted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
        break;
}

mysqli_close($conn);
?>