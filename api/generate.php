<?php
require_once __DIR__ . '/config.php';

/**
 * プロンプト生成API
 * 
 * POST /api/generate.php
 * {
 *   "intent_type": "2",
 *   "structure_confirmed": true
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "executable_prompt": "以下の条件で、Zenn向けの記事本文を...",
 *     "media": "zenn",
 *     "theme": "...",
 *     "structure": [...]
 *   }
 * }
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('POST メソッドのみ対応しています', 405);
}

// セッションチェック
if (!isset($_SESSION['promptman'])) {
    errorResponse('セッションが存在しません。最初から操作をやり直してください', 400);
}

// 入力取得
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('無効な JSON 入力です');
}

// バリデーション
if (empty($input['intent_type'])) {
    errorResponse('意図タイプを選択してください');
}

$intentType = trim($input['intent_type']);
$structureConfirmed = isset($input['structure_confirmed']) ? (bool)$input['structure_confirmed'] : false;

// セッションから情報取得
$session = $_SESSION['promptman'];
$theme = $session['theme'];
$media = $session['media'];

try {
    // PromptBuilder 初期化
    $builder = new PromptBuilder();
    $analyzer = new IntentAnalyzer();
    
    // 意図タイプの妥当性チェック
    if (!$analyzer->validateChoice($intentType)) {
        errorResponse('無効な意図タイプです');
    }
    
    // 構成案生成
    $structure = $builder->generateStructure($media, $intentType);
    
    // 構成確認が必要な場合
    if (!$structureConfirmed) {
        // 構成案のみ返す（Agentic 挙動）
        successResponse([
            'needs_confirmation' => true,
            'structure' => $structure,
            'media' => $media,
            'theme' => $theme,
            'confirmation_message' => 'この構成で進めて良いですか？'
        ]);
    }
    
    // 実行用プロンプト生成
    $executablePrompt = $builder->buildExecutablePrompt(
        $media,
        $theme,
        $intentType,
        $structure
    );
    
    // セッションに保存
    $_SESSION['promptman']['intent_type'] = $intentType;
    $_SESSION['promptman']['structure'] = $structure;
    $_SESSION['promptman']['executable_prompt'] = $executablePrompt;
    $_SESSION['promptman']['generated_at'] = time();
    
    successResponse([
        'executable_prompt' => $executablePrompt,
        'media' => $media,
        'theme' => $theme,
        'structure' => $structure,
        'intent_type' => $intentType
    ]);
    
} catch (Exception $e) {
    errorResponse('プロンプト生成に失敗しました: ' . $e->getMessage(), 500);
}
