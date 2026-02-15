<?php
require_once __DIR__ . '/LLMAdapter.php';

/**
 * OllamaLLM - Ollama ローカルLLM実装
 */
class OllamaLLM extends LLMAdapter {
    private $baseUrl;
    
    public function __construct($baseUrl = null, $model = null, $timeout = 30, $maxRetries = 3) {
        parent::__construct($model, $timeout, $maxRetries);
        
        $this->baseUrl = $baseUrl ?? EnvLoader::get('OLLAMA_BASE_URL', 'http://localhost:11434');
        $this->model = $model ?? EnvLoader::get('OLLAMA_MODEL', 'llama3.2');
    }
    
    public function getProviderName() {
        return 'Ollama';
    }
    
    public function generate($systemPrompt, $userPrompt) {
        return $this->requestWithRetry(function() use ($systemPrompt, $userPrompt) {
            // Ollamaのプロンプト形式
            $fullPrompt = "{$systemPrompt}\n\n{$userPrompt}";
            
            $url = rtrim($this->baseUrl, '/') . '/api/generate';
            
            $data = [
                'model' => $this->model,
                'prompt' => $fullPrompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'top_p' => 0.9,
                ]
            ];
            
            $response = $this->httpRequest($url, $data);
            
            if (!isset($response['response'])) {
                throw new Exception('Ollama レスポンスが不正です');
            }
            
            return $response['response'];
        });
    }
    
    /**
     * Ollama が起動しているか確認
     */
    public function isAvailable() {
        try {
            $ch = curl_init(rtrim($this->baseUrl, '/') . '/api/tags');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
        } catch (Exception $e) {
            return false;
        }
    }
}
