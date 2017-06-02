// LocalStorage service that fallbacks to cookies in case of QUOTAEXCEEDED error in Safari Private mode

import { getCookie, setCookie } from './cookies';

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

export default { getItem, setItem };
