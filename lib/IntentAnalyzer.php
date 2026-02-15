<?php
/**
 * IntentAnalyzer - 意図解析専用クラス
 * 
 * 【最重要原則】
 * - プロンプトを実行してはならない
 * - 本文を生成してはならない
 * - 解析結果を直接出力してはならない
 * - 必ず三択の選択肢を提示すること
 */
class IntentAnalyzer {
    private $intentTypes;
    
    public function __construct() {
        $jsonPath = __DIR__ . '/../data/intent_types.json';
        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);
        $this->intentTypes = $data['intent_types'];
    }
    
    /**
     * 意図解析（内部処理）
     * 
     * @param string $theme ユーザーが入力したテーマ
     * @param string $media 選択された媒体（note/zenn/x）
     * @return array 三択の選択肢（正解を匂わせない）
     */
    public function analyze($theme, $media) {
        // 内部解析（ブラックボックス）
        $analysis = $this->detectIntent($theme, $media);
        
        // ❌ 解析結果を直接返さない
        // ✅ 三択の選択肢のみを返す
        return [
            'options' => $this->intentTypes,
            'selection_prompt' => $this->getSelectionPrompt(),
            'recommendation' => null // 正解を匂わせない
        ];
    }
    
    /**
     * 内部解析ロジック（出力には使わない）
     * 
     * 検出項目:
     * - user_position: ユーザーの立場
     * - purpose: 記事の目的
     * - risk_of_overreach: 暴走リスク
     * - abstraction_level: 抽象度
     */
    private function detectIntent($theme, $media) {
        // 実装例（必要に応じて拡張）
        $analysis = [
            'user_position' => 'EC実務者',
            'purpose' => 'unknown', // あえて判断しない
            'risk_of_overreach' => 'medium',
            'abstraction_level' => 'medium'
        ];
        
        // ※ この結果は選択肢生成には使わない
        // ※ あくまで内部ログ用（デバッグ時のみ使用）
        
        return $analysis;
    }
    
    /**
     * 三択提示文言（固定）
     */
    private function getSelectionPrompt() {
        return "このテーマは、次のどれで進めるのが適切そうです。\nどれを採用しますか？";
    }
    
    /**
     * ユーザー選択の妥当性チェック
     */
    public function validateChoice($choice) {
        return isset($this->intentTypes[$choice]);
    }
    
    /**
     * 選択されたタイプの詳細を取得
     */
    public function getIntentDetails($choice) {
        if (!$this->validateChoice($choice)) {
            return null;
        }
        return $this->intentTypes[$choice];
    }
}
