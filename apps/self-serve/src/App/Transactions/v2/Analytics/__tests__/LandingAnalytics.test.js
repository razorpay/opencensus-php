/* eslint-disable import/order */
import { waitFor, screen, userEvent } from 'apps/self-serve/src/services/test/test-utils';
import {
  renderApp,
  modifyData,
  assertRefetchData,
  assertNoRefetchData,
} from './mocks/fixtures/LandingAnalytics';
import { useMobile } from '@libs/shared-utils';

describe('LandingAnalytics', () => {
  beforeEach(() => {
    useMobile.mockReturnValue(false);
    modifyData({
      type: 'all',
      kind: 'loading',
      value: true,
    });
    modifyData({
      type: 'all',
      kind: 'failed',
      value: false,
    });
  });

  test('should renders all the component', async () => {
    modifyData({
      type: 'all',
      kind: 'loading',
      value: false,
    });
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Overview')).toBeInTheDocument();
      expect(screen.getByText('Top overview')).toBeInTheDocument();
      expect(screen.getByText('Bottom overview')).toBeInTheDocument();
    });
  });

  test('should renders all the component in mobile view', async () => {
    useMobile.mockReturnValue(true);
    modifyData({
      type: 'all',
      kind: 'loading',
      value: false,
    });
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Overview')).toBeInTheDocument();
      expect(screen.getByText('Top overview')).toBeInTheDocument();
      expect(screen.getByText('Bottom overview')).toBeInTheDocument();
    });
  });

  test('should show failed state when api fails', async () => {
    modifyData({
      type: 'all',
      kind: 'failed',
      value: true,
    });
    renderApp();
    expect(screen.getByTestId('payments-data-failed')).toBeInTheDocument();
    const refreshButton = await screen.getByRole('button', {
      name: 'Refresh',
    });
    expect(refreshButton).toBeInTheDocument();
    await userEvent.click(refreshButton);
    await assertRefetchData('all');
  });

  ['refunds', 'disputes', 'failedPayments'].forEach((type) => {
    test(`should show failed state when ${type} api fails`, async () => {
      modifyData({
        type: 'all',
        kind: 'loading',
        value: false,
      });
      modifyData({
        type,
        kind: 'failed',
        value: true,
      });
      renderApp();
      expect(screen.getByTestId(`${type}-data-failed`)).toBeInTheDocument();
      const refreshButton = await screen.getByRole('button', {
        name: 'Refresh',
      });
      expect(refreshButton).toBeInTheDocument();
      await userEvent.click(refreshButton);
      await assertRefetchData(type);
    });
  });

  ['refunds', 'failedPayments'].forEach((type) => {
    test(`should not refetch in test mode if ${type} api fails`, async () => {
      modifyData({
        type: 'all',
        kind: 'loading',
        value: false,
      });
      modifyData({
        type,
        kind: 'failed',
        value: true,
      });
      renderApp({
        mode: 'test',
      });
      expect(screen.getByTestId(`${type}-data-failed`)).toBeInTheDocument();
      const refreshButton = await screen.getByRole('button', {
        name: 'Refresh',
      });
      expect(refreshButton).toBeInTheDocument();
      await userEvent.click(refreshButton);
      await assertNoRefetchData(type);
    });
  });

  test('should refetch data on load', async () => {
    // loading is true still it should fetch data because of use effect
    renderApp();
    await assertRefetchData('all');
  });

  test('should refetch all data on duration change', async () => {
    modifyData({
      type: 'all',
      kind: 'loading',
      value: false,
    });
    renderApp();
    const dropdownTrigger = screen.getByRole('button', { name: 'Today' });
    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);
    await userEvent.click(screen.getByRole('menuitem', { name: 'Last 30 days' }));
    await assertRefetchData('all');
  });

  test('should refetch SR data when feature is on', async () => {
    modifyData({
      type: 'all',
      kind: 'loading',
      value: false,
    });
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
    await assertRefetchData('successRate');
  });
});
