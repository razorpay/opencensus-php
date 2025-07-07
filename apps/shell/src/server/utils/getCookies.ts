import type { Request } from 'express';
import { shellLogger } from './shellLogger';

// Whitelisted cookie keys that can be included from forward_cookies
const WHITELISTED_FORWARD_COOKIES: string[] = [
  // 'rzp_cross_region',
];

/**
 * Get only whitelisted cookies from forward_cookies
 * @param req - Express request object
 * @returns Object with whitelisted cookie names as keys and their values
 */
export const getWhitelistedForwardCookies = (req: Request): Record<string, string> => {
  const whitelistedCookies: Record<string, string> = {};

  if (!req.forward_cookies || typeof req.forward_cookies !== 'object') {
    return whitelistedCookies;
  }

  // req.forward_cookies is already a parsed cookie object like req.cookies
  Object.entries(req.forward_cookies).forEach(([cookieName, cookieValue]) => {
    if (typeof cookieValue === 'string' && cookieValue.trim()) {
      // Only include whitelisted cookies
      if (WHITELISTED_FORWARD_COOKIES.includes(cookieName)) {
        whitelistedCookies[cookieName] = cookieValue.trim();
      }
    }
  });

  return whitelistedCookies;
};

/**
 * Get the final merged cookies object (req.cookies + forward_cookies with override)
 * @param req - Express request object
 * @returns Object with final cookie names as keys and their values (forward_cookies override req.cookies)
 */
export const getMergedCookies = (req: Request): Record<string, string> => {
  const cookieMap: Record<string, string> = {};

  // First, add regular cookies from req.cookies
  if (req.cookies && typeof req.cookies === 'object') {
    Object.entries(req.cookies).forEach(([cookieName, cookieValue]) => {
      if (typeof cookieValue === 'string' && cookieValue.trim()) {
        cookieMap[cookieName] = cookieValue.trim();
      }
    });
  }

  // Then, add whitelisted cookies from forward_cookies (these will override req.cookies)
  const whitelistedForwardCookies = getWhitelistedForwardCookies(req);
  Object.entries(whitelistedForwardCookies).forEach(([cookieName, cookieValue]) => {
    cookieMap[cookieName] = cookieValue; // This will override if key already exists
  });

  return cookieMap;
};

/**
 * Get all cookies (regular + whitelisted forward cookies) as a string
 * @param req - Express request object
 * @returns Cookie string in format "name1=value1; name2=value2"
 */
export const getCookiesV2 = (req: Request): string => {
  if (!req || typeof req !== 'object') {
    shellLogger.warn({
      message: 'Invalid request object provided to getCookies',
      moduleName: '@shell/getCookies',
    });
    return '';
  }

  const cookies: string[] = [];
  const cookieMap: Record<string, string> = {};

  // First, add regular cookies from req.cookies
  if (req.cookies && typeof req.cookies === 'object') {
    Object.entries(req.cookies).forEach(([cookieName, cookieValue]) => {
      if (typeof cookieValue === 'string' && cookieValue.trim()) {
        cookieMap[cookieName] = cookieValue.trim();
      } else {
        req.shellLogger.warn({
          message: `Invalid or empty cookie: ${cookieName}`,
          moduleName: '@shell/getCookies',
        });
      }
    });
  } else {
    req.shellLogger.warn({
      message: 'No cookies found on the request object',
      moduleName: '@shell/getCookies',
    });
  }

  // Then, add whitelisted cookies from forward_cookies
  const whitelistedForwardCookies = getWhitelistedForwardCookies(req);
  Object.entries(whitelistedForwardCookies).forEach(([cookieName, cookieValue]) => {
    cookieMap[cookieName] = cookieValue; // override if key already exists
  });

  // Convert the final cookie map to strings
  const cookieStrings = Object.entries(cookieMap)
    .map(([name, value]) => `${name}=${value}`);

  cookies.push(...cookieStrings);

  if (Object.keys(whitelistedForwardCookies).length > 0) {
    req.shellLogger.info({
      message: 'Forward cookies processed',
      moduleName: '@shell/getCookies',
      context: {
        whitelistedKeys: Object.keys(whitelistedForwardCookies)
      }
    });
  }

  const finalCookieString = cookies.join('; ');

  req.shellLogger.info({
    message: 'Cookies processed successfully',
    moduleName: '@shell/getCookies',
    context: {
      cookieString: finalCookieString,
    }
  });

  return finalCookieString;
};

export const getCookies = (req: Request): string => {
  if (!req || typeof req !== 'object') {
    shellLogger.warn({
      message: 'Invalid request object provided to getCookies',
      moduleName: '@shell/getCookies',
    });
    return '';
  }

  if (!req.cookies || typeof req.cookies !== 'object') {
    req.shellLogger.warn({
      message: 'No cookies found on the request object',
      moduleName: '@shell/getCookies',
    });
    return '';
  }

  const cookies = Object.entries(req.cookies)
    .map(([cookieName, cookieValue]) => {
      if (typeof cookieValue !== 'string' || !cookieValue.trim()) {
        req.shellLogger.warn({
          message: `Invalid or empty cookie: ${cookieName}`,
          moduleName: '@shell/getCookies',
        });
        return '';
      }
      return `${cookieName}=${cookieValue};`;
    })
    .filter(Boolean)
    .join(' ');

  return cookies;
};
