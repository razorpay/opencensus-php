/**
 * Determine the mobile operating system.
 * This function returns one of 'iOS', 'Android', 'Windows Phone', or 'unknown'.
 *
 * @returns {string} - The detected mobile operating system.
 *
 * @example
 * const os = getMobileOperatingSystem();
 * console.log(os); // Output: 'iOS', 'Android', 'Windows Phone', or 'unknown'
 */
export function getMobileOperatingSystem(): string {
  const userAgent = navigator.userAgent || navigator.vendor || (window as any).opera;

  // Windows Phone must come first because its UA also contains "Android"
  if (/windows phone/i.test(userAgent)) {
    return 'Windows Phone';
  }

  if (/android/i.test(userAgent)) {
    return 'Android';
  }

  if (/iPhone/.test(userAgent) && !(window as any).MSStream) {
    return 'iOS';
  }

  return 'unknown';
}
