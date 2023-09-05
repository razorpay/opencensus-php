import { screen, waitFor, userEvent } from 'test-utils';

import { renderApp } from './mocks/fixtures/PaymentsList';

describe('PaymentsList', () => {
  test('should render Payments List Filter', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Payments List Filter')).toBeInTheDocument();
    });
  });

  test('should search by filters', async () => {
    const { history } = renderApp();
    await waitFor(() => {
      expect(screen.getByText('Payments List Filter')).toBeInTheDocument();
    });
    await userEvent.click(
      screen.getByRole('button', {
        name: 'Search',
      }),
    );
    expect(history.location.search).toBe('?method=card');
    await waitFor(() => {
      expect(screen.getByText('Payments Table')).toBeInTheDocument();
    });
  });

  test('should render Payments Table', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Payments Table')).toBeInTheDocument();
    });
  });
});
