<?php
// auth.php — include on the FIRST line of every protected page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

// 1. Owner Check
if (!function_exists('is_owner')) {
    function is_owner() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'owner';
    }
}

if (!function_exists('require_owner')) {
    function require_owner() {
        if (!is_owner()) {
            http_response_code(403);
            die("403 - Access Denied. Only the Owner can view this page. <br><a href='index.php'>Return to Home</a>");
        }
    }
}

// Backward compatibility aliases
if (!function_exists('is_admin')) {
    function is_admin() {
        return is_owner();
    }
}

if (!function_exists('require_admin')) {
    function require_admin() {
        require_owner();
    }
}

// 2. Cashier Check
if (!function_exists('is_cashier')) {
    function is_cashier() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'cashier';
    }
}

if (!function_exists('require_cashier')) {
    function require_cashier() {
        if (!is_cashier()) {
            http_response_code(403);
            die("403 - Access Denied. Only Cashiers can view this page. <br><a href='index.php'>Return to Home</a>");
        }
    }
}

// 3. Customer Check
if (!function_exists('is_customer')) {
    function is_customer() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
    }
}
?>
