/**
 * Checks if the current window width is for mobile resolution (<= 768px).
 *
 * @returns {boolean} - True if the window width is 768px or less.
 */
export function isMobileResolution(): boolean {
  return typeof window !== 'undefined' && window.innerWidth <= 768;
}
