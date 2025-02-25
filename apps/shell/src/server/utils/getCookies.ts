import type { Request } from 'express';
import { shellLogger } from './shellLogger';

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
