import { mockOpenModal, props, renderApp } from './mocks/fixtures/TopOverview';
import { screen, waitFor, userEvent } from 'apps/self-serve/src/services/test/test-utils';

describe('TopOverviewContainer', () => {
  test('should render the captured payment card and payment method split', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Collected Amount')).toBeInTheDocument();
    });
    await waitFor(() => {
      expect(
        screen.getByText(`from ${props.paymentCapturedCount} captured payments`),
      ).toBeInTheDocument();
    });
    await waitFor(() => {
      expect(screen.getByText('Payment Method Split')).toBeInTheDocument();
    });
  });

  test('should render the captured payment card and payment method split in mobile view', async () => {
    renderApp({
      isMobile: true,
    });
    await waitFor(() => {
      expect(screen.getByText('Collected Amount')).toBeInTheDocument();
    });
    await waitFor(() => {
      expect(
        screen.getByText(`from ${props.paymentCapturedCount} captured payments`),
      ).toBeInTheDocument();
    });
    await waitFor(() => {
      expect(screen.getByText('Payment Method Split')).toBeInTheDocument();
    });
  });

  test('should not render payment method split when no paymentMethod', async () => {
    renderApp({
      paymentByMethod: [],
    });
    await waitFor(() => {
      expect(screen.queryByText('Payment Method Split')).not.toBeInTheDocument();
    });
  });

  test('should show loading shimmer when data is loading', async () => {
    renderApp({
      isPaymentsDataLoading: true,
    });
    await waitFor(() => {
      expect(screen.getByTestId('loading-shimmer')).toBeInTheDocument();
    });
  });

  test('should show failed banner when data fetch fails', async () => {
    renderApp({
      isPaymentsDataFailed: true,
    });
    await waitFor(() => {
      expect(screen.getByText('Refresh to try again')).toBeInTheDocument();
    });
  });

  describe('settled cycle link', () => {
    test('should not show settlement cycle link when capture amount is zero', async () => {
      renderApp({
        paymentCapturedAmount: 0,
      });
      await waitFor(() => {
        expect(
          screen.queryByRole('button', {
            name: 'settlement cycle',
          }),
        ).not.toBeInTheDocument();
      });
    });
    test('should show settlement cycle link when capture amount is more than zero and open modal on clicking', async () => {
      renderApp({
        paymentCapturedAmount: 100,
      });
      const settlementCycleButton = screen.getByRole('button', {
        name: 'settlement cycle',
      });
      expect(settlementCycleButton).toBeInTheDocument();
      await userEvent.click(settlementCycleButton);
      await waitFor(() => {
        expect(mockOpenModal).toHaveBeenCalled();
      });
    });
  });
});
