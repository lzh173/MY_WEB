<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// ����ʱ��Ϊ����ʱ��
date_default_timezone_set('Asia/Shanghai');

// ���������ļ�
$dataFile = 'tributes.json';
$statsFile = 'stats.json';

// ��ȡPOST����
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// ��֤����
if (!isset($data['userId']) || empty($data['userId'])) {
    echo json_encode(['success' => false, 'message' => '��Ч���û�ID']);
    exit;
}

$message = isset($data['message']) ? trim($data['message']) : '�¾�̫���ڱ�';
if (strlen($message) > 100) {
    $message = substr($message, 0, 100);
}

// ��ȡ���ʼ��ͳ������
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

// ����Ƿ���Ҫ���ý��ռ�����ʹ�ñ���ʱ�䣩
$today = date('Y-m-d');
if ($stats['lastReset'] !== $today) {
    $stats['todayCount'] = 0;
    $stats['lastReset'] = $today;
}

// ����ͳ������
$stats['totalCount']++;
$stats['todayCount']++;

// ������û��������δ��¼��
if (!in_array($data['userId'], $stats['uniqueUsers'])) {
    $stats['uniqueUsers'][] = $data['userId'];
}

// ��ȡ���ʼ���¾���¼
if (file_exists($dataFile)) {
    $tributes = json_decode(file_get_contents($dataFile), true);
} else {
    $tributes = [];
}

// ����¼�¼��ʹ�ñ���ʱ�䣩
$newTribute = [
    'userId' => $data['userId'],
    'message' => $message,
    'time' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR']
];

// ���Ƽ�¼�������������100��
array_unshift($tributes, $newTribute);
$tributes = array_slice($tributes, 0, 100);

// ��������
file_put_contents($dataFile, json_encode($tributes));
file_put_contents($statsFile, json_encode($stats));

// ���سɹ���Ӧ
echo json_encode([
    'success' => true,
    'totalCount' => $stats['totalCount'],
    'todayCount' => $stats['todayCount'],
    'uniqueUsers' => count($stats['uniqueUsers'])
]);
?>