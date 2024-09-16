import moment from 'moment';

import {
  renderApp,
  defaultProps,
  useMobileSpy,
} from 'apps/self-serve/src/App/Transactions/v2/Disputes/__tests__/mocks/fixtures/DisputeListFilter.js';
import {
  disputeDurationOptionsMap,
  statusOptionsMap,
} from 'apps/self-serve/src/App/Transactions/v2/Disputes/constants.ts';
import { screen, userEvent } from 'apps/self-serve/src/services/test/test-utils';
import 'jest-location-mock';

describe('DisputeListFilter', () => {
  beforeEach(() => {
    useMobileSpy.mockReset();
  });

  describe('Duration filter', () => {
    test('should render default Last 7 days and duration options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'Last 7 days' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      Object.values(disputeDurationOptionsMap).forEach((duration) => {
        const dropdownOption = screen.getByRole('menuitem', { name: duration });
        expect(dropdownOption).toBeInTheDocument();
      });
    });

    test('should allow to change duration options', async () => {
      renderApp();
      let dropdownTrigger = screen.getByRole('button', { name: 'Last 7 days' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('menuitem', { name: 'Last 30 days' }));
      dropdownTrigger = screen.getByRole('button', { name: 'Last 30 days' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getAllByRole('menuitem', { name: 'Last 7 days' })[0]);
      dropdownTrigger = screen.getByRole('button', { name: 'Last 7 days' });
      expect(dropdownTrigger).toBeInTheDocument();
    });

    test('should allow to select custom duration', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'Last 7 days' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('menuitem', { name: 'Custom' }));
      expect(screen.getByText('Date Range Picker')).toBeInTheDocument();
      await userEvent.click(screen.getByText('Change Date'));
      expect(defaultProps.onSubmit).toHaveBeenCalled();
    });

    test('should allow to select custom duration on Mobile', async () => {
      useMobileSpy.mockImplementationOnce(() => true);
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'Last 7 days' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('menuitem', { name: 'Custom' }));
      expect(screen.getByText('Date Range Picker')).toBeInTheDocument();
      await userEvent.click(screen.getByText('Change Date'));
      expect(defaultProps.onSubmit).toHaveBeenCalled();
    });
  });

  describe('Status filter', () => {
    test('should render default All and status options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'Status: All' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      Object.values(statusOptionsMap).forEach((status) => {
        if (status !== statusOptionsMap.all) {
          const dropdownOption = screen.getByRole('menuitem', { name: status });
          expect(dropdownOption).toBeInTheDocument();
        }
      });
    });

    test('should allow to change status options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'Status: All' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('menuitem', { name: 'Open' }));
      const selectedOption = screen.getByRole('button', { name: 'Status: Open' });
      expect(selectedOption).toBeInTheDocument();
    });
  });

  describe('Search by filter', () => {
    test('should allow to enter search value', async () => {
      const paymentId = 'pay_1234567890';
      renderApp();
      const searchInput = screen.getByPlaceholderText('Payment ID or Dispute ID');
      await userEvent.type(searchInput, paymentId);
      expect(searchInput).toHaveValue(paymentId);
      await userEvent.click(screen.getByTestId('search'));
    });

    test('should allow to search by payment id value', async () => {
      const paymentId = 'pay_1234567890';
      renderApp();
      const searchInput = screen.getByPlaceholderText('Payment ID or Dispute ID');
      await userEvent.type(searchInput, paymentId);
      expect(searchInput).toHaveValue(paymentId);
      await userEvent.click(screen.getByTestId('search'));
      expect(defaultProps.onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          payment_id: paymentId,
        }),
      );
    });

    test('should allow to search by query params', () => {
      const paymentId = 'pay_1234567890';
      const from = moment().subtract(7, 'days').startOf('day').unix();
      const to = moment().endOf('day').unix();
      window.location.search = `?payment_id=${paymentId}&from=${from}&to=${to}`;
      renderApp();
      const searchInput = screen.getByPlaceholderText('Payment ID or Dispute ID');
      expect(searchInput).toHaveValue(paymentId);
    });
  });
});
