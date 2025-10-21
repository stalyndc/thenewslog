import { hydrateTimeAgo } from "./timeago";
import { enableReorder } from "./reorder";

function autoDismissAlerts(): void {
  const alerts = document.querySelectorAll<HTMLElement>(".alert[data-auto-dismiss]");

  alerts.forEach((alert) => {
    if (alert.dataset.dismissBound === "1") {
      return;
    }

    const delay = parseInt(alert.dataset.autoDismiss || "", 10);

    if (Number.isNaN(delay) || delay <= 0) {
      return;
    }

    alert.dataset.dismissBound = "1";

    window.setTimeout(() => {
      alert.classList.add("is-hidden");

      window.setTimeout(() => {
        alert.remove();
      }, 400);
    }, delay);
  });
}

document.addEventListener("DOMContentLoaded", () => {
  hydrateTimeAgo();
  enableReorder();
  autoDismissAlerts();
});

document.addEventListener("htmx:afterSwap", () => {
  hydrateTimeAgo();
  enableReorder();
  autoDismissAlerts();
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
