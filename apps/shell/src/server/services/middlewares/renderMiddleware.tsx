import { NextFunction, Request, Response } from 'express';
import {
  generateLATemplate,
  generateOneDashboardTemplate,
  generateMerchantTemplate,
} from '@apps/shell/src/server/services/templates';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { checkIfPosSalesAgent } from '@apps/shell/src/server/services/helpers/posAgentUtil';

type AppLocals = {
  user: any;
  org: any;
  server_evaluated_experiments?: Record<string, any>;
  clientTemplate: string;
};

type ExpressMiddleware = () => (req: Request, res: Response, next: NextFunction) => void;

export const renderMiddleware: ExpressMiddleware =
  () =>
  async (req, res, next): Promise<any> => {
    try {
      if (!Boolean(res.locals.user?.current)) {
        throw new ShellError({
          moduleName: '@renderMiddleware',
          message: 'Critical! Render initiated with invalid user object.',
          statusCode: 500,
        });
      }

      const isLinkedAccountDashboard = Boolean(res.locals.user?.linked_account);
      const isUserActivated = res.locals.user?.activation_status === 'activated';
      const isUserCountryIndia = res.locals.user?.merchant?.country_code === 'IN';
      const isUserOrgRZP = res.locals.org?.custom_code?.toLowerCase() === 'rzp';

      const { isPosSalesAgent, isPosEkycAgent } = checkIfPosSalesAgent({
        user: res.locals.user,
        serverEvaluatedExperiments: res.locals?.server_evaluated_experiments || {},
      });

      // Production: https://admin-dashboard.razorpay.com/razorx/splitz/experiments/OWpgdX3tJs6kZo
      // Devstack: https://dashboard.dev.razorpay.in/razorx/splitz/experiments/OWphI2FwA0dqSz

      //TODO: Remove connected_navigation once one-dashboard is ramped up 100%
      const isOneDashboard =
        isUserActivated &&
        isUserCountryIndia &&
        isUserOrgRZP &&
        !isPosSalesAgent &&
        !isPosEkycAgent &&
        Boolean(res.locals?.server_evaluated_experiments?.['connected_navigation']) &&
        Boolean(res.locals?.server_evaluated_experiments?.['one-dashboard']);

      const getTemplate = () => {
        switch (true) {
          case isLinkedAccountDashboard:
            return {
              template: 'la-dashboard',
              generator: generateLATemplate,
            };
          case isOneDashboard:
            return {
              template: 'one-dashboard',
              generator: generateOneDashboardTemplate,
            };
          default:
            return {
              template: 'payments-dashboard',
              generator: generateMerchantTemplate,
            };
        }
      };

      res.locals.clientTemplate = getTemplate().template;

      req.shellLogger.info({
        message: `Rendering Initiated`,
        moduleName: '@renderMiddleware',
        context: {
          currentUserMid: res.locals.user.current,
          targetTemplate: getTemplate().template,
        },
      });

      const serverResponse = await getTemplate().generator(req, res.locals as AppLocals);

      const setCookies = res.locals['x-set-cookie'];

      if (setCookies) {
        setCookies.forEach((cookie: string) => res.append('Set-Cookie', cookie));
      } else {
        req.shellLogger.warn({
          message: `Invalid Set-Cookie format received from locals.`,
          moduleName: '@renderMiddleware',
        });
      }

      // @ref https://docs.sentry.io/platforms/javascript/guides/react/profiling/browser-profiling/#step-2-add-document-policy-js-profiling-header
      res.setHeader('Document-Policy', 'js-profiling');

      return res.status(200).send(serverResponse);
    } catch (err) {
      req.shellLogger.warn({
        message: `Rendering Failed.`,
        moduleName: '@renderMiddleware',
        context: {
          currentUserMid: res.locals.user.current,
        },
      });

      return next(err);
    }
  };
