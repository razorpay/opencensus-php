import useEnv from '../useEnv';

describe('useEnv', () => {
  it('should return isProduction as true when APP_ENV is production', () => {
    window.APP_ENV = 'production';
    const { isProduction } = useEnv();
    expect(isProduction).toBe(true);
  });

  it('should return isProduction as false when APP_ENV is not production', () => {
    window.APP_ENV = 'development';
    const { isProduction } = useEnv();
    expect(isProduction).toBe(false);
  });
});
