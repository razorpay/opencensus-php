import { getCookie, setCookie, removeCookie } from './cookies';

/**
 * Determines if the error encountered is related to localStorage quota being exceeded.
 * This can happen in certain browsers when storage limits are reached, such as Safari in private mode.
 * 
 * @param {any} e - The error object encountered while interacting with localStorage.
 * @returns {boolean} - True if the error is related to storage quota being exceeded, false otherwise.
 */
function isLocalStorageQuotaExceeded(e: any): boolean {
  let quotaExceeded = false;
  if (e) {
    if (e.code) {
      switch (e.code) {
        case 22:
          quotaExceeded = true;
          break;
        case 1014:
          // Firefox-specific error
          if (e.name === 'NS_ERROR_DOM_QUOTA_REACHED') {
            quotaExceeded = true;
          }
          break;
        default:
          break;
      }
    } else if (e.number === -2147024882) {
      // Internet Explorer 8 error code
      quotaExceeded = true;
    }
  }
  return quotaExceeded;
}

/**
 * Retrieves an item from localStorage, or falls back to cookies if an error occurs (e.g., quota exceeded).
 * 
 * @param {string} key - The key of the item to retrieve.
 * @returns {string | null} - The value of the stored item, or null if the item doesn't exist.
 */
export const getItemFromLocalStorage = (key: string): string | null => {
  try {
    return window.localStorage.getItem(key);
  } catch (e) {
    return getCookie(key);
  }
};

/**
 * Stores an item in localStorage, or falls back to cookies if localStorage quota is exceeded.
 * 
 * @param {string} key - The key of the item to store.
 * @param {string} value - The value of the item to store.
 */
export const setItemInLocalStorage = (key: string, value: string): void => {
  try {
    window.localStorage.setItem(key, value);
  } catch (e) {
    if (isLocalStorageQuotaExceeded(e)) {
      window.localStorage.removeItem(key);
    }
    setCookie(key, value);
  }
};

/**
 * Removes an item from localStorage, or falls back to removing the item from cookies if an error occurs.
 * 
 * @param {string} key - The key of the item to remove.
 */
export const removeItemFromLocalStorage = (key: string): void => {
  try {
    window.localStorage.removeItem(key);
  } catch (e) {
    removeCookie(key);
  }
};

