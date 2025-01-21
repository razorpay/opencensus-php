// TODO: to delete this file after full shell roll-out and consume from shell

declare global {
  interface Window {
    rzp_user: any;
  }
}

type ModeT = 'test' | 'live';
const mode: ModeT = 'test';

const getCookie = (key) => {
  if (!key) return null;
  return (
    decodeURIComponent(
      document.cookie.replace(
        new RegExp(
          `(?:(?:^|.*;)\\s*${encodeURIComponent(key).replace(
            /[-.+*]/g,
            '\\$&',
          )}\\s*\\=\\s*([^;]*).*$)|^.*$`,
        ),
        '$1',
      ),
    ) || null
  );
};

const localStorage = window.localStorage;

const getItem = (key) => {
  try {
    return localStorage.getItem(key);
  } catch (e) {
    return getCookie(key);
  }
};

export function getMode(merchantId?: string): string {
  const _mode = getItem(`rzp_mode--${merchantId}`) || mode;
  return _mode;
}

// eslint-disable-next-line consistent-return
export const deepClone = (obj) => {
  try {
    return JSON.parse(JSON.stringify(obj));
  } catch (err) {
    console.log('Deepclone error: ', err);
  }
};
