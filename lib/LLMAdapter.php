<?php
/**
 * LLMAdapter - LLMプロバイダー抽象層
 */
abstract class LLMAdapter {
    protected $model;
    protected $timeout;
    protected $maxRetries;
    
    public function __construct($model = null, $timeout = 30, $maxRetries = 3) {
        $this->model = $model;
        $this->timeout = $timeout;
        $this->maxRetries = $maxRetries;
    }
    
    /**
     * LLMにリクエストを送信（抽象メソッド）
     * 
     * @param string $systemPrompt システムプロンプト
     * @param string $userPrompt ユーザープロンプト
     * @return string LLMの応答
     */
    abstract public function generate($systemPrompt, $userPrompt);
    
    /**
     * プロバイダー名を取得
     */
    abstract public function getProviderName();
    
    /**
     * リトライ処理付きリクエスト
     */
    protected function requestWithRetry(callable $callback) {
        $lastException = null;
        
        for ($i = 0; $i < $this->maxRetries; $i++) {
            try {
                return $callback();
            } catch (Exception $e) {
                $lastException = $e;
                
                // 最後のリトライでなければ待機
                if ($i < $this->maxRetries - 1) {
                    sleep(pow(2, $i)); // Exponential backoff
                }
            }
        }
        
        throw $lastException;
    }
    
    /**
     * HTTPリクエストを送信
     */
    protected function httpRequest($url, $data, $headers = []) {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
            ], $headers),
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("HTTP エラー: {$error}");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP {$httpCode}: {$response}");
        }
        
        return json_decode($response, true);
    }
}
