import React from 'react';
import { getByText, render, screen, userEvent } from 'test-utils';

import currencies from 'merchant/constants/currency';

import View from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/View';
import { SEC_REF_ID_MAX_ERROR } from 'merchant/views/PaymentPages/PaymentPages/constants';
import { FIXED_FIELDS } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers/preAddedFields';

const defaultState = {
  wysiwyg: {
    FORM_ITEMS: [],
    paymentPageEntity: {
      settings: { checkout_options: { email: '', phone: '' } },
      currency: 'INR',
      receipt: { selected_udf_field: '' },
    },
  },
};

function renderApp({ props = {}, initialState = {} }) {
  return render(<View {...props} />, { initialState: { ...defaultState, ...initialState } });
}

describe('View', () => {
  beforeAll(() => {
    window.rzpQ = {
      paymentPages: () => ({
        success: jest.fn(),
        interaction: jest.fn(),
      }),
    };
    (window as any).currencyList = currencies;
  });

  test('should notify if more than five secondary ref id has been added when creating batch payment pages', async () => {
    const FORM_ITEMS = Array.from(new Array(5)).map((_, index) => ({
      name: `sec__ref__id_${index}`,
      title: `Test title ${index}`,
      type: 'string',
      pattern: 'alphanumeric',
      settings: {
        position: index,
      },
    }));

    renderApp({
      props: { isBatchPaymentPages: true },
      initialState: {
        wysiwyg: {
          ...defaultState.wysiwyg,
          FORM_ITEMS,
        },
      },
    });

    // Click to add new input field.
    await userEvent.click(screen.getByText('Input field'));
    // Add new single line text field.
    await userEvent.click(screen.getByText('Single Line Text'));
    // Enter label name.
    await userEvent.type(screen.getByPlaceholderText('Enter field label'), 'Test title 6');
    // Open additional options.
    await userEvent.click(screen.getByTestId('dropdown-trigger'));
    // Select the option.
    await userEvent.click(screen.getByText('Select as Secondary Reference ID'));
    // Save the field.
    await userEvent.click(screen.getByRole('button', { name: 'Save' }));

    const notifyEl = screen.getByTestId('Notification--info');

    expect(notifyEl).toBeInTheDocument();
    expect(getByText(notifyEl, SEC_REF_ID_MAX_ERROR));
  });

  test('should be able edit and to select the field as secondary reference id', async () => {
    renderApp({
      props: { isBatchPaymentPages: true },
      initialState: {
        wysiwyg: {
          ...defaultState.wysiwyg,
          FORM_ITEMS: [FIXED_FIELDS.email],
        },
      },
    });

    // Click on email field.
    await userEvent.click(screen.getByTestId(FIXED_FIELDS.email.title));
    // Open additional options.
    await userEvent.click(screen.getByTestId('dropdown-trigger'));
    // Select the option.
    await userEvent.click(screen.getByText('Select as Secondary Reference ID'));
    // Save the field.
    await userEvent.click(screen.getByRole('button', { name: 'Save' }));
    // Click on email field.
    await userEvent.click(screen.getByTestId(FIXED_FIELDS.email.title));
    // Edit the email title.
    await userEvent.type(screen.getByPlaceholderText('Enter field label'), ' Title Edit');
    // Save the field.
    await userEvent.click(screen.getByRole('button', { name: 'Save' }));

    expect(screen.getByTestId('Email Title Edit')).toBeInTheDocument();
  });
});
