const MINUTE = 60 * 1000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;

function timeAgo(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) {
    return iso;
  }

  const diff = Math.max(0, Date.now() - d.getTime());
  if (diff < HOUR) {
    const mins = Math.floor(diff / MINUTE) || 0;
    return mins <= 1 ? "1 min ago" : `${mins} min ago`;
  }

  if (diff < DAY) {
    const hrs = Math.floor(diff / HOUR);
    return hrs <= 1 ? "1 hr ago" : `${hrs} hrs ago`;
  }

  const days = Math.floor(diff / DAY);
  return days <= 1 ? "1 day ago" : `${days} days ago`;
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
