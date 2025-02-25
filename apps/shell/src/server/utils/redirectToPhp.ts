import { Request, Response } from 'express';
import { PHP_BASE_URL, STAGE } from '@apps/shell/src/env';

export const redirectToPhp = (req: Request, res: Response) => {
  const originalUrl = req.originalUrl;
  const hostName = req.hostname;
  const isDev = STAGE === 'development';
  const dashboardBackendBaseUrl = isDev ? PHP_BASE_URL : `https://${hostName}`;
  const nextPath = encodeURIComponent(originalUrl);

  return res.redirect(`${dashboardBackendBaseUrl}/?next=${nextPath}`);
};
