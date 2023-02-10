import Banner from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner';
import { render } from 'test-utils';

jest.mock('merchant/views/Account/Profile/components/WorkflowRequests/utils', () => ({
  ...jest.requireActual('merchant/views/Account/Profile/components/WorkflowRequests/utils'),
  hideWorkflowStatus: jest.fn(),
}));

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/BankAccountUpdateFlow',
  () => () => <div>BankAccountUpdateFlow</div>,
);

export const renderApp = ({ props, user, profile } = {}) => {
  return render(<Banner {...props} />, {
    showModal: true,
    initialState: {
      profile: {
        bankAccount: { account_number: '123456789' },
        ...profile,
      },
      session: {
        user: {
          ...user,
        },
      },
    },
  });
};
