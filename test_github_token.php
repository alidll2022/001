<?php
// GitHub Token 测试文件

// 测试 GitHub API 连接
function testGitHubAPI($endpoint, $token) {
    $url = 'https://api.github.com' . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: PHP GitHub Test',
        'Authorization: token ' . $token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 禁用 SSL 验证（仅用于测试）
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 设置连接超时
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => $httpCode == 200,
        'data' => json_decode($response, true),
        'http_code' => $httpCode,
        'response' => $response,
        'curl_error' => $curlError
    ];
}

// 测试结果
$token = 'YOUR_GITHUB_TOKEN_HERE'; // 请在此处填写您的 GitHub Token

// 初始化测试结果
$testResults = [
    'user' => testGitHubAPI('/user', $token),
    'repos' => testGitHubAPI('/user/repos?per_page=5', $token),
    'github' => testGitHubAPI('/users/github', $token),
    'create_repo' => [],
    'upload' => []
];

// 测试 4: 创建仓库
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_repo'])) {
    $repo_name = $_POST['new_repo_name'] ?? '';
    $description = $_POST['repo_description'] ?? '';
    $private = isset($_POST['repo_private']) ? true : false;
    
    if ($repo_name) {
        $testResults['create_repo'] = createGitHubRepo($repo_name, $description, $private, $token);
        // 重新获取仓库列表
        $userRepos = getUserRepos($token);
    }
}

// 测试 5: 上传文件到仓库
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])) {
    $owner = $_POST['repo_owner'] ?? 'alidi2022';
    $repo = $_POST['repo_name'] ?? '';
    $path = $_POST['file_path'] ?? '';
    $content = $_POST['file_content'] ?? '';
    $message = $_POST['commit_message'] ?? 'Upload file via API';
    
    if ($repo && $path && $content) {
        $testResults['upload'] = uploadFileToGitHub($owner, $repo, $path, $content, $message, $token);
    }
}

// 获取用户仓库列表
function getUserRepos($token) {
    $url = 'https://api.github.com/user/repos?per_page=100';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: PHP GitHub Test',
        'Authorization: token ' . $token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return json_decode($response, true);
    }
    return [];
}

// 创建 GitHub 仓库
function createGitHubRepo($name, $description, $private, $token) {
    $url = 'https://api.github.com/user/repos';
    
    $data = [
        'name' => $name,
        'description' => $description,
        'private' => $private
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: PHP GitHub Test',
        'Authorization: token ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => in_array($httpCode, [200, 201]),
        'data' => json_decode($response, true),
        'http_code' => $httpCode,
        'response' => $response,
        'curl_error' => $curlError
    ];
}

// 上传文件到 GitHub 仓库
function uploadFileToGitHub($owner, $repo, $path, $content, $message, $token) {
    $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$path}";
    
    $data = [
        'message' => $message,
        'content' => base64_encode($content),
        'branch' => 'main' // 默认为 main 分支
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: PHP GitHub Test',
        'Authorization: token ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // 禁用 HTTP/2，使用 HTTP/1.1
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => in_array($httpCode, [200, 201]),
        'data' => json_decode($response, true),
        'http_code' => $httpCode,
        'response' => $response,
        'curl_error' => $curlError
    ];
}

