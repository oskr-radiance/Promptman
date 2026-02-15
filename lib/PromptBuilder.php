<?php
require_once __DIR__ . '/MediaRules.php';
require_once __DIR__ . '/IntentAnalyzer.php';

/**
 * PromptBuilder - 実行用プロンプト生成専用
 * 
 * 【最重要原則】
 * - 本文を生成してはならない
 * - APIを実行してはならない
 * - ユーザーが無料AIに貼るプロンプトを生成すること
 */
class PromptBuilder {
    private $mediaRules;
    private $intentAnalyzer;
    
    public function __construct() {
        $this->mediaRules = new MediaRules();
        $this->intentAnalyzer = new IntentAnalyzer();
    }
    
    /**
     * 実行用プロンプトを生成
     * 
     * @param string $media 媒体（note/zenn/x）
     * @param string $theme テーマ
     * @param string $intentType 選択された意図タイプ（1/2/3）
     * @param array $structure 構成案
     * @return string 実行用プロンプト（ユーザーがコピーするもの）
     */
    public function buildExecutablePrompt($media, $theme, $intentType, $structure) {
        $rules = $this->mediaRules->getRules($media);
        $intentDetails = $this->intentAnalyzer->getIntentDetails($intentType);
        
        // プロンプトテンプレート生成
        $prompt = $this->generatePromptTemplate(
            $media,
            $theme,
            $rules,
            $intentDetails,
            $structure
        );
        
        return $prompt;
    }
    
    /**
     * プロンプトテンプレート生成（核心ロジック）
     */
    private function generatePromptTemplate($media, $theme, $rules, $intentDetails, $structure) {
        $mediaName = $this->mediaRules->getDisplayName($media);
        $globalConstraints = $rules['global'];
        
        // ヘッダー
        $template = "以下の条件で、{$mediaName}向けの記事本文を書いてください。\n\n";
        
        // テーマ
        $template .= "【テーマ】\n{$theme}\n\n";
        
        // 書き手の立場（グローバル制約）
        $template .= "【書き手の立場】\n";
        $template .= "・{$globalConstraints['writer_role']}\n";
        $template .= "・AIを売る立場ではない\n";
        $template .= "・業務効率化の体験を淡々と共有する\n\n";
        
        // トーン（媒体別）
        $template .= $this->buildToneSection($rules['tone']);
        
        // 構成
        $template .= "【構成】\n";
        foreach ($structure as $index => $section) {
            $num = $index + 1;
            $template .= "{$num}. {$section}\n";
        }
        $template .= "\n";
        
        // 禁止事項（グローバル + 媒体別）
        $template .= "【禁止事項】\n";
        $template .= "・収益化の話\n";
        $template .= "・AIを主役にする表現\n";
        $template .= "・「誰でも」「簡単に」などの煽り\n";
        
        foreach ($rules['forbid'] as $forbidden) {
            $template .= "・" . $this->translateForbidden($forbidden) . "\n";
        }
        $template .= "\n";
        
        // 品質基準
        $template .= "【品質基準】\n";
        $template .= "・実務でそのまま使える内容\n";
        $template .= "・書き手の声が残っている\n";
        $template .= "・読後に「売られた感」がない\n\n";
        
        // 実行指示
        $template .= "上記を守って、自然な日本語で本文を書いてください。\n";
        
        return $template;
    }
    
    /**
     * トーンセクション生成
     */
    private function buildToneSection($toneSettings) {
        $section = "【トーン】\n";
        
        // 一人称
        if ($toneSettings['first_person'] === 'allowed') {
            $section .= "・一人称使用OK\n";
        } elseif ($toneSettings['first_person'] === 'optional') {
            $section .= "・一人称は任意（使いすぎない）\n";
        }
        
        // 断定性
        $assertiveness = $toneSettings['assertiveness'];
        if ($assertiveness === 'low') {
            $section .= "・柔らかいが誇張しない\n";
            $section .= "・断定を避け、余白を残す\n";
        } elseif ($assertiveness === 'high') {
            $section .= "・断定的\n";
            $section .= "・簡潔に結論を示す\n";
        } else {
            $section .= "・適度な断定性\n";
        }
        
        // 感情表現
        $emotional = $toneSettings['emotional_words'];
        if ($emotional === 'forbidden') {
            $section .= "・感情表現は禁止\n";
        } elseif ($emotional === 'limited') {
            $section .= "・感情表現は控えめに\n";
        }
        
        $section .= "\n";
        return $section;
    }
    
    /**
     * 禁止事項の翻訳
     */
    private function translateForbidden($key) {
        $translations = [
            'aggressive_claims' => '攻撃的な主張',
            'strong_calls_to_action' => '強い行動喚起',
            'storytelling' => '過度なストーリーテリング',
            'vague_phrases' => '曖昧な表現',
            'motivational_language' => '動機づけ的表現',
            'detailed_explanations' => '詳細すぎる説明',
            'conclusions_with_answers' => '完全に答えを出す結論'
        ];
        
        return $translations[$key] ?? $key;
    }
    
    /**
     * 構成案を生成
     */
    public function generateStructure($media, $intentType) {
        $rules = $this->mediaRules->getRules($media);
        $order = $rules['structure']['order'];
        
        // 構成順序を日本語に変換
        $structure = [];
        foreach ($order as $item) {
            $structure[] = $this->translateStructureItem($item, $media);
        }
        
        return $structure;
    }
    
    /**
     * 構成要素の翻訳
     */
    private function translateStructureItem($item, $media) {
        $translations = [
            // note
            'experience' => 'AIを使っていて感じていた違和感',
            'realization' => '設定を見直すきっかけと気づき',
            'reasoning' => '実際に変えたこと・今思うこと',
            
            // zenn
            'problem' => '課題',
            'approach' => '解決アプローチ',
            'implementation' => '設計・実装のポイント',
            'result' => '結果・業務への影響',
            
            // x
            'hook' => '問題提起',
            'insight' => '気づき・視点',
            'implication' => '余韻・問いかけ'
        ];
        
        return $translations[$item] ?? $item;
    }
}
