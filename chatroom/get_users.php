<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$usersFile = 'users.json';

// 读取用户数据
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true);
    
    // 过滤掉超过5分钟未活动的用户
    $activeUsers = [];
    $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    
    foreach ($users as $user) {
        if ($user['lastActive'] > $fiveMinutesAgo) {
            $activeUsers[] = $user;
        }
    }
    
    // 更新用户列表
    file_put_contents($usersFile, json_encode($activeUsers, JSON_UNESCAPED_UNICODE));
    
    echo json_encode($activeUsers, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
?>