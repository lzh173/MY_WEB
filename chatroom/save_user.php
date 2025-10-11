<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$usersFile = 'users.json';

// 读取现有用户数据
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true);
} else {
    $users = [];
}

// 获取POST数据
$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['id'];
$username = $input['name'];

// 检查用户是否已存在
$userExists = false;
foreach ($users as &$user) {
    if ($user['id'] === $userId) {
        $user['name'] = $username; // 更新用户名
        $user['lastActive'] = date('Y-m-d H:i:s');
        $userExists = true;
        break;
    }
}

// 如果用户不存在，添加新用户
if (!$userExists) {
    $users[] = [
        'id' => $userId,
        'name' => $username,
        'joinTime' => date('Y-m-d H:i:s'),
        'lastActive' => date('Y-m-d H:i:s')
    ];
}

// 保存用户数据
file_put_contents($usersFile, json_encode($users, JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
?>