/**
 * Prevents the default action of an event and stops it from propagating further in the DOM.
 *
 * @example
 * document.getElementById('myButton').addEventListener('click', (e) => {
 *   prevent(e);
 * });
 *
 * @param {Event} e - The event object to prevent and stop.
 */
export const prevent = (e: Event): void => {
  e.preventDefault();
  e.stopPropagation();
};
