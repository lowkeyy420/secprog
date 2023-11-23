<?php
session_start();
require $_SERVER['DOCUMENT_ROOT'].'/controller/connection.php';
require $_SERVER['DOCUMENT_ROOT'].'/utils/validator.php';

$query = "SELECT * FROM User WHERE Username = ?";
$prepared_statement = $conn->prepare($query);
$prepared_statement->bind_param("s", $_SESSION['logged_user']);
$prepared_statement->execute();
$result = $prepared_statement->get_result();
$data = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['insert_package']) && isset($_SESSION['manage_token']) && $_SESSION['manage_token'] == $_POST['token']) {
    $name = htmlspecialchars($_POST['packagename']);
    $price = htmlspecialchars($_POST['packageprice']);
    $menus = $_POST['chosen_menu_list'];
    $menus = (array)json_decode($menus);

    if (check_empty($name) || check_empty($price)) {
        $_SESSION['insert_package_error'] = "All fields must be filled!";
    } else if (sizeof($menus) == 0) {
        $_SESSION['insert_package_error'] = "Please choose package content!";
    } else if (!isset($_FILES) || $_FILES['package_image']['error'] > 0) {
        $_SESSION['insert_package_error'] = "Insert Package Image!";
    } else {
        $query_header = "INSERT INTO packageheader VALUES (null, ?, ?, ?, ?)";

        $type = $_FILES['package_image']['type'];

        if ($type != "image/jpg" && $type != "image/png" && $type != "image/jpeg") {
            $_SESSION['insert_package_error'] = "Image must be in jpg/png/jpeg format!";
        } else {
            $image = file_get_contents($_FILES['package_image']['tmp_name']);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($image);
    
            $price = (int)$price;
            $userid = (int)$data['UserID'];
    
            $header_statement = $conn->prepare($query_header);
            $header_statement->bind_param("siis", $name, $price, $userid, $base64);
            $header_statement->execute();
            $last_id = $conn->insert_id;
    
            foreach ($menus as $menu) {
                $menu = (array)$menu;
                $qty = (int)$menu['quantity'];
                $query_detail = "INSERT INTO packagedetail VALUES (?, ?, ?)";
                $detail_statement = $conn->prepare($query_detail);
                $detail_statement->bind_param("iii", $last_id, $menu['foodid'], $qty);
                $detail_statement->execute();
            }
        }
    }
} else {
    $_SESSION['insert_package_error'] = "Invalid Request";
}

$_SESSION['active'] = "package";
header("Location: " . $_SERVER["HTTP_REFERER"]);

?>