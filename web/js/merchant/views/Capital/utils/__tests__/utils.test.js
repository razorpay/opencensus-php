import User from 'merchant/models/User';
import store, { storeWithInitialState } from 'merchant/store';
import {
  canViewCashAdvanceProduct,
  canViewLOCEMIProduct,
  isLOCEMIProduct,
  isCashAdvanceProductActive,
  canViewLoans,
} from 'merchant/views/Capital/utils/index';

const getUserInstance = ({
  features = [],
  role = 'admin',
  country_code = 'IN',
  custom_code = 'rzp',
} = {}) => {
  const state = storeWithInitialState({
    session: {
      user: new User({
        current: 'mid',
        merchants: {
          mid: {
            role,
          },
        },
        features: [...features],
        merchant: {
          country_code,
        },
      }),
      org: {
        custom_code,
      },
    },
  }).getState();
  const user = state.session.user;
  store.getState().session.org = { custom_code };
  return {
    session: store.getState().session,
    user,
  };
};

describe('capital/utils', () => {
  test('canViewCashAdvanceProduct', () => {
    const { user } = getUserInstance({
      features: [
        {
          feature: 'loc',
        },
      ],
    });
    // eligible for cash advance
    expect(canViewCashAdvanceProduct(user)).toBe(true);
    // not eligible for cash advance
    user.features = [];
    expect(canViewCashAdvanceProduct(user)).toBe(false);
    // eligible for cash advance
    user.features = [{ feature: 'cash_on_card' }];
    expect(canViewCashAdvanceProduct(user)).toBe(true);
  });

  test('canViewLOCEMIProduct', () => {
    const { user } = getUserInstance();
    // eligible for loc emi
    user.features = [{ feature: 'withdraw_loc' }];
    expect(canViewLOCEMIProduct(user)).toBe(true);
    user.features = [{ feature: 'loc_emi' }];
    expect(canViewLOCEMIProduct(user)).toBe(true);
    // not eligible whe has cash advance
    user.features = [{ feature: 'loc' }];
    expect(canViewLOCEMIProduct(user)).toBe(false);
    user.features = [{ feature: 'cash_on_card' }];
    expect(canViewLOCEMIProduct(user)).toBe(false);
  });

  test('isLOCEMIProduct', () => {
    expect(isLOCEMIProduct('LOC')).toBe(false);
    expect(isLOCEMIProduct('LOC_EMI')).toBe(true);
  });

  test('isCashAdvanceProductActive', () => {
    const { user } = getUserInstance();
    user.features = [{ feature: 'loc' }];
    expect(isCashAdvanceProductActive(user)).toBe(false);
    user.features = [{ feature: 'cash_on_card' }];
    expect(isCashAdvanceProductActive(user)).toBe(true);
    user.features = [];
    expect(isCashAdvanceProductActive(user)).toBe(false);
    user.features = [{ feature: 'cash_on_card' }, { feature: 'loc' }];
    expect(isCashAdvanceProductActive(user)).toBe(true);
    user.features = [{ feature: 'withdraw_loc' }, { feature: 'loc' }];
    expect(isCashAdvanceProductActive(user)).toBe(true);
  });

  test('Allowed Roles for canViewLOCEMIProduct/canViewCashAdvanceProduct', () => {
    const { user } = getUserInstance({ role: 'operations' });
    expect(canViewCashAdvanceProduct(user)).toBe(false);
    expect(canViewLOCEMIProduct(user)).toBe(false);
    user.features = [{ feature: 'loc' }];
    expect(canViewLOCEMIProduct(user)).toBe(false);
  });

  describe('canViewLoans', () => {
    it('should hava access for rzp org, IN region, admin role and non active loc merchants', () => {
      const { user } = getUserInstance();
      expect(canViewLoans(user)).toBe(true);
    });
    it('should not have access for active loc merchants', () => {
      const { user } = getUserInstance({ features: [{ feature: 'withdraw_loc' }] });
      expect(canViewLoans(user)).toBe(false);
    });
    it('should not have access for finance role', () => {
      const { user } = getUserInstance({ role: 'finance' });
      expect(canViewLoans(user)).toBe(false);
    });
    it('should not have access for non rzp org', () => {
      const { user } = getUserInstance({ custom_code: 'curlec' });
      expect(canViewLoans(user)).toBe(false);
    });
    it('should not have access for non IN region', () => {
      const { user } = getUserInstance({ country_code: 'SG' });
      expect(canViewLoans(user)).toBe(false);
    });
  });
});