// 获取用户仓库列表
$userRepos = getUserRepos($token);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Token 测试</title>
    <!-- 引入外部资源 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    
    <!-- Tailwind 配置 -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#24292e',
                        secondary: '#666666',
                        success: '#52c41a',
                        danger: '#ff4d4f',
                    },
                }
            }
        }
    </script>
    
    <!-- 自定义样式 -->
    <style type="text/tailwindcss">
        @layer utilities {
            .shadow-soft {
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            }
            .transition-all-300 {
                transition: all 0.3s ease;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800 min-h-screen">
    <!-- 顶部导航 -->
    <header class="bg-primary text-white shadow-soft sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fa fa-github text-2xl"></i>
                <h1 class="text-xl md:text-2xl font-bold">GitHub Token 测试</h1>
            </div>
        </div>
    </header>

    <!-- 主内容区 -->
    <main class="container mx-auto px-4 py-8 max-w-3xl">
        <!-- Token 信息 -->
        <div class="bg-white rounded-xl shadow-soft p-6 mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center">
                <i class="fa fa-key text-primary mr-2"></i> Token 信息
            </h2>
            <div class="bg-gray-100 p-4 rounded">
                <p class="text-sm font-mono break-all">
                    <?php echo $token; ?>
                </p>
            </div>
        </div>
        
        <!-- 测试结果 -->
        <div class="bg-white rounded-xl shadow-soft p-6 mb-8">
            <h2 class="text-lg font-bold mb-6 flex items-center">
                <i class="fa fa-check-circle text-primary mr-2"></i> 测试结果
            </h2>
            
            <!-- 测试 1: 当前用户信息 -->
            <div class="mb-6">
                <h3 class="font-medium mb-3">测试 1: 获取当前用户信息</h3>
                <div class="border rounded-lg p-4">
                    <?php if ($testResults['user']['success']): ?>
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full overflow-hidden mr-4">
                            <img src="<?php echo $testResults['user']['data']['avatar_url']; ?>" alt="User Avatar" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-primary">连接成功</h4>
                            <p class="text-gray-600">用户: <?php echo $testResults['user']['data']['login']; ?></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded">
                            <h5 class="text-sm font-medium text-gray-600 mb-1">用户名</h5>
                            <p class="text-sm font-medium"><?php echo $testResults['user']['data']['login']; ?></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <h5 class="text-sm font-medium text-gray-600 mb-1">邮箱</h5>
                            <p class="text-sm"><?php echo $testResults['user']['data']['email'] ?? '未设置'; ?></p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-danger/20 rounded-full flex items-center justify-center mr-4">
                            <i class="fa fa-times text-danger text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-danger">连接失败</h4>
                            <p class="text-gray-600">HTTP 状态码: <?php echo $testResults['user']['http_code']; ?></p>
                        </div>
                    </div>
                    <div class="bg-danger/10 p-3 rounded">
                        <h5 class="text-sm font-medium text-danger mb-1">错误信息</h5>
                        <p class="text-sm font-mono break-all"><?php echo htmlspecialchars($testResults['user']['response']); ?></p>
                        <p class="text-sm font-mono break-all mt-2 text-red-600">cURL 错误: <?php echo htmlspecialchars($testResults['user']['curl_error']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 测试 2: 仓库列表 -->
            <div class="mb-6">
                <h3 class="font-medium mb-3">测试 2: 获取用户仓库列表</h3>
                <div class="border rounded-lg p-4">
                    <?php if ($testResults['repos']['success']): ?>
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-success/20 rounded-full flex items-center justify-center mr-4">
                            <i class="fa fa-check text-success text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-success">连接成功</h4>
                            <p class="text-gray-600">获取到 <?php echo count($testResults['repos']['data']); ?> 个仓库</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($testResults['repos']['data'] as $repo): ?>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="flex justify-between items-start">
                                <h5 class="text-sm font-medium"><?php echo $repo['name']; ?></h5>
                                <a href="<?php echo $repo['html_url']; ?>" target="_blank" class="text-primary hover:underline text-xs">查看</a>
                            </div>
                            <p class="text-xs text-gray-600 mt-1"><?php echo $repo['description'] ?? '无描述'; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-danger/20 rounded-full flex items-center justify-center mr-4">
                            <i class="fa fa-times text-danger text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-danger">连接失败</h4>
                            <p class="text-gray-600">HTTP 状态码: <?php echo $testResults['repos']['http_code']; ?></p>
                        </div>
                    </div>
                    <div class="bg-danger/10 p-3 rounded">
                        <h5 class="text-sm font-medium text-danger mb-1">错误信息</h5>
                        <p class="text-sm font-mono break-all"><?php echo htmlspecialchars($testResults['repos']['response']); ?></p>
                        <p class="text-sm font-mono break-all mt-2 text-red-600">cURL 错误: <?php echo htmlspecialchars($testResults['repos']['curl_error']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 测试 3: GitHub 官方用户 -->
            <div>
                <h3 class="font-medium mb-3">测试 3: 获取 GitHub 官方用户信息</h3>
                <div class="border rounded-lg p-4">
                    <?php if ($testResults['github']['success']): ?>
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full overflow-hidden mr-4">
                            <img src="<?php echo $testResults['github']['data']['avatar_url']; ?>" alt="GitHub Logo" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-success">连接成功</h4>
                            <p class="text-gray-600">GitHub 官方账号信息</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded">
                            <h5 class="text-sm font-medium text-gray-600 mb-1">用户名</h5>
                            <p class="text-sm font-medium"><?php echo $testResults['github']['data']['login']; ?></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <h5 class="text-sm font-medium text-gray-600 mb-1">仓库数</h5>
                            <p class="text-sm"><?php echo $testResults['github']['data']['public_repos']; ?></p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-danger/20 rounded-full flex items-center justify-center mr-4">
                            <i class="fa fa-times text-danger text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-danger">连接失败</h4>
                            <p class="text-gray-600">HTTP 状态码: <?php echo $testResults['github']['http_code']; ?></p>
                        </div>
                    </div>
                    <div class="bg-danger/10 p-3 rounded">
                        <h5 class="text-sm font-medium text-danger mb-1">错误信息</h5>
                        <p class="text-sm font-mono break-all"><?php echo htmlspecialchars($testResults['github']['response']); ?></p>
                        <p class="text-sm font-mono break-all mt-2 text-red-600">cURL 错误: <?php echo htmlspecialchars($testResults['github']['curl_error']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 创建仓库 -->
        <div class="bg-white rounded-xl shadow-soft p-6 mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center">
                <i class="fa fa-plus-circle text-primary mr-2"></i> 创建 GitHub 仓库
            </h2>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">仓库名称</label>
                    <input type="text" name="new_repo_name" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50" placeholder="例如: test-repo">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">仓库描述</label>
                    <textarea name="repo_description" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50" rows="2" placeholder="描述您的仓库..."></textarea>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="repo_private" class="mr-2">
                        <span class="text-sm">设为私有仓库</span>
                    </label>
                </div>
                
                <div class="flex justify-center">
                    <button type="submit" name="create_repo" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary/90 transition-all-300 flex items-center">
                        <i class="fa fa-plus mr-2"></i> 创建仓库
                    </button>
                </div>
                <input type="hidden" name="action" value="create_repo">
            </form>
            
            <!-- 创建仓库结果 -->
            <div class="mt-6">
                <div class="bg-gray-100 border-l-4 border-gray-300 p-4 rounded">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                            <i class="fa fa-info text-gray-500 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-gray-700">等待创建</h4>
                            <p class="text-gray-600">请填写仓库信息并点击创建按钮</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 文件上传 -->
        <div class="bg-white rounded-xl shadow-soft p-6 mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center">
                <i class="fa fa-upload text-primary mr-2"></i> 上传文件到 GitHub 仓库
            </h2>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">仓库所有者</label>
                        <input type="text" name="repo_owner" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50" value="alidi2022" placeholder="例如: alidi2022">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">仓库名称</label>
                        <select name="repo_name" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option value="">选择仓库</option>
                            <?php foreach ($userRepos as $repo): ?>
                            <option value="<?php echo htmlspecialchars($repo['name']); ?>"><?php echo htmlspecialchars($repo['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-red-500 mt-1">注意：仓库必须存在且您的 Token 必须有写入权限</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">选择文件</label>
                    <input type="file" id="file_input" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <p class="text-xs text-gray-500 mt-1">选择本地文件后会自动填充文件路径和内容</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">文件路径</label>
                    <input type="text" name="file_path" id="file_path" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50" placeholder="例如: test.txt">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">文件内容</label>
                    <textarea name="file_content" id="file_content" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50" rows="4" placeholder="输入文件内容..."></textarea>
                </div>
                
                <!-- JavaScript 代码 -->
                <script>
                    document.getElementById('file_input').addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            // 填充文件路径
                            document.getElementById('file_path').value = file.name;
                            
                            // 读取文件内容
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                document.getElementById('file_content').value = e.target.result;
                            };
                            reader.readAsText(file);
                        }
                    });
                </script>
                
                <div>
                    <label class="block text-sm font-medium mb-2">提交信息</label>
                    <input type="text" name="commit_message" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50" value="Upload file via API" placeholder="例如: Add test file">
                </div>
                
                <div class="flex justify-center">
                    <button type="submit" name="upload_file" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary/90 transition-all-300 flex items-center">
                        <i class="fa fa-cloud-upload mr-2"></i> 上传文件
                    </button>
                </div>
            </form>
            
            <!-- 上传结果 -->
            <div class="mt-6 border rounded-lg p-4">
                <h3 class="font-medium mb-3">上传结果</h3>
                <div class="bg-gray-100 border-l-4 border-gray-300 p-4 rounded">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                            <i class="fa fa-info text-gray-500 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-gray-700">等待上传</h4>
                            <p class="text-gray-600">请选择文件并点击上传按钮</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 故障排除 -->
        <div class="bg-white rounded-xl shadow-soft p-6">
            <h2 class="text-lg font-bold mb-4 flex items-center">
                <i class="fa fa-info-circle text-primary mr-2"></i> 故障排除
            </h2>
            <div class="space-y-4">
                <div class="p-3 border rounded">
                    <h3 class="font-medium mb-2">常见问题</h3>
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        <li>Token 权限不足 - 确保 Token 有适当的权限</li>
                        <li>网络连接问题 - 检查服务器网络连接</li>
                        <li>Token 已过期或被撤销 - 检查 Token 状态</li>
                        <li>GitHub API 限制 - 可能达到了 API 速率限制</li>
                    </ul>
                </div>
                <div class="p-3 border rounded">
                    <h3 class="font-medium mb-2">Token 权限建议</h3>
                    <p class="text-sm">对于基本功能，建议 Token 至少具有以下权限：</p>
                    <ul class="list-disc list-inside space-y-1 text-sm mt-2">
                        <li>repo - 访问仓库</li>
                        <li>user - 访问用户信息</li>
                    </ul>
                </div>
                <div class="p-3 border rounded">
                    <h3 class="font-medium mb-2">文件上传注意事项</h3>
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        <li>确保仓库存在且 Token 有写入权限</li>
                        <li>文件路径应包含文件名和扩展名</li>
                        <li>文件内容将被 base64 编码上传</li>
                        <li>默认上传到 main 分支</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- 结果提示模态框 -->
        <div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
                <div class="p-4 border-b">
                    <h3 id="modalTitle" class="text-lg font-medium"></h3>
                </div>
                <div id="modalContent" class="p-6">
                    <!-- 内容将通过 JavaScript 动态填充 -->
                </div>
                <div class="p-4 border-t flex justify-end">
                    <button id="closeModal" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">
                        关闭
                    </button>
                </div>
            </div>
        </div>
        
        <!-- JavaScript 代码 -->
        <script>
            // 等待 DOM 加载完成
            document.addEventListener('DOMContentLoaded', function() {
                // 显示结果模态框
                function showResultModal(title, content) {
                    document.getElementById('modalTitle').textContent = title;
                    document.getElementById('modalContent').innerHTML = content;
                    document.getElementById('resultModal').classList.remove('hidden');
                }
                
                // 关闭结果模态框
                document.getElementById('closeModal').addEventListener('click', function() {
                    document.getElementById('resultModal').classList.add('hidden');
                });
                
                // 点击模态框外部关闭
                document.getElementById('resultModal').addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.add('hidden');
                    }
                });
                
                // 检查是否需要显示结果
                <?php if (!empty($testResults['create_repo'])): ?>
                    <?php if ($testResults['create_repo']['success']): ?>
                        showResultModal('创建仓库成功', `
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-success/20 rounded-full flex items-center justify-center mr-4">
                                    <i class="fa fa-check text-success text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-medium text-success">创建成功</h4>
                                    <p class="text-gray-600">仓库已成功创建</p>
                                </div>
                            </div>
                            <div class="space-y-3 mt-4">
                                <div class="bg-gray-50 p-3 rounded">
                                    <h5 class="text-sm font-medium text-gray-600 mb-1">仓库名称</h5>
                                    <p class="text-sm font-medium"><?php echo htmlspecialchars($testResults['create_repo']['data']['name']); ?></p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <h5 class="text-sm font-medium text-gray-600 mb-1">仓库 URL</h5>
                                    <a href="<?php echo htmlspecialchars($testResults['create_repo']['data']['html_url']); ?>" target="_blank" class="text-primary hover:underline text-sm break-all"><?php echo htmlspecialchars($testResults['create_repo']['data']['html_url']); ?></a>
                                </div>
                            </div>
                        `);
                    <?php else: ?>
                        showResultModal('创建仓库失败', `
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-danger/20 rounded-full flex items-center justify-center mr-4">
                                    <i class="fa fa-times text-danger text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-medium text-danger">创建失败</h4>
                                    <p class="text-gray-600">HTTP 状态码: <?php echo $testResults['create_repo']['http_code']; ?></p>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded shadow-sm mt-4">
                                <h5 class="text-sm font-medium text-danger mb-2">错误信息</h5>
                                <div class="bg-gray-100 p-3 rounded font-mono text-sm break-all">
                                    <?php echo htmlspecialchars($testResults['create_repo']['response']); ?>
                                </div>
                            </div>
                            <div class="bg-yellow-100 border-l-4 border-yellow-400 p-4 mt-4 rounded">
                                <h5 class="text-sm font-medium text-yellow-800 mb-2">权限不足</h5>
                                <p class="text-sm text-yellow-700">您的个人访问令牌没有创建仓库的权限。请按照以下步骤创建具有正确权限的 Token：</p>
                                <ol class="list-decimal list-inside space-y-1 text-sm text-yellow-700 mt-2">
                                    <li>登录 GitHub</li>
                                    <li>进入 <strong>Settings</strong> → <strong>Developer settings</strong> → <strong>Personal access tokens</strong></li>
                                    <li>点击 <strong>Generate new token</strong></li>
                                    <li>设置 Token 名称和过期时间</li>
                                    <li>在 <strong>Select scopes</strong> 中勾选 <strong>repo</strong> 权限</li>
                                    <li>点击 <strong>Generate token</strong> 生成新 Token</li>
                                    <li>使用新 Token 替换当前 Token</li>
                                </ol>
                            </div>
                        `);
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (!empty($testResults['upload'])): ?>
                    <?php if ($testResults['upload']['success']): ?>
                        showResultModal('上传文件成功', `
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-success/20 rounded-full flex items-center justify-center mr-4">
                                    <i class="fa fa-check text-success text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-medium text-success">上传成功</h4>
                                    <p class="text-gray-600">文件已成功上传到 GitHub 仓库</p>
                                </div>
                            </div>
                            <div class="space-y-3 mt-4">
                                <div class="bg-gray-50 p-3 rounded">
                                    <h5 class="text-sm font-medium text-gray-600 mb-1">文件路径</h5>
                                    <p class="text-sm font-medium font-mono"><?php echo htmlspecialchars($testResults['upload']['data']['path']); ?></p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <h5 class="text-sm font-medium text-gray-600 mb-1">文件 URL</h5>
                                    <a href="<?php echo htmlspecialchars($testResults['upload']['data']['html_url']); ?>" target="_blank" class="text-primary hover:underline text-sm break-all"><?php echo htmlspecialchars($testResults['upload']['data']['html_url']); ?></a>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <h5 class="text-sm font-medium text-gray-600 mb-1">下载链接</h5>
                                    <a href="<?php echo htmlspecialchars($testResults['upload']['data']['download_url']); ?>" target="_blank" class="text-primary hover:underline text-sm break-all"><?php echo htmlspecialchars($testResults['upload']['data']['download_url']); ?></a>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <h5 class="text-sm font-medium text-gray-600 mb-1">提交信息</h5>
                                    <p class="text-sm"><?php echo htmlspecialchars($_POST['commit_message'] ?? 'Upload file via API'); ?></p>
                                </div>
                            </div>
                        `);
                    <?php else: ?>
                        showResultModal('上传文件失败', `
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-danger/20 rounded-full flex items-center justify-center mr-4">
                                    <i class="fa fa-times text-danger text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-medium text-danger">上传失败</h4>
                                    <p class="text-gray-600">HTTP 状态码: <?php echo $testResults['upload']['http_code']; ?></p>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded shadow-sm mt-4">
                                <h5 class="text-sm font-medium text-danger mb-2">错误信息</h5>
                                <div class="bg-gray-100 p-3 rounded font-mono text-sm break-all">
                                    <?php echo htmlspecialchars($testResults['upload']['response']); ?>
                                </div>
                                <?php if (!empty($testResults['upload']['curl_error'])): ?>
                                <div class="mt-3 bg-gray-100 p-3 rounded font-mono text-sm break-all text-red-600">
                                    cURL 错误: <?php echo htmlspecialchars($testResults['upload']['curl_error']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        `);
                    <?php endif; ?>
                <?php endif; ?>
            });
        </script>
    </main>

    <!-- 页脚 -->
    <footer class="bg-primary text-white border-t mt-8 py-6">
        <div class="container mx-auto px-4 text-center text-sm">
            <p>GitHub Token 测试工具 &copy; 2026</p>
        </div>
    </footer>
</body>
</html>