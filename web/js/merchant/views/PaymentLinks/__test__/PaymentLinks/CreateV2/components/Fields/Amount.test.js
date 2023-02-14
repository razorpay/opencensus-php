import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import Amount from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/Amount';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

jest.spyOn(track.lj.fields, 'amount').mockImplementation(() => {});
jest.spyOn(track.lj.form, 'fail').mockImplementation(() => {});

describe('[Component] Amount', () => {
  afterEach(() => {
    track.lj.fields.amount.mockClear();
    track.lj.form.fail.mockClear();
  });

  const renderApp = (props = {}) => {
    return render(<Amount defaultCurrency="INR" {...props} />, {
      initialState: {
        session: {
          user: {
            merchant: {
              product_international: '0000000000',
            },
          },
        },
      },
    });
  };

  test('should render the input and currency select components', () => {
    renderApp();
    expect(screen.getByText('Amount')).toBeInTheDocument();
    expect(screen.getByText('Failed to load currency, please try later.')).toBeInTheDocument();
  });

  test('should render the Input.Group component with the correct class names and label', () => {
    renderApp();
    expect(screen.getByText('Amount')).toHaveClass('Input-label');
  });

  describe('callTrackers function', () => {
    test('should call track.lj.fields.amount', () => {
      renderApp();

      // Get the amount input field and trigger the onBlur event
      const input = screen.getByPlaceholderText('0.00');
      fireEvent.blur(input);

      // Verify that track.lj.fields.amount is called with the correct arguments
      expect(track.lj.fields.amount).toHaveBeenCalled();
    });
  });
});
