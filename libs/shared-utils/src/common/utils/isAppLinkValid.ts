/**
 * Validates whether a given app link is a valid Google Play Store or Apple App Store URL.
 * 
 * This function checks if the provided `appLink` matches the URL patterns for Google Play Store
 * and Apple App Store. If the URL matches either of these patterns, the function returns `true`; 
 * otherwise, it returns `false`.
 * 
 * @param {string} appLink - The app link URL to validate.
 * @returns {boolean} Returns `true` if the app link is valid, `false` otherwise.
 * 
 * @example
 * // Example 1: Valid Google Play Store link
 * const result = isAppLinkValid('https://play.google.com/store/apps/details?id=com.example.app');
 * console.log(result); // Output: true
 * 
 * @example
 * // Example 2: Valid Apple App Store link
 * const result = isAppLinkValid('https://apps.apple.com/us/app/example-app/id1234567890');
 * console.log(result); // Output: true
 * 
 * @example
 * // Example 3: Invalid app link
 * const result = isAppLinkValid('https://example.com');
 * console.log(result); // Output: false
 */
export const isAppLinkValid = (appLink: string): boolean => {
    // Regular expression to validate Google Play Store URLs
    const PLAY_STORE_URL_REGEX =
      /^(https?:\/\/)?play.google.com\/store\/apps\/(details|developer)\?id=.*/;
    
    // Regular expression to validate Apple App Store URLs
    /* eslint-disable-next-line */
    const APP_STORE_URL_REGEX = /^(https?:\/\/)?apps.apple.com\/.*/;
    
    // Check if the appLink matches either Play Store or App Store URL patterns
    return PLAY_STORE_URL_REGEX.test(appLink) || APP_STORE_URL_REGEX.test(appLink);
  };
  