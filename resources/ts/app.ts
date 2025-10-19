import { hydrateTimeAgo } from "./timeago";
import { enableReorder } from "./reorder";

document.addEventListener("DOMContentLoaded", () => {
  hydrateTimeAgo();
  enableReorder();
});

// Copy-to-clipboard for data-copy buttons

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
    // no-op; clipboard failures silently ignored
  });
});

// Sticky masthead on scroll

const masthead = document.querySelector<HTMLElement>(".masthead-sentinel");
const header = document.querySelector<HTMLElement>(".masthead");

if (masthead && header) {
  const observer = new IntersectionObserver(
    ([entry]) => {
      header.classList.toggle("is-stuck", !entry.isIntersecting);
    },
    {
      threshold: [1],
    }
  );
  observer.observe(masthead);
}
