/**
 * Promptman v0 - フロントエンド制御
 */

// グローバル状態
let state = {
    media: null,
    theme: null,
    intentType: null,
    structure: null,
    executablePrompt: null
};

/**
 * Phase 0 → Phase 1: テーマ送信
 */
async function submitTheme() {
    const media = document.querySelector('input[name="media"]:checked');
    const theme = document.getElementById('theme-input').value.trim();

    // バリデーション
    if (!media) {
        alert('媒体を選択してください');
        return;
    }

    if (!theme) {
        alert('テーマを入力してください');
        return;
    }

    state.media = media.value;
    state.theme = theme;

    showLoading();

    try {
        const response = await fetch('../api/analyze.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                media: state.media,
                theme: state.theme
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || '意図解析に失敗しました');
        }

        // 三択を表示
        showIntentOptions(result.data);
        showPhase(1);

    } catch (error) {
        alert('エラー: ' + error.message);
    } finally {
        hideLoading();
    }
}

/**
 * 三択オプションを表示
 */
function showIntentOptions(data) {
    const container = document.getElementById('intent-options');
    const prompt = document.getElementById('selection-prompt');

    prompt.textContent = data.selection_prompt;
    container.innerHTML = '';

    Object.entries(data.options).forEach(([key, option]) => {
        const label = document.createElement('label');
        label.className = 'flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-400 transition';
        label.innerHTML = `
            <input type="radio" name="intent" value="${key}" class="mr-3 w-5 h-5">
            <div>
                <div class="font-semibold">${option.label}</div>
                <div class="text-sm text-gray-600">${option.description}</div>
            </div>
        `;
        container.appendChild(label);
    });
}

/**
 * Phase 1 → Phase 2: 意図タイプ送信
 */
async function submitIntent() {
    const intent = document.querySelector('input[name="intent"]:checked');

    if (!intent) {
        alert('方向性を選択してください');
        return;
    }

    state.intentType = intent.value;

    showLoading();

    try {
        const response = await fetch('../api/generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                intent_type: state.intentType,
                structure_confirmed: false
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || '構成生成に失敗しました');
        }

        // 構成確認が必要
        if (result.data.needs_confirmation) {
            state.structure = result.data.structure;
            showStructurePreview(result.data.structure);
            showPhase(2);
        }

    } catch (error) {
        alert('エラー: ' + error.message);
    } finally {
        hideLoading();
    }
}

/**
 * 構成プレビューを表示
 */
function showStructurePreview(structure) {
    const container = document.getElementById('structure-preview');
    container.innerHTML = '<ol class="list-decimal list-inside space-y-2">';

    structure.forEach(item => {
        const li = document.createElement('li');
        li.className = 'text-gray-800';
        li.textContent = item;
        container.querySelector('ol').appendChild(li);
    });
}

/**
 * Phase 2 → Phase 3: 構成確定 → プロンプト生成
 */
async function confirmStructure() {
    showLoading();

    try {
        const response = await fetch('../api/generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                intent_type: state.intentType,
                structure_confirmed: true
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'プロンプト生成に失敗しました');
        }

        state.executablePrompt = result.data.executable_prompt;
        showExecutablePrompt(result.data.executable_prompt);
        showPhase(3);

    } catch (error) {
        alert('エラー: ' + error.message);
    } finally {
        hideLoading();
    }
}

/**
 * 実行用プロンプトを表示
 */
function showExecutablePrompt(prompt) {
    const container = document.getElementById('executable-prompt');
    container.textContent = prompt;
}

/**
 * プロンプトをクリップボードにコピー
 */
async function copyPrompt() {
    const prompt = state.executablePrompt;

    if (!prompt) {
        alert('コピーするプロンプトがありません');
        return;
    }

    try {
        await navigator.clipboard.writeText(prompt);

        // フィードバック
        const btn = document.getElementById('copy-btn');
        if (btn) {
            const originalText = btn.textContent;
            btn.textContent = '✅ コピーしました！';
            btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            btn.classList.add('bg-green-600');

            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('bg-green-600');
                btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            }, 2000);
        }

    } catch (error) {
        // clipboard API が使えない場合のフォールバック
        try {
            const textarea = document.createElement('textarea');
            textarea.value = prompt;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('コピーしました！');
        } catch (e) {
            alert('コピーに失敗しました。プロンプトを手動で選択してコピーしてください。');
        }
    }
}

/**
 * フェーズ表示切り替え
 */
function showPhase(phaseNumber) {
    const phases = document.querySelectorAll('.phase-container');
    phases.forEach(phase => phase.classList.add('hidden'));

    const targetPhase = document.getElementById(`phase-${phaseNumber}`);
    if (targetPhase) {
        targetPhase.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

/**
 * Phase 2 → Phase 1: 戻る
 */
function goBackToIntent() {
    showPhase(1);
}

/**
 * 最初からやり直す
 */
function resetAll() {
    if (confirm('最初からやり直しますか？')) {
        state = {
            media: null,
            theme: null,
            intentType: null,
            structure: null,
            executablePrompt: null
        };

        // フォームリセット
        document.getElementById('theme-input').value = '';
        document.querySelectorAll('input[type="radio"]').forEach(input => {
            input.checked = false;
        });

        showPhase(0);
    }
}

/**
 * ローディング表示
 */
function showLoading() {
    document.getElementById('loading').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading').classList.add('hidden');
}

/**
 * 初期化
 */
document.addEventListener('DOMContentLoaded', () => {
    showPhase(0);
});
