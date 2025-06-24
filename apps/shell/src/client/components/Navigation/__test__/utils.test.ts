import { matchPath } from 'react-router-dom';
import { isProductPathActive, getActiveProductAlias } from '../utils';
import { PRODUCT_PATH_MAP, PRODUCT_ALIAS_MAP } from '../constants';
import { isOneHomeExperimentEnabled, isCompanyRegistrationExperimentEnabled } from '../../utils';

// Mock server utils to prevent env.ts import chain
jest.mock('../../../../server/utils', () => ({
  isBrowser: jest.fn(() => true),
}));

jest.mock('../constants', () => ({
  PRODUCT_ALIAS_MAP: {
    PAYMENTS: 'payments_top_navigation_item',
    BANKING: 'banking_top_navigation_item',
    PAYROLL: 'payroll_top_navigation_item',
    RIZE: 'rize_top_navigation_item',
    PARTNERS: 'partners_top_navigation_item',
    HOME: 'home_top_navigation_item',
    COMPANY_REGISTRATION: 'company_registration_top_navigation_item',
  },
  PRODUCT_PATH_MAP: {
    banking_top_navigation_item: '/banking/*',
    partners_top_navigation_item: '/partners/*',
    company_registration_top_navigation_item: '/company-registration/*',
    home_top_navigation_item: '/home',
  },
}));

// Extend Window interface for TypeScript
declare global {
  interface Window {
    IS_ONE_HOME_ENABLED?: boolean;
    IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED?: boolean;
  }
}

