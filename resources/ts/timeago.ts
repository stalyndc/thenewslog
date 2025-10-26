import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";

dayjs.extend(relativeTime);

function timeAgo(iso: string): string {
  const d = dayjs(iso);
  if (!d.isValid()) {
    return iso;
  }

  return d.fromNow();
}

export function hydrateTimeAgo(): void {
  document.querySelectorAll<HTMLElement>("[data-time]").forEach((el) => {
    if (el.dataset.timeBound === '1') {
      return;
    }

    el.dataset.timeBound = '1';
    const iso = el.dataset.time;
    if (!iso) {
      return;
    }

    const update = () => {
      el.textContent = timeAgo(iso);
    };

    update();
    const timer = window.setInterval(update, 60_000);

    el.addEventListener("htmx:beforeSwap", () => window.clearInterval(timer), {
      once: true,
    });
  });
}
