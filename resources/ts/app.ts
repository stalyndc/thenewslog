import { hydrateTimeAgo } from "./timeago";
import { enableReorder } from "./reorder";
import { pushToastById } from "./toast";

declare const htmx: any;

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

function bindEditionInfinite(): void {
  if ((window as any).__editionInfiniteBound) {
    return;
  }

  (window as any).__editionInfiniteBound = true;

  document.body.addEventListener("editions:page-loaded", (event: Event) => {
    const custom = event as CustomEvent;
    const detail = custom.detail ?? {};
    const list = document.getElementById("edition-list");
    if (!list) {
      return;
    }

    if (typeof detail.page === "number") {
      list.dataset.editionsCurrent = String(detail.page);
    }

    if (!detail.next) {
      const pagination = document.querySelector<HTMLElement>(".pagination--fallback");
      if (pagination) {
        pagination.classList.add("is-hidden");
      }
    }
  });
}

function bindInboxPolling(): void {
  if ((window as any).__inboxBound) {
    return;
  }

  (window as any).__inboxBound = true;

  document.body.addEventListener("inbox:updated", (event: Event) => {
    const custom = event as CustomEvent;
    const detail = custom.detail ?? {};
    const afterInput = document.getElementById("inbox-after-id") as HTMLInputElement | null;
    const tbody = document.getElementById("inbox-table-body") as HTMLElement | null;
    const inboxLink = document.getElementById("admin-inbox-link") as HTMLElement | null;

    const previousCount = inboxLink && inboxLink.dataset.inboxCount ? Number(inboxLink.dataset.inboxCount) : 0;

    if (typeof detail.latest_id === "number" && afterInput) {
      const currentValue = Number(afterInput.value || "0");
      if (detail.latest_id > currentValue) {
        afterInput.value = String(detail.latest_id);
      }
    }

    if (tbody && typeof detail.latest_id === "number") {
      tbody.dataset.latestId = String(detail.latest_id);
    }

    if (inboxLink && typeof detail.count === "number") {
      inboxLink.dataset.inboxCount = String(detail.count);
      inboxLink.textContent = detail.count > 0 ? `Inbox (${detail.count})` : "Inbox";
    }

  if (inboxLink && typeof detail.count === "number" && detail.count > previousCount) {
      const diff = detail.count - previousCount;
      pushToastById("inbox-toast", diff === 1 ? "1 new inbox item ready." : `${diff} new inbox items ready.`, {
        variant: "info",
        timeout: 5000,
      });
    }
  });
}

type TagContext = {
  active: string;
  existing: string[];
};

let currentTagInput: HTMLInputElement | null = null;
let tagSuggestionsUrl = "/admin/tags/suggest";
let tagValidationUrl = "/admin/tags/validate";
let tagSuggestionAbortController: AbortController | null = null;
let tagValidationAbortController: AbortController | null = null;
let lastSuggestionValue = "";
let lastValidationValue = "";

function ensureTagInput(): HTMLInputElement | null {
  if (currentTagInput && document.body.contains(currentTagInput)) {
    return currentTagInput;
  }

  currentTagInput = document.querySelector<HTMLInputElement>("[data-tags-input]") ?? null;
  return currentTagInput;
}

function tagSuggestionContainer(): HTMLElement | null {
  return document.getElementById("tag-suggestions");
}

function tagFeedbackContainer(): HTMLElement | null {
  return document.getElementById("tag-feedback");
}

function parseTags(value: string): string[] {
  return value
    .split(",")
    .map((tag) => tag.trim())
    .filter((tag) => tag.length > 0);
}

function computeTagContext(value: string): TagContext {
  const parts = value.split(",").map((tag) => tag.trim());

  if (parts.length === 0) {
    return { active: "", existing: [] };
  }

  const active = parts.pop() ?? "";
  const existing = parts.filter((tag) => tag.length > 0);

  return { active, existing };
}

function formatTags(tags: string[]): string {
  return tags.join(", ");
}

function clearTagSuggestions(): void {
  if (tagSuggestionAbortController) {
    tagSuggestionAbortController.abort();
    tagSuggestionAbortController = null;
  }

  const container = tagSuggestionContainer();
  if (container) {
    container.innerHTML = "";
  }

  lastSuggestionValue = "";
}

