export function updateDefaultViewInStorage(merchantId, boolean) {
  localStorage.setItem(`show_default_view-${merchantId}`, boolean);
}
