// Todo: delete this file, it's available in @dashboard/shared-utils
// LocalStorage service that fallbacks to cookies in case of QUOTAEXCEEDED error in Safari Private mode
import { getCookie, setCookie, removeCookie } from './cookies';

// Taken from http://crocodillon.com/blog/always-catch-localstorage-security-and-quota-exceeded-errors
function isQuotaExceeded(e) {
  var quotaExceeded = false;
  if (e) {
    if (e.code) {
      switch (e.code) {
        case 22:
          quotaExceeded = true;
          break;
        case 1014:
          // Firefox
          if (e.name === 'NS_ERROR_DOM_QUOTA_REACHED') {
            quotaExceeded = true;
          }
          break;
      }
    } else if (e.number === -2147024882) {
      // Internet Explorer 8
      quotaExceeded = true;
    }
  }
  return quotaExceeded;
}

const localStorage = window.localStorage;
export const getItem = (key) => {
  try {
    return localStorage.getItem(key);
  } catch (e) {
    return getCookie(key);
  }
};

export const setItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
  } catch (e) {
    if (isQuotaExceeded(e)) {
      localStorage.removeItem(key);
    }
    setCookie(key, value);
  }
};

export const removeItem = (key) => {
  try {
    return localStorage.removeItem(key);
  } catch (e) {
    removeCookie(key);
  }
};

export default { getItem, setItem, removeItem };
