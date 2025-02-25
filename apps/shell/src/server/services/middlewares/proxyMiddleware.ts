import { PHP_BASE_URL } from '@apps/shell/src/env';
import { NextFunction, Request, Response } from 'express';
import { createProxyMiddleware } from 'http-proxy-middleware';
import { shellLogger } from '@apps/shell/src/server/utils';

type ProxyMiddleware = () => (req: Request, res: Response, next: NextFunction) => void;

/**
 *
 * This proxy middleware is only intended for development env.
 * Context: All endpoints called inside of dashboard uses relative url for api calls. So in dev, we need to proxy them. In prod, since domain is same, it will automatically hit
 *          dashboard BE.
 */
export const proxyMiddleware: ProxyMiddleware = () => {
  return createProxyMiddleware({
    target: PHP_BASE_URL,
    changeOrigin: true,
    proxyTimeout: 45 * 1000,
    pathFilter: (pathname) => {
      const regexPattern = /^(?!\/$|\/app.*).*/;
      return Boolean(pathname.match(regexPattern));
    },
    on: {
      error: (err: Error, req: any, res: any) => {
        shellLogger.error({
          message: `Proxy ${process.pid}  🥷 : Error`,
          error: err,
          moduleName: '@proxyMiddleware',
        });

        return res.end('Bad gateway. Proxy encountered an issue.');
      },
    },
  });
};
