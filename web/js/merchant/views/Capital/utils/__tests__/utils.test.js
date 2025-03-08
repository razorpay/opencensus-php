import User from 'merchant/models/User';
import store, { storeWithInitialState } from 'merchant/store';
import {
  canViewCashAdvanceProduct,
  canViewLOCEMIProduct,
  isLOCEMIProduct,
  isCashAdvanceProductActive,
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
    expect(canViewCashAdvanceProduct(user)).toBe(false);
    // not eligible for cash advance
    user.features = [];
    expect(canViewCashAdvanceProduct(user)).toBe(false);
    // eligible for cash advance
    user.features = [{ feature: 'cash_on_card' }];
    expect(canViewCashAdvanceProduct(user)).toBe(true);
    // active
    user.features = [{ feature: 'loc' }, { feature: 'withdraw_loc' }];
    expect(canViewCashAdvanceProduct(user)).toBe(true);
  });

  test('canViewLOCEMIProduct', () => {
    const { user } = getUserInstance();
    user.features = [{ feature: 'withdraw_loc' }];
    expect(canViewLOCEMIProduct(user)).toBe(false);
    user.features = [{ feature: 'loc_emi' }];
    expect(canViewLOCEMIProduct(user)).toBe(false);
    user.features = [{ feature: 'loc_emi' }, { feature: 'withdraw_loc' }];
    expect(canViewLOCEMIProduct(user)).toBe(true);
    // not eligible whe has cash advance
    user.features = [{ feature: 'loc' }];
    expect(canViewLOCEMIProduct(user)).toBe(false);
    user.features = [{ feature: 'cash_on_card' }];
    expect(canViewLOCEMIProduct(user)).toBe(false);
    user.features = [{ feature: 'loc' }, { feature: 'withdraw_loc' }];
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
});
