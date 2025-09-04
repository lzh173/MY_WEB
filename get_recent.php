<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dataFile = 'tributes.json';

// 读取最近记录
if (file_exists($dataFile)) {
    $tributes = json_decode(file_get_contents($dataFile), true);
    echo json_encode($tributes);
} else {
    echo json_encode([]);
}
?>