export function updateDefaultViewInStorage(merchantId, boolean) {
  window?.localStorage.setItem(`show_default_view-${merchantId}`, boolean);
}
