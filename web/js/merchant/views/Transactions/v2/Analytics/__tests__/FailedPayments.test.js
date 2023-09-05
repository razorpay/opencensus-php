import { screen, waitFor, userEvent } from 'test-utils';
import {
  modifyData,
  assertRefetchData,
  renderApp,
  assertFailureData,
  assertHeadings,
} from './mocks/fixtures/FailedPayments';
import { useMobile } from 'common/hooks/useMobile';

describe('Failed Payments Analytics', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    useMobile.mockReturnValue(false);
    modifyData({ kind: 'loading', value: true });
    modifyData({ kind: 'failed', value: false });
  });

  test('should renders failed payments data', async () => {
    modifyData({ kind: 'loading', value: false });
    renderApp();
    await assertFailureData();
  });

  test('should fetch failed payments data on mount', async () => {
    modifyData({ kind: 'loading', value: false });
    renderApp();
    await assertFailureData();
  });

  test('should not fetch failed payments data if mode is test', async () => {
    modifyData({ kind: 'loading', value: false });
    renderApp({
      session: {
        mode: 'test',
      },
    });
    await assertRefetchData({
      isNegate: true,
    });
  });

  test('should renders failed payments in mobile view', async () => {
    modifyData({ kind: 'loading', value: false });
    useMobile.mockReturnValue(true);
    renderApp();
    await assertFailureData();
  });

  test('should show loading shimmer and headings when data is loading', async () => {
    modifyData({ kind: 'loading', value: true });
    renderApp();
    await assertHeadings();
    await waitFor(() => {
      expect(screen.queryAllByTestId('loading-shimmer').length).toBeGreaterThan(0);
    });
  });

  test('should show failed state when api fails', async () => {
    modifyData({ kind: 'failed', value: true });
    renderApp();
    await waitFor(() => {
      expect(
        screen.getByText("We couldn't load the summary of your failed payments."),
      ).toBeInTheDocument();
    });
    await waitFor(() => {
      expect(screen.getByText('Refresh to try again')).toBeInTheDocument();
    });
    await waitFor(async () => {
      const refreshButton = screen.getByRole('button', { name: 'Refresh' });
      expect(refreshButton).toBeInTheDocument();
      await userEvent.click(refreshButton);
      await assertRefetchData();
    });
  });

  test('should refetch all data on duration change', async () => {
    modifyData({ kind: 'loading', value: false });
    renderApp();
    const dropdownTrigger = screen.getByRole('button', { name: 'Today' });
    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);
    await userEvent.click(screen.getByRole('menuitem', { name: 'Last 30 days' }));
    await assertRefetchData();
  });

  test('should fetch data on page load', async () => {
    // loading is true still it should fetch data because of use effect
    renderApp();
    await assertRefetchData();
  });

  test('should show Success Rate Banner when feature is on', async () => {
    modifyData({ type: 'all', kind: 'loading', value: false });
    renderApp({
      session: {
        mode: 'live',
        user: {
          isTransactionsV2Enabled: true,
          merchant: {
            currency: 'INR',
          },
          findTag: () => true,
          isAllowedView: () => true,
        },
      },
    });
    const dropdownTrigger = screen.getByRole('button', { name: 'Today' });
    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);
    await userEvent.click(screen.getByRole('menuitem', { name: 'Last 30 days' }));
    await assertRefetchData('all');
    await waitFor(() => {
      expect(screen.getByText('99% success rate')).toBeInTheDocument();
    });
  });
});
