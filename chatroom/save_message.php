<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$messagesFile = 'messages.json';

// 读取现有消息数据
if (file_exists($messagesFile)) {
    $messages = json_decode(file_get_contents($messagesFile), true);
} else {
    $messages = [];
}

// 获取POST数据
$input = json_decode(file_get_contents('php://input'), true);

// 添加新消息
$messages[] = [
    'userId' => $input['userId'],
    'username' => $input['username'],
    'content' => $input['content'],
    'time' => $input['time']
];

// 只保留最近100条消息
if (count($messages) > 100) {
    $messages = array_slice($messages, -100);
}

// 保存消息数据
file_put_contents($messagesFile, json_encode($messages, JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
?>