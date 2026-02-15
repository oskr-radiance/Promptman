<?php
/**
 * EnvLoader - 環境変数読み込み
 */
class EnvLoader {
    private static $loaded = false;
    private static $env = [];
    
    /**
     * .env ファイルを読み込み
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($path)) {
            // .env がない場合は .env.example をコピー
            $examplePath = dirname(__DIR__) . '/.env.example';
            if (file_exists($examplePath)) {
                copy($examplePath, $path);
            }
        }
        
        if (!file_exists($path)) {
            throw new Exception('.env ファイルが見つかりません');
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // コメント行をスキップ
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // KEY=VALUE をパース
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // 環境変数に設定
                if (!array_key_exists($key, $_ENV)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    self::$env[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * 環境変数を取得
     */
    public static function get($key, $default = null) {
        self::load();
        
        // 優先順位: $_ENV > $_SERVER > self::$env
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        
        if (isset(self::$env[$key])) {
            return self::$env[$key];
        }
        
        return $default;
    }
    
    /**
     * 必須の環境変数を取得（なければエラー）
     */
    public static function require($key) {
        $value = self::get($key);
        
        if ($value === null) {
            throw new Exception("環境変数 {$key} が設定されていません");
        }
        
        return $value;
    }
}
