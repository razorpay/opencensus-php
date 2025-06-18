import {
  PUBLIC_API_URL,
  APP_ENV,
  SPLITZ_INTERNAL_AUTH_TOKEN,
  SPLITZ_INTERNAL_API_ENDPOINT,
} from '@apps/shell/src/env';
import {
  shellSplitzConfig,
  getVariants,
  initABService,
  parseExperimentsForServerSideUsage,
  type InitABServiceConfig,
} from '@apps/shell/src/server/services/splitz';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { Request, Response } from 'express';

type ShellSplitzMiddleware = () => (req: Request, res: Response) => Promise<void>;

export const shellSplitzMiddleware: ShellSplitzMiddleware =
  () =>
  async (req, res): Promise<void> => {
    const currentUserMid = res.locals?.user_session?.merchant_id;

    // This means the current session is authenticated, but does not meet the requirement to proceed with render.
    // handled by @preRenderHandler (Redirection Logic in place)
    if (!Boolean(currentUserMid)) {
      req.shellLogger.warn({
        message: `Merchant ID not found in user session data. Skipping splitz evaluation.`,
        moduleName: '@shellSplitzMiddleware',
        context: {
          userId: res.locals.user_session?.user_id,
        },
      });
      return;
    }

    req.shellLogger.info({
      message: `Evaluating experiments for ${APP_ENV} environment`,
      moduleName: '@shellSplitzMiddleware',
      context: {
        currentUserMid,
      },
    });

    // const isInternalAPICallEnabled = IS_PRODUCTION;
    const isInternalAPICallEnabled = false;

    const splitzConfig: InitABServiceConfig = isInternalAPICallEnabled
      ? {
          apiBaseUrl: SPLITZ_INTERNAL_API_ENDPOINT as string,
          internalApiAuthToken: SPLITZ_INTERNAL_AUTH_TOKEN,
          isInternalEndpointCall: true,
        }
      : {
          apiBaseUrl: PUBLIC_API_URL as string,
        };

    initABService(splitzConfig);

    const evaluateExperiments = async () =>
      await Promise.all([getVariants(shellSplitzConfig, currentUserMid)]).then(
        ([evaluatedExperimentsForShell]) => {
          if (!Boolean(evaluatedExperimentsForShell)) {
            throw new ShellError({
              moduleName: '@shellSplitzMiddleware',
              message: 'Failed to evaluate experiments.',
              context: {
                path: splitzConfig.apiBaseUrl,
                dashboardBackendRequestId: undefined,
              },
            });
          }

          // For usage in shell
          res.locals.server_evaluated_experiments = parseExperimentsForServerSideUsage(
            evaluatedExperimentsForShell,
          );

          req.shellLogger.success({
            message: `Experiments evaluated successfully.`,
            moduleName: '@shellSplitzMiddleware',
            context: {
              currentUserMid,
            },
          });

          return;
        },
      );

    try {
      await evaluateExperiments();
    } catch (e) {
      req.shellLogger.warn({
        message: 'Splitz Experiment Evaluation Failed.',
        moduleName: '@shellSplitzMiddleware',
        context: {
          currentUserMid,
        },
      });

      throw new ShellError({
        moduleName: '@shellSplitzMiddleware',
        message: 'Failed to evaluate experiments.',
        statusCode: 500,
        context: {
          path: splitzConfig.apiBaseUrl,
          dashboardBackendRequestId: undefined,
        },
      });
    }
  };
