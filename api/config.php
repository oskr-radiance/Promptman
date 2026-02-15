<?php
/**
 * API 共通設定
 */

// エラーレポート（開発時のみ有効化）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// タイムゾーン
date_default_timezone_set('Asia/Tokyo');

// CORS設定（開発時）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// JSON レスポンス設定
header('Content-Type: application/json; charset=utf-8');

// セッション設定
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// パス定義
define('ROOT_PATH', dirname(__DIR__));
define('LIB_PATH', ROOT_PATH . '/lib');
define('DATA_PATH', ROOT_PATH . '/data');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// オートロード
spl_autoload_register(function ($class) {
    $file = LIB_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * JSON レスポンスを返す
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * エラーレスポンスを返す
 */
function errorResponse($message, $status = 400) {
    jsonResponse([
        'success' => false,
        'error' => $message
    ], $status);
}

/**
 * 成功レスポンスを返す
 */
function successResponse($data) {
    jsonResponse([
        'success' => true,
        'data' => $data
    ]);
}
