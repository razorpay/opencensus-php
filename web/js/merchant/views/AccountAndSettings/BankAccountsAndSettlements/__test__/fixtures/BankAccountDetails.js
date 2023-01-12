import BankAccountDetails from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetails';
import { render, server } from 'test-utils';
import {
  fetchBankAccountSuccess,
  fetchSettlementAmountSuccess,
  fetchBankAccountChangeStatusSuccess,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/__test__/fixtures/handlers';
import * as context from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import * as bankAccountDetailsChangeStepsUtils from 'merchant/views/Account/Profile/components/BankAccountDetailsChangeSteps/utils';

jest.mock(
  'merchant/views/Account/Profile/components/BankAccountDetailsChange',
  () => ({ onSave }) => (
    <div>
      BankAccountDetailsChange{' '}
      <button type="button" onClick={onSave}>
        Submit & Verify
      </button>
    </div>
  ),
);

jest.mock('merchant/views/Account/Profile/components/BankAccountDetails', () => ({
  __esModule: true,
  default: ({ onChangeBankAccountDetails, isBankAccountChangeAllowed }) => (
    <div>
      BankAccountDetails{' '}
      {isBankAccountChangeAllowed && (
        <button type="button" onClick={onChangeBankAccountDetails}>
          Change Bank Account
        </button>
      )}
    </div>
  ),
}));

export const trackBankAccountDetailsChangeSpy = jest.spyOn(
  bankAccountDetailsChangeStepsUtils,
  'trackBankAccountDetailsChange',
);

export const renderApp = ({ user } = {}) => {
  return render(<BankAccountDetails />, {
    showModal: true,
    initialState: {
      session: {
        user: {
          isAdminOrOwner: true,
          bankAccountAutoUpdateOrWorkflow: () => true,
          ...user,
        },
      },
    },
  });
};

beforeAll(() => {
  window.rzp_user = {};
  jest.spyOn(context, 'useTwoFactorVerificationContext').mockImplementation(() => {
    return {
      criticalFlow: ({ onUserTwoFaVerified }) => {
        onUserTwoFaVerified();
      },
    };
  });
});

beforeEach(() => {
  server.use(
    fetchBankAccountSuccess(),
    fetchSettlementAmountSuccess(),
    fetchBankAccountChangeStatusSuccess(),
  );
});