function triggerTagSuggestions(force = false): void {
  const input = ensureTagInput();
  const container = tagSuggestionContainer();

  if (!input || !container) {
    return;
  }

  const raw = input.value;
  const trimmed = raw.trim();

  if (trimmed === "") {
    clearTagSuggestions();
    return;
  }

  if (!force && raw === lastSuggestionValue) {
    return;
  }

  if (tagSuggestionAbortController) {
    tagSuggestionAbortController.abort();
  }

  const context = computeTagContext(raw);
  const params = new URLSearchParams();
  params.set("tags", context.active);
  params.set("tags_full", raw);

  if (context.existing.length > 0) {
    params.set("existing", context.existing.join(", "));
  }

  tagSuggestionAbortController = new AbortController();

  fetch(`${tagSuggestionsUrl}?${params.toString()}`, {
    credentials: "same-origin",
    headers: {
      "X-Requested-With": "XMLHttpRequest",
      Accept: "text/html",
    },
    signal: tagSuggestionAbortController.signal,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Failed to fetch tag suggestions: ${response.status}`);
      }

      return response.text();
    })
    .then((html) => {
      container.innerHTML = html;
      lastSuggestionValue = raw;
    })
    .catch((error) => {
      if ((error as Error).name === "AbortError") {
        return;
      }

      console.error("Tag suggestion fetch failed", error);
      clearTagSuggestions();
    })
    .finally(() => {
      tagSuggestionAbortController = null;
    });
}

function triggerTagValidation(force = false): void {
  const input = ensureTagInput();
  const feedback = tagFeedbackContainer();

  if (!input || !feedback) {
    return;
  }

  const raw = input.value;

  if (!force && raw === lastValidationValue) {
    return;
  }

  if (tagValidationAbortController) {
    tagValidationAbortController.abort();
  }

  const params = new URLSearchParams();
  params.set("tags", raw);

  tagValidationAbortController = new AbortController();

  fetch(`${tagValidationUrl}?${params.toString()}`, {
    credentials: "same-origin",
    headers: {
      "X-Requested-With": "XMLHttpRequest",
      Accept: "text/html",
    },
    signal: tagValidationAbortController.signal,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Failed to validate tags: ${response.status}`);
      }

      return response.text();
    })
    .then((html) => {
      feedback.innerHTML = html;
      lastValidationValue = raw;
    })
    .catch((error) => {
      if ((error as Error).name === "AbortError") {
        return;
      }

      console.error("Tag validation fetch failed", error);
    })
    .finally(() => {
      tagValidationAbortController = null;
    });
}

function bindTagHelpers(): void {
  const input = document.querySelector<HTMLInputElement>("[data-tags-input]");
  if (!input || input.dataset.helperBound === "1") {
    return;
  }

  currentTagInput = input;
  tagSuggestionsUrl = input.dataset.tagsSuggestUrl ?? tagSuggestionsUrl;
  tagValidationUrl = input.dataset.tagsValidateUrl ?? tagValidationUrl;
  lastSuggestionValue = "";
  lastValidationValue = "";

  input.dataset.helperBound = "1";
  input.setAttribute("autocomplete", "off");

  input.addEventListener("blur", () => {
    window.setTimeout(() => {
      triggerTagValidation();
    }, 150);
  });

  input.addEventListener("change", () => {
    triggerTagValidation(true);
  });

  let validationDebounceId: number | undefined;
  let suggestionDebounceId: number | undefined;

  const scheduleValidation = () => {
    if (typeof validationDebounceId !== "undefined") {
      window.clearTimeout(validationDebounceId);
    }

    validationDebounceId = window.setTimeout(() => {
      triggerTagValidation();
    }, 250);
  };

  const scheduleSuggestions = () => {
    if (typeof suggestionDebounceId !== "undefined") {
      window.clearTimeout(suggestionDebounceId);
    }

    suggestionDebounceId = window.setTimeout(() => {
      triggerTagSuggestions();
    }, 150);
  };

  input.addEventListener("input", () => {
    scheduleValidation();
    scheduleSuggestions();
  });

  input.addEventListener("focus", () => {
    triggerTagSuggestions(true);
  });

  triggerTagValidation(true);

  if (!(window as any).__tagSuggestionHandler) {
    (window as any).__tagSuggestionHandler = true;

    document.addEventListener("click", (event) => {
      const target = event.target as HTMLElement;
      const suggestion = target.closest<HTMLButtonElement>(".tag-suggestion");
      const tagsInput = ensureTagInput();
      const container = tagSuggestionContainer();

      if (!tagsInput || !container) {
        return;
      }

      if (suggestion) {
        const name = suggestion.dataset.tagName;
        if (!name) {
          return;
        }

        const tags = parseTags(tagsInput.value);
        const exists = tags.some((tag) => tag.toLowerCase() === name.toLowerCase());
        if (!exists) {
          tags.push(name);
          tagsInput.value = formatTags(tags);
          tagsInput.dispatchEvent(new Event("change", { bubbles: true }));
        }

        clearTagSuggestions();
        tagsInput.focus();
        triggerTagValidation(true);
        triggerTagSuggestions(true);
        return;
      }

      if (!target.closest("#tag-suggestions") && !target.closest("[data-tags-input]")) {
        clearTagSuggestions();
      }
    });
  }
}

