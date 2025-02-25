interface UseEnvResponse {
  isProduction: boolean;
  cdnBaseUrl: string;
  cdnDashboardAssetsUrl: string;
}

const useEnv = (): UseEnvResponse => {
  const isProduction = window.APP_ENV === 'production';
  const cdnBaseUrl = window.cdnBaseUrl;
  const cdnDashboardAssetsUrl = window.cdnDashboardAssetsUrl;
  return {
    isProduction,
    cdnBaseUrl,
    cdnDashboardAssetsUrl,
  };
};

export default useEnv;
