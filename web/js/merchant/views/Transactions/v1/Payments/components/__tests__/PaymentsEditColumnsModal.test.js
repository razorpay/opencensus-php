import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import { rest } from 'msw';

import {
  ERROR_MESSAGES,
  FIXED_COLUMNS_TRANSACTIONS_V1,
  OPTIONAL_COLUMNS_TRANSACTIONS_V1,
} from 'merchant/views/Transactions/constants';
import * as saveMerchantColumnPreferences from 'merchant/views/Transactions/model';
import { PaymentsEditColumnsModal } from 'merchant/views/Transactions/v1/Payments/components/PaymentsEditColumnsModal';
import { render, screen, server, waitFor } from 'test-utils';

const mockSubmit = jest.fn();
const mockShowNotification = jest.fn();

const defaultProps = {
  isOpen: true,
  columnsList: [
    'cart_id',
    'storefront_id',
    'Script_Discount_Amount',
    'Script_Discount_Title',
    'discount_source',
    'gstin',
    'order_instructions',
    'shopify_order_id',
    'cancelUrl',
    'domain',
    'domains_list',
    'gid',
    'mode',
    'type',
    'cart_mask_id',
    'referer_url',
    'shopify_transaction_type',
    'is_magic_x',
    'bigc_cart_id',
    'bigc_order_id',
    'carrierCode',
    'invoice_id',
    'methodName',
    'platform',
    'store_hash',
    'growlytics_did',
    'purpose',
  ],
  selectedColumnsList: ['Email', 'Contact', 'domain', 'bigc_order_id'],
  fixedColumns: FIXED_COLUMNS_TRANSACTIONS_V1,
  optionalColumns: OPTIONAL_COLUMNS_TRANSACTIONS_V1,
  onSubmit: mockSubmit,
  showNotification: mockShowNotification,
};

const App = (props) => {
  return <PaymentsEditColumnsModal {...defaultProps} {...props} />;
};

describe('PaymentsEditColumnsModal', () => {
  const saveMerchantColumnPreferencesSpy = jest.spyOn(
    saveMerchantColumnPreferences,
    'saveMerchantColumnPreferences',
  );

  test('Should render modal with Save preferences checkbox and submit changes button', () => {
    render(<App />);
    const submitButton = screen.getByText(/Save Changes/);
    const savePreferencesCheckbox = screen.getByLabelText(/Save my preferences/);
    expect(savePreferencesCheckbox).toBeInTheDocument();
    expect(savePreferencesCheckbox).not.toBeChecked();
    expect(submitButton).toBeInTheDocument();
  });

  test('Should render all fixed columns with checked and disabled true', () => {
    render(<App />);
    FIXED_COLUMNS_TRANSACTIONS_V1.forEach((column) => {
      expect(screen.getByText(column)).toBeInTheDocument();
      expect(screen.getByLabelText(column)).toBeDisabled();
      expect(screen.getByLabelText(column)).toBeChecked();
    });
  });

  test('Should render all columns in columns list', () => {
    render(<App />);
    defaultProps.columnsList.forEach((column) => {
      expect(screen.getByText(column)).toBeInTheDocument();
    });
  });

  test('Should render all selected columns as checked', () => {
    render(<App />);
    defaultProps.selectedColumnsList.forEach((column) => {
      expect(screen.getByText(column)).toBeInTheDocument();
      expect(screen.getByLabelText(column)).toBeChecked();
    });
  });

  test('Should call saveMerchantColumnPreferences on submit', () => {
    render(<App />);
    const submitButton = screen.getByText(/Save Changes/);
    const savePreferencesCheckbox = screen.getByLabelText(/Save my preferences/);
    savePreferencesCheckbox.click();
    expect(savePreferencesCheckbox).toBeChecked();
    submitButton.click();
    expect(saveMerchantColumnPreferencesSpy).toHaveBeenCalledWith(defaultProps.selectedColumnsList);
    expect(mockSubmit).toHaveBeenCalledWith(defaultProps.selectedColumnsList);
  });

  test('Should show error notification if saveMerchantColumnPreferences throws error', async () => {
    render(<App />);
    const submitButton = screen.getByText(/Save Changes/);
    const savePreferencesCheckbox = screen.getByLabelText(/Save my preferences/);
    savePreferencesCheckbox.click();
    submitButton.click();
    server.use(
      rest.post('*/merchants/payments/saved_columns', (_, res, ctx) => {
        return res.once(ctx.errors(['Some error occurred']), ctx.delay(20));
      }),
    );
    await waitFor(
      () =>
        expect(mockShowNotification).toHaveBeenCalledWith({
          type: 'error',
          message: ERROR_MESSAGES.SAVE_PREFERENCES,
        }),
      expect(mockSubmit).toHaveBeenCalledWith(defaultProps.selectedColumnsList),
    );
  });

  test.skip('Should show error notification if saveMerchantColumnPreferences returns status code other than 200', async () => {
    render(<App />);
    const submitButton = screen.getByText(/Save Changes/);
    const savePreferencesCheckbox = screen.getByLabelText(/Save my preferences/);
    savePreferencesCheckbox.click();
    submitButton.click();
    server.use(
      rest.post('*/merchants/payments/saved_columns', (_, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 500,
          }),
          ctx.delay(50),
        );
      }),
    );
    await waitFor(
      () =>
        expect(mockShowNotification).toHaveBeenCalledWith({
          type: 'error',
          message: ERROR_MESSAGES.SAVE_PREFERENCES,
        }),
      expect(mockSubmit).toHaveBeenCalledWith(defaultProps.selectedColumnsList),
    );
  });

  test('Should update selected columns list when a checkbox is toggled', () => {
    render(<App />);
    const submitButton = screen.getByText(/Save Changes/);
    const checkbox = screen.getByLabelText('cart_id');
    checkbox.click();
    expect(screen.getByLabelText('cart_id')).toBeChecked();
    checkbox.click();
    submitButton.click();
    expect(mockSubmit).toHaveBeenCalledWith(defaultProps.selectedColumnsList);
  });
});
