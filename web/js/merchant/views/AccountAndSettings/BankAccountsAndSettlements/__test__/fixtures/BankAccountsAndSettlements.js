import BankAccountsAndSettlements from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/BankAccountsAndSettlements';
import { render } from 'test-utils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetails',
  () => ({
    __esModule: true,
    default: () => <div>BankAccountDetails</div>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2',
  () => ({
    __esModule: true,
    default: () => <div>BankAccountDetailsV2</div>,
  }),
);

jest.mock('merchant/views/Account/Profile/components/SettlementDetails', () => ({
  __esModule: true,
  default: () => <div>SettlementDetails</div>,
}));

jest.mock('merchant/views/Account/Profile/components/FIRC/FIRCSection', () => ({
  __esModule: true,
  default: () => <div>FIRSDetails</div>,
}));

export const renderApp = ({ user, pathname } = {}) => {
  return render(<BankAccountsAndSettlements />, {
    initialEntries: [pathname ?? ROUTES_INFO.BANK_ACCOUNT_DETAILS],
    initialState: {
      session: {
        user: {
          isAccountAndSettingsRevampEnabled: true,
          ...user,
        },
      },
    },
  });
};

beforeEach(() => {
  jest.clearAllMocks();
});
