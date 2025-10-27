type EditorOpts = {
  el: HTMLElement;
  outputHtmlInputId: string;
  outputTextInputId: string;
  wordLimit?: number;
};

function countWords(text: string): number {
  const normalized = text.replace(/\s+/g, ' ').trim();
  if (!normalized) return 0;
  return normalized.split(' ').length;
}

function initSimpleEditor(opts: EditorOpts): void {
  const el = opts.el;
  const htmlInput = document.getElementById(opts.outputHtmlInputId) as HTMLInputElement | null;
  const textInput = document.getElementById(opts.outputTextInputId) as HTMLInputElement | null;
  let savedRange: Range | null = null;

  // Seed content from hidden html input if present
  if (htmlInput && htmlInput.value && el.innerHTML.trim() === '') {
    el.innerHTML = htmlInput.value;
  }

  // Ensure editable and accessible
  el.setAttribute('contenteditable', 'true');
  el.setAttribute('role', 'textbox');
  el.setAttribute('aria-multiline', 'true');

  const updateOutputs = () => {
    const html = el.innerHTML;
    const text = el.textContent || '';
    if (htmlInput) htmlInput.value = html;
    if (textInput) {
      textInput.value = text;
      textInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    const limit = opts.wordLimit ?? 250;
    const current = countWords(text);
    const counter = el.closest('.form-group')?.querySelector('[data-wordcount]') as HTMLElement | null;
    if (counter) counter.textContent = `${current}/${limit} words`;
    const hint = el.closest('.form-group')?.querySelector('[data-wordhint]') as HTMLElement | null;
    if (hint) hint.classList.toggle('is-visible', current > limit);
  };

  const captureSelection = () => {
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0 && el.contains(sel.anchorNode)) {
      savedRange = sel.getRangeAt(0).cloneRange();
    }
  };

  el.addEventListener('input', () => {
    captureSelection();
    updateOutputs();
  });
  el.addEventListener('blur', updateOutputs);
  el.addEventListener('keyup', captureSelection);
  el.addEventListener('mouseup', captureSelection);
  updateOutputs();

  // Toolbar (execCommand-based)
  const group = el.closest('.form-group') || el.parentElement;
  const toolbar = group?.querySelector('.editor-toolbar');
  if (toolbar) {
    toolbar.addEventListener('click', (e) => {
      const btn = (e.target as HTMLElement).closest('[data-cmd]') as HTMLElement | null;
      if (!btn) return;
      e.preventDefault();
      // Restore selection before executing command
      el.focus();
      const sel = window.getSelection();
      if (sel && savedRange) {
        sel.removeAllRanges();
        sel.addRange(savedRange);
      }
      const cmd = btn.getAttribute('data-cmd');
      switch (cmd) {
        case 'bold': document.execCommand('bold'); break;
        case 'italic': document.execCommand('italic'); break;
        case 'bullet': document.execCommand('insertUnorderedList'); break;
        case 'ordered': document.execCommand('insertOrderedList'); break;
        case 'blockquote': document.execCommand('formatBlock', false, 'blockquote'); break;
        case 'code': document.execCommand('formatBlock', false, 'pre'); break;
        case 'undo': document.execCommand('undo'); break;
        case 'redo': document.execCommand('redo'); break;
        case 'clear': el.innerHTML = ''; break;
      }
      updateOutputs();
    });
  }
}

export function bootstrapEditors(): void {
  document.querySelectorAll<HTMLElement>('[data-editor]').forEach((root) => {
    const htmlInputId = root.getAttribute('data-output-html') || '';
    const textInputId = root.getAttribute('data-output-text') || '';
    const limit = parseInt(root.getAttribute('data-word-limit') || '250', 10);
    initSimpleEditor({ el: root, outputHtmlInputId: htmlInputId, outputTextInputId: textInputId, wordLimit: limit });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootstrapEditors, { once: true });
} else {
  bootstrapEditors();
}
