import { useSplitzService } from 'common/splitz';
import { titleCase } from 'common/utils/rzp-utils';
import * as b2bActions from 'merchant/reducers/b2bExports/actions';
import * as purposeCodeActions from 'merchant/reducers/profile';
import LocalWireTransfer from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer';
import {
  getLeafListData,
  getAccounts,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/__tests__/mocks/fixtures';
import {
  GREYED,
  ACTION_REQUIRED,
  ACTIVATED,
} from 'merchant/views/Settings/PaymentMethods/constants';
import * as modalActions from 'merchant_common/reducers/modals';
import * as notifications from 'merchant_common/reducers/notifications';
import { render, screen, waitFor, userEvent } from 'test-utils';

jest.mock('merchant/reducers/b2bExports/actions', () => ({
  fetchB2bAccounts: jest.fn(() => (dispatch) => {
    return Promise.resolve(() =>
      dispatch({
        type: 'B2B_EXPORTS_FETCH_ACCOUNTS::SUCCESS',
        payload: { data: [] },
      }),
    );
  }),
}));

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
  return render(<LocalWireTransfer {...props} />, {
    initialState: {
      unlockIntlPaymentMethods: { showMorePaymentMethodsSection: false },
      ...initialState,
    },
  });
};

describe('When LocalWireTransfer is shown for the first time', () => {
  const fetchPurposeCode = jest.spyOn(purposeCodeActions, 'fetchPurposeCode');
  const leafList = getLeafListData(GREYED);

  beforeAll(() => {
    fetchPurposeCode.mockClear();
  });

  test('Component should render without breaking', () => {
    expect(renderComponent({ leafList })).toBeDefined();
  });

  test('fetchPurposeCode and fetchB2BAccounts functions should be called', () => {
    renderComponent({ leafList });

    expect(b2bActions.fetchB2bAccounts).toHaveBeenCalled();
    expect(fetchPurposeCode).toHaveBeenCalled();
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

  test('should show request button if disableInternationalPaymentMethods exp is enabled', () => {
    useSplitzService.mockImplementation(() => ({
      abExperiments: {
        disableInternationalPaymentMethods: {
          variables: {
            result: 'on',
          },
        },
      },
    }));

    renderComponent(
      { leafList },
      {
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
        session: { user: { promoter_pan_name: 'sanchit' } },
      },
    );

    expect(screen.getByText('Request')).toBeInTheDocument();
  });

  test('should show activated if is accounts are activated and disableInternationalPaymentMethods exp is enabled', () => {
    useSplitzService.mockImplementation(() => ({
      abExperiments: {
        disableInternationalPaymentMethods: {
          variables: {
            result: 'on',
          },
        },
      },
    }));

    renderComponent(
      { leafList: getLeafListData(ACTIVATED) },
      {
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
        session: { user: { promoter_pan_name: 'sanchit' } },
      },
    );

    expect(screen.getByText('Activated')).toBeInTheDocument();
  });
});

describe('When all required info is available for ACH account creation', () => {
  const leafList = getLeafListData(GREYED);

  test('Error should be shown when fetchB2baccounts fail', async () => {
    const showNotification = jest.spyOn(notifications, 'showNotification');

    b2bActions.fetchB2bAccounts.mockImplementation(() => (dispatch) => {
      return dispatch({
        type: 'B2B_EXPORTS_FETCH_ACCOUNTS::ERROR',
        payload: {
          errors: ['dummy error'],
        },
      });
    });

    renderComponent(
      { leafList },
      {
        profile: { fircDetails: { data: { purpose_code: '12121' } } },
        session: { user: { promoter_pan_name: 'sanchit' } },
      },
    );

    await waitFor(() => expect(showNotification).toHaveBeenCalled());
    expect(showNotification).toHaveBeenCalledWith({
      type: 'error',
      message: 'dummy error',
    });
  });
});

describe('When purpose code provided is not valid for ACH', () => {
  const fetchPurposeCode = jest.spyOn(purposeCodeActions, 'fetchPurposeCode');
  const openModal = jest.spyOn(modalActions, 'openModal');
  const leafList = getLeafListData(GREYED);

  test('Should open PurposeCodeIneligible Popup on render', () => {
    fetchPurposeCode.mockImplementation(() => (dispatch) => {
      return dispatch({
        type: 'GET_FIRC_DETAILS::SUCCESS',
        payload: {
          data: {
            merchants: [
              {
                purpose_code: '12121',
              },
            ],
          },
        },
      });
    });

    renderComponent(
      { leafList },
      {
        session: { user: { promoter_pan_name: 'sanchit' } },
        b2bExportsAccounts: {
          isIneligiblePurposeCodeModalOpen: true,
          data: getAccounts(),
        },
      },
    );

    expect(openModal).toHaveBeenCalledWith({
      size: 'medium',
      component: expect.any(Object),
    });
  });
});

describe('Tests for showMorePaymentMethodsSection', () => {
  test('Should show request button in disabled state if showMorePaymentMethodsSection is true and account is not created', () => {
    const leafList = getLeafListData(GREYED);
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
    const leafList = getLeafListData(GREYED);
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
      { leafList: getLeafListData(ACTIVATED) },
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
