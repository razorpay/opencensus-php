import { screen, waitFor, userEvent } from 'test-utils';
import { renderApp } from './mocks/fixtures/RefundsList';

describe('RefundsList', () => {
  test('should render Refunds List Filter', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Refunds List Filter')).toBeInTheDocument();
    });
  });

  test('should search by filters', async () => {
    const { history } = renderApp();
    await waitFor(() => {
      expect(screen.getByText('Refunds List Filter')).toBeInTheDocument();
    });
    await userEvent.click(
      screen.getByRole('button', {
        name: 'Search',
      }),
    );
    expect(history.location.search).toBe('?status=processing');
    await waitFor(() => {
      expect(screen.getByText('Refunds Table')).toBeInTheDocument();
    });
  });

  test('should render Refunds Table', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Refunds Table')).toBeInTheDocument();
    });
  });
});
