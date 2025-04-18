<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
    switch($_GET['url']){
        case '':
            echo "<h1>This is FarmFresh API Service</h1>";
            break;
        case 'api/login':
            require 'api/auth/login.php';
            break;
        case 'api/register':
            require 'api/auth/register.php';
            break;
        case 'api/getuser':
            require 'api/user/getuser.php';
            break;
        case 'api/products/add':
            require 'api/products/add.php';
            break;
        case 'api/products/getall':
            require 'api/products/getall.php';
            break;
        case 'api/products/getone':
            require 'api/products/getone.php';
            break;
        case 'api/products/update':
            require 'api/products/update.php';
            break;
        case 'api/products/delete':
            require 'api/products/delete.php';
            break;
        case 'api/products/byfarmer':
            require 'api/products/byfarmer.php';
            break;
        case 'api/products/search':
            require 'api/products/search.php';
            break;
        case 'api/orders/place':
            require 'api/orders/place.php';
            break;
        case 'api/orders/myorders':
            require 'api/orders/myorders.php';
            break;
        case 'api/orders/farmerorders':
            require 'api/orders/farmerorders.php';
            break;
        case 'api/orders/updatestatus':
            require 'api/orders/updatestatus.php';
            break;
        case 'api/orders/view':
            require 'api/orders/view.php';
            break;
        case 'api/delivery/schedule':
            require 'api/delivery/schedule.php';
            break;
        case 'api/delivery/view':
            require 'api/delivery/view.php';
            break;
        case 'api/cart/add':
            require 'api/cart/add.php';
            break;
        case 'api/cart/view':
            require 'api/cart/view.php';
            break;
        case 'api/cart/update':
            require 'api/cart/update.php';
            break;
        case 'api/cart/remove':
            require 'api/cart/remove.php';
            break;
        case 'api/reviews/add':
            require 'api/reviews/add.php';
            break;
        case 'api/reviews/get':
            require 'api/reviews/get.php';
            break;
        case 'api/dashboard/totalusers':
            require 'api/dashboard/totalusers.php';
            break;
        case 'api/dashboard/totalproducts':
            require 'api/dashboard/totalproducts.php';
            break;
        case 'api/dashboard/totalorders':
            require 'api/dashboard/totalorders.php';
            break;
        case 'api/dashboard/totalrevenue':
            require 'api/dashboard/totalrevenue.php';
            break;
        case 'api/dashboard/topproducts':
            require 'api/dashboard/topproducts.php';
            break;
        case 'api/dashboard/userslist':
            require 'api/dashboard/userslist.php';
            break;
        case 'api/dashboard/orderslist':
            require 'api/dashboard/orderslist.php';
            break;
        case 'api/dashboard/farmer':
            require 'api/dashboard/farmer.php';
            break;
        case 'api/dashboard/adminsummary':
            require 'api/dashboard/adminsummary.php';
            break;
        case 'api/createorder':
            require 'api/payment/createorder.php';
            break;
        default:
            header("HTTP/1.0 404 Not Found");
            echo json_encode(["message" => "Endpoint not found"]);
            break;
    }
?>
