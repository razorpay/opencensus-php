import { redirectToPhp } from '../../utils';
import { PostConcurrentResHandler } from '../middlewares';
import { combineUserPayload } from '../transformer/combineUserPayload';
import { ShellRedirectionService } from '@apps/shell/src/server/services/redirection/ShellRedirectionService';
import { AppConstants } from '@apps/shell/src/server/constants';

export const preRenderHandler: PostConcurrentResHandler = async (req, res, next) => {
  try {
    res.locals.user = combineUserPayload(res);

    const redirectionService = new ShellRedirectionService(req, res);
    const redirectionData = await redirectionService.getShellRedirectionData();

    if (
      Boolean(redirectionData?.destination) &&
      redirectionData.destination === AppConstants.DESTINATION_PHP_BE
    ) {
      req.shellLogger.info({
        moduleName: '@concurrentMiddlewareExecutor',
        message: 'Redirecting to PHP',
        context: {
          destination: redirectionData.destination,
        },
      });
      return redirectToPhp(req, res);
    } else if (Boolean(redirectionData?.redirectTo)) {
      req.shellLogger.info({
        moduleName: '@concurrentMiddlewareExecutor',
        message: 'Making external redirect...',
        context: {
          destination_url: redirectionData?.redirectTo,
        },
      });

      return res.redirect(redirectionData.redirectTo as string);
    } else {
      req.shellLogger.info({
        moduleName: '@concurrentMiddlewareExecutor',
        message: 'Continuing with render...',
        context: {
          destination: redirectionData.destination,
        },
      });

      return next();
    }
  } catch (error) {
    req.shellLogger.warn({
      moduleName: '@preRenderHandler',
      message: 'Error in checking redirection logic',
    });
    return next(error);
  }
};
