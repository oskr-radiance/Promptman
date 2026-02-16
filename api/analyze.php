<?php
require_once __DIR__ . '/config.php';

/**
 * 意図解析API
 * 
 * POST /api/analyze.php
 * {
 *   "theme": "AIの設定を見直したら業務が楽になった話",
 *   "media": "note"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "options": {...},
 *     "selection_prompt": "..."
 *   }
 * }
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('POST メソッドのみ対応しています', 405);
}

// 入力取得
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('無効な JSON 入力です');
}

// バリデーション
if (empty($input['theme'])) {
    errorResponse('テーマを入力してください');
}

if (empty($input['media'])) {
    errorResponse('媒体を選択してください');
}

$theme = trim($input['theme']);
$media = strtolower(trim($input['media']));

// 媒体の妥当性チェック
$allowedMedia = ['note', 'zenn', 'x'];
if (!in_array($media, $allowedMedia)) {
    errorResponse('媒体は note, zenn, x のいずれかを指定してください');
}

try {
    // 意図解析 → 三択オプション生成
    $analyzer = new IntentAnalyzer();
    $result = $analyzer->analyzeForOptions($theme, $media);

    // セッションに保存
    $_SESSION['promptman'] = [
        'theme' => $theme,
        'media' => $media,
        'analysis_time' => time()
    ];

    successResponse($result);

} catch (Exception $e) {
    errorResponse('意図解析に失敗しました: ' . $e->getMessage(), 500);
}
