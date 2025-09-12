<?php
// 启用错误报告（调试用）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置默认时区
date_default_timezone_set('Asia/Shanghai');

// 定义存储数据的目录
$dataDir = __DIR__ . '/data/';
if (!is_dir($dataDir)) {
    if (!mkdir($dataDir, 0755, true)) {
        // 如果无法创建目录，显示错误页面
        renderErrorPage('无法创建数据目录');
        exit();
    }
}

// 处理POST请求（保存数据）
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 设置JSON头
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    
    handlePostRequest();
    exit();
}

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit();
}

// 处理GET请求 - 渲染完整页面
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // 如果是请求原始数据
    if (isset($_GET['raw'])) {
        header("Content-Type: application/json; charset=utf-8");
        handleGetRequest();
        exit();
    }
    
    // 否则渲染完整HTML页面
    renderFullPage();
    exit();
}

// 渲染完整页面的函数
function renderFullPage() {
    global $dataDir;
    
    // 检查是否请求特定日期的数据
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    // 验证日期格式
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        renderErrorPage('日期格式不正确');
        return;
    }
    
    $filename = $dataDir . 'scores_' . $date . '.json';
    
    if (!file_exists($filename)) {
        renderWelcomePage($date);
        return;
    }
    
    $data = file_get_contents($filename);
    
    if ($data === false) {
        renderErrorPage('无法读取数据文件');
        return;
    }
    
    renderHtmlTable($data, $date);
}

