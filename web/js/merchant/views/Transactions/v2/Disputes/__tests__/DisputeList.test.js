import {
  columns,
  mockDisputeItems,
  renderApp,
} from 'merchant/views/Transactions/v2/Disputes/__tests__/mocks/fixtures/DisputeList.js';
import { screen, waitFor } from 'test-utils';

const items = mockDisputeItems();

describe('DisputeList', () => {
  test('should render component', () => {
    renderApp({ items });
    expect(screen.getByText('Disputes')).toBeInTheDocument();
  });

  test('should renders correct columns', () => {
    renderApp({ items });
    columns.forEach((column) => {
      expect(
        screen.getByRole('columnheader', {
          name: column,
        }),
      ).toBeInTheDocument();
    });
  });

  test('should not render disputes when no items are present', async () => {
    renderApp({ items: [] });
    await waitFor(() => {
      expect(screen.getByText('No disputes in selected duration')).toBeInTheDocument();
    });
  });
});
