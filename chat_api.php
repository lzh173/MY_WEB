<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// ����洢�ļ�
define('USERS_FILE', 'users.json');
define('MESSAGES_FILE', 'messages.json');
define('ONLINE_FILE', 'online.json');

// ȷ���ļ�����
if (!file_exists(USERS_FILE)) file_put_contents(USERS_FILE, '[]');
if (!file_exists(MESSAGES_FILE)) file_put_contents(MESSAGES_FILE, '[]');
if (!file_exists(ONLINE_FILE)) file_put_contents(ONLINE_FILE, '[]');

// ��ȡ��������
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ����ͬ����
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
        echo json_encode(['success' => false, 'message' => 'δ֪����']);
        break;
}

// �����¼
function handleLogin() {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => '�û��������벻��Ϊ��']);
        return;
    }
    
    $users = json_decode(file_get_contents(USERS_FILE), true);
    $userFound = false;
    
    // �����û�
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $userFound = true;
            if ($user['password'] === $password) {
                // ��¼�ɹ�����������״̬
                updateOnlineStatus($username);
                echo json_encode(['success' => true]);
                return;
            } else {
                echo json_encode(['success' => false, 'message' => '�������']);
                return;
            }
        }
    }
    
    // �û������ڣ��������û�
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

// ��������Ϣ
function handleSendMessage() {
    $username = $_POST['username'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($username) || empty($message)) {
        echo json_encode(['success' => false, 'message' => '�û�������Ϣ����Ϊ��']);
        return;
    }
    
    $messages = json_decode(file_get_contents(MESSAGES_FILE), true);
    
    // �������Ϣ
    $messages[] = [
        'id' => count($messages) > 0 ? max(array_column($messages, 'id')) + 1 : 1,
        'username' => $username,
        'message' => htmlspecialchars($message),
        'time' => time()
    ];
    
    // ֻ�������100����Ϣ
    if (count($messages) > 100) {
        $messages = array_slice($messages, -100);
    }
    
    file_put_contents(MESSAGES_FILE, json_encode($messages, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
}

// �����ȡ��Ϣ
function handleGetMessages() {
    $lastId = $_GET['last_id'] ?? 0;
    
    $messages = json_decode(file_get_contents(MESSAGES_FILE), true);
    $newMessages = [];
    
    // ��ȡ��lastId�µ���Ϣ
    foreach ($messages as $message) {
        if ($message['id'] > $lastId) {
            $newMessages[] = $message;
        }
    }
    
    echo json_encode($newMessages);
}

// ������������
function handleOnlineCount() {
    $onlineUsers = json_decode(file_get_contents(ONLINE_FILE), true);
    $currentTime = time();
    $activeUsers = 0;
    
    // ������5����δ����û�
    foreach ($onlineUsers as $username => $lastActivity) {
        if ($currentTime - $lastActivity < 300) { // 5����
            $activeUsers++;
        } else {
            unset($onlineUsers[$username]);
        }
    }
    
    file_put_contents(ONLINE_FILE, json_encode($onlineUsers, JSON_PRETTY_PRINT));
    echo json_encode(['count' => $activeUsers]);
}

// ����ǳ�
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

// ��������״̬
function updateOnlineStatus($username) {
    $onlineUsers = json_decode(file_get_contents(ONLINE_FILE), true);
    $onlineUsers[$username] = time();
    file_put_contents(ONLINE_FILE, json_encode($onlineUsers, JSON_PRETTY_PRINT));
}
?>