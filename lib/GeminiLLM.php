<?php
require_once __DIR__ . '/LLMAdapter.php';

/**
 * GeminiLLM - Google Gemini API実装
 */
class GeminiLLM extends LLMAdapter
{
    private $apiKey;

    public function __construct($apiKey = null, $model = null, $timeout = 30, $maxRetries = 3)
    {
        parent::__construct($model, $timeout, $maxRetries);

        $this->apiKey = $apiKey ?? EnvLoader::require('GEMINI_API_KEY');
        $this->model = $model ?? EnvLoader::get('GEMINI_MODEL', 'gemini-2.5-flash');
    }

    public function getProviderName()
    {
        return 'Gemini';
    }

    public function generate($systemPrompt, $userPrompt)
    {
        return $this->requestWithRetry(function () use ($systemPrompt, $userPrompt) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt],
                            ['text' => $userPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topP' => 0.9,
                    'maxOutputTokens' => 2048,
                ]
            ];

            $response = $this->httpRequest($url, $data);

            if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                throw new Exception('Gemini レスポンスが不正です: ' . json_encode($response));
            }

            return $response['candidates'][0]['content']['parts'][0]['text'];
        });
    }
}
