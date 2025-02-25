import { INTERNAL_PHP_ENDPOINT } from '@apps/shell/src/env';
import type { NextFunction, Request, Response } from 'express';
import { createProxyMiddleware } from 'http-proxy-middleware';
import { getCookies } from '@apps/shell/src/server/utils';

type ProxyMiddleware = () => (req: Request, res: Response, next: NextFunction) => void;

/**
 *
 * This proxy middleware is only intended for internal endpoint testing (Whenever needed, needs to be enabled and deployed)
 */
export const internalEndpointProxyMiddleware: ProxyMiddleware = () => {
  return createProxyMiddleware({
    target: INTERNAL_PHP_ENDPOINT,
    changeOrigin: true,
    proxyTimeout: 20 * 1000,
    on: {
      proxyReq: (proxyReq: any, req: Request) => {
        req.shellLogger.info({
          message: `Proxy PID: ${process.pid} 🥷 : ${req.method} → ${req.originalUrl}`,
          moduleName: '@internalEndpointProxyMiddleware',
        });
        proxyReq.setHeader('Cookie', getCookies(req));
      },
      proxyRes: (proxyRes: any, req: Request) => {
        req.shellLogger.info({
          message: `Proxy PID: ${process.pid} 🥷 : ${req.method} → ${req.originalUrl} : ${proxyRes.statusCode}`,
          moduleName: '@internalEndpointProxyMiddleware',
        });
      },
      error: (err: Error, req: Request, res: Response) => {
        req.shellLogger.error({
          message: `Proxy ${process.pid}  🥷 : Error`,
          error: err,
          moduleName: '@internalEndpointProxyMiddleware',
        });

        return res.end('Bad gateway. Proxy encountered an issue.');
      },
    },
  });
};
