export function announceResult(kind) {
  if (kind !== "success" && kind !== "error") return;
  window.dispatchEvent(new CustomEvent("cescort:announce", { detail: { kind } }));
}