function initDrawerNavigation(): void {
  const desktopNav = document.querySelector<HTMLElement>(".app-nav");
  const drawer = document.querySelector<HTMLElement>("[data-mobile-drawer]");

  if (!desktopNav || !drawer) {
    return;
  }

  const drawerInner = drawer.querySelector<HTMLElement>(".drawer-nav__inner");
  if (!drawerInner) {
    return;
  }

  const drawerLinks = Array.from(drawerInner.querySelectorAll<HTMLAnchorElement>("a"));

  const findMatchingLinks = (href: string | null): HTMLAnchorElement[] =>
    drawerLinks.filter((link) => link.getAttribute("href") === href);

  desktopNav.querySelectorAll<HTMLAnchorElement>("a").forEach((navLink) => {
    const href = navLink.getAttribute("href");
    if (!href) {
      return;
    }

    let matches = findMatchingLinks(href);
    if (matches.length === 0) {
      const clone = navLink.cloneNode(true) as HTMLAnchorElement;
      clone.classList.add("mobile-link");
      drawerInner.appendChild(clone);
      matches = [clone];
      drawerLinks.push(clone);
    } else {
      matches.forEach((match) => {
        match.classList.add("mobile-link");
      });
    }

    const isActive = navLink.classList.contains("is-active");
    matches.forEach((match) => {
      match.classList.toggle("is-active", isActive);
    });
  });
}

function bindMobileNav(): void {
  const toggle = document.querySelector<HTMLButtonElement>("[data-nav-toggle]");
  const drawer = document.querySelector<HTMLElement>("[data-mobile-drawer]");
  const overlay = document.querySelector<HTMLElement>("[data-mobile-drawer-overlay]");

  if (!toggle || !drawer || toggle.dataset.navBound === "1") {
    return;
  }

  toggle.dataset.navBound = "1";

  const body = document.body;

  const isOpen = (): boolean => drawer.classList.contains("is-open");

  const setState = (open: boolean): void => {
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
    drawer.classList.toggle("is-open", open);
    if (overlay) {
      overlay.classList.toggle("is-open", open);
    }
    body.classList.toggle("is-mobile-nav-open", open);
  };

  const close = (): void => {
    if (!isOpen()) {
      return;
    }

    setState(false);
  };

  const open = (): void => {
    if (isOpen()) {
      return;
    }

    setState(true);
  };

  toggle.addEventListener("click", (event) => {
    event.preventDefault();
    if (isOpen()) {
      close();
    } else {
      open();
    }
  });

  drawer.querySelectorAll<HTMLAnchorElement>("a").forEach((link) => {
    link.addEventListener("click", () => {
      close();
    });
  });

  if (overlay) {
    overlay.addEventListener("click", (event) => {
      event.preventDefault();
      close();
    });
  }

  if (!(window as any).__mobileNavDocumentHandlers) {
    (window as any).__mobileNavDocumentHandlers = true;

    document.addEventListener("click", (event) => {
      const target = event.target as HTMLElement | null;
      const withinDrawer = target ? target.closest("[data-mobile-drawer]") : null;
      const withinToggle = target ? target.closest("[data-nav-toggle]") : null;

      if (!withinDrawer && !withinToggle) {
        close();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        close();
      }
    });
  }
}

function initEnhancements(): void {
  initDrawerNavigation();
  hydrateTimeAgo();
  enableReorder();
  autoDismissAlerts();
  bindEditionInfinite();
  bindInboxPolling();
  bindTagHelpers();
  bindMobileNav();
}

const runEnhancements = (): void => {
  initEnhancements();
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", runEnhancements, { once: true });
} else {
  runEnhancements();
}

document.addEventListener("htmx:afterSwap", () => {
  runEnhancements();
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

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    clearTagSuggestions();
  }
});
