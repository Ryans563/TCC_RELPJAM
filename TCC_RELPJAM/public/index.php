<?php


require_once '../app/controllers/HomeController.php';

$controller = new HomeController();
$controller->index();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// controllers
require_once '../app/controllers/HomeController.php';//
require_once '../app/controllers/ProductController.php';//
require_once '../app/controllers/AuthController.php';//
require_once '../app/controllers/CartController.php';//
require_once '../app/controllers/SellerController.php';//
require_once '../app/controllers/AdminController.php';//

// rota
$page = $_GET['page'] ?? 'home';

switch ($page) {

    case 'produto':
        produto();
        break;

    case 'auth':
        auth();
        break;

    case 'carrinho':
        carrinho();
        break;

    case 'pedidos':
        pedidos();
        break;

    case 'vendedor':
        vendedor();
        break;

    case 'produto_form':
        produto_form();
        break;

    case 'admin':
        admin();
        break;

    default:
        home();
}
