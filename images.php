<?php
// image.php - Proxy loader for external images
if (!isset($_GET['url'])) {
    http_response_code(400);
    exit('No image URL provided.');
}

$url = $_GET['url'];

// Security: only allow http/https
if (!preg_match('/^https?:\/\//', $url)) {
    http_response_code(400);
    exit('Invalid URL.');
}

// Fetch image with cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$data = curl_exec($ch);
$mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($data) {
    header("Content-Type: ".$mime);
    echo $data;
} else {
    http_response_code(404);
    echo "Image not found.";
}
