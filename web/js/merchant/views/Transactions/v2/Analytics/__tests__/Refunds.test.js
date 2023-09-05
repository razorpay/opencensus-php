import { screen, waitFor, userEvent } from 'test-utils';
import {
  modifyData,
  assertRefetchData,
  renderApp,
  assertRefundData,
  assertHeadings,
} from './mocks/fixtures/Refunds';
import { useMobile } from 'common/hooks/useMobile';

describe('Refund Analytics', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    useMobile.mockReturnValue(false);
    modifyData({ kind: 'loading', value: true });
    modifyData({ kind: 'failed', value: false });
  });

  test('should render refund data', async () => {
    modifyData({ kind: 'loading', value: false });
    renderApp();
    await assertRefundData();
  });

  test('should fetch refund data', async () => {
    modifyData({ kind: 'loading', value: false });
    renderApp();
    await assertRefetchData();
  });

  test('should not fetch refund data if mode is test', async () => {
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

  test('should render refund data in mobile view', async () => {
    modifyData({ kind: 'loading', value: false });
    useMobile.mockReturnValue(true);
    renderApp();
    await assertRefundData();
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
      expect(screen.getByText("We couldn't load the summary of your refunds.")).toBeInTheDocument();
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
});
