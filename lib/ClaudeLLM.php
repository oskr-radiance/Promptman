<?php
require_once __DIR__ . '/LLMAdapter.php';

/**
 * ClaudeLLM - Anthropic Claude API実装
 */
class ClaudeLLM extends LLMAdapter {
    private $apiKey;
    
    public function __construct($apiKey = null, $model = null, $timeout = 30, $maxRetries = 3) {
        parent::__construct($model, $timeout, $maxRetries);
        
        $this->apiKey = $apiKey ?? EnvLoader::require('CLAUDE_API_KEY');
        $this->model = $model ?? EnvLoader::get('CLAUDE_MODEL', 'claude-sonnet-4-20250514');
    }
    
    public function getProviderName() {
        return 'Claude';
    }
    
    public function generate($systemPrompt, $userPrompt) {
        return $this->requestWithRetry(function() use ($systemPrompt, $userPrompt) {
            $url = 'https://api.anthropic.com/v1/messages';
            
            $data = [
                'model' => $this->model,
                'max_tokens' => 2048,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'temperature' => 0.7,
            ];
            
            $headers = [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ];
            
            $response = $this->httpRequest($url, $data, $headers);
            
            if (!isset($response['content'][0]['text'])) {
                throw new Exception('Claude レスポンスが不正です: ' . json_encode($response));
            }
            
            return $response['content'][0]['text'];
        });
    }
}
