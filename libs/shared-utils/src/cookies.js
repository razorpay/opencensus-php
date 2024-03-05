// Taken from MDN https://developer.mozilla.org/en-US/docs/Web/API/Document/cookie/Simple_document.cookie_framework

export const getCookie = (key) => {
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

export const setCookie = (sKey, sValue, vEnd, sPath, sDomain, bSecure) => {
  // eslint-disable-next-line no-useless-escape
  if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) {
    return false;
  }
  let sExpires = '';
  if (vEnd) {
    switch (vEnd.constructor) {
      case Number:
        sExpires =
          vEnd === Infinity ? '; expires=Fri, 31 Dec 9999 23:59:59 GMT' : `; max-age=${vEnd}`;
        break;
      case String:
        sExpires = `; expires=${vEnd}`;
        break;
      case Date:
        sExpires = `; expires=${vEnd.toUTCString()}`;
        break;
      default:
        break;
    }
  }
  document.cookie = `${encodeURIComponent(sKey)}=${encodeURIComponent(sValue)}${sExpires}${
    sDomain ? `; domain=${sDomain}` : ''
  }${sPath ? `; path=${sPath}` : ''}${bSecure ? '; secure' : ''}`;
  return true;
};

export const hasCookie = (sKey) => {
  if (!sKey) {
    return false;
  }
  return new RegExp(
    `(?:^|;\\s*)${encodeURIComponent(sKey).replace(/[-.+*]/g, '\\$&')}\\s*\\=`,
  ).test(document.cookie);
};

export const removeCookie = (sKey, sPath, sDomain) => {
  if (!hasCookie(sKey)) {
    return false;
  }
  document.cookie = `${encodeURIComponent(sKey)}=; expires=Thu, 01 Jan 1970 00:00:00 GMT${
    sDomain ? `; domain=${sDomain}` : ''
  }${sPath ? `; path=${sPath}` : ''}`;
  return true;
};
