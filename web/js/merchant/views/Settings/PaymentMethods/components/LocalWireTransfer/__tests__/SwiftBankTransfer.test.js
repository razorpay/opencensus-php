import { titleCase } from 'common/utils/rzp-utils';
import * as b2bActions from 'merchant/reducers/b2bExports/actions';
import SwiftBankTransfer from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/SwiftBankTransfer';
import {
  getLeafListData,
  getAccounts,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/__tests__/mocks/fixtures';
import * as services from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services';
import {
  GREYED,
  ACTION_REQUIRED,
  ACTIVATED,
} from 'merchant/views/Settings/PaymentMethods/constants';
import * as modalActions from 'merchant_common/reducers/modals';
import { render, screen, userEvent } from 'test-utils';

jest.mock('merchant/reducers/b2bExports/actions', () => ({
  fetchB2bAccounts: jest.fn(() => (dispatch) => {
    return Promise.resolve(() =>
      dispatch({
        type: 'B2B_EXPORTS_FETCH_ACCOUNTS::SUCCESS',
        payload: { data: [] },
      }),
    );
  }),
  activateAccountSuccess: jest.fn((payload) => ({
    type: 'B2B_EXPORTS_ACTIVATE_ACCOUNTS::SUCCESS',
    payload,
  })),
  activateAccountError: jest.fn((payload) => ({
    type: 'B2B_EXPORTS_ACTIVATE_ACCOUNTS::ERROR',
    payload,
  })),
}));

jest.mock('merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services');

jest.mock('common/splitz', () => ({
  useSplitzService: jest.fn(() => ({
    abExperiments: {
      disableInternationalPaymentMethods: {
        variables: {
          result: 'off',
        },
      },
    },
  })),
}));

const renderComponent = (props = {}, initialState = {}) => {
  return render(<SwiftBankTransfer {...props} />, {
    initialState: {
      unlockIntlPaymentMethods: { showMorePaymentMethodsSection: false },
      ...initialState,
    },
  });
};

describe('When SwiftBankTransfer is shown for the first time', () => {
  const leafList = getLeafListData(GREYED, 'SWIFT');

  test('Component should render without breaking', () => {
    expect(renderComponent({ leafList })).toBeDefined();
  });

  test('Error should be visible when purpose code is not selected', () => {
    renderComponent({ leafList });

    //ACTION REQUIRED status should be visible
    expect(screen.getByText(titleCase(ACTION_REQUIRED.replace('_', ' ')))).toBeInTheDocument();

    //error message should be visible along with update purpose code button
    expect(
      screen.getByText('Purpose code is required for activating', { exact: false }),
    ).toBeInTheDocument();
    expect(screen.getByText('purpose code')).toBeInTheDocument();

    //request button should be hidden for instruments in the list
    expect(screen.queryByText('Request')).not.toBeInTheDocument();
  });

  test('Purpose code selection popup should open when clicked on update purpose code', async () => {
    const openModal = jest.spyOn(modalActions, 'openModal');
    renderComponent({ leafList });

    const updatePurposeCode = screen.getByText('purpose code');
    await userEvent.click(updatePurposeCode);

    expect(openModal).toHaveBeenCalledTimes(1);
  });

  test('Error should be visible when promoter pan is not present', () => {
    renderComponent(
      { leafList },
      {
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
      },
    );

    expect(
      screen.queryByText('Purpose code is required for activating', { exact: false }),
    ).not.toBeInTheDocument();

    expect(screen.getByText(titleCase(ACTION_REQUIRED.replace('_', ' ')))).toBeInTheDocument();
    expect(
      screen.getByText('Authorised Signatory PAN details are mandatory for activating', {
        exact: false,
      }),
    ).toBeInTheDocument();
  });

  test('Error should not be visible when purpose code exists for a merchant', () => {
    renderComponent(
      { leafList },
      {
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
        session: { user: { promoter_pan_name: 'sanchit' } },
      },
    );

    expect(
      screen.queryByText(titleCase(ACTION_REQUIRED.replace('_', ' '))),
    ).not.toBeInTheDocument();

    expect(
      screen.queryByText('Purpose code is required for activating', { exact: false }),
    ).not.toBeInTheDocument();
    expect(screen.getByText('Request')).toBeInTheDocument();
  });
});

describe('When all required info is available for SWIFT account creation', () => {
  const leafList = getLeafListData(GREYED, 'SWIFT');
  const openModal = jest.spyOn(modalActions, 'openModal');

  test('When accounts have not been created and request button is clicked', async () => {
    services.activateAccount.mockImplementation(() => (dispatch) => {
      return new Promise((resolve) => {
        dispatch(b2bActions.activateAccountSuccess({ type: 'intBankTransfer', response: null }));
        resolve({ success: true });
      });
    });

    renderComponent(
      { leafList },
      {
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
        session: { user: { promoter_pan_name: 'sanchit' } },
      },
    );
    expect(screen.getByText('Request')).toBeInTheDocument();

    const requestButton = screen.getByText('Request');
    await userEvent.click(requestButton);

    expect(openModal).toHaveBeenCalled();
  });

  test('should open modal when requested for USD virtual account', async () => {
    renderComponent(
      { leafList },
      {
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
        session: { user: { promoter_pan_name: 'sanchit' } },
        b2bExportsAccounts: {
          data: getAccounts(),
          isLoading: false,
        },
      },
    );

    const requestButton = screen.getByText('Request');
    await userEvent.click(requestButton);

    expect(openModal).toHaveBeenCalled();
  });

  test('should open modal if account creation failed with multiple errors', async () => {
    const errors = ['Something went wrong. Please try again later', 'Status 400'];

    services.activateAccount.mockImplementation(() => (dispatch) => {
      return new Promise((_, reject) => {
        dispatch(b2bActions.activateAccountError({ type: 'intBankTransfer', response: null }));
        reject({ success: false, errors });
      });
    });

    renderComponent(
      { leafList },
      {
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
        session: { user: { promoter_pan_name: 'sanchit' } },
      },
    );
    expect(screen.getByText('Request')).toBeInTheDocument();

    const requestButton = screen.getByText('Request');
    await userEvent.click(requestButton);

    expect(openModal).toHaveBeenCalled();
  });

  test('should open modal if account creation failed with one error', async () => {
    const error = 'Something went wrong. Please try again later';

    services.activateAccount.mockImplementation(() => (dispatch) => {
      return new Promise((_, reject) => {
        dispatch(b2bActions.activateAccountError({ type: 'intBankTransfer', response: null }));
        reject({ success: false, errors: error });
      });
    });

    renderComponent(
      { leafList },
      {
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
        session: { user: { promoter_pan_name: 'sanchit' } },
      },
    );
    expect(screen.getByText('Request')).toBeInTheDocument();

    const requestButton = screen.getByText('Request');
    await userEvent.click(requestButton);

    expect(openModal).toHaveBeenCalled();
  });
});

describe('Tests for showMorePaymentMethodsSection', () => {
  test('Should show request button in disabled state if showMorePaymentMethodsSection is true and account is not created', () => {
    const leafList = getLeafListData(GREYED, 'SWIFT');
    renderComponent(
      { leafList },
      {
        unlockIntlPaymentMethods: {
          showMorePaymentMethodsSection: true,
        },
      },
    );

    expect(screen.getByRole('button', { name: 'Request' })).toBeDisabled();
  });

  test('Should show request button in enabled state if showMorePaymentMethodsSection is false and account is not created', () => {
    const leafList = getLeafListData(GREYED, 'SWIFT');
    renderComponent(
      { leafList },
      {
        unlockIntlPaymentMethods: {
          showMorePaymentMethodsSection: false,
        },
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
        session: { user: { promoter_pan_name: 'sanchit' } },
      },
    );

    expect(screen.getByRole('button', { name: 'Request' })).not.toBeDisabled();
  });

  test('should show activated if is accounts are activated and showMorePaymentMethods is true', () => {
    renderComponent(
      { leafList: getLeafListData(ACTIVATED, 'SWIFT') },
      {
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
        session: { user: { promoter_pan_name: 'sanchit' } },
        unlockIntlPaymentMethods: {
          showMorePaymentMethodsSection: true,
        },
      },
    );

    expect(screen.getByText('Activated')).toBeInTheDocument();
  });
});
