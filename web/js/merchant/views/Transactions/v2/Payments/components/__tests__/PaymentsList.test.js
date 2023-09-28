import { screen, waitFor, userEvent } from 'test-utils';

import { renderApp, handleDetailsClickSpy } from './mocks/fixtures/PaymentsList';
import 'jest-location-mock';

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

  test('should allow to click on Payments Table Row', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Payments Table')).toBeInTheDocument();
    });
    const tableRow = screen.getByRole('button', {
      name: 'Table Row',
    });
    tableRow.click();
    expect(handleDetailsClickSpy).toBeCalled();
  });
});
