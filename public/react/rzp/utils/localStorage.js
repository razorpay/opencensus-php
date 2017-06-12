// LocalStorage service that fallbacks to cookies in case of QUOTAEXCEEDED error in Safari Private mode

import { getCookie, setCookie, removeCookie } from './cookies';

export const getItem = key => {
  return window.localStorage.getItem(key) || getCookie(key);
};

export const setItem = (key, value) => {
  try {
    window.localStorage.setItem(key, value);
  } catch (e) {
    setCookie(key, value);
  }
};

export const removeItem = key => {
  try {
    return window.localStorage.removeItem(key);
  } catch (e) {
    removeCookie(key);
  }
};

export default { getItem, setItem, removeItem };
