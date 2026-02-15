<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promptman v0 - 記事作成補助</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        
        <!-- ヘッダー -->
        <header class="mb-8 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Promptman</h1>
            <p class="text-gray-600">記事を生成するのではなく、生成させる『設計』を提供します</p>
        </header>

        <!-- Phase 0: 媒体選択 + テーマ入力 -->
        <div id="phase-0" class="phase-container">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold mb-4">📝 記事の準備</h2>
                
                <!-- 媒体選択 -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        1. どこに投稿しますか？
                    </label>
                    <div class="space-y-3">
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-400 transition">
                            <input type="radio" name="media" value="note" class="mr-3 w-5 h-5">
                            <div>
                                <div class="font-semibold">note</div>
                                <div class="text-sm text-gray-600">体験ベース・柔らかい語調</div>
                            </div>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-400 transition">
                            <input type="radio" name="media" value="zenn" class="mr-3 w-5 h-5">
                            <div>
                                <div class="font-semibold">Zenn</div>
                                <div class="text-sm text-gray-600">技術記事・簡潔な語調</div>
                            </div>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-400 transition">
                            <input type="radio" name="media" value="x" class="mr-3 w-5 h-5">
                            <div>
                                <div class="font-semibold">X（スレッド）</div>
                                <div class="text-sm text-gray-600">短文・フック重視</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- テーマ入力 -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        2. 記事のテーマは？（箇条書きメモでもOK）
                    </label>
                    <textarea 
                        id="theme-input" 
                        rows="4" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="例：AIの設定を見直したら、業務でのやり取りがかなり楽になった話"></textarea>
                </div>

                <button 
                    onclick="submitTheme()" 
                    class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                    次へ
                </button>
            </div>
        </div>

        <!-- Phase 1: 三択提示 -->
        <div id="phase-1" class="phase-container hidden">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold mb-4">🎯 方向性の選択</h2>
                <p class="text-gray-600 mb-6" id="selection-prompt"></p>
                
                <div id="intent-options" class="space-y-3">
                    <!-- 動的生成 -->
                </div>

                <button 
                    onclick="submitIntent()" 
                    class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition mt-6">
                    この方向性で進める
                </button>
            </div>
        </div>

        <!-- Phase 2: 構成確認 -->
        <div id="phase-2" class="phase-container hidden">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold mb-4">📋 構成の確認</h2>
                <p class="text-gray-600 mb-6">この構成で進めて良いですか？</p>
                
                <div id="structure-preview" class="bg-gray-50 p-4 rounded-lg mb-6">
                    <!-- 動的生成 -->
                </div>

                <div class="flex gap-3">
                    <button 
                        onclick="goBackToIntent()" 
                        class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                        戻る
                    </button>
                    <button 
                        onclick="confirmStructure()" 
                        class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        この構成でOK
                    </button>
                </div>
            </div>
        </div>

        <!-- Phase 3: 実行用プロンプト出力 -->
        <div id="phase-3" class="phase-container hidden">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold mb-4">✅ 実行用プロンプト</h2>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <p class="text-sm text-yellow-800">
                        <strong>💡 使い方</strong><br>
                        以下のプロンプトをコピーして、ChatGPT・Claude・Gemini などの無料版に貼り付けてください。
                    </p>
                </div>

                <div class="relative">
                    <pre id="executable-prompt" class="bg-gray-900 text-gray-100 p-6 rounded-lg overflow-x-auto text-sm leading-relaxed whitespace-pre-wrap"></pre>
                    <button 
                        onclick="copyPrompt()" 
                        class="absolute top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                        📋 コピー
                    </button>
                </div>

                <div class="mt-6 text-center">
                    <button 
                        onclick="resetAll()" 
                        class="text-blue-600 hover:text-blue-800 font-semibold">
                        最初からやり直す
                    </button>
                </div>
            </div>
        </div>

        <!-- ローディング -->
        <div id="loading" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <p class="text-gray-700">処理中...</p>
            </div>
        </div>

    </div>

    <script src="script.js"></script>
</body>
</html>
