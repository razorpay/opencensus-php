import { CDN_DASHBOARD_ASSETS_URL, DASHBOARD_MICROAPPS, STAGE } from '@apps/shell/src/env';
import { Request, Response } from 'express';
import { shellFetch } from '@apps/shell/src/server/services/shellFetch';

type AppVersionsRouteFn = (req: Request, res: Response) => void;

// s
const fetchCommitId = async (appName: string, req: Request): Promise<string | null> => {
  const url = `${CDN_DASHBOARD_ASSETS_URL}/${
    STAGE === 'canary' ? 'dashboard-canary' : 'dashboard'
  }/core-bundles/${appName}/commit.txt`;
  req.shellLogger.info({
    message: `Fetching commit for ${appName}..`,
    moduleName: '@getAppVersions',
    context: {
      appName,
    },
  });
  try {
    const response = await shellFetch(url);

    if (!response.ok) {
      req.shellLogger.warn({
        message: `Failed to fetch commit for ${appName}..`,
        moduleName: '@getAppVersions',
        context: {
          appName,
        },
      });
      return null;
    }

    const commitId = await response.text();
    return commitId.trim();
  } catch (error) {
    req.shellLogger.error({
      message: `Failed executing fetch for commit of ${appName}..`,
      moduleName: '@getAppVersions',
      error,
      context: {
        appName,
      },
    });
    return null;
  }
};

export const getAppVersions: AppVersionsRouteFn = async (req, res): Promise<any> => {
  req.shellLogger.info({
    message: `Fetching app versions...`,
    moduleName: '@getAppVersions',
  });

  try {
    const appNames = [
      'payments-dashboard',
      'la-dashboard',
      'pokedex-dashboard',
      'newauth-dashboard',
      'tnc-dashboard',
      'razorx-dashboard',
    ];

    if (DASHBOARD_MICROAPPS) {
      try {
        appNames.push(...DASHBOARD_MICROAPPS.split(','));
      } catch (error) {
        req.shellLogger.error({
          sentry: true,
          error,
          message: `Failed to get microapps via env.`,
          moduleName: '@getAppVersions',
          context: {
            DASHBOARD_MICROAPPS,
          },
        });
      }
    }

    const results = await Promise.all(
      appNames.map(async (appName) => await fetchCommitId(appName, req)),
    );

    const commitMap = results.reduce((acc, current, currentIndex) => {
      if (current) {
        acc[appNames[currentIndex]] = current;
      }
      return acc;
    }, {} as Record<string, string>);

    return res.status(200).json({
      ...commitMap,
      'shell-server': __APP_VERSION__,
    });
  } catch (error) {
    req.shellLogger.error({
      message: `Failed to fetch commits..`,
      sentry: true,
      moduleName: '@getAppVersions',
      error,
    });
    return res.status(500).json({
      message: 'Failed to fetch commits',
    });
  }
};
