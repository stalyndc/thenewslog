import { hydrateTimeAgo } from "./timeago";
import { enableReorder } from "./reorder";

document.addEventListener("DOMContentLoaded", () => {
  hydrateTimeAgo();
  enableReorder();
});

document.addEventListener("htmx:afterSwap", () => {
  hydrateTimeAgo();
  enableReorder();
});

document.addEventListener("click", (event) => {
  const target = event.target as HTMLElement;
  const button = target.closest<HTMLElement>("[data-copy]");
  if (!button) {
    return;
  }

  const value = button.dataset.copy;
  if (!value) {
    return;
  }

  navigator.clipboard.writeText(value).catch(() => {
    // ignore errors
  });
});
