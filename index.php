<?php
// index.php - Main entry point that serves the HTML files

require_once 'config.php';

// Simple router to serve HTML files
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = trim($path, '/');

// Remove query string for routing
$route = explode('?', $path)[0];

// Define available HTML files
$htmlFiles = [
    '' => 'Home-Page.html',
    'home' => 'Home-Page.html',
    'collection' => 'Art_Collection.html',
    'art' => 'Art_Details.html',
    'submit' => 'Artist_New_entry.html',
    'login' => 'Login.html',
    'signup' => 'Signup.html',
    'admin' => 'Admin_Dashboard.html'
];

// Check if it's an API request
if (strpos($route, 'auth.php') !== false || strpos($route, 'api.php') !== false) {
    // Let the web server handle API files directly
    return false;
}

// Serve HTML file
if (array_key_exists($route, $htmlFiles)) {
    $file = $htmlFiles[$route];
    if (file_exists($file)) {
        include $file;
    } else {
        http_response_code(404);
        echo "Page not found";
    }
} else {
    // Try to find exact HTML file
    $htmlFile = $route . '.html';
    if (file_exists($htmlFile)) {
        include $htmlFile;
    } else {
        http_response_code(404);
        echo "Page not found";
    }
}
?>