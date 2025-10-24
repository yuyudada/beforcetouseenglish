<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理 OPTIONS 请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 数据文件路径
$dataFile = __DIR__ . '/data/data.json';

// 确保数据目录存在
$dataDir = dirname($dataFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// 初始化数据文件
if (!file_exists($dataFile)) {
    $initialData = [
        'reports' => []
    ];
    file_put_contents($dataFile, json_encode($initialData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// 读取数据
function readData() {
    global $dataFile;
    $content = file_get_contents($dataFile);
    return json_decode($content, true);
}

// 保存数据
function saveData($data) {
    global $dataFile;
    return file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// 生成唯一ID
function generateId() {
    return uniqid() . '_' . time();
}

// 获取请求数据
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// 处理不同的操作
try {
    switch ($action) {
        case 'list':
            // 获取所有自述
            $data = readData();
            echo json_encode([
                'success' => true,
                'data' => $data['reports'] ?? []
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'create':
            // 创建新自述
            $title = trim($input['title'] ?? '');
            $content = trim($input['content'] ?? '');
            $author = trim($input['author'] ?? '');

            if (empty($title) || empty($content) || empty($author)) {
                throw new Exception('标题、内容和作者姓名不能为空');
            }

            $data = readData();
            $newReport = [
                'id' => generateId(),
                'title' => $title,
                'content' => $content,
                'author' => $author,
                'created_at' => date('Y-m-d H:i:s'),
                'ratings' => [],
                'average_rating' => 0,
                'rating_count' => 0
            ];

            $data['reports'][] = $newReport;
            saveData($data);

            echo json_encode([
                'success' => true,
                'message' => '自述发表成功',
                'data' => $newReport
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'rate':
            // 提交评分
            $reportId = $input['report_id'] ?? '';
            $rating = intval($input['rating'] ?? 0);
            $rater = trim($input['rater'] ?? '');

            if (empty($reportId) || $rating < 1 || $rating > 5 || empty($rater)) {
                throw new Exception('评分数据不完整或无效');
            }

            $data = readData();
            $reportIndex = -1;

            // 查找对应的自述
            foreach ($data['reports'] as $index => $report) {
                if ($report['id'] === $reportId) {
                    $reportIndex = $index;
                    break;
                }
            }

            if ($reportIndex === -1) {
                throw new Exception('找不到对应的自述');
            }

            // 检查是否已经评分
            foreach ($data['reports'][$reportIndex]['ratings'] as $existingRating) {
                if ($existingRating['rater'] === $rater) {
                    throw new Exception('您已经对此自述评分过了');
                }
            }

            // 添加评分
            $data['reports'][$reportIndex]['ratings'][] = [
                'rater' => $rater,
                'rating' => $rating,
                'rated_at' => date('Y-m-d H:i:s')
            ];

            // 计算平均分
            $totalRating = array_sum(array_column($data['reports'][$reportIndex]['ratings'], 'rating'));
            $ratingCount = count($data['reports'][$reportIndex]['ratings']);
            $data['reports'][$reportIndex]['average_rating'] = round($totalRating / $ratingCount, 1);
            $data['reports'][$reportIndex]['rating_count'] = $ratingCount;

            saveData($data);

            echo json_encode([
                'success' => true,
                'message' => '评分提交成功',
                'data' => [
                    'average_rating' => $data['reports'][$reportIndex]['average_rating'],
                    'rating_count' => $data['reports'][$reportIndex]['rating_count']
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'update':
            // 编辑自述
            $reportId = $input['report_id'] ?? '';
            $title = trim($input['title'] ?? '');
            $content = trim($input['content'] ?? '');
            $author = trim($input['author'] ?? '');

            if (empty($reportId) || empty($title) || empty($content) || empty($author)) {
                throw new Exception('数据不完整');
            }

            $data = readData();
            $reportIndex = -1;

            // 查找对应的自述
            foreach ($data['reports'] as $index => $report) {
                if ($report['id'] === $reportId) {
                    $reportIndex = $index;
                    break;
                }
            }

            if ($reportIndex === -1) {
                throw new Exception('找不到对应的自述');
            }

            // 更新自述
            $data['reports'][$reportIndex]['title'] = $title;
            $data['reports'][$reportIndex]['content'] = $content;
            $data['reports'][$reportIndex]['author'] = $author;

            saveData($data);

            echo json_encode([
                'success' => true,
                'message' => '自述更新成功'
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'delete':
            // 删除自述
            $reportId = $input['report_id'] ?? '';

            if (empty($reportId)) {
                throw new Exception('缺少自述ID');
            }

            $data = readData();
            $reportIndex = -1;

            // 查找对应的自述
            foreach ($data['reports'] as $index => $report) {
                if ($report['id'] === $reportId) {
                    $reportIndex = $index;
                    break;
                }
            }

            if ($reportIndex === -1) {
                throw new Exception('找不到对应的自述');
            }

            // 删除自述
            array_splice($data['reports'], $reportIndex, 1);
            saveData($data);

            echo json_encode([
                'success' => true,
                'message' => '自述删除成功'
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'export':
            // 导出数据
            $data = readData();
            $reports = $data['reports'] ?? [];

            // 设置 CSV 文件头
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="团员评议数据_' . date('Y-m-d') . '.csv"');

            // 输出 CSV 内容
            $output = fopen('php://output', 'w');
            
            // 添加 BOM 以支持中文
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // 写入表头
            fputcsv($output, ['ID', '标题', '作者', '创建时间', '平均评分', '评分人数', '评分详情', '自述内容']);

            // 写入数据
            foreach ($reports as $report) {
                $ratingsDetail = '';
                if (!empty($report['ratings'])) {
                    $ratingDetails = [];
                    foreach ($report['ratings'] as $rating) {
                        $ratingDetails[] = $rating['rater'] . '(' . $rating['rating'] . '分)';
                    }
                    $ratingsDetail = implode(', ', $ratingDetails);
                }

                fputcsv($output, [
                    $report['id'],
                    $report['title'],
                    $report['author'],
                    $report['created_at'],
                    $report['average_rating'],
                    $report['rating_count'],
                    $ratingsDetail,
                    $report['content']
                ]);
            }

            fclose($output);
            exit;

        default:
            throw new Exception('无效的操作');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
