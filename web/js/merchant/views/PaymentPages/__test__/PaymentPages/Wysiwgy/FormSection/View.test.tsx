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

  test('should be able to select email & phone as as secondary reference id and their label should be disabled', async () => {
    renderApp({
      props: { isBatchPaymentPages: true },
      initialState: {
        wysiwyg: {
          ...defaultState.wysiwyg,
          FORM_ITEMS: [FIXED_FIELDS.email, FIXED_FIELDS.phone],
        },
      },
    });

    // Click on email field.
    await userEvent.click(screen.getByTestId(FIXED_FIELDS.email.title));
    // Label should be there
    expect(screen.getByPlaceholderText('Enter field label')).toBeInTheDocument();
    // Label should be disabled
    expect(screen.getByTestId('Email--title')).toBeDisabled();
    // Open additional options.
    await userEvent.click(screen.getByTestId('dropdown-trigger'));
    // Select the option.
    await userEvent.click(screen.getByText('Select as Secondary Reference ID'));
    // Save the field.
    await userEvent.click(screen.getByRole('button', { name: 'Save' }));
    // Click on phone field.
    await userEvent.click(screen.getByTestId(FIXED_FIELDS.phone.title));
    // Label should be there
    expect(screen.getByPlaceholderText('Enter field label')).toBeInTheDocument();
    // Label should be disabled
    expect(screen.getByTestId('Phone--title')).toBeDisabled();
    // Open additional options.
    await userEvent.click(screen.getByTestId('dropdown-trigger'));
    // Select the option.
    await userEvent.click(screen.getByText('Select as Secondary Reference ID'));
    // Save the field.
    await userEvent.click(screen.getByRole('button', { name: 'Save' }));
  });
});
