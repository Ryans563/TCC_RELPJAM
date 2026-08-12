<?php

require_once __DIR__ . '/../models/Product.php';

class HomeController {
    public function index() {
        require_once '../app/views/home.php';
    }
}