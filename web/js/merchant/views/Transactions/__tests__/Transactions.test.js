import { renderApp } from 'merchant/views/Transactions/__tests__/mocks/fixtures/Transactions';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';
import { screen, waitFor } from 'test-utils';

jest.mock('merchant/views/Transactions/v2/common/utils', () => ({
  ...jest.requireActual('merchant/views/Transactions/v2/common/utils'),
  isTransactionsV2Enabled: jest.fn(),
}));

describe('Transactions component', () => {
  test('should render TransactionsV2 when isTransactionsV2Enabled is true', async () => {
    renderApp();
    await waitFor(() => expect(screen.getByText('TransactionsV2')).toBeInTheDocument());
  });

  test('should render TransactionsV1 when isTransactionsV2Enabled is false', async () => {
    isTransactionsV2Enabled.mockReturnValue(false);
    renderApp();
    await waitFor(() => expect(screen.getByText('TransactionsV1')).toBeInTheDocument());
  });
});
