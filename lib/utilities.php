<?php
/**
 * ユーティリティ関数
 */

/**
 * セッションをリセット
 */
function resetSession() {
    if (isset($_SESSION['promptman'])) {
        unset($_SESSION['promptman']);
    }
}

/**
 * セッション情報を取得
 */
function getSession() {
    return $_SESSION['promptman'] ?? null;
}

/**
 * 文字列をサニタイズ
 */
function sanitize($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * テキストを切り詰め
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * 現在の日時を取得
 */
function now() {
    return date('Y-m-d H:i:s');
}
