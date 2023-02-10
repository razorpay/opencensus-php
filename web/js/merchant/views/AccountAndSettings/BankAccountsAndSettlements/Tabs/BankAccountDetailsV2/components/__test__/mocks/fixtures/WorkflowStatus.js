import WorkflowStatus from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/WorkflowStatus';
import { render } from 'test-utils';
import rolesList from 'merchant/helpers/permissions/roles-list';

jest.mock('merchant/views/Account/Profile/components/WorkflowRequests/utils', () => ({
  ...jest.requireActual('merchant/views/Account/Profile/components/WorkflowRequests/utils'),
  isVisible: () => true,
}));

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner',
  () => ({ type, workflowEta }) => (
    <div>
      <title>Banner</title>
      <span>{type}</span>
      <span>{workflowEta}</span>
    </div>
  ),
);

export const renderApp = ({ props, user } = {}) => {
  return render(<WorkflowStatus {...props} />, {
    showModal: true,
    initialState: {
      session: {
        user: {
          role: rolesList.OWNER,
          ...user,
        },
      },
    },
  });
};
