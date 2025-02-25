import type { Request, Response, NextFunction } from 'express';
import { matchesUA } from 'browserslist-useragent';

export const userAgentMiddleware = () => {
  return (req: Request, res: Response, next: NextFunction) => {
    const userAgent = req.get('User-Agent');
    if (userAgent) {
      try {
        res.locals.isESMSupported = matchesUA(userAgent, {
          browsers: [
            'safari >= 10.1',
            'ios >= 10.1',
            'Chrome >= 61',
            'Firefox >= 60',
            'Edge >= 16',
            'iOS >= 10.1',
          ],
          allowHigherVersions: true,
        });
      } catch (error) {
        // for certain unconventional browsers this version check errors out, assume no ESM support
        req.shellLogger.error({
          moduleName: '@bootstrap',
          error,
          message: 'Error in matchesUA',
        });
        res.locals.isESMSupported = false;
      }
    }

    return next();
  };
};
