<?php

    session_start();
    require $_SERVER['DOCUMENT_ROOT'].'/controller/connection.php';

    $query = "SELECT * FROM User WHERE Username = ?";
    $prepared_statement = $conn->prepare($query);
    $prepared_statement->bind_param("s", $_SESSION['logged_user']);
    $prepared_statement->execute();
    $result = $prepared_statement->get_result();
    
    $data = $result->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['verify_payment'])) {
        $transactionid = $_POST['transactionid'];
        $query = "INSERT INTO transactionstatus VALUES (null, ?, ?, null, ?)";
        $status = "On Process";
        $statement = $conn->prepare($query);
        $statement->bind_param("isi", $transactionid, $status, $data['UserID']);
        $statement->execute();

        $query = "INSERT INTO notification VALUES (null, ?, ?, ?)";
        $receiverid = $data['UserID'];
        $status = "unseen";
        $message = "Your order has been processed!";
        $prepared_statement = $conn->prepare($query);
        $prepared_statement->bind_param("iss", $receiverid, $message, $status);
        $prepared_statement->execute();
    }

    if ($_POST['ordertype'] == 1) {
        $_SESSION['transaction_active'] = "buffet";
    } else if ($_POST['ordertype'] == 2) {
        $_SESSION['transaction_active'] = "package";
    }

    header("Location: " . $_SERVER["HTTP_REFERER"]);
?>