<?php
header("Content-Type: application/json");
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $current_date = date('Y-m-d');

        $sql_distributed = "SELECT COALESCE(SUM(Quantity), 0) AS total_distributed 
                           FROM tbl_milk_delivery 
                           WHERE DATE(DateTime) = ?";
        $stmt_distributed = mysqli_prepare($conn, $sql_distributed);
        mysqli_stmt_bind_param($stmt_distributed, "s", $current_date);
        mysqli_stmt_execute($stmt_distributed);
        $result_distributed = mysqli_stmt_get_result($stmt_distributed);

        if ($result_distributed === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Query failed: " . mysqli_error($conn)
            ]);
            mysqli_stmt_close($stmt_distributed);
            exit;
        }

        $total_distributed = mysqli_fetch_assoc($result_distributed)['total_distributed'];
        mysqli_stmt_close($stmt_distributed);

        $sql_assigned = "SELECT COALESCE(SUM(Assigned_quantity), 0) AS total_assigned 
                        FROM tbl_milk_assignment 
                        WHERE DATE(Date) = ?";
        $stmt_assigned = mysqli_prepare($conn, $sql_assigned);
        mysqli_stmt_bind_param($stmt_assigned, "s", $current_date);
        mysqli_stmt_execute($stmt_assigned);
        $result_assigned = mysqli_stmt_get_result($stmt_assigned);

        if ($result_assigned === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Query failed: " . mysqli_error($conn)
            ]);
            mysqli_stmt_close($stmt_assigned);
            exit;
        }

        $total_assigned = mysqli_fetch_assoc($result_assigned)['total_assigned'];
        mysqli_stmt_close($stmt_assigned);

        echo json_encode([
            "status" => "success",
            "data" => [
                "total_distributed" => floatval($total_distributed),
                "total_assigned" => floatval($total_assigned)
            ]
        ]);
        break;

    default:
        echo json_encode([
            "status" => "error",
            "message" => "Invalid Request Method"
        ]);
        break;
}

mysqli_close($conn);
?>