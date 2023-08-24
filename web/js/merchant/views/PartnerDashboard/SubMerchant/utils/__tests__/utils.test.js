import { renderHook } from 'test-utils';
import {
  numberDifferentiation,
  getInitialState,
} from 'merchant/views/PartnerDashboard/SubMerchant/utils';
import usePartnerPageNPS from 'merchant/views/PartnerDashboard/SubMerchant/utils/usePartnerPageNPS';
import {
  defaultAddMerchantState,
  addMerchantProps,
} from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import { PRODUCT_TYPE, ADD_MODE } from 'merchant/views/PartnerDashboard/constants';
import User from 'merchant/models/User';
import store from 'merchant/store';

describe('Number Differentiation', () => {
  test('should return short form of Lacks and Crores', () => {
    expect(numberDifferentiation(1500000)).toStrictEqual('15.00 L');
    expect(numberDifferentiation(25000000)).toStrictEqual('2.50 Cr');
  });
});

describe('InitialState for AddMerchant', () => {
  test('should return initial state based on props passed', () => {
    expect(getInitialState(addMerchantProps)).toStrictEqual({
      ...defaultAddMerchantState,
      step: 2,
      merchantType: PRODUCT_TYPE.CAPITAL,
      addMode: ADD_MODE.bulk,
    });

    expect(getInitialState({ ...addMerchantProps, addType: PRODUCT_TYPE.X })).toStrictEqual({
      ...defaultAddMerchantState,
      step: 2,
      merchantType: PRODUCT_TYPE.X,
    });

    expect(getInitialState({ ...addMerchantProps, addType: PRODUCT_TYPE.PG })).toStrictEqual({
      ...defaultAddMerchantState,
    });

    expect(
      getInitialState({
        ...addMerchantProps,
        addType: PRODUCT_TYPE.PG,
      }),
    ).toStrictEqual({
      ...defaultAddMerchantState,
      merchantType: PRODUCT_TYPE.PG,
    });
  });
});

describe('usePartnerPageNPS', () => {
  const initialState = { session: { user: new User({ email: 'email.com' }) } };
  const stateSpy = jest.spyOn(store, 'getState');
  test('hook renders without any errors', () => {
    stateSpy.mockReturnValue(initialState);
    const { result } = renderHook(() => usePartnerPageNPS('surveyID'), {
      initialState,
    });
    expect(result.current).toBe(undefined);
    stateSpy.mockClear();
  });
});
