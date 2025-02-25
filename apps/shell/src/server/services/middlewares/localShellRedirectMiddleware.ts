import { STAGE } from '@apps/shell/src/env';
import { Request, Response, NextFunction } from 'express';
import { decrypt, encrypt } from '../../utils/encryptor';
import { URL } from 'url';

/**
 * Logic:
 * Dashboard BE -> Login -> Redirects To app/dashboard or based on next params -> Ingress -> Shell Node Server
 * -> localShellRedirectMiddleware Check -> Local | Devstack or Prod
 */

export const localShellRedirectMiddleware =
  () => (req: Request, res: Response, next: NextFunction) => {
    try {
      if (STAGE != 'development') {
        const IS_LOCAL_SHELL_REDIRECT = JSON.parse(
          (req.headers?.['local-shell'] as string) || 'false',
        );

        if (!IS_LOCAL_SHELL_REDIRECT) {
          return next();
        }

        req.shellLogger.info({
          message: `Local Redirect Initiated`,
          moduleName: '@localShellRedirectMiddleware',
        });

        const userSession = req.cookies['rzp_usr_session'];
        const xsrfToken = req.cookies['XSRF-TOKEN'];

        if (userSession && xsrfToken) {
          const encryptedUserSession = encodeURIComponent(encrypt(userSession));
          const encryptedXsrfToken = encodeURIComponent(encrypt(xsrfToken));

          const url = new URL(`https://localhost:8888${req.originalUrl}`);

          url.searchParams.set('session', encryptedUserSession);
          url.searchParams.set('xsrf', encryptedXsrfToken);

          const redirectUrl = url.toString();
          req.shellLogger.success({
            message: `Local Redirect Successful`,
            moduleName: '@localShellRedirectMiddleware',
          });
          return res.redirect(redirectUrl);
        }
      } else {
        const encryptedUserSession = req.query.session as string;
        const encryptedXsrfToken = req.query.xsrf as string;

        if (encryptedUserSession && encryptedXsrfToken) {
          const userSession = decrypt(decodeURIComponent(encryptedUserSession));
          const xsrfToken = decrypt(decodeURIComponent(encryptedXsrfToken));

          const baseCookieConfig = {
            domain: 'localhost',
            path: '/',
            httpOnly: true,
            maxAge: 30 * 24 * 60 * 60 * 1000,
          };

          res.cookie('rzp_usr_session', userSession, baseCookieConfig);
          res.cookie('XSRF-TOKEN', xsrfToken, baseCookieConfig);

          const url = new URL(`https://localhost:8888${req.originalUrl}`);
          url.searchParams.delete('session');
          url.searchParams.delete('xsrf');
          const cleanUrl = url.pathname + url.search;

          req.shellLogger.success({
            message: `[DEV] Local Redirect Successful`,
            moduleName: '@localShellRedirectMiddleware',
          });
          return res.redirect(cleanUrl);
        }
      }
    } catch (error) {
      req.shellLogger.error({
        error,
        message: 'Local Redirect Failed!',
        moduleName: '@localShellRedirectMiddleware',
      });
    }
    return next();
  };
