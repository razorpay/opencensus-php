import React from 'react';
import { render, server, screen, userEvent, waitFor } from 'test-utils';
import { waitForElementToBeRemoved } from '@testing-library/react';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import BankAccountDetails from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/BankAccountDetails';
import {
  defaultState,
  fetchBankAccountChangeStatusFailure,
  fetchBankAccountChangeStatusSuccess,
  fetchBankAccountSuccess,
  HOLD_CASES_FIXTURES,
  mockFetchSettlementConfig,
  mockUser,
} from './mocks/fixtures';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';

jest.mock('common/ui/TriggerOnQueryParamMatch');

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
const openModalSpy = jest.spyOn(ModalActions, 'openModal');

const mockBannerComponent = jest.fn();
const mockWorkflowStatusComponent = jest.fn();

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner',
  () => (props) => {
    mockBannerComponent(props);
    return <div>Banner Component</div>;
  },
);

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/WorkflowStatus',
  () => (props) => {
    mockWorkflowStatusComponent(props);
    return <div>WorkflowStatus Component</div>;
  },
);

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/AccountSection/AccountSection',
  () => ({
    __esModule: true,
    default: ({ handleAction }) => (
      <>
        <div>Bank Account Section</div>
        <button onClick={() => handleAction({ flowType: 'update' })}>
          Change Bank Account CTA
        </button>
      </>
    ),
  }),
);

const mockCriticalFlow = jest.fn();
jest.mock('common/ui/TwoFactorVerification/TwoFactorVerificationContext', () => ({
  useTwoFactorVerificationContext: () => ({
    criticalFlow: mockCriticalFlow,
  }),
}));

const renderApp = ({ initialState = defaultState } = {}) => {
  return render(<BankAccountDetails />, {
    initialState,
  });
};

const clickOnChangeBankAccountCTA = async () => {
  await waitForElementToBeRemoved(screen.queryAllByTestId('bankAccount-shimmer'));
  const changeBankAccountCTA = screen.getByRole('button', {
    name: 'Change Bank Account CTA',
  });
  expect(changeBankAccountCTA).toBeInTheDocument();
  await userEvent.click(changeBankAccountCTA);
};

describe('BankAccountDetailsV2 - BankAccountDetails Landing Page', () => {
  beforeEach(() => {
    window.rzp_user = mockUser;
    TriggerOnQueryParamMatch.mockImplementation(() => {
      return null;
    });
    mockCriticalFlow.mockImplementation(({ onUserTwoFaVerified }) => {
      onUserTwoFaVerified();
    });
    showNotificationSpy.mockClear();
    openModalSpy.mockClear();
    server.use(
      fetchBankAccountSuccess(),
      mockFetchSettlementConfig(),
      fetchBankAccountChangeStatusSuccess(),
    );
  });

  test('should open modal on 2fa success and flow type is update', async () => {
    renderApp();
    await clickOnChangeBankAccountCTA();
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });

  test('should show notification error on 2fa failure', async () => {
    mockCriticalFlow.mockImplementation(({ onFlowTermination }) => {
      onFlowTermination();
    });
    renderApp();
    await clickOnChangeBankAccountCTA();
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: 'Something went wrong, Please try again later',
      });
    });
  });

  test('should show notification error when unable to fetch bank account change status', async () => {
    server.use(fetchBankAccountChangeStatusFailure());
    renderApp();
    await waitForElementToBeRemoved(screen.queryAllByTestId('bankAccount-shimmer'));
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: 'Unable to fetch Bank Information',
      });
    });
  });

  test('should trigger bank account update flow on query param match', async () => {
    TriggerOnQueryParamMatch.mockImplementation(({ queryParamsMapping }) => {
      queryParamsMapping[0].trigger();
      return null;
    });

    renderApp();
    await waitForElementToBeRemoved(screen.queryAllByTestId('bankAccount-shimmer'));
    await waitFor(() => {
      expect(mockCriticalFlow).toHaveBeenCalled();
    });
  });

  describe('WorkflowStatus and Banner Hold type', () => {
    beforeEach(() => {
      mockBannerComponent.mockReset();
      mockWorkflowStatusComponent.mockReset();
    });
    test('should pass isSettlementOnHold as false for Activated Merchant', async () => {
      renderApp({
        initialState: HOLD_CASES_FIXTURES.ACTIVATED,
      });
      server.use(mockFetchSettlementConfig(false));
      await waitForElementToBeRemoved(screen.queryAllByTestId('bankAccount-shimmer'));
      await waitFor(() => {
        expect(mockWorkflowStatusComponent).toHaveBeenCalledWith({
          isSettlementOnHold: false || undefined,
        });
      });
    });

    test('should pass isSettlementOnHold as true and banner type complete_kyc for Not activated in L2 state merchant', async () => {
      renderApp({
        initialState: HOLD_CASES_FIXTURES.NON_ACTIVATED_WITH_L2,
      });
      await waitForElementToBeRemoved(screen.queryAllByTestId('bankAccount-shimmer'));
      await waitFor(() => {
        expect(mockBannerComponent).toHaveBeenCalledWith({ type: 'complete_kyc' });
      });
      await waitFor(() => {
        expect(mockWorkflowStatusComponent).toHaveBeenCalledWith({ isSettlementOnHold: true });
      });
    });

    test('should pass isSettlementOnHold as true and banner type complete_kyc for Not activated in L1 state merchant', async () => {
      renderApp({
        initialState: HOLD_CASES_FIXTURES.NON_ACTIVATED_WITH_L1,
      });
      await waitForElementToBeRemoved(screen.queryAllByTestId('bankAccount-shimmer'));
      await waitFor(() => {
        expect(mockBannerComponent).toHaveBeenCalledWith({ type: 'complete_kyc' });
      });
      await waitFor(() => {
        expect(mockWorkflowStatusComponent).toHaveBeenCalledWith({ isSettlementOnHold: true });
      });
    });

    test('should pass isSettlementOnHold as true and banner type risk_foh for FOH merchant', async () => {
      render(<BankAccountDetails />, {
        initialState: HOLD_CASES_FIXTURES.FOH,
      });
      await waitForElementToBeRemoved(screen.queryAllByTestId('bankAccount-shimmer'));
      await waitFor(() => {
        expect(mockBannerComponent).toHaveBeenCalledWith({ type: 'risk_foh' });
      });
      await waitFor(() => {
        expect(mockWorkflowStatusComponent).toHaveBeenCalledWith({ isSettlementOnHold: true });
      });
    });

    test('should pass isSettlementOnHold as true and banner type soh for DOH merchant', async () => {
      render(<BankAccountDetails />, {
        initialState: HOLD_CASES_FIXTURES.SOH,
      });
      await waitForElementToBeRemoved(screen.queryAllByTestId('bankAccount-shimmer'));
      await waitFor(() => {
        expect(mockBannerComponent).toHaveBeenCalledWith({ type: 'soh' });
      });
      await waitFor(() => {
        expect(mockWorkflowStatusComponent).toHaveBeenCalledWith({ isSettlementOnHold: true });
      });
    });
  });
});
