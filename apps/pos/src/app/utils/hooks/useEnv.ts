interface UseEnvResponse {
  isProduction: boolean;
}
declare global {
  interface Window {
    APP_ENV: string;
  }
}

const useEnv = (): UseEnvResponse => {
  const isProduction = window.APP_ENV === 'production';
  return {
    isProduction,
  };
};

export default useEnv;
