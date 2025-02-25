/**
 * Checks if the specified percentage of the element is visible within the viewport, considering offsets.
 *
 * @param {HTMLElement} element - The HTML element to check.
 * @param {number} percentVisible - The percentage of the element that should be visible.
 * @param {number} [offsetTop=0] - Optional: Offset from the top of the window to start the check.
 * @param {number} [offsetBottom=0] - Optional: Offset from the bottom of the window to start the check.
 * @returns {boolean} - Returns true if the specified percentage of the element is visible in the viewport.
 */
export const isElementXPercentInViewport = (
  element: HTMLElement,
  percentVisible: number,
  offsetTop: number = 0,
  offsetBottom: number = 0
): boolean => {
  let elementTop: number, elementHeight: number, elementBottom: number;
  let percentCutFromTop: number, percentCutFromBottom: number;
  let rect: DOMRect, windowHeight: number;

  rect = element.getBoundingClientRect();
  windowHeight = window.innerHeight || document.documentElement.clientHeight;

  elementTop = rect.top - offsetTop >= 0 ? 0 : offsetTop - rect.top;
  elementHeight = rect.height;
  percentCutFromTop = (elementTop / elementHeight) * 100;

  elementBottom = rect.bottom + offsetBottom - windowHeight;
  percentCutFromBottom = (elementBottom / elementHeight) * 100;

  return !(
    Math.floor(100 - percentCutFromTop) < percentVisible ||
    Math.floor(100 - percentCutFromBottom) < percentVisible
  );
};
