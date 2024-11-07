import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';

import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Form } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/components/Form';

import { useFormContext } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/Context';

import {
  COD_ON_ALL_ORDERS,
  INVALID_PAYMENT_METHOD,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/constants';

import { mockFormData } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/__tests__/mocks';

// Mocking the useFormContext to provide mock form data and functions
jest.mock('merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/Context', () => ({
  useFormContext: jest.fn(),
}));

describe('Form component', () => {
  const mockUpdateFormData = jest.fn();

  beforeEach(() => {
    (useFormContext as jest.Mock).mockReturnValue({
      formData: mockFormData,
      updateFormData: mockUpdateFormData,
      formErrors: {},
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const App = () => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <Form />
      </BladeProvider>
    );
  };

  it('Should render Allowed Payment Methods with initial checkbox state', () => {
    render(<App />);

    // Check that the checkboxes for COD and Prepaid are in the correct initial state
    expect(screen.getByLabelText('COD')).toBeChecked();
    expect(screen.getByLabelText('Prepaid')).not.toBeChecked();
  });

  it('Should update formData on payment method change', () => {
    render(<App />);

    // Simulate clicking the Prepaid checkbox
    const prepaidCheckbox = screen.getByLabelText('Prepaid');
    fireEvent.click(prepaidCheckbox);

    // Expect that the form data was updated for the Prepaid method
    expect(mockUpdateFormData).toHaveBeenCalledWith('allow_prepaid', true);
  });

  it('Should render COD slab inputs with initial values', () => {
    render(<App />);

    // Check that the Min and Max order value inputs have the correct initial values
    expect(screen.getByLabelText('Min Order Value')).toHaveValue('100');
    expect(screen.getByLabelText('Max Order Value')).toHaveValue('1000');
  });

  it('Should update formData on COD slab value change', () => {
    render(<App />);

    // Simulate changing the Min Order Value
    const minOrderInput = screen.getByLabelText('Min Order Value');
    fireEvent.change(minOrderInput, { target: { value: 200 } });

    // Expect that the form data was updated for the Min Order Value
    expect(mockUpdateFormData).toHaveBeenCalledWith('cod_fee_rules', {
      amount: { gte: '200', lt: 1000 },
    });
  });

  it('Should render COD availability alert based on formData', () => {
    render(<App />);

    // Check that the alert shows the correct COD availability message
    expect(
      screen.getByText('COD will be available for carts between ₹100 and ₹1000'),
    ).toBeInTheDocument();
  });

  it('Should show payment method form errors when present', () => {
    (useFormContext as jest.Mock).mockReturnValueOnce({
      formData: {},
      updateFormData: mockUpdateFormData,
      formErrors: {
        paymentMethod: INVALID_PAYMENT_METHOD,
      },
    });

    render(<App />);

    // Check that form error messages are displayed correctly
    expect(screen.getByText(INVALID_PAYMENT_METHOD)).toBeInTheDocument();
  });

  it('Should show allow COD for all orders if COD is checked and COD Order Slabs are null', () => {
    (useFormContext as jest.Mock).mockReturnValueOnce({
      formData: {
        ...mockFormData,
        cod_fee_rules: {
          amount: {
            gte: null,
            lt: null,
          },
        },
      },
      updateFormData: mockUpdateFormData,
      formErrors: {},
    });
    render(<App />);
    expect(screen.getByText(COD_ON_ALL_ORDERS)).toBeInTheDocument();
  });
});
