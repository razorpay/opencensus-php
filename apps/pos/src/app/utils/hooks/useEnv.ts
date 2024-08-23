interface UseEnvResponse {
  isProduction: boolean;
  cdnBaseUrl: string;
}
declare global {
  interface Window {
    APP_ENV: string;
    cdnBaseUrl: string;
    INSTANCE_TYPE: 'production' | 'canary' | '';
  }
}

const useEnv = (): UseEnvResponse => {
  const isProduction = window.APP_ENV === 'production';
  const cdnBaseUrl = window.cdnBaseUrl;
  return {
    isProduction,
    cdnBaseUrl,
  };
};

export default useEnv;
