export type ToastVariant = "info" | "success" | "warn";

interface ToastOptions {
  variant?: ToastVariant;
  actionLabel?: string;
  onAction?: (() => void) | null;
  timeout?: number;
}

function ensureContainer(containerId: string): HTMLElement | null {
  const element = document.getElementById(containerId);
  if (element) {
    return element;
  }

  const created = document.createElement("div");
  created.id = containerId;
  created.className = "toast-stack";
  created.setAttribute("aria-live", "polite");
  document.body.appendChild(created);
  return created;
}

export function pushToast(container: HTMLElement, message: string, options: ToastOptions = {}): void {
  const variant = options.variant ?? "info";
  const toast = document.createElement("div");
  toast.className = `toast toast--${variant}`;

  const text = document.createElement("span");
  text.textContent = message;
  toast.appendChild(text);

  if (options.onAction && options.actionLabel) {
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = options.actionLabel;
    button.addEventListener("click", () => {
      options.onAction && options.onAction();
      toast.remove();
    });
    toast.appendChild(button);
  }

  container.appendChild(toast);

  const timeout = options.timeout ?? 6000;
  if (timeout > 0) {
    window.setTimeout(() => {
      toast.classList.add("is-hidden");
      window.setTimeout(() => toast.remove(), 400);
    }, timeout);
  }
}

export function pushToastById(containerId: string, message: string, options: ToastOptions = {}): void {
  const container = ensureContainer(containerId);
  if (!container) {
    return;
  }

  pushToast(container, message, options);
}
