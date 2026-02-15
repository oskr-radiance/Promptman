<?php
/**
 * MediaRules - 媒体別ルール管理
 * 
 * note / Zenn / X の語調・構成・制約を管理
 */
class MediaRules {
    private $rules;
    private $globalConstraints;
    
    public function __construct() {
        $jsonPath = __DIR__ . '/../data/media_rules.json';
        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);
        
        $this->globalConstraints = $data['global'];
        $this->rules = [
            'note' => $data['note'],
            'zenn' => $data['zenn'],
            'x' => $data['x']
        ];
    }
    
    /**
     * 指定された媒体のルールを取得
     */
    public function getRules($media) {
        if (!isset($this->rules[$media])) {
            throw new Exception("未知の媒体: {$media}");
        }
        
        return array_merge(
            ['global' => $this->globalConstraints],
            $this->rules[$media]
        );
    }
    
    /**
     * 媒体の表示名を取得
     */
    public function getDisplayName($media) {
        return $this->rules[$media]['display_name'] ?? $media;
    }
    
    /**
     * 構成順序を取得
     */
    public function getStructureOrder($media) {
        return $this->rules[$media]['structure']['order'] ?? [];
    }
    
    /**
     * 禁止事項を取得
     */
    public function getForbiddenItems($media) {
        return $this->rules[$media]['forbid'] ?? [];
    }
    
    /**
     * 語調設定を取得
     */
    public function getToneSettings($media) {
        return $this->rules[$media]['tone'] ?? [];
    }
    
    /**
     * 読者想定を取得
     */
    public function getReaderAssumption($media) {
        return $this->rules[$media]['reader_assumption'] ?? '一般読者';
    }
    
    /**
     * 利用可能な媒体一覧
     */
    public function getAvailableMedia() {
        return array_keys($this->rules);
    }
    
    /**
     * グローバル制約を取得
     */
    public function getGlobalConstraints() {
        return $this->globalConstraints;
    }
}
