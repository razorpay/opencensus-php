import React from 'react';
import { render, screen, fireEvent, userEvent } from 'test-utils';
import Amount from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/Amount';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

jest.spyOn(track.lj.fields, 'amount').mockImplementation(() => {});
jest.spyOn(track.lj.form, 'fail').mockImplementation(() => {});

describe('[Component] Amount', () => {
  beforeAll(() => {
    window.rzpQ = {
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  afterEach(() => {
    track.lj.fields.amount.mockClear();
    track.lj.form.fail.mockClear();
  });

  const renderApp = (props = {}, user = {}) => {
    return render(<Amount defaultCurrency="INR" {...props} />, {
      initialState: {
        session: {
          user: {
            merchant: {
              product_international: '0000000000',
            },
            ...user,
          },
        },
      },
    });
  };

  test('should render the input and currency select components', () => {
    renderApp();
    expect(screen.getByText('Amount')).toBeInTheDocument();
    expect(screen.getByText('₹')).toBeInTheDocument();
  });

  test('should render the Input.Group component with the correct class names and label', () => {
    renderApp();
    expect(screen.getByText('Amount')).toHaveClass('Input-label');
  });

  describe('callTrackers function', () => {
    test('should call track.lj.fields.amount', () => {
      renderApp();

      // Get the amount input field and trigger the onBlur event
      const input = screen.getByPlaceholderText('100.00');
      fireEvent.blur(input);

      // Verify that track.lj.fields.amount is called with the correct arguments
      expect(track.lj.fields.amount).toHaveBeenCalled();
    });
  });

  describe('Currency select when internalization is enabled', () => {
    test('should render both frequently used and all other currencies in the CurrencySelect', async () => {
      renderApp({}, { isInttCurrenciesEnabled: true });
      const powerSelect = screen.getByText('₹');
      await userEvent.click(powerSelect);
      expect(screen.getByText('Frequently Used')).toBeInTheDocument();
      expect(screen.getByText('All others')).toBeInTheDocument();
    });

    test('should be able to select frequently used Euro currency from the CurrencySelect', async () => {
      renderApp({}, { isInttCurrenciesEnabled: true });
      const powerSelect = screen.getByText('₹');
      await userEvent.click(powerSelect);
      const currencySearch = screen.getAllByRole('textbox')[1];
      const amountField = screen.getAllByRole('textbox')[2];
      await userEvent.type(currencySearch, 'Euro');
      const euroOption = screen.getByText(/Euro/);
      await userEvent.click(euroOption);
      await userEvent.click(amountField);
      expect(screen.getByText('€')).toBeInTheDocument();
    });

    test('should be able to select infrequently used Australian Dollar currency from the CurrencySelect', async () => {
      renderApp({}, { isInttCurrenciesEnabled: true });
      const powerSelect = screen.getByText('₹');
      await userEvent.click(powerSelect);
      const currencySearch = screen.getAllByRole('textbox')[1];
      const amountField = screen.getAllByRole('textbox')[2];
      await userEvent.type(currencySearch, 'Australian Dollar');
      const australianDollarOption = screen.getByText(/Australian Dollar/);
      await userEvent.click(australianDollarOption);
      await userEvent.click(amountField);
      expect(screen.getByText('A$')).toBeInTheDocument();
    });
  });
});
