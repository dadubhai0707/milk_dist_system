<?php
header("Content-Type: application/json");
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT * FROM tbl_Seller ";
        $result = mysqli_query($conn, $sql);

        $sellers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $sellers[] = $row;
        }
        
        echo json_encode([
            "status" => "success",
            "data" => $sellers
        ]);
        mysqli_free_result($result);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        if (
            empty($data['Name']) ||
            empty($data['Contact']) ||
            empty($data['Password']) ||
            empty($data['Vehicle_no'])
        ) {
            echo json_encode([
                "status" => "error",
                "message" => "All fields are required"
            ]);
            exit;
        }

        $name = mysqli_real_escape_string($conn, trim($data['Name']));
        $contact = mysqli_real_escape_string($conn, trim($data['Contact']));
        $password = password_hash(trim($data['Password']), PASSWORD_BCRYPT);
        $vehicle_no = mysqli_real_escape_string($conn, trim($data['Vehicle_no']));

        if (!preg_match("/^[0-9]{10}$/", $contact)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid phone number"
            ]);
            exit;
        }

        if (!preg_match("/^[A-Za-z0-9\s\-]{1,20}$/", $vehicle_no)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid vehicle number"
            ]);
            exit;
        }

        // Check if phone or vehicle number already exists
        $checkQuery = "SELECT Seller_id FROM tbl_Seller WHERE Contact = ? OR Vehicle_no = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "ss", $contact, $vehicle_no);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Phone number or vehicle number already exists"
            ]);
            mysqli_stmt_close($checkStmt);
            exit;
        }
        mysqli_stmt_close($checkStmt);

        // Prepare and execute insert query
        $sql = "INSERT INTO tbl_Seller (Name, Contact, Password, Vehicle_no, is_active) 
                VALUES (?, ?, ?, ?, 1)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {  
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "ssss", $name, $contact, $password, $vehicle_no);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "success",
                "message" => "Seller added successfully",
                "seller_id" => mysqli_insert_id($conn)
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to add seller: " . mysqli_stmt_error($stmt)
            ]);
        }

        mysqli_stmt_close($stmt);
        break;

    case 'PUT':
        // UPDATE seller
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['Seller_id']) || empty($data['Name']) || empty($data['Contact']) || empty($data['Vehicle_no'])) {
            echo json_encode([
                "status" => "error",
                "message" => "All fields are required"
            ]);
            exit;
        }

        $id = intval($data['Seller_id']);
        $name = mysqli_real_escape_string($conn, trim($data['Name']));
        $contact = mysqli_real_escape_string($conn, trim($data['Contact']));
        $vehicle_no = mysqli_real_escape_string($conn, trim($data['Vehicle_no']));

        // Validate phone number
        if (!preg_match("/^[0-9]{10}$/", $contact)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid phone number"
            ]);
            exit;
        }

        // Validate vehicle number
        if (!preg_match("/^[A-Za-z0-9\s\-]{1,20}$/", $vehicle_no)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid vehicle number"
            ]);
            exit;
        }

        $sql = "UPDATE tbl_Seller SET Name = ?, Contact = ?, Vehicle_no = ? WHERE Seller_id = ? AND is_active = 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "sssi", $name, $contact, $vehicle_no, $id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["status" => "success", "message" => "Seller updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'DELETE':
        // Soft delete seller (set is_active = 0)
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['Seller_id'])) {
            echo json_encode([
                "status" => "error",
                "message" => "Seller ID is required"
            ]);
            exit;
        }

        $id = intval($data['Seller_id']);

        $sql = "UPDATE tbl_Seller SET is_active = 0 WHERE Seller_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["status" => "success", "message" => "Seller deactivated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
        break;
}

mysqli_close($conn);
?>