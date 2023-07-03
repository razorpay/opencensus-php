import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import AddUDFButton from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/AddUDFButton';
import fUnits from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers/field-units';

describe('AddUDFButton', () => {
  beforeEach(() => {
    window.rzpQ = {
      paymentPages: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  test('should not have dropdown as an option when batch payment pages are created', async () => {
    render(<AddUDFButton isBatchPaymentPages />);

    // Clicking on UDF button to open available input options.
    await userEvent.click(screen.getByText('Input field'));

    expect(screen.queryByText(fUnits.dropdown.label)).not.toBeInTheDocument();
  });
});
