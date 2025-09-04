<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 设置时区为北京时间
date_default_timezone_set('Asia/Shanghai');

// 定义数据文件
$dataFile = 'tributes.json';
$statsFile = 'stats.json';

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 验证数据
if (!isset($data['userId']) || empty($data['userId'])) {
    echo json_encode(['success' => false, 'message' => '无效的用户ID']);
    exit;
}

$message = isset($data['message']) ? trim($data['message']) : '致敬太空哨兵';
if (strlen($message) > 100) {
    $message = substr($message, 0, 100);
}

// 读取或初始化统计数据
if (file_exists($statsFile)) {
    $stats = json_decode(file_get_contents($statsFile), true);
} else {
    $stats = [
        'totalCount' => 0,
        'todayCount' => 0,
        'uniqueUsers' => [],
        'lastReset' => date('Y-m-d')
    ];
}

// 检查是否需要重置今日计数（使用北京时间）
$today = date('Y-m-d');
if ($stats['lastReset'] !== $today) {
    $stats['todayCount'] = 0;
    $stats['lastReset'] = $today;
}

// 更新统计数据
$stats['totalCount']++;
$stats['todayCount']++;

// 添加新用户（如果尚未记录）
if (!in_array($data['userId'], $stats['uniqueUsers'])) {
    $stats['uniqueUsers'][] = $data['userId'];
}

// 读取或初始化致敬记录
if (file_exists($dataFile)) {
    $tributes = json_decode(file_get_contents($dataFile), true);
} else {
    $tributes = [];
}

// 添加新记录（使用北京时间）
$newTribute = [
    'userId' => $data['userId'],
    'message' => $message,
    'time' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR']
];

// 限制记录数量，保留最近100条
array_unshift($tributes, $newTribute);
$tributes = array_slice($tributes, 0, 100);

// 保存数据
file_put_contents($dataFile, json_encode($tributes));
file_put_contents($statsFile, json_encode($stats));

// 返回成功响应
echo json_encode([
    'success' => true,
    'totalCount' => $stats['totalCount'],
    'todayCount' => $stats['todayCount'],
    'uniqueUsers' => count($stats['uniqueUsers'])
]);
?>