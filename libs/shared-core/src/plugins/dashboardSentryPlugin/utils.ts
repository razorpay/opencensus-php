import { DASHBOARD_FEDERATED_MODULES } from '../../constants';
import { DASHBOARD_FEDERATED_MODULE_CONFIGS } from '../../configs';

export const getSentryMeta = ({
  sentryProject,
  moduleName,
  env: { STAGE, APP_VERSION },
}: {
  sentryProject: string;
  moduleName: DASHBOARD_FEDERATED_MODULES;
  env: { STAGE?: string; APP_VERSION?: string };
}) => {
  const targetModuleConfig = DASHBOARD_FEDERATED_MODULE_CONFIGS[moduleName];
  const sentryDist = `${sentryProject}@${targetModuleConfig.buildType}`;
  const sentryAppVersion = `${sentryDist}.${STAGE}.${APP_VERSION}`;
  return {
    sentryAppVersion,
    sentryDist,
  };
};
