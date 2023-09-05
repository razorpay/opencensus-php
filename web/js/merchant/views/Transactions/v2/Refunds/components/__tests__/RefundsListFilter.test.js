import moment from 'moment';

import {
  refundsDurationOptionsMap,
  statusOptionsMap,
  searchByOptionsMap,
} from 'merchant/views/Transactions/v2/Refunds/components/RefundsListFilter/constants';
import { screen, userEvent } from 'test-utils';

import { renderApp, defaultProps, useMobileSpy } from './mocks/fixtures/RefundsListFilter';
import 'jest-location-mock';

describe('RefundsListFilter', () => {
  beforeEach(() => {
    useMobileSpy.mockReset();
  });

  describe('Duration filter', () => {
    test('should render default All days and duration options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'All' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      Object.values(refundsDurationOptionsMap).forEach((duration) => {
        if (duration !== refundsDurationOptionsMap.all) {
          const dropdownOption = screen.getByRole('menuitem', { name: duration });
          expect(dropdownOption).toBeInTheDocument();
        }
      });
    });

    test('should allow to change duration options', async () => {
      renderApp();
      let dropdownTrigger = screen.getByRole('button', { name: 'All' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('menuitem', { name: 'Last 30 days' }));
      dropdownTrigger = screen.getByRole('button', { name: 'Last 30 days' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getAllByRole('menuitem', { name: 'All' })[0]);
      dropdownTrigger = screen.getByRole('button', { name: 'All' });
      expect(dropdownTrigger).toBeInTheDocument();
    });

    test('should allow to select custom duration', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'All' });
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
      const dropdownTrigger = screen.getByRole('button', { name: 'All' });
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
      await userEvent.click(screen.getByRole('menuitem', { name: 'Processed' }));
      const selectedOption = screen.getByRole('button', { name: 'Status: Processed' });
      expect(selectedOption).toBeInTheDocument();
    });
  });

  describe('Search by filter', () => {
    test('should render default Refund ID and search by options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('combobox');
      expect(dropdownTrigger).toHaveValue('Refund ID');
      await userEvent.click(dropdownTrigger);
      Object.values(searchByOptionsMap).forEach((searchBy) => {
        const dropdownOption = screen.getByRole('option', { name: searchBy });
        expect(dropdownOption).toBeInTheDocument();
      });
    });

    test('should allow to change search by options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('combobox');
      expect(dropdownTrigger).toHaveValue('Refund ID');
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('option', { name: 'Payment ID' }));
      const selectedOption = screen.getByRole('combobox');
      expect(selectedOption).toHaveValue('Payment ID');
    });

    test('should allow to search by refund id value', async () => {
      const refundId = 'rfnd_1234567890';
      renderApp();
      const dropdownTrigger = screen.getByRole('combobox');
      expect(dropdownTrigger).toHaveValue('Refund ID');
      const searchInput = screen.getByPlaceholderText('Search');
      await userEvent.type(searchInput, refundId);
      expect(searchInput).toHaveValue(refundId);
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Search',
        }),
      );
      expect(defaultProps.onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          id: refundId,
        }),
      );
    });

    test('should allow to search by query params', async () => {
      const paymentId = 'pay_1234567890';
      const from = moment().subtract(7, 'days').startOf('day').unix();
      const to = moment().endOf('day').unix();
      window.location.search = `?payment_id=${paymentId}&from=${from}&to=${to}`;
      renderApp();
      const dropdownTrigger = screen.getByRole('combobox');
      expect(dropdownTrigger).toHaveValue('Payment ID');
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Search',
        }),
      );
      expect(defaultProps.onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          payment_id: paymentId,
          from,
          to,
        }),
      );
    });
  });
});
