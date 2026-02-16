<?php
/**
 * lib/IntentAnalyzer.php
 * 修正版：EnvLoaderを使用して環境変数を統一管理
 */
require_once __DIR__ . '/EnvLoader.php';

class IntentAnalyzer
{
    private $apiKey;
    private $model;
    private $apiEndpoint;

    public function __construct()
    {
        // EnvLoader で .env を読み込み（未ロードの場合のみ）
        EnvLoader::load();

        // 1. API Key を取得
        $this->apiKey = EnvLoader::get('GEMINI_API_KEY');
        if (empty($this->apiKey)) {
            throw new Exception('GEMINI_API_KEY が設定されていません。.env ファイルを確認してください。');
        }

        // 2. モデル名を環境変数から取得
        $this->model = EnvLoader::get('GEMINI_MODEL', 'gemini-2.5-flash');

        // 3. APIエンドポイントを動的に構築
        $this->apiEndpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            $this->model
        );

        // デバッグ情報
        error_log("IntentAnalyzer initialized with model: {$this->model}");
        error_log("API Endpoint: {$this->apiEndpoint}");
    }

    /**
     * ユーザーのテーマを分析してプロンプトを生成
     */
    public function analyze($theme)
    {
        // 1. テーマから意図を検出
        $intent = $this->detectIntent($theme);

        // 2. 柔軟なシステムプロンプトを構築
        $systemPrompt = $this->buildFlexibleSystemPrompt($intent);

        // 3. Gemini API でプロンプトを生成
        $generatedPrompt = $this->callGeminiAPI($systemPrompt, $theme);

        return [
            'intent' => $intent,
            'prompt' => $generatedPrompt,
            'metadata' => [
                'theme' => $theme,
                'platform' => $intent['platform'],
                'model' => $this->model,
                'api_called' => true,
                'timestamp' => time()
            ]
        ];
    }

    /**
     * テーマから意図を検出
     */
    private function detectIntent($theme)
    {
        $theme_lower = mb_strtolower($theme);

        // プラットフォーム検出
        $platform = 'note';
        if (preg_match('/(zenn|技術|実装|コード|エンジニア)/u', $theme_lower)) {
            $platform = 'zenn';
        } elseif (preg_match('/(twitter|x|ツイート|短文|sns)/u', $theme_lower)) {
            $platform = 'x';
        } elseif (preg_match('/(note|記事|エッセイ|体験)/u', $theme_lower)) {
            $platform = 'note';
        }

        // コンテンツタイプ検出
        $content_type = 'experiential';
        if (preg_match('/(技術|実装|設計|開発)/u', $theme_lower)) {
            $content_type = 'technical';
        } elseif (preg_match('/(考察|分析|レビュー)/u', $theme_lower)) {
            $content_type = 'analytical';
        } elseif (preg_match('/(ハウツー|方法|手順|ガイド)/u', $theme_lower)) {
            $content_type = 'howto';
        }

        return [
            'platform' => $platform,
            'content_type' => $content_type,
            'raw_theme' => $theme
        ];
    }

    /**
     * 柔軟なシステムプロンプトを構築
     */
    private function buildFlexibleSystemPrompt($intent)
    {
        $platform = $intent['platform'];

        $platformGuidelines = [
            'note' => [
                'target_length' => '1500〜3000字',
                'tone' => '柔らかく、誇張しない。一人称OK。感情表現は控えめ。',
                'avoid' => '収益化の話、「誰でも」「簡単に」などの煽り、強い行動喚起',
                'focus' => '書き手の実体験と気づきを重視。読者に余白を残す。'
            ],
            'zenn' => [
                'target_length' => '2000〜5000字',
                'tone' => '技術的に正確。再現可能な内容。コードベースで説明。',
                'avoid' => '曖昧な表現、主観のみの記述、検証していない情報',
                'focus' => '課題・解決策・実装の具体性を重視。'
            ],
            'x' => [
                'target_length' => '200〜280字',
                'tone' => '簡潔で共感を呼ぶ。行動より思考を促す。',
                'avoid' => '長文、説教臭さ、広告的表現',
                'focus' => '問題提起と余韻。読者に考えさせる。'
            ]
        ];

        $guidelines = $platformGuidelines[$platform] ?? $platformGuidelines['note'];

        $systemPrompt = "あなたは{$platform}向けのコンテンツ作成プロンプトを生成する専門家です。

【あなたの役割】
ユーザーが提示したテーマに基づいて、最適なコンテンツ作成プロンプトを生成してください。
プロンプトには、「テーマに応じた適切な構成」も含めてください。

【{$platform}の特性】
- 想定文字数: {$guidelines['target_length']}
- トーン: {$guidelines['tone']}
- 避けるべき要素: {$guidelines['avoid']}
- 重視すべき点: {$guidelines['focus']}

【構成の考え方】
固定の構成を使わず、テーマの性質に応じて最適な構成を提案してください。

例えば：
- 体験談なら → 「違和感・気づき・変化」のような流れ
- 技術記事なら → 「課題・解決策・実装・結果」のような流れ  
- ハウツーなら → 「背景・手順・注意点・まとめ」のような流れ
- 考察なら → 「問題提起・分析・示唆」のような流れ

テーマの内容から、最も効果的な構成を判断して提示してください。

【出力形式】
以下の形式で、{$platform}向けの記事本文作成プロンプトを生成してください：

```
以下の条件で、{$platform}向けの記事本文を書いてください。

【テーマ】
（ユーザーのテーマをそのまま記載）

【書き手の立場】
（テーマから推測される適切な立場）

【トーン】
（{$platform}の特性に合ったトーン指定）

【構成】
（テーマに最適な3〜5個のセクション構成を提案）

【禁止事項】
（{$platform}で避けるべき要素）

【品質基準】
（読者に価値を提供するための基準）

上記を守って、自然な日本語で本文を書いてください。
```

【重要】
- 構成は固定パターンを使わず、テーマごとに最適化してください
- ユーザーのテーマの内容を必ず反映させてください
- {$platform}の文化とユーザー層を考慮してください";

        return $systemPrompt;
    }

    /**
     * Gemini API を呼び出し
     */
    private function callGeminiAPI($systemPrompt, $userTheme)
    {
        $url = $this->apiEndpoint . '?key=' . $this->apiKey;

        $requestBody = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $systemPrompt . "\n\n【ユーザーのテーマ】\n「" . $userTheme . "」\n\n上記のテーマに合わせて、最適なプロンプトを生成してください。"
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.8,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("API呼び出しエラー: {$error}");
        }

        if ($httpCode !== 200) {
            throw new Exception("API HTTPエラー→ {$httpCode}, Response: {$response}");
        }

        $data = json_decode($response, true);

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception("API レスポンスが不正です: " . json_encode($data));
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * API Key が設定されているか確認
     */
    public function hasApiKey()
    {
        return !empty($this->apiKey);
    }

    /**
     * テーマ・媒体・方向性に基づいて動的に構成案を生成（Gemini API 使用）
     *
     * @param string $theme ユーザーのテーマ
     * @param string $media 媒体（note/zenn/x）
     * @param string $intentType 方向性（1/2/3）
     * @return array 構成案（3〜5項目の文字列配列）
     */
    public function generateDynamicStructure($theme, $media, $intentType)
    {
        $intentDetails = $this->getIntentDetails($intentType);
        $intentLabel = $intentDetails['label'];

        $mediaNames = [
            'note' => 'note（体験共有型プラットフォーム）',
            'zenn' => 'Zenn（技術記事プラットフォーム）',
            'x' => 'X / Twitter（短文SNS）'
        ];
        $mediaName = $mediaNames[$media] ?? $media;

        $systemPrompt = <<<PROMPT
あなたは記事構成の専門家です。

ユーザーが指定したテーマ・媒体・方向性に基づいて、最適な記事構成を提案してください。

【ルール】
- 構成は3〜5項目とする
- 各項目は簡潔な日本語の見出し（10〜25文字程度）
- テーマの内容に合った具体的な見出しにする
- 固定パターンをそのまま使わず、テーマに最適化する
- JSON配列のみを返す。説明文や装飾は一切不要

【出力形式】
以下のJSON配列のみを返してください：
["見出し1", "見出し2", "見出し3"]

例：テーマ「猫の飼い方」の場合
["猫を迎える前に知っておくべきこと", "日常のケアと食事の基本", "よくあるトラブルと対処法", "猫との暮らしで得られるもの"]
PROMPT;

        $userPrompt = "【媒体】{$mediaName}\n【方向性】{$intentLabel}\n【テーマ】{$theme}";

        try {
            $responseText = $this->callGeminiAPI($systemPrompt, $userPrompt);

            // JSON部分を抽出（```json ... ``` で囲まれている場合にも対応）
            $jsonText = $responseText;
            if (preg_match('/\[.*\]/s', $responseText, $matches)) {
                $jsonText = $matches[0];
            }

            $structure = json_decode($jsonText, true);

            if (is_array($structure) && count($structure) >= 2 && count($structure) <= 7) {
                // 文字列の配列であることを確認
                $structure = array_values(array_filter($structure, 'is_string'));
                if (count($structure) >= 2) {
                    error_log("Dynamic structure generated: " . json_encode($structure, JSON_UNESCAPED_UNICODE));
                    return $structure;
                }
            }

            error_log("Structure parse fallback. Raw response: " . $responseText);
        } catch (Exception $e) {
            error_log("Dynamic structure generation failed: " . $e->getMessage());
        }

        // フォールバック：汎用構成
        return $this->getFallbackStructure($media);
    }

    /**
     * API失敗時の汎用フォールバック構成
     */
    private function getFallbackStructure($media)
    {
        $fallbacks = [
            'note' => ['背景・きっかけ', '体験したこと・気づき', '今思うこと・学び'],
            'zenn' => ['課題', '解決アプローチ', '実装のポイント', '結果と考察'],
            'x' => ['問題提起', '気づき・視点', '余韻・問いかけ']
        ];
        return $fallbacks[$media] ?? $fallbacks['note'];
    }

    /**
     * 使用中のモデル名を取得
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * テーマと媒体から三択オプションを生成（Phase 0→1 用）
     * Gemini API は使わず、ローカルロジックで方向性を提示
     */
    public function analyzeForOptions($theme, $media)
    {
        $intent = $this->detectIntent($theme);

        // 媒体に応じた三択オプションを生成
        $optionSets = [
            'note' => [
                '1' => [
                    'label' => '体験ベース',
                    'description' => '自分の体験・気づきを中心に、読者に共感を届ける構成'
                ],
                '2' => [
                    'label' => '考察・分析',
                    'description' => 'テーマを掘り下げて、自分なりの視点や分析を展開する構成'
                ],
                '3' => [
                    'label' => 'ハウツー・ガイド',
                    'description' => '具体的な手順やノウハウを整理して伝える構成'
                ]
            ],
            'zenn' => [
                '1' => [
                    'label' => '技術解説',
                    'description' => '課題→解決策→実装の流れで技術的に解説する構成'
                ],
                '2' => [
                    'label' => '設計・思想',
                    'description' => '設計判断や技術選定の背景・理由を共有する構成'
                ],
                '3' => [
                    'label' => 'チュートリアル',
                    'description' => '手順を追って再現可能な形で説明する構成'
                ]
            ],
            'x' => [
                '1' => [
                    'label' => '問題提起型',
                    'description' => '読者に考えさせる問いを投げかけるスタイル'
                ],
                '2' => [
                    'label' => '気づき共有型',
                    'description' => '自分の気づきや発見を簡潔に共有するスタイル'
                ],
                '3' => [
                    'label' => 'ノウハウ型',
                    'description' => '実用的なTipsや方法を端的に伝えるスタイル'
                ]
            ]
        ];

        $options = $optionSets[$media] ?? $optionSets['note'];

        return [
            'options' => $options,
            'selection_prompt' => "「{$theme}」をどの方向性で書きますか？",
            'detected_intent' => $intent
        ];
    }

    /**
     * 選択肢の妥当性チェック（generate.php 用）
     */
    public function validateChoice($intentType)
    {
        return in_array($intentType, ['1', '2', '3']);
    }

    /**
     * 意図タイプの詳細情報を取得（PromptBuilder 用）
     */
    public function getIntentDetails($intentType)
    {
        $details = [
            '1' => [
                'type' => 'experiential',
                'label' => '体験ベース',
                'tone_modifier' => '体験に基づく自然な語り口'
            ],
            '2' => [
                'type' => 'analytical',
                'label' => '考察・分析',
                'tone_modifier' => '論理的で掘り下げた分析'
            ],
            '3' => [
                'type' => 'howto',
                'label' => 'ハウツー・ガイド',
                'tone_modifier' => '具体的で実用的な解説'
            ]
        ];

        return $details[$intentType] ?? $details['1'];
    }
}