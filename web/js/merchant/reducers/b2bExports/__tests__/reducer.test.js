import { storeWithInitialState } from 'merchant/store';
import { closePurposeCodeIneligibleModal } from 'merchant/reducers/b2bExports/actions';
import { INITIAL_STATE } from 'merchant/reducers/b2bExports/__tests__/mocks/fixtures';

const store = storeWithInitialState({
  b2bExportsAccounts: INITIAL_STATE,
});

describe('Test b2bExportsAccounts reducer', () => {
  test('should return the initial state', () => {
    expect(store.getState()).toMatchObject({
      b2bExportsAccounts: INITIAL_STATE,
    });
  });

  test('should handle B2B_EXPORTS_FETCH_ACCOUNTS::PENDING', () => {
    store.dispatch({
      type: 'B2B_EXPORTS_FETCH_ACCOUNTS::PENDING',
    });

    expect(store.getState().b2bExportsAccounts).toMatchObject({
      isLoading: true,
    });
  });

  test('should handle B2B_EXPORTS_FETCH_ACCOUNTS::SUCCESS', () => {
    store.dispatch({
      type: 'B2B_EXPORTS_FETCH_ACCOUNTS::SUCCESS',
      payload: {
        data: {
          accounts: [],
        },
      },
    });

    expect(store.getState().b2bExportsAccounts).toMatchObject({
      isLoading: false,
      data: [],
      accountsDeactivated: false,
    });

    store.dispatch({
      type: 'B2B_EXPORTS_FETCH_ACCOUNTS::SUCCESS',
      payload: {},
    });

    expect(store.getState().b2bExportsAccounts).toMatchObject({
      isLoading: false,
      data: [],
      accountsDeactivated: false,
    });
  });

  test('should handle B2B_EXPORTS_FETCH_ACCOUNTS::ERROR', () => {
    store.dispatch({
      type: 'B2B_EXPORTS_FETCH_ACCOUNTS::ERROR',
      payload: {
        errors: ['purpose code is not eligible'],
      },
    });

    expect(store.getState().b2bExportsAccounts).toMatchObject({
      isLoading: false,
      error: 'purpose code is not eligible',
      isIneligiblePurposeCodeModalOpen: true,
    });

    store.dispatch({
      type: 'B2B_EXPORTS_FETCH_ACCOUNTS::ERROR',
      payload: {},
    });

    expect(store.getState().b2bExportsAccounts).toMatchObject({
      isLoading: false,
      error: [],
      isIneligiblePurposeCodeModalOpen: false,
    });
  });

  test('should handle B2B_CLOSE_PURPOSE_CODE_INELIGIBLE_MODAL', () => {
    store.dispatch(closePurposeCodeIneligibleModal());

    expect(store.getState().b2bExportsAccounts).toMatchObject({
      isIneligiblePurposeCodeModalOpen: false,
    });
  });
});
