/**
 * Checks if the device is mobile based on the custom width or the default threshold of 767px.
 *
 * @param {number} [customWidth=767] - The custom width to check if the device is mobile.
 * @returns {boolean} - True if the device is considered mobile, otherwise false.
 *
 * @example
 * isMobileDevice(); // returns true if the device width is <= 767px
 * isMobileDevice(1024); // returns true if the device width is <= 1024px
 */
export const isMobileDevice = (customWidth?: number): boolean =>
  document.documentElement?.clientWidth
    ? document.documentElement.clientWidth <= (customWidth ? customWidth : 767)
    : window.innerWidth <= (customWidth ? customWidth : 767);
