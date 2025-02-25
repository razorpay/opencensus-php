/**
 * Opens a ticket modal with a unique id based on the current timestamp.
 *
 * @param {T} data - The data of generic type T to be passed when opening the modal.
 */
export const openTicketModal = <T extends Record<string, unknown>>(data: T = {} as T): void => {
  const id = `ticket-${Date.now()}`;
  window.rzpTicketSystem?.openModal(id, data);
};