describe('Navigation utils', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // Set default window properties
    window.IS_ONE_HOME_ENABLED = true;
    window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;
  });

  afterEach(() => {
    // Clean up window properties
    delete window.IS_ONE_HOME_ENABLED;
    delete window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED;
  });

  describe('isProductPathActive', () => {
    test('OneHome Experiment Enabled - HOME product should be active', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'home_top_navigation_item',
        currentPath: '/home',
      });

      expect(result).toBe(true);
    });

    test('OneHome Experiment Disabled - HOME product should be inactive', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'home_top_navigation_item',
        currentPath: '/home',
      });

      expect(result).toBe(false);
    });

    test('Company Registration Experiment Enabled - COMPANY_REGISTRATION should be active', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'company_registration_top_navigation_item',
        currentPath: '/company-registration/step1',
      });

      expect(result).toBe(true);
    });

    test('Company Registration Experiment Disabled - COMPANY_REGISTRATION should be inactive', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = false;

      const result = isProductPathActive({
        productAlias: 'company_registration_top_navigation_item',
        currentPath: '/company-registration/step1',
      });

      expect(result).toBe(false);
    });

    test('Payments as default when no other product matches', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/dashboard',
      });

      expect(result).toBe(true);
    });

    test('Payments inactive when another product matches', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/banking/accounts',
      });

      expect(result).toBe(false);
    });

    test('Payments as default when OneHome disabled and no matches', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/some-random-path',
      });

      expect(result).toBe(true);
    });

    test('Payments as default when Company Registration disabled and no matches', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = false;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/unknown-route',
      });

      expect(result).toBe(true);
    });

    test('Payments as default when both experiments disabled and no matches', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = false;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/random-path',
      });

      expect(result).toBe(true);
    });

    test('Payments active when OneHome enabled and no other product matches', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/dashboard',
      });

      expect(result).toBe(true);
    });

    test('Payments inactive when OneHome enabled and home path matches', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/home',
      });

      expect(result).toBe(false);
    });

    test('Payments inactive when OneHome enabled and banking path matches', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/banking/accounts',
      });

      expect(result).toBe(false);
    });

    test('Payments active when OneHome disabled and no other product matches', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/dashboard',
      });

      expect(result).toBe(true);
    });

    test('Payments active when OneHome disabled and user is on home path', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/home',
      });

      expect(result).toBe(true);
    });

    test('Payments inactive when OneHome disabled but banking path matches', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/banking/dashboard',
      });

      expect(result).toBe(false);
    });

    test('Payments active when both experiments disabled and no other matches', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = false;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/dashboard',
      });

      expect(result).toBe(true);
    });

    test('Payments active when both experiments disabled and on company registration path', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = false;

      const result = isProductPathActive({
        productAlias: 'payments_top_navigation_item',
        currentPath: '/company-registration/step1',
      });

      expect(result).toBe(true);
    });

    test('Banking product active on banking path', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'banking_top_navigation_item',
        currentPath: '/banking/accounts',
      });

      expect(result).toBe(true);
    });

    test('Banking product inactive on non-banking path', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'banking_top_navigation_item',
        currentPath: '/dashboard',
      });

      expect(result).toBe(false);
    });

    test('Partners product active on partners path', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'partners_top_navigation_item',
        currentPath: '/partners/dashboard',
      });

      expect(result).toBe(true);
    });

    test('Partners product inactive on non-partners path', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'partners_top_navigation_item',
        currentPath: '/banking/accounts',
      });

      expect(result).toBe(false);
    });

    test('Invalid/Non-existent product alias', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'non_existent_product',
        currentPath: '/dashboard',
      });

      expect(result).toBe(false);
    });

    test('Empty product alias', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: '',
        currentPath: '/dashboard',
      });

      expect(result).toBe(false);
    });

    test('Empty current path', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'banking_top_navigation_item',
        currentPath: '',
      });

      expect(result).toBe(false);
    });

    test('Root path with banking product', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = isProductPathActive({
        productAlias: 'banking_top_navigation_item',
        currentPath: '/',
      });

      expect(result).toBe(false);
    });

    test('Banking product matches wildcard paths', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      // Test various banking sub-paths
      expect(
        isProductPathActive({
          productAlias: 'banking_top_navigation_item',
          currentPath: '/banking',
        }),
      ).toBe(true);

      expect(
        isProductPathActive({
          productAlias: 'banking_top_navigation_item',
          currentPath: '/banking/',
        }),
      ).toBe(true);

      expect(
        isProductPathActive({
          productAlias: 'banking_top_navigation_item',
          currentPath: '/banking/accounts/details',
        }),
      ).toBe(true);
    });

    test('Partners product matches wildcard paths', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      // Test various partners sub-paths
      expect(
        isProductPathActive({
          productAlias: 'partners_top_navigation_item',
          currentPath: '/partners',
        }),
      ).toBe(true);

      expect(
        isProductPathActive({
          productAlias: 'partners_top_navigation_item',
          currentPath: '/partners/',
        }),
      ).toBe(true);

      expect(
        isProductPathActive({
          productAlias: 'partners_top_navigation_item',
          currentPath: '/partners/submerchants/list',
        }),
      ).toBe(true);
    });

    test('Company registration product matches wildcard paths', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      // Test various company registration sub-paths
      expect(
        isProductPathActive({
          productAlias: 'company_registration_top_navigation_item',
          currentPath: '/company-registration',
        }),
      ).toBe(true);

      expect(
        isProductPathActive({
          productAlias: 'company_registration_top_navigation_item',
          currentPath: '/company-registration/',
        }),
      ).toBe(true);

      expect(
        isProductPathActive({
          productAlias: 'company_registration_top_navigation_item',
          currentPath: '/company-registration/step1/details',
        }),
      ).toBe(true);
    });

    test('Home product matches exact path only', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      // Should match exact path
      expect(
        isProductPathActive({
          productAlias: 'home_top_navigation_item',
          currentPath: '/home',
        }),
      ).toBe(true);

      // Should also match with trailing slash (react-router-dom behavior)
      expect(
        isProductPathActive({
          productAlias: 'home_top_navigation_item',
          currentPath: '/home/',
        }),
      ).toBe(true);

      // Should not match sub-paths (no wildcard)
      expect(
        isProductPathActive({
          productAlias: 'home_top_navigation_item',
          currentPath: '/home/dashboard',
        }),
      ).toBe(false);
    });

    test('Experiment functions work correctly with window properties', () => {
      // Test OneHome experiment function
      window.IS_ONE_HOME_ENABLED = true;
      expect(isOneHomeExperimentEnabled()).toBe(true);

      window.IS_ONE_HOME_ENABLED = false;
      expect(isOneHomeExperimentEnabled()).toBe(false);

      delete window.IS_ONE_HOME_ENABLED;
      expect(isOneHomeExperimentEnabled()).toBe(undefined);

      // Test Company Registration experiment function
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;
      expect(isCompanyRegistrationExperimentEnabled()).toBe(true);

      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = false;
      expect(isCompanyRegistrationExperimentEnabled()).toBe(false);

      delete window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED;
      expect(isCompanyRegistrationExperimentEnabled()).toBe(undefined);
    });
  });

  describe('getActiveProductAlias', () => {
    test('Returns first matching product alias', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = getActiveProductAlias('/banking/accounts');

      expect(result).toBe('banking_top_navigation_item');
    });

    test('Returns payments when no other product matches', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = getActiveProductAlias('/dashboard');

      expect(result).toBe('payments_top_navigation_item');
    });

    test('Returns home when OneHome enabled and on home path', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = getActiveProductAlias('/home');

      expect(result).toBe('home_top_navigation_item');
    });

    test('Returns company registration when enabled and on company registration path', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = getActiveProductAlias('/company-registration/step1');

      expect(result).toBe('company_registration_top_navigation_item');
    });

    test('Skips home when OneHome disabled', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = getActiveProductAlias('/home');

      expect(result).toBe('payments_top_navigation_item');
    });

    test('Skips company registration when experiment disabled', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = false;

      const result = getActiveProductAlias('/company-registration/step1');

      expect(result).toBe('payments_top_navigation_item');
    });

    test('Returns payments when OneHome disabled and on home path', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      const result = getActiveProductAlias('/home');

      expect(result).toBe('payments_top_navigation_item');
    });

    test('Returns payments when both experiments disabled and on company registration path', () => {
      window.IS_ONE_HOME_ENABLED = false;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = false;

      const result = getActiveProductAlias('/company-registration/step1');

      expect(result).toBe('payments_top_navigation_item');
    });

    test('Returns partners for partners paths', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      expect(getActiveProductAlias('/partners')).toBe('partners_top_navigation_item');
      expect(getActiveProductAlias('/partners/dashboard')).toBe('partners_top_navigation_item');
      expect(getActiveProductAlias('/partners/submerchants')).toBe('partners_top_navigation_item');
    });

    test('Returns banking for banking paths', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      expect(getActiveProductAlias('/banking')).toBe('banking_top_navigation_item');
      expect(getActiveProductAlias('/banking/accounts')).toBe('banking_top_navigation_item');
      expect(getActiveProductAlias('/banking/transactions')).toBe('banking_top_navigation_item');
    });

    test('Product priority order - first match wins', () => {
      window.IS_ONE_HOME_ENABLED = true;
      window.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED = true;

      // If somehow multiple products could match, the first one in PRODUCT_ALIAS_MAP order should win
      // This tests the iteration order in getActiveProductAlias
      const result = getActiveProductAlias('/banking/accounts');
      expect(result).toBe('banking_top_navigation_item');
    });
  });
});
