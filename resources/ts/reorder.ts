interface ReorderRow extends HTMLElement {
  dataset: {
    id: string;
  };
}

function updatePositions(container: HTMLElement): void {
  const rows = Array.from(container.querySelectorAll<HTMLInputElement>("[data-order-input]"));
  rows.forEach((input, index) => {
    input.value = String(index + 1);
  });
}

export function enableReorder(): void {
  const list = document.querySelector<HTMLElement>("[data-reorder-list]");
  if (!list) {
    return;
  }

  if (list.dataset.reorderBound === '1') {
    return;
  }

  list.dataset.reorderBound = '1';

  let dragging: ReorderRow | null = null;

  const rows = Array.from(list.querySelectorAll<ReorderRow>("[data-id]"));

  rows.forEach((row) => {
    row.draggable = true;
    row.addEventListener("dragstart", () => {
      dragging = row;
      row.classList.add("is-dragging");
    });

    row.addEventListener("dragend", () => {
      row.classList.remove("is-dragging");
      dragging = null;
      updatePositions(list);
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
