interface UseEnvResponse {
  isProduction: boolean;
  cdnBaseUrl: string;
}
declare global {
  interface Window {
    APP_ENV: string;
    cdnBaseUrl: string;
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
