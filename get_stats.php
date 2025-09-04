<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ����ʱ��Ϊ����ʱ��
date_default_timezone_set('Asia/Shanghai');

$statsFile = 'stats.json';

// ��ȡͳ������
if (file_exists($statsFile)) {
    $stats = json_decode(file_get_contents($statsFile), true);
    
    // ����Ƿ���Ҫ���ý��ռ�����ʹ�ñ���ʱ�䣩
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
    // ����Ĭ������
    echo json_encode([
        'totalCount' => 0,
        'todayCount' => 0,
        'uniqueUsers' => 0
    ]);
}
?>