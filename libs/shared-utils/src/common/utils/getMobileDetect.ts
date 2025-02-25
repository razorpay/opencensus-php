/**
 * Defines a type for detecting mobile, desktop, and web view environments.
 */
type MobileDetect = {
  /**
   * Determines if the device is mobile.
   * @returns {boolean} True if the device is mobile, otherwise false.
   */
  isMobile: () => boolean;

  /**
   * Determines if the device is a desktop.
   * @returns {boolean} True if the device is a desktop, otherwise false.
   */
  isDesktop: () => boolean;

  /**
   * Determines if the device is running Android OS.
   * @returns {boolean} True if the device is Android, otherwise false.
   */
  isAndroid: () => boolean;

  /**
   * Determines if the device is running iOS.
   * @returns {boolean} True if the device is iOS, otherwise false.
   */
  isIos: () => boolean;

  /**
   * Determines if the request is from server-side rendering (SSR).
   * @returns {boolean} True if it's SSR, otherwise false.
   */
  isSSR: () => boolean;

  /**
   * Determines if the page is being accessed via a webview, particularly in a React Native webview.
   * @returns {boolean} True if it's a webview, otherwise false.
   */
  isWebView: () => boolean;
};

/**
 * Type definition for a function that creates a mobile detection object.
 */
type GetMobileDetect = (userAgent?: string) => MobileDetect;

/**
 * A utility function to detect the type of device (mobile, desktop, Android, iOS, SSR, or WebView).
 * It analyzes the user agent string to make the determination.
 * 
 * @param {string} [userAgent=navigator.userAgent] - The user agent string, defaulting to the browser's user agent.
 * @returns {MobileDetect} - Returns an object containing functions to detect the environment.
 */
export const getMobileDetect: GetMobileDetect = (userAgent = navigator.userAgent): MobileDetect => {
  const isAndroid = (): boolean => Boolean(userAgent.match(/Android/i));
  const isIos = (): boolean => Boolean(userAgent.match(/iPhone|iPad|iPod/i));
  const isOpera = (): boolean => Boolean(userAgent.match(/Opera Mini/i));
  const isWindows = (): boolean => Boolean(userAgent.match(/IEMobile/i));
  const isSSR = (): boolean => Boolean(userAgent.match(/SSR/i));

  const isMobile = (): boolean => Boolean(isAndroid() || isIos() || isOpera() || isWindows());
  const isDesktop = (): boolean => Boolean(!isMobile() && !isSSR());
  // we are specifically expecting source=webview in query params from mobile app team whenever they open pages in react native webview.
  const isWebView = (): boolean => Boolean(window.location.search.includes('source=webview'));

  return {
    isMobile,
    isDesktop,
    isAndroid,
    isIos,
    isSSR,
    isWebView,
  };
};

