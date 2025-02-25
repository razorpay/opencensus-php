import { CROSS_REPO_ONBOARDED_MICROAPPS } from '../config';

export const getRuntimeRemoteUrl = (namespace: string) => {
  // @ts-ignore
  if (!CROSS_REPO_ONBOARDED_MICROAPPS?.[namespace]) {
    throw new Error(`[shell] Namespace ${namespace} not onboarded.`);
  }
  const config =
    CROSS_REPO_ONBOARDED_MICROAPPS[namespace as keyof typeof CROSS_REPO_ONBOARDED_MICROAPPS];

  const getCdnUrl = () => {
    switch (__STAGE__) {
      case 'development':
        return config.developmentCdnUrl;
      case 'devstack':
        return config.devstackCdnUrl;
      case 'production':
      default:
        return config.prodCdnUrl;
    }
  };

  return `${getCdnUrl()}/${config.federatedEntry.remoteEntry}`;
};
