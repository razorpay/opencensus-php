import React from 'react';
import { act } from 'react-dom/test-utils';

import AssistedFinancing from 'merchant/views/Affordability/AssistedFinancing';
import { render, screen, userEvent, waitFor } from 'test-utils';

const variantOn = { assisted_financing: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('Affordability: AssistedFinancing', () => {
  it('should display available option list page after clicking on check emi options button', async () => {
    render(<AssistedFinancing />);

    expect(screen.getByText('Assisted Financing')).toBeInTheDocument();
    expect(screen.getByText('Mobile Number')).toBeInTheDocument();
    expect(screen.getByText('Order Amount')).toBeInTheDocument();

    const mobileInput = screen.getByRole('textbox', { name: 'Mobile Number' });
    const orderAmountInput = screen.getByRole('textbox', { name: 'Order Amount' });

    const checkEmiButton = screen.getByRole('button', { name: 'Check Available EMI Options' });

    expect(checkEmiButton).toBeInTheDocument();
    expect(mobileInput).toBeInTheDocument();
    expect(orderAmountInput).toBeInTheDocument();

    await userEvent.type(mobileInput, '9876598765');
    await userEvent.type(orderAmountInput, '100');

    await waitFor(() => {
      expect(mobileInput).toHaveValue('9876598765');
      expect(orderAmountInput).toHaveValue('100');
    });

    act(async () => {
      await userEvent.click(checkEmiButton);
    });

    await waitFor(() => {
      expect(screen.getByText('Available EMI Options')).toBeInTheDocument();
    });
  });

  it('should not display assisted financing feature if org is not razorpay', () => {
    render(<AssistedFinancing />, {
      initialState: {
        session: {
          org: {
            custom_code: 'curlec',
            business_name: 'Curlec',
          },
        },
      },
    });

    expect(screen.queryByText('Assisted Financing')).not.toBeInTheDocument();
  });
});