// 渲染欢迎页面（没有数据时）
function renderWelcomePage($date) {
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>德育分统计系统</title>
        <style>
            body { 
                font-family: "Microsoft YaHei", sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .container { 
                background: white; 
                padding: 40px; 
                border-radius: 15px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 600px;
                width: 90%;
            }
            h1 { 
                color: #333; 
                margin-bottom: 20px;
                font-size: 2.5em;
            }
            .welcome-text {
                color: #666;
                font-size: 1.2em;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .btn { 
                display: inline-block;
                padding: 15px 30px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 50px;
                margin: 10px;
                font-weight: bold;
                transition: transform 0.3s ease;
            }
            .btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            }
            .date-picker {
                margin: 20px 0;
            }
            .date-picker input {
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 8px;
                font-size: 16px;
                width: 200px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🏆 德育分统计系统</h1>
            <div class="welcome-text">
                <p>欢迎使用班级德育分统计系统！</p>
                <p>'. $date .' 暂无数据，请先从前端系统提交数据。</p>
            </div>
            
            <div class="date-picker">
                <form method="GET">
                    <input type="date" name="date" value="'. $date .'" onchange="this.form.submit()">
                </form>
            </div>
            
            <div>
                <a href="javascript:history.back()" class="btn">返回</a>
                <a href="score-api.php?date='. date('Y-m-d') .'" class="btn">查看今天</a>
            </div>
        </div>
    </body>
    </html>';
}

// 渲染HTML表格的函数
function renderHtmlTable($jsonData, $date) {
    $data = json_decode($jsonData, true);
    if (!$data || !isset($data['students'])) {
        renderErrorPage('无法解析数据或数据格式不正确');
        return;
    }
    
    $students = $data['students'];
    $timestamp = isset($data['timestamp']) ? $data['timestamp'] : '未知时间';
    
    // 计算小组积分
    $groupScores = [];
    foreach ($students as $student) {
        if (!isset($student['group']) || !isset($student['points'])) {
            continue;
        }
        $group = $student['group'];
        if (!isset($groupScores[$group])) {
            $groupScores[$group] = 0;
        }
        $groupScores[$group] += $student['points'];
    }
    arsort($groupScores);
    
    // 按个人积分排序
    usort($students, function($a, $b) {
        $pointsA = isset($a['points']) ? $a['points'] : 0;
        $pointsB = isset($b['points']) ? $b['points'] : 0;
        return $pointsB - $pointsA;
    });
    
    // 输出完整的HTML页面
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>德育分统计表 - '. $date .'</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            
            body {
                font-family: "Microsoft YaHei", "Segoe UI", sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                color: #333;
                line-height: 1.6;
                padding: 20px;
                min-height: 100vh;
            }
            
            .container {
                max-width: 1400px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #eee;
            }
            
            h1 {
                color: #2c3e50;
                font-size: 2.5em;
                margin-bottom: 10px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            
            .subtitle {
                color: #7f8c8d;
                font-size: 1.1em;
            }
            
            .controls {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                justify-content: center;
                margin: 25px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 10px;
            }
            
            .btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 25px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                border: none;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }
            
            .btn-secondary {
                background: linear-gradient(135deg, #fd746c 0%, #ff9068 100%);
            }
            
            .group-score {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 25px;
                border-radius: 10px;
                margin: 30px 0;
            }
            
            .group-item {
                display: flex;
                align-items: center;
                margin: 15px 0;
                padding: 15px;
                background: rgba(255,255,255,0.1);
                border-radius: 8px;
            }
            
            .group-rank {
                width: 40px;
                height: 40px;
                background: white;
                color: #667eea;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 1.2em;
                margin-right: 20px;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 25px 0;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            }
            
            th, td {
                padding: 15px;
                text-align: center;
                border-bottom: 1px solid #eee;
            }
            
            th {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                font-weight: 600;
                font-size: 1.1em;
            }
            
            tr:hover {
                background-color: #f8f9fa;
            }
            
            tr:nth-child(even) {
                background-color: #fafafa;
            }
            
            .points-positive {
                color: #27ae60;
                font-weight: bold;
            }
            
            .points-negative {
                color: #e74c3c;
                font-weight: bold;
            }
            
            .notes {
                text-align: left;
                max-width: 300px;
                font-size: 0.9em;
                color: #666;
            }
            
            .timestamp {
                text-align: center;
                color: #7f8c8d;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #eee;
            }
            
            @media (max-width: 768px) {
                .container {
                    padding: 15px;
                }
                
                .controls {
                    flex-direction: column;
                }
                
                .btn {
                    width: 100%;
                    justify-content: center;
                }
                
                table {
                    font-size: 0.9em;
                }
                
                th, td {
                    padding: 10px 5px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-trophy"></i> 德育分统计表</h1>
                <div class="subtitle">班级积分管理系统</div>
            </div>
            
            <div class="controls">
                <a href="?date='. $date .'&raw=true" class="btn" download="scores_'. $date .'.json">
                    <i class="fas fa-download"></i> 下载JSON数据
                </a>
                <a href="javascript:window.print()" class="btn">
                    <i class="fas fa-print"></i> 打印表格
                </a>
                <a href="score-api.php" class="btn">
                    <i class="fas fa-home"></i> 返回首页
                </a>
            </div>
            
            <div class="timestamp">
                <i class="fas fa-clock"></i> 统计时间: '. $timestamp .'
            </div>
            
            <div class="group-score">
                <h2><i class="fas fa-users"></i> 小组积分排行榜</h2>';
    
    $rank = 1;
    foreach ($groupScores as $group => $score) {
        echo '<div class="group-item">
                <div class="group-rank">'. $rank .'</div>
                <div style="flex-grow: 1;">
                    <strong>雁队 '. $group .'</strong>
                </div>
                <div style="font-size: 1.3em; font-weight: bold;">
                    '. $score .' 分
                </div>
              </div>';
        $rank++;
    }
    
    echo '</div>
            
            <h2 style="text-align: center; margin: 40px 0 20px 0; color: #2c3e50;">
                <i class="fas fa-user-graduate"></i> 个人积分排行榜
            </h2>
            
            <table>
                <thead>
                    <tr>
                        <th>排名</th>
                        <th>小组</th>
                        <th>学号</th>
                        <th>姓名</th>
                        <th>积分</th>
                        <th>备注</th>
                    </tr>
                </thead>
                <tbody>';
    
    $rank = 1;
    foreach ($students as $student) {
        $group = isset($student['group']) ? $student['group'] : '无';
        $id = isset($student['id']) ? $student['id'] : '无';
        $name = isset($student['name']) ? $student['name'] : '未知';
        $points = isset($student['points']) ? $student['points'] : 0;
        $pointsClass = $points > 0 ? 'points-positive' : ($points < 0 ? 'points-negative' : '');
        $notes = isset($student['notes']) && is_array($student['notes']) ? implode('<br>', $student['notes']) : '';
        
        echo '<tr>
                <td><strong>'. $rank .'</strong></td>
                <td>雁队 '. $group .'</td>
                <td>'. $id .'</td>
                <td><strong>'. $name .'</strong></td>
                <td class="'. $pointsClass .'"><strong>'. $points .'</strong></td>
                <td class="notes">'. $notes .'</td>
              </tr>';
        $rank++;
    }
    
    echo '</tbody>
            </table>
            
            <div class="timestamp">
                生成时间: '. date('Y-m-d H:i:s') .' | 共 '. count($students) .' 名学生
            </div>
        </div>
        
        <script>
            // 添加一些交互效果
            document.addEventListener("DOMContentLoaded", function() {
                // 表格行点击效果
                const tableRows = document.querySelectorAll("tbody tr");
                tableRows.forEach(row => {
                    row.addEventListener("click", function() {
                        this.style.backgroundColor = this.style.backgroundColor === "rgb(240, 240, 240)" 
                            ? "" 
                            : "#f0f0f0";
                    });
                });
                
                // 打印按钮功能
                const printBtn = document.querySelector("[href=\"javascript:window.print()\"]");
                printBtn.addEventListener("click", function() {
                    window.print();
                });
            });
        </script>
    </body>
    </html>';
}

// 处理POST请求的函数
function handlePostRequest() {
    global $dataDir;
    
    // 获取原始POST数据
    $json = file_get_contents('php://input');
    
    if (empty($json)) {
        sendJsonResponse(false, '没有接收到数据');
    }
    
    $data = json_decode($json, true);
    
    if ($data === null) {
        sendJsonResponse(false, '无效的JSON数据: ' . json_last_error_msg());
    }
    
    // 验证必需字段
    if (!isset($data['students']) || !is_array($data['students'])) {
        sendJsonResponse(false, '数据格式不正确，缺少students数组');
    }
    
    // 添加时间戳
    $data['timestamp'] = date('Y-m-d H:i:s');
    $data['received_at'] = time();
    
    // 生成文件名
    $date = date('Y-m-d');
    $filename = $dataDir . 'scores_' . $date . '.json';
    
    // 保存数据到文件
    $result = file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($result === false) {
        sendJsonResponse(false, '数据保存失败，请检查目录权限');
    }
    
    // 成功响应
    sendJsonResponse(true, '数据保存成功', [
        'timestamp' => $data['timestamp'],
        'file' => basename($filename),
        'students_count' => count($data['students'])
    ]);
}

// 处理GET请求的函数（返回原始JSON）
function handleGetRequest() {
    global $dataDir;
    
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        sendJsonResponse(false, '日期格式不正确');
    }
    
    $filename = $dataDir . 'scores_' . $date . '.json';
    
    if (!file_exists($filename)) {
        sendJsonResponse(false, '未找到' . $date . '的数据');
    }
    
    $data = file_get_contents($filename);
    
    if ($data === false) {
        sendJsonResponse(false, '无法读取数据文件');
    }
    
    echo $data;
}

// 发送JSON响应的函数
function sendJsonResponse($success, $message, $additionalData = []) {
    $response = array_merge([
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], $additionalData);
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

// 渲染错误页面的函数
function renderErrorPage($message) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>错误 - 德育分统计系统</title>
        <style>
            body { 
                font-family: "Microsoft YaHei", sans-serif; 
                background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                color: white;
            }
            .error-container {
                text-align: center;
                padding: 40px;
                background: rgba(255,255,255,0.1);
                border-radius: 15px;
                backdrop-filter: blur(10px);
            }
            h1 { margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>😕 出错了</h1>
        <p>'. htmlspecialchars($message) .'</p>
            <p><a href="score-api.php" style="color: white; text-decoration: underline;">返回首页</a></p>
        </div>
    </body>
    </html>';
}
?>