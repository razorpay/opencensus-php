/**
 * Gets the value of a specific cookie by its key.
 *
 * @param {string} key - The key of the cookie to retrieve.
 * @returns {string | null} - The value of the cookie, or null if not found.
 */

export const getCookie = (key: string): string | null => {
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

/**
 * Sets a cookie with the given parameters.
 *
 * @param {string} sKey - The name of the cookie.
 * @param {string} sValue - The value to store in the cookie.
 * @param {number | string | Date} [vEnd] - The expiration date of the cookie. Can be a number (max-age in seconds), a string (expires in string format), or a Date object.
 * @param {string} [sPath] - The path within the domain where the cookie is valid.
 * @param {string} [sDomain] - The domain where the cookie is valid.
 * @param {boolean} [bSecure] - Whether the cookie should be marked as secure (only sent over HTTPS).
 * @returns {boolean} - Whether the cookie was successfully set.
 */
export const setCookie = (
  sKey: string,
  sValue: string,
  vEnd?: number | string | Date,
  sPath?: string,
  sDomain?: string,
  bSecure?: boolean,
): boolean => {
  if (!sKey || /^(?:expires|max-age|path|domain|secure)$/i.test(sKey)) {
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
        sExpires = `; expires=${(vEnd as Date).toUTCString()}`;
        break;
    }
  }

  document.cookie = `${encodeURIComponent(sKey)}=${encodeURIComponent(sValue)}${sExpires}${
    sDomain ? `; domain=${sDomain}` : ''
  }${sPath ? `; path=${sPath}` : ''}${bSecure ? '; secure' : ''}`;
  return true;
};

/**
 * Checks if a specific cookie exists by its key.
 *
 * @param {string} sKey - The key of the cookie to check.
 * @returns {boolean} - True if the cookie exists, false otherwise.
 */
export const hasCookie = (sKey: string): boolean => {
  if (!sKey) {
    return false;
  }
  return new RegExp(
    `(?:^|;\\s*)${encodeURIComponent(sKey).replace(/[-.+*]/g, '\\$&')}\\s*\\=`,
  ).test(document.cookie);
};

/**
 * Removes a specific cookie by its key.
 *
 * @param {string} sKey - The key of the cookie to remove.
 * @param {string} [sPath] - The path within the domain where the cookie was set.
 * @param {string} [sDomain] - The domain where the cookie was set.
 * @returns {boolean} - True if the cookie was removed, false if the cookie did not exist.
 */
export const removeCookie = (sKey: string, sPath?: string, sDomain?: string): boolean => {
  if (!hasCookie(sKey)) {
    return false;
  }
  document.cookie = `${encodeURIComponent(sKey)}=; expires=Thu, 01 Jan 1970 00:00:00 GMT${
    sDomain ? `; domain=${sDomain}` : ''
  }${sPath ? `; path=${sPath}` : ''}`;
  return true;
};
