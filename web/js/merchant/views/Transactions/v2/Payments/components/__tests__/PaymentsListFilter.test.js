import {
  paymentMethodOptionsMap,
  statusOptionsMap,
  searchByOptionsMap,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/constants';
import { durationOptionsMap } from 'merchant/views/Transactions/v2/common/constants';
import * as ModalActions from 'merchant_common/reducers/modals';
import { screen, userEvent, waitFor } from 'test-utils';

import { renderApp, defaultProps, useMobileSpy } from './mocks/fixtures/PaymentsListFilter';
import 'jest-location-mock';

describe('PaymentsListFilter', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');
  beforeEach(() => {
    useMobileSpy.mockReset();
    openModalSpy.mockClear();
  });

  describe('Duration filter', () => {
    test('should render default last 7 days and duration options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'Last 7 days' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      Object.values(durationOptionsMap).forEach((duration) => {
        const dropdownOption = screen.getByRole('menuitem', { name: duration });
        expect(dropdownOption).toBeInTheDocument();
      });
    });

    test('should allow to change duration options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'Last 7 days' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('menuitem', { name: 'Last 30 days' }));
      const selectedOption = screen.getByRole('button', { name: 'Last 30 days' });
      expect(selectedOption).toBeInTheDocument();
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
      useMobileSpy.mockImplementation(() => true);
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

  describe('Payment method filter', () => {
    test('should render default All and method options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'Payment method: All' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      Object.values(paymentMethodOptionsMap).forEach((method) => {
        if (method !== paymentMethodOptionsMap.all) {
          const dropdownOption = screen.getByRole('menuitem', { name: method });
          expect(dropdownOption).toBeInTheDocument();
        }
      });
    });

    test('should allow to change method options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('button', { name: 'Payment method: All' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('menuitem', { name: 'Card' }));
      const selectedOption = screen.getByRole('button', { name: 'Payment method: Card' });
      expect(selectedOption).toBeInTheDocument();
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
      await userEvent.click(screen.getByRole('menuitem', { name: 'Created' }));
      const selectedOption = screen.getByRole('button', { name: 'Status: Created' });
      expect(selectedOption).toBeInTheDocument();
    });
  });

  describe('Search by filter', () => {
    test('should render default Payment ID and search by options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('combobox');
      expect(dropdownTrigger).toHaveValue('Payment ID');
      await userEvent.click(dropdownTrigger);
      Object.values(searchByOptionsMap).forEach((searchBy) => {
        const dropdownOption = screen.getByRole('option', { name: searchBy });
        expect(dropdownOption).toBeInTheDocument();
      });
    });

    test('should allow to change search by options', async () => {
      renderApp();
      const dropdownTrigger = screen.getByRole('combobox');
      expect(dropdownTrigger).toHaveValue('Payment ID');
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('option', { name: 'Email' }));
      const selectedOption = screen.getByRole('combobox');
      expect(selectedOption).toHaveValue('Email');
    });

    test('should allow to select Mobile number & change Country code', async () => {
      const { container } = renderApp();
      const dropdownTrigger = screen.getByRole('combobox');
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('option', { name: 'Mobile number' }));
      const selectedOption = screen.getByRole('combobox');
      expect(selectedOption).toHaveValue('Mobile number');
      const countryCode = await screen.getByTestId('dialCodeValue');
      await waitFor(() => expect(countryCode.textContent).toBe('+91'));
      const dialCodeSelector = screen.getByTestId('dialCodeSelector');
      await userEvent.click(dialCodeSelector);
      const dropdownItems = screen.getByTestId('dropdownItems');
      expect(dropdownItems).toBeInTheDocument();
      container.querySelector('.flag.gb').click();
      expect(countryCode.textContent).toBe('+44');
    });

    test('should allow to search by Mobile number & Country code query param', async () => {
      window.location.search = '?country_code=+44';
      renderApp();
      const dropdownTrigger = screen.getByRole('combobox');
      await userEvent.click(dropdownTrigger);
      await userEvent.click(screen.getByRole('option', { name: 'Mobile number' }));
      const selectedOption = screen.getByRole('combobox');
      expect(selectedOption).toHaveValue('Mobile number');
      const searchInput = screen.getByPlaceholderText('Search');
      await userEvent.type(searchInput, '8964567890');
      expect(searchInput).toHaveValue('8964567890');
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Search',
        }),
      );
      expect(defaultProps.onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          contact: '8964567890',
          country_code: '+44',
        }),
      );
    });
  });

  describe('Extra Filters for Omni Merchants', () => {
    test('should open extra filters modal when clicked on All Filters', async () => {
      renderApp(
        {},
        {
          session: {
            user: {
              isOmniChannelMerchant: true,
              pos_activation_status: 'under_review',
            },
          },
        },
      );
      const extraFiltersButton = screen.getByRole('button', { name: 'All Filters' });
      expect(extraFiltersButton).toBeInTheDocument();
      await userEvent.click(extraFiltersButton);
      await waitFor(() => {
        expect(openModalSpy).toHaveBeenCalled();
      });
    });
  });
});
