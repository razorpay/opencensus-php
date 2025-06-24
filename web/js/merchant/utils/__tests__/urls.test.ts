import { getCountryOnboardingUrl } from '../urls';
import { CountryCodeType } from '@razorpay/i18nify-js';

describe('getCountryOnboardingUrl', () => {
  const originalWindow = { ...window };

  beforeEach(() => {
    window.EASY_ONBOARDING_URL = 'https://easy-onboarding.example.com';
    window.EASY_DASHBOARD_CURLEC_URL = 'https://curlec.example.com';
    window.EASY_DASHBOARD_SG_URL = 'https://sg-dashboard.example.com';
    window.EASY_DASHBOARD_US_URL = 'https://us-dashboard.example.com';
  });

  afterEach(() => {
    Object.assign(window, originalWindow);
  });

  it('should return EASY_ONBOARDING_URL for country code IN', () => {
    const result = getCountryOnboardingUrl('IN' as CountryCodeType);
    expect(result).toBe('https://easy-onboarding.example.com');
  });

  it('should return EASY_DASHBOARD_CURLEC_URL for country code MY', () => {
    const result = getCountryOnboardingUrl('MY' as CountryCodeType);
    expect(result).toBe('https://curlec.example.com');
  });

  it('should return EASY_DASHBOARD_SG_URL for country code SG', () => {
    const result = getCountryOnboardingUrl('SG' as CountryCodeType);
    expect(result).toBe('https://sg-dashboard.example.com');
  });

  it('should return EASY_DASHBOARD_US_URL for country code US', () => {
    const result = getCountryOnboardingUrl('US' as CountryCodeType);
    expect(result).toBe('https://us-dashboard.example.com');
  });

  it('should return EASY_ONBOARDING_URL for an unsupported country code', () => {
    const result = getCountryOnboardingUrl('XX' as CountryCodeType);
    expect(result).toBe('https://easy-onboarding.example.com');
  });

  it('should return EASY_ONBOARDING_URL when country code is undefined', () => {
    const result = getCountryOnboardingUrl((undefined as unknown) as CountryCodeType);
    expect(result).toBe('https://easy-onboarding.example.com');
  });
});
