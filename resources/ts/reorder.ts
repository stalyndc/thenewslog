import { pushToastById } from "./toast";

declare const htmx: any;

type OrderMap = Record<string, number>;

interface ReorderRow extends HTMLElement {
  dataset: {
    id: string;
  };
}

let activeList: HTMLElement | null = null;
let endpoint: string | null = null;
let tokenInput: HTMLInputElement | null = null;
let requestInFlight = false;
let queuedOrder: { current: OrderMap; previous: OrderMap | null } | null = null;
let lastSavedOrder: OrderMap = {};
let pendingUndo: OrderMap | null = null;
let listenersBound = false;

function serialize(list: HTMLElement): OrderMap {
  const map: OrderMap = {};
  const rows = Array.from(list.querySelectorAll<ReorderRow>("[data-id]"));
  rows.forEach((row, index) => {
    const id = row.dataset.id;
    if (id) {
      map[id] = index + 1;
    }
  });

  return map;
}

function ordersEqual(a: OrderMap, b: OrderMap): boolean {
  const keys = Object.keys(a);
  if (keys.length !== Object.keys(b).length) {
    return false;
  }

  return keys.every((key) => a[key] === b[key]);
}

function updatePositions(list: HTMLElement): void {
  const inputs = Array.from(list.querySelectorAll<HTMLInputElement>("[data-order-input]"));
  inputs.forEach((input, index) => {
    input.value = String(index + 1);
  });
}

function applyOrder(list: HTMLElement, order: OrderMap): void {
  const rows = Array.from(list.querySelectorAll<ReorderRow>("[data-id]"));
  rows
    .sort((a, b) => {
      const aId = a.dataset.id ?? "";
      const bId = b.dataset.id ?? "";
      return (order[aId] ?? Number.MAX_SAFE_INTEGER) - (order[bId] ?? Number.MAX_SAFE_INTEGER);
    })
    .forEach((row) => list.appendChild(row));

  updatePositions(list);
}

function toFormValues(map: OrderMap, prefix: string): Record<string, string> {
  const values: Record<string, string> = {};
  Object.entries(map).forEach(([id, position]) => {
    values[`${prefix}[${id}]`] = String(position);
  });
  return values;
}

function sendOrder(current: OrderMap, previous: OrderMap | null): void {
  if (!activeList || !endpoint) {
    return;
  }

  if (ordersEqual(current, lastSavedOrder)) {
    return;
  }

  if (requestInFlight) {
    queuedOrder = { current, previous };
    return;
  }

  const values: Record<string, string> = {};
  if (tokenInput?.value) {
    values["_token"] = tokenInput.value;
  }
  Object.assign(values, toFormValues(current, "positions"));
  if (previous) {
    Object.assign(values, toFormValues(previous, "previous_positions"));
  }

  requestInFlight = true;
  const xhr = htmx.ajax("POST", endpoint, {
    values,
    target: "#edition-toast",
    swap: "none",
  });

  if (xhr && xhr.addEventListener) {
    xhr.addEventListener("loadend", () => {
      requestInFlight = false;
      lastSavedOrder = { ...current };
      if (queuedOrder) {
        const next = queuedOrder;
        queuedOrder = null;
        sendOrder(next.current, next.previous);
      }
    });
  } else {
    requestInFlight = false;
  }
}

export function triggerEditionUndo(): void {
  if (!activeList || !pendingUndo) {
    return;
  }

  const current = serialize(activeList);
  const undoOrder = { ...pendingUndo };
  applyOrder(activeList, undoOrder);
  sendOrder(undoOrder, current);
  pendingUndo = null;
}

function bindEditionEvents(): void {
  if (listenersBound) {
    return;
  }

  listenersBound = true;

  document.body.addEventListener("edition:order-saved", (event: Event) => {
    if (!activeList) {
      activeList = document.querySelector<HTMLElement>("[data-reorder-list]");
    }

    if (!activeList) {
      return;
    }

    const custom = event as CustomEvent;
    const detail = (custom.detail ?? {}) as { message?: string; variant?: string; undo?: OrderMap | null };
    pendingUndo = detail.undo ? { ...detail.undo } : null;

    const message = detail.message ?? "Edition order updated.";
    const variant = (detail.variant as "info" | "success" | "warn") ?? "info";

    pushToastById("edition-toast", message, {
      variant,
      actionLabel: pendingUndo ? "Undo" : undefined,
      onAction: pendingUndo ? () => triggerEditionUndo() : null,
    });
  });
}

export function enableReorder(): void {
  const list = document.querySelector<HTMLElement>("[data-reorder-list]");
  if (!list || list.dataset.reorderBound === "1") {
    return;
  }

  const reorderEndpoint = list.dataset.reorderEndpoint;
  if (!reorderEndpoint) {
    return;
  }

  bindEditionEvents();

  list.dataset.reorderBound = "1";
  activeList = list;
  endpoint = reorderEndpoint;
  const form = list.closest("form");
  tokenInput = form?.querySelector<HTMLInputElement>('input[name="_token"]') ?? null;
  lastSavedOrder = serialize(list);

  let dragging: ReorderRow | null = null;
  let startOrder: OrderMap = { ...lastSavedOrder };

  const rows = Array.from(list.querySelectorAll<ReorderRow>("[data-id]"));

  rows.forEach((row) => {
    row.draggable = true;

    row.addEventListener("dragstart", () => {
      dragging = row;
      startOrder = serialize(list);
      row.classList.add("is-dragging");
    });

    row.addEventListener("dragend", () => {
      row.classList.remove("is-dragging");
      dragging = null;
      updatePositions(list);

      const currentOrder = serialize(list);
      if (!ordersEqual(currentOrder, startOrder)) {
        sendOrder(currentOrder, startOrder);
      }
    });

    row.addEventListener("dragover", (event) => {
      event.preventDefault();
      const target = event.currentTarget as ReorderRow;
      if (!dragging || dragging === target) {
        return;
      }

      const box = target.getBoundingClientRect();
      const isBefore = (event.clientY - box.top) < box.height / 2;
      list.insertBefore(dragging, isBefore ? target : target.nextSibling);
    });
  });
}
