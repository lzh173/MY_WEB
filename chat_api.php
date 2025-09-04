<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 定义存储文件
define('USERS_FILE', 'users.json');
define('MESSAGES_FILE', 'messages.json');
define('ONLINE_FILE', 'online.json');

// 确保文件存在
if (!file_exists(USERS_FILE)) file_put_contents(USERS_FILE, '[]');
if (!file_exists(MESSAGES_FILE)) file_put_contents(MESSAGES_FILE, '[]');
if (!file_exists(ONLINE_FILE)) file_put_contents(ONLINE_FILE, '[]');

// 获取输入数据
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 处理不同操作
switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'send':
        handleSendMessage();
        break;
    case 'get':
        handleGetMessages();
        break;
    case 'online_count':
        handleOnlineCount();
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
        break;
}

// 处理登录
function handleLogin() {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => '用户名和密码不能为空']);
        return;
    }
    
    $users = json_decode(file_get_contents(USERS_FILE), true);
    $userFound = false;
    
    // 查找用户
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $userFound = true;
            if ($user['password'] === $password) {
                // 登录成功，更新在线状态
                updateOnlineStatus($username);
                echo json_encode(['success' => true]);
                return;
            } else {
                echo json_encode(['success' => false, 'message' => '密码错误']);
                return;
            }
        }
    }
    
    // 用户不存在，创建新用户
    if (!$userFound) {
        $users[] = [
            'username' => $username,
            'password' => $password,
            'created_at' => time()
        ];
        file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
        updateOnlineStatus($username);
        echo json_encode(['success' => true]);
        return;
    }
}

// 处理发送消息
function handleSendMessage() {
    $username = $_POST['username'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($username) || empty($message)) {
        echo json_encode(['success' => false, 'message' => '用户名或消息不能为空']);
        return;
    }
    
    $messages = json_decode(file_get_contents(MESSAGES_FILE), true);
    
    // 添加新消息
    $messages[] = [
        'id' => count($messages) > 0 ? max(array_column($messages, 'id')) + 1 : 1,
        'username' => $username,
        'message' => htmlspecialchars($message),
        'time' => time()
    ];
    
    // 只保留最近100条消息
    if (count($messages) > 100) {
        $messages = array_slice($messages, -100);
    }
    
    file_put_contents(MESSAGES_FILE, json_encode($messages, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
}

// 处理获取消息
function handleGetMessages() {
    $lastId = $_GET['last_id'] ?? 0;
    
    $messages = json_decode(file_get_contents(MESSAGES_FILE), true);
    $newMessages = [];
    
    // 获取比lastId新的消息
    foreach ($messages as $message) {
        if ($message['id'] > $lastId) {
            $newMessages[] = $message;
        }
    }
    
    echo json_encode($newMessages);
}

// 处理在线人数
function handleOnlineCount() {
    $onlineUsers = json_decode(file_get_contents(ONLINE_FILE), true);
    $currentTime = time();
    $activeUsers = 0;
    
    // 清理超过5分钟未活动的用户
    foreach ($onlineUsers as $username => $lastActivity) {
        if ($currentTime - $lastActivity < 300) { // 5分钟
            $activeUsers++;
        } else {
            unset($onlineUsers[$username]);
        }
    }
    
    file_put_contents(ONLINE_FILE, json_encode($onlineUsers, JSON_PRETTY_PRINT));
    echo json_encode(['count' => $activeUsers]);
}

// 处理登出
function handleLogout() {
    $username = $_POST['username'] ?? '';
    
    if (!empty($username)) {
        $onlineUsers = json_decode(file_get_contents(ONLINE_FILE), true);
        if (isset($onlineUsers[$username])) {
            unset($onlineUsers[$username]);
            file_put_contents(ONLINE_FILE, json_encode($onlineUsers, JSON_PRETTY_PRINT));
        }
    }
    
    echo json_encode(['success' => true]);
}

// 更新在线状态
function updateOnlineStatus($username) {
    $onlineUsers = json_decode(file_get_contents(ONLINE_FILE), true);
    $onlineUsers[$username] = time();
    file_put_contents(ONLINE_FILE, json_encode($onlineUsers, JSON_PRETTY_PRINT));
}
?>