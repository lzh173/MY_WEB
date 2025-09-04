<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 设置时区为北京时间
date_default_timezone_set('Asia/Shanghai');

$statsFile = 'stats.json';

// 读取统计数据
if (file_exists($statsFile)) {
    $stats = json_decode(file_get_contents($statsFile), true);
    
    // 检查是否需要重置今日计数（使用北京时间）
    $today = date('Y-m-d');
    if ($stats['lastReset'] !== $today) {
        $stats['todayCount'] = 0;
        $stats['lastReset'] = $today;
        file_put_contents($statsFile, json_encode($stats));
    }
    
    echo json_encode([
        'totalCount' => $stats['totalCount'],
        'todayCount' => $stats['todayCount'],
        'uniqueUsers' => count($stats['uniqueUsers'])
    ]);
} else {
    // 返回默认数据
    echo json_encode([
        'totalCount' => 0,
        'todayCount' => 0,
        'uniqueUsers' => 0
    ]);
}
?>