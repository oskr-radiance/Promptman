<?php
require_once __DIR__ . '/EnvLoader.php';
require_once __DIR__ . '/OllamaLLM.php';
require_once __DIR__ . '/GeminiLLM.php';
require_once __DIR__ . '/ClaudeLLM.php';

/**
 * LLMFactory - LLMプロバイダー切り替え
 */
class LLMFactory {
    /**
     * 設定されたプロバイダーのLLMインスタンスを取得
     * 
     * @return LLMAdapter
     */
    public static function create() {
        EnvLoader::load();
        
        $provider = EnvLoader::get('LLM_PROVIDER', 'gemini');
        $timeout = (int)EnvLoader::get('LLM_TIMEOUT', 30);
        $maxRetries = (int)EnvLoader::get('LLM_MAX_RETRIES', 3);
        
        switch (strtolower($provider)) {
            case 'ollama':
                return self::createOllama($timeout, $maxRetries);
                
            case 'gemini':
                return self::createGemini($timeout, $maxRetries);
                
            case 'claude':
                return self::createClaude($timeout, $maxRetries);
                
            default:
                throw new Exception("未知のLLMプロバイダー: {$provider}");
        }
    }
    
    /**
     * Ollama インスタンス作成
     */
    private static function createOllama($timeout, $maxRetries) {
        $baseUrl = EnvLoader::get('OLLAMA_BASE_URL', 'http://localhost:11434');
        $model = EnvLoader::get('OLLAMA_MODEL', 'llama3.2');
        
        $llm = new OllamaLLM($baseUrl, $model, $timeout, $maxRetries);
        
        // Ollama が利用不可の場合は自動フォールバック
        if (!$llm->isAvailable()) {
            error_log('Ollama が利用できません。Gemini にフォールバックします。');
            return self::createGemini($timeout, $maxRetries);
        }
        
        return $llm;
    }
    
    /**
     * Gemini インスタンス作成
     */
    private static function createGemini($timeout, $maxRetries) {
        $apiKey = EnvLoader::get('GEMINI_API_KEY');
        
        if (empty($apiKey) || $apiKey === 'your_gemini_api_key_here') {
            throw new Exception('GEMINI_API_KEY が設定されていません。.env ファイルを確認してください。');
        }
        
        $model = EnvLoader::get('GEMINI_MODEL', 'gemini-2.0-flash-exp');
        
        return new GeminiLLM($apiKey, $model, $timeout, $maxRetries);
    }
    
    /**
     * Claude インスタンス作成
     */
    private static function createClaude($timeout, $maxRetries) {
        $apiKey = EnvLoader::get('CLAUDE_API_KEY');
        
        if (empty($apiKey) || $apiKey === 'your_claude_api_key_here') {
            throw new Exception('CLAUDE_API_KEY が設定されていません。.env ファイルを確認してください。');
        }
        
        $model = EnvLoader::get('CLAUDE_MODEL', 'claude-sonnet-4-20250514');
        
        return new ClaudeLLM($apiKey, $model, $timeout, $maxRetries);
    }
    
    /**
     * 利用可能なプロバイダー一覧を取得
     */
    public static function getAvailableProviders() {
        $providers = [];
        
        // Ollama チェック
        try {
            $ollama = new OllamaLLM();
            if ($ollama->isAvailable()) {
                $providers[] = 'ollama';
            }
        } catch (Exception $e) {
            // スキップ
        }
        
        // Gemini チェック
        $geminiKey = EnvLoader::get('GEMINI_API_KEY');
        if (!empty($geminiKey) && $geminiKey !== 'your_gemini_api_key_here') {
            $providers[] = 'gemini';
        }
        
        // Claude チェック
        $claudeKey = EnvLoader::get('CLAUDE_API_KEY');
        if (!empty($claudeKey) && $claudeKey !== 'your_claude_api_key_here') {
            $providers[] = 'claude';
        }
        
        return $providers;
    }
}
