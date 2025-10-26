import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

type EditorOpts = {
  selector: string; // CSS selector for editor root
  outputHtmlInputId: string; // hidden input for html
  outputTextInputId: string; // hidden input for plain text
  wordLimit?: number;
};

function countWords(text: string): number {
  const normalized = text.replace(/\s+/g, ' ').trim();
  if (!normalized) return 0;
  return normalized.split(' ').length;
}

export function initTiptap(opts: EditorOpts): void {
  const el = document.querySelector<HTMLElement>(opts.selector);
  if (!el) return;

  const htmlInput = document.getElementById(opts.outputHtmlInputId) as HTMLInputElement | null;
  const textInput = document.getElementById(opts.outputTextInputId) as HTMLInputElement | null;

  const editor = new Editor({
    element: el,
    extensions: [StarterKit],
    content: htmlInput?.value || '',
    onUpdate: ({ editor }) => {
      const html = editor.getHTML();
      const text = editor.getText();
      if (htmlInput) htmlInput.value = html;
      if (textInput) textInput.value = text;

      const limit = opts.wordLimit ?? 250;
      const current = countWords(text);
      const counter = el.closest('.form-group')?.querySelector('[data-wordcount]') as HTMLElement | null;
      if (counter) counter.textContent = `${current}/${limit} words`;
      const hint = el.closest('.form-group')?.querySelector('[data-wordhint]') as HTMLElement | null;
      if (hint) {
        hint.classList.toggle('is-visible', current > limit);
      }
    },
  });

  // expose for debugging
  (window as any).tiptap = editor;
}

export function bootstrapEditors(): void {
  document.querySelectorAll<HTMLElement>('[data-editor="tiptap"]').forEach((root) => {
    const htmlInputId = root.getAttribute('data-output-html') || '';
    const textInputId = root.getAttribute('data-output-text') || '';
    const limit = parseInt(root.getAttribute('data-word-limit') || '250', 10);
    initTiptap({ selector: `[data-editor="tiptap"][data-editor-id="${root.getAttribute('data-editor-id')}"]`, outputHtmlInputId: htmlInputId, outputTextInputId: textInputId, wordLimit: limit });
  });
}

// Auto-init on load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootstrapEditors, { once: true });
} else {
  bootstrapEditors();
}

