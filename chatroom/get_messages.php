<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$messagesFile = 'messages.json';

// 读取消息数据
if (file_exists($messagesFile)) {
    $messages = json_decode(file_get_contents($messagesFile), true);
    echo json_encode($messages, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
?>